<?php

declare(strict_types=1);

namespace Chiron\Monolog\Test;

use Chiron\Container\Container;
use Chiron\Monolog\MonologFactory;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Chiron\Monolog\Config\MonologConfig;

class MonologFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $app;

    protected function setUp(): void
    {
        $this->app = null;
        $this->container = new Container();
        //$this->tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'log';
    }

    public function testMonologFactoryCachesLoggerInstances()
    {
        $conf = new MonologConfig(
            ['channels' => [
                'single' => [
                    'driver' => 'single',
                    'path'   => './logs/chiron.log',
                    'level'  => 'debug',
                ], ]]
        );

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        $logger1 = $manager->channel('single');
        $logger2 = $manager->channel('single');

        $this->assertSame($logger1, $logger2);
    }

    public function testStackChannel()
    {
        $conf = ['channels' => [
            'stack' => [
                'driver'   => 'stack',
                'channels' => ['stderr', 'stdout'],
            ],
            'stderr' => [
                'driver'       => 'monolog',
                'handler'      => StreamHandler::class,
                'level'        => 'notice',
                'handler_with' => [
                    'stream' => 'php://stderr',
                    'bubble' => false,
                ],
            ],
            'stdout' => [
                'driver'       => 'monolog',
                'handler'      => StreamHandler::class,
                'level'        => 'info',
                'handler_with' => [
                    'stream' => 'php://stdout',
                    'bubble' => true,
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('stack');
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertInstanceOf(StreamHandler::class, $handlers[1]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertEquals(Monolog::INFO, $handlers[1]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());
        $this->assertTrue($handlers[1]->getBubble());
    }

    public function testMonologFactoryCreatesConfiguredMonologHandler()
    {
        $conf = ['channels' => [
            'nonbubblingstream' => [
                'driver'       => 'monolog',
                'name'         => 'foobar',
                'handler'      => StreamHandler::class,
                'level'        => 'notice',
                'handler_with' => [
                    'stream' => 'php://stderr',
                    'bubble' => false,
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('nonbubblingstream');
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertEquals('foobar', $logger->getName());
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());
        $url = new ReflectionProperty(get_class($handlers[0]), 'url');
        $url->setAccessible(true);
        $this->assertEquals('php://stderr', $url->getValue($handlers[0]));

        // === second test ===
        $conf = ['channels' => [
            'logentries' => [
                'driver'       => 'monolog',
                'name'         => 'le',
                'handler'      => LogEntriesHandler::class,
                'handler_with' => [
                    'token' => '123456789',
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);

        $logger = $manager->channel('logentries');
        $handlers = $logger->getHandlers();
        $logToken = new ReflectionProperty(get_class($handlers[0]), 'logToken');
        $logToken->setAccessible(true);
        $this->assertInstanceOf(LogEntriesHandler::class, $handlers[0]);
        $this->assertEquals('123456789', $logToken->getValue($handlers[0]));
    }

    public function testMonologFactoryCreatesMonologHandlerWithConfiguredFormatter()
    {
        $conf = ['channels' => [
            'newrelic' => [
                'driver'    => 'monolog',
                'name'      => 'nr',
                'handler'   => NewRelicHandler::class,
                'formatter' => 'default',
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('newrelic');
        $handler = $logger->getHandlers()[0];
        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(NormalizerFormatter::class, $handler->getFormatter());

        // === second test ===
        $conf = ['channels' => [
            'newrelic2' => [
                'driver'         => 'monolog',
                'name'           => 'nr',
                'handler'        => NewRelicHandler::class,
                'formatter'      => HtmlFormatter::class,
                'formatter_with' => [
                    'dateFormat' => 'Y/m/d--test',
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        $logger = $manager->channel('newrelic2');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);
        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);
        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testMonologFactoryCreateSingleDriverWithConfiguredFormatter()
    {
        $conf = ['channels' => [
            'defaultsingle' => [
                'driver' => 'single',
                'name'   => 'ds',
                'path'   => './logs/chiron.log',
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsingle');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        // === second test ===
        $conf = ['channels' => [
            'formattedsingle' => [
                'driver'         => 'single',
                'name'           => 'fs',
                'path'           => './logs/chiron.log',
                'formatter'      => HtmlFormatter::class,
                'formatter_with' => [
                    'dateFormat' => 'Y/m/d--test',
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);

        $logger = $manager->channel('formattedsingle');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);
        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);
        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testMonologFactoryCreateDailyDriverWithConfiguredFormatter()
    {
        $conf = ['channels' => [
            'defaultdaily' => [
                'driver' => 'daily',
                'name'   => 'dd',
                'path'   => './logs/chiron.log',
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultdaily');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        // === second test ===
        $conf = ['channels' => [
            'formatteddaily' => [
                'driver'         => 'daily',
                'name'           => 'fd',
                'path'           => './logs/chiron.log',
                'formatter'      => HtmlFormatter::class,
                'formatter_with' => [
                    'dateFormat' => 'Y/m/d--test',
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        $logger = $manager->channel('formatteddaily');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);
        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);
        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testMonologFactoryCreateSyslogDriverWithConfiguredFormatter()
    {
        $conf = ['channels' => [
            'defaultsyslog' => [
                'driver' => 'syslog',
                'name'   => 'ds',
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsyslog');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        // === second test ===
        $conf = ['channels' => [
            'formattedsyslog' => [
                'driver'         => 'syslog',
                'name'           => 'fs',
                'formatter'      => HtmlFormatter::class,
                'formatter_with' => [
                    'dateFormat' => 'Y/m/d--test',
                ],
            ],
        ]];

        $manager = new MonologFactory($this->container, $conf, __DIR__);
        $logger = $manager->channel('formattedsyslog');
        $handler = $logger->getHandlers()[0];
        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);
        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);
        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }
}
