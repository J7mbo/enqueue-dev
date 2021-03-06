<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\QueueNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

class BuildQueueMetaRegistryPassTest extends TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildQueueMetaRegistryPass();
    }

    public function testShouldDoNothingIfRegistryServicesNotSetToContainer()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);
    }

    public function testThrowIfProcessorClassNameCouldNotBeFound()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition('notExistingClass');
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The class "notExistingClass" could not be found.');
        $pass->process($container);
    }

    public function testShouldBuildQueueMetaRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'theProcessorName',
            'topicName' => 'aTopicName',
        ]);
        $container->setDefinition('processor', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'aDefaultQueueName' => ['processors' => ['theProcessorName']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'aTopicName',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'aDefaultQueueName' => ['processors' => ['processor-service-id']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    public function testShouldSetQueueIfSetInTag()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'queueName' => 'theClientQueueName',
            'topicName' => 'aTopicName',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'theClientQueueName' => ['processors' => ['processor-service-id']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    public function testShouldBuildQueueFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyTopicNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'aDefaultQueueName' => ['processors' => ['processor-service-id']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    public function testShouldBuildQueueFromSubscriberIfProcessorNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'aDefaultQueueName' => ['processors' => ['subscriber-processor-name']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    public function testShouldBuildQueueFromSubscriberIfQueueNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(QueueNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $registry = new Definition();
        $registry->setArguments([null, []]);
        $container->setDefinition('enqueue.client.meta.queue_meta_registry', $registry);

        $pass = new BuildQueueMetaRegistryPass();
        $pass->process($container);

        $expectedQueues = [
            'subscriber-queue-name' => ['processors' => ['processor-service-id']],
        ];

        $this->assertEquals($expectedQueues, $registry->getArgument(1));
    }

    /**
     * @return ContainerBuilder
     */
    private function createContainerBuilder()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.client.default_queue_name', 'aDefaultQueueName');

        return $container;
    }
}
