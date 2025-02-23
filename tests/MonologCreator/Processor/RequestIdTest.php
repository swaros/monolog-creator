<?php

namespace MonologCreator\Processor;

/**
 * Class FormatterTest
 *
 * @package MonologCreator\Factory
 */
class RequestIdTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        $subject = new RequestId();
        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );

        $actual  = $subject->__invoke($record);
        $this->assertTrue(\array_key_exists('request_id', $actual->extra));
    }

    public function testMultipleInvokesHaveSameID()
    {
        $subject = new RequestId();
        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );
        $actual1 = $subject->__invoke($record);
        $actual2 = $subject->__invoke($record);
        $this->assertTrue(\array_key_exists('request_id', $actual1->extra));
        $this->assertSame(
            $actual1->extra['request_id'],
            $actual2->extra['request_id']
        );
    }

    /**
     * A UUIDv4 is formatted
     * xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * where x is 0-9, A-F and y is 8-9, A-B.
     */
    public function testGeneratedUUIDValid()
    {
        $subject = new RequestId();
        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );
        $actual  = $subject->__invoke($record);
        $UUID    = $actual->extra['request_id'];
        $this->assertTrue(
            1 === \preg_match(
                '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i',
                $UUID
            )
        );
    }

    public function testgenerateUUIDWithRandomBytes()
    {
        $subject =
            $this->getMockBuilder('MonologCreator\Processor\RequestId')
                ->onlyMethods(
                    array(
                        'isCallable',
                        'randomBytes',
                    )
                )
                ->disableOriginalConstructor()
                ->getMock();

        $subject->expects($this->once())
            ->method('isCallable')
            ->with($this->equalTo('random_bytes'))
            ->willReturn(true);

        $subject->expects($this->once())
            ->method('randomBytes')
            ->with($this->equalTo(16))
            ->willReturn('abcdefgh12345678');

        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );
        $subject->__invoke($record);
    }

    public function testgenerateUUIDWithOpenSSLRandomPseudoBytes()
    {
        $subject =
            $this->getMockBuilder('MonologCreator\Processor\RequestId')
                ->onlyMethods(
                    array(
                        'isCallable',
                        'opensslRandomPseudoBytes',
                    )
                )
                ->disableOriginalConstructor()
                ->getMock();

        $subject->expects($this->exactly(2))
            ->method('isCallable')
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $subject->expects($this->once())
            ->method('opensslRandomPseudoBytes')
            ->with($this->equalTo(16))
            ->willReturn('abcdefgh12345678');

        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );
        $subject->__invoke($record);
    }

    public function testgenerateUUIDWithMtRand()
    {
        $subject =
            $this->getMockBuilder('MonologCreator\Processor\RequestId')
                ->onlyMethods(
                    array(
                        'isCallable',
                        'generateBytesWithMtRand',
                    )
                )
                ->disableOriginalConstructor()
                ->getMock();

        $subject->expects($this->exactly(3))
            ->method('isCallable')
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                true
            );

        $subject->expects($this->once())
            ->method('generateBytesWithMtRand')
            ->with($this->equalTo(16))
            ->willReturn('abcdefgh12345678');

        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );

        $subject->__invoke($record);
    }

    public function testgenerateUUIDWithoutRNG()
    {
        $subject =
            $this->getMockBuilder('MonologCreator\Processor\RequestId')
                ->onlyMethods(
                    array(
                        'isCallable',
                        'generateBytesWithMtRand',
                    )
                )
                ->disableOriginalConstructor()
                ->getMock();

        $subject->expects($this->exactly(3))
            ->method('isCallable')
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false
            );

        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );

        $subject->__invoke($record);
    }

    public function testgenerateBytesWithMtRand()
    {
        $subject =
            $this->getMockBuilder('MonologCreator\Processor\RequestId')
                ->onlyMethods(
                    array(
                        'isCallable',
                        'mtRand',
                    )
                )
                ->disableOriginalConstructor()
                ->getMock();

        $subject->expects($this->exactly(3))
            ->method('isCallable')
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                true
            );

        $subject->expects($this->exactly(16))
            ->method('mtRand')
            ->with($this->equalTo(0), $this->equalTo(255))
            ->willReturn(97);

        $record  = new \Monolog\LogRecord(
            new \DateTimeImmutable(),
            'testChannel',
            \Monolog\Level::Debug,
            'testMessage',
        );

        $subject->__invoke($record);
    }
}
