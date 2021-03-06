<?php

namespace Enqueue\Null\Tests;

use Enqueue\Psr\PsrProducer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullProducer;
use Enqueue\Null\NullTopic;
use PHPUnit\Framework\TestCase;

class NullProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, NullProducer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullProducer();
    }

    public function testShouldDoNothingOnSend()
    {
        $producer = new NullProducer();

        $producer->send(new NullTopic('aName'), new NullMessage());
    }
}
