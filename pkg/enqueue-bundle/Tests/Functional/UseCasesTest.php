<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Bundle\Tests\Functional\App\CustomAppKernel;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class UseCasesTest extends WebTestCase
{
    public function provideEnqueueConfigs()
    {
        return [
            ['amqp' => [
                'transport' => [
                    'default' => 'amqp',
                    'amqp' => [
                        'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                        'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                        'login' => getenv('SYMFONY__RABBITMQ__USER'),
                        'password' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                        'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                        'lazy' => false,
                    ]
                ]
            ]],
            ['stomp' => [
                'transport' => [
                    'default' => 'stomp',
                    'stomp' => [
                        'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                        'port' => getenv('SYMFONY__RABBITMQ__STOMP__PORT'),
                        'login' => getenv('SYMFONY__RABBITMQ__USER'),
                        'password' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                        'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                        'lazy' => false,
                    ]
                ]
            ]],
            ['predis' => [
                'transport' => [
                    'default' => 'redis',
                    'redis' => [
                        'host' => getenv('SYMFONY__REDIS__HOST'),
                        'port' => (int) getenv('SYMFONY__REDIS__PORT'),
                        'vendor' => 'predis',
                        'lazy' => false,
                    ]
                ]
            ]],
            ['phpredis' => [
                'transport' => [
                    'default' => 'redis',
                    'redis' => [
                        'host' => getenv('SYMFONY__REDIS__HOST'),
                        'port' => (int) getenv('SYMFONY__REDIS__PORT'),
                        'vendor' => 'phpredis',
                        'lazy' => false,
                    ]
                ]
            ]],
            ['fs' => [
                'transport' => [
                    'default' => 'fs',
                    'fs' => [
                        'store_dir' => sys_get_temp_dir(),
                    ]
                ]
            ]],
            ['dbal' => [
                'transport' => [
                    'default' => 'dbal',
                    'dbal' => [
                        'dbname' => getenv('SYMFONY__DB__NAME'),
                        'user' => getenv('SYMFONY__DB__USER'),
                        'password' => getenv('SYMFONY__DB__PASSWORD'),
                        'host' => getenv('SYMFONY__DB__HOST'),
                        'port' => getenv('SYMFONY__DB__PORT'),
                        'driver' => getenv('SYMFONY__DB__DRIVER'),
                    ]
                ]
            ]],
            ['sqs' => [
                'transport' => [
                    'default' => 'sqs',
                    'sqs' => [
                        'key' => getenv('AWS__SQS__KEY'),
                        'secret' => getenv('AWS__SQS__SECRET'),
                        'region' => getenv('AWS__SQS__REGION'),
                    ]
                ]
            ]],
        ];
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testProducerSendsMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $queue = $this->getPsrContext()->createQueue('enqueue.test');

        $consumer = $this->getPsrContext()->createConsumer($queue);

        $message = $consumer->receive(100);

        $this->assertInstanceOf(PsrMessage::class, $message);
        $this->assertSame('test message body', $message->getBody());
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testClientConsumeMessagesFromExplicitlySetQueue(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $command = $this->container->get('enqueue.client.consume_messages_command');
        $processor = $this->container->get('test.message.processor');

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 2,
            '--time-limit' => 'now +10 seconds',
            'client-queue-names' => ['test'],
        ]);

        $this->assertInstanceOf(PsrMessage::class, $processor->message);
        $this->assertEquals('test message body', $processor->message->getBody());
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testTransportConsumeMessagesCommandShouldConsumeMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $command = $this->container->get('enqueue.command.consume_messages');
        $command->setContainer($this->container);
        $processor = $this->container->get('test.message.processor');

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 1,
            '--time-limit' => '+10sec',
            '--queue' => ['enqueue.test'],
            'processor-service' => 'test.message.processor',
        ]);

        $this->assertInstanceOf(PsrMessage::class, $processor->message);
        $this->assertEquals('test message body', $processor->message->getBody());
    }

    /**
     * @return ProducerInterface|object
     */
    private function getMessageProducer()
    {
        return $this->container->get('enqueue.client.producer');
    }

    /**
     * @return PsrContext|object
     */
    private function getPsrContext()
    {
        return $this->container->get('enqueue.transport.context');
    }

    protected function customSetUp(array $enqueueConfig)
    {
        static::$class = null;

        $this->client = static::createClient(['enqueue_config' => $enqueueConfig]);
        $this->client->getKernel()->boot();
        $this->container = static::$kernel->getContainer();

        /** @var DriverInterface $driver */
        $driver = $this->container->get('enqueue.client.driver');
        $context = $this->getPsrContext();

        $queue = $driver->createQueue('test');

        //guard
        $this->assertEquals('enqueue.test', $queue->getQueueName());

        if (method_exists($context, 'deleteQueue')) {
            $context->deleteQueue($queue);
        }

        $driver->setupBroker();
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = array())
    {
        /** @var CustomAppKernel $kernel */
        $kernel = parent::createKernel($options);

        $kernel->setEnqueueConfig(isset($options['enqueue_config']) ? $options['enqueue_config'] : []);

        return $kernel;
    }

    /**
     * @return string
     */
    public static function getKernelClass()
    {
        include_once __DIR__.'/app/CustomAppKernel.php';

        return CustomAppKernel::class;
    }

    public function setUp()
    {
        // do not call parent::setUp.
        // parent::setUp();
    }
}
