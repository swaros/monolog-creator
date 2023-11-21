<?php

namespace MonologCreator\Factory;

use Monolog;
use MonologCreator\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

/**
 * Class HandlerTest
 *
 * @package MonologCreator\Factory
 */
class HandlerTest extends TestCase
{
    /**
     * @var Formatter|MockObject|null
     */
    private Formatter|MockObject|null $mockFormatterFactory = null;

    /**
     * @var MockObject|null|Monolog\Formatter\FormatterInterface
     */
    private MockObject|null|Monolog\Formatter\FormatterInterface $mockFormatter = null;

    /**
     * @var MockObject|Monolog\Handler\SyslogUdp\UdpSocket|null
     */
    private MockObject|null|Monolog\Handler\SyslogUdp\UdpSocket $mockUdpSocket = null;


    public function setUp(): void
    {
        parent::setUp();

        $this->mockFormatterFactory = $this->getMockBuilder('\MonologCreator\Factory\Formatter')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->mockFormatter = $this->getMockBuilder('\Monolog\Formatter\FormatterInterface')
            ->disableOriginalConstructor()
            ->onlyMethods(['format', 'formatBatch'])
            ->getMock();

        $this->mockUdpSocket = $this->getMockBuilder('\Monolog\Handler\SyslogUdp\UdpSocket')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateFailNoConfig()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no handler configuration found');

        $factory = new Handler(array(), $this->mockFormatterFactory);
        $factory->create('mockHandler', 'INFO');
    }

    public function testCreateFailWrongHandlerType()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "stream" : {
                        "path" : "./app.log"
                    }
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no handler configuration found for handlerType: mockHandler');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('mockHandler', 'INFO');
    }

    public function testCreateFailNotSupported()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "mockHandler" : {
                        "path" : "./app.log"
                    }
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('handler type: mockHandler is not supported');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('mockHandler', 'INFO');
    }

    public function testCreateStreamHandlerFail()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "stream" : {}
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('path configuration for stream handler is missing');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('stream', 'INFO');
    }

    /**
     * @throws Exception
     */
    public function testCreateStreamHandler()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "stream" : {
                        "path" : "./app.log"
                    }
                }
            }',
            true
        );

        $factory = new Handler(
            $config,
            $this->mockFormatterFactory
        );
        $handler = $factory->create('stream', 'INFO');

        $this->assertInstanceOf(
            '\Monolog\Handler\StreamHandler',
            $handler
        );
    }

    public function testCreateUdpFailNoHost()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "udp" : {}
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('host configuration for udp handler is missing');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('udp', 'INFO');
    }

    public function testCreateUdpFailNoPort()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "udp" : {
                        "host" : "192.168.50.48"
                    }
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('port configuration for udp handler is missing');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('udp', 'INFO');
    }

    /**
     * @throws Exception
     */
    public function testCreateUdp()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "udp" : {
                        "host"      : "192.168.50.48",
                        "port"      : 9999,
                        "level"     : "INFO"
                    }
                }
            }',
            true
        );

        $factory = $this->getMockBuilder('\MonologCreator\Factory\Handler')
            ->setConstructorArgs(
                [
                    $config,
                    $this->mockFormatterFactory,
                ]
            )
            ->onlyMethods(['createUdpSocket'])
            ->getMock();

        $factory->expects($this->exactly(1))
            ->method('createUdpSocket')
            ->with(
                $this->equalTo('192.168.50.48'),
                $this->equalTo('9999')
            )
            ->willReturn($this->mockUdpSocket);

        $handler = $factory->create('udp', 'INFO');

        $this->assertInstanceOf(
            '\MonologCreator\Handler\Udp',
            $handler
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateWithFormatter()
    {
        $this->mockFormatterFactory
            ->expects($this->exactly(1))
            ->method('create')
            ->with($this->equalTo('logstash'))
            ->willReturn($this->mockFormatter);

        $config = json_decode(
            '{
                "handler" : {
                    "stream" : {
                        "path"      : "./app.log",
                        "formatter" : "logstash"
                    }
                },
                "formatter" : {
                    "logstash" : {
                        "type" : "test"
                    }
                }
            }',
            true
        );

        $factory = new Handler(
            $config,
            $this->mockFormatterFactory
        );
        $handler = $factory->create('stream', 'INFO');

        $this->assertInstanceOf(
            '\Monolog\Handler\StreamHandler',
            $handler
        );

        $this->assertInstanceOf(
            '\Monolog\Formatter\FormatterInterface',
            $handler->getFormatter()
        );
    }

    public function testCreateRedisFailNoKey()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "redis" : {
                    }
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('key configuration for redis handler is missing');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('redis', 'INFO');
    }

    public function testCreateRedisNoObject()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "redis" : {
                        "key" : "mockKey"
                    }
                }
            }',
            true
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('predis client object is not set');

        $factory = new Handler($config, $this->mockFormatterFactory);
        $factory->create('redis', 'INFO');
    }

    /**
     * @throws Exception
     */
    public function testCreateRedis()
    {
        $config = json_decode(
            '{
                "handler" : {
                    "redis" : {
                        "key" : "mockKey"
                    }
                }
            }',
            true
        );

        $factory      = new Handler(
            $config,
            $this->mockFormatterFactory,
            new Client('')
        );
        $redisHandler = $factory->create('redis', 'INFO');

        $this->assertInstanceOf(Monolog\Handler\RedisHandler::class, $redisHandler);
    }
}
