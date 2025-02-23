<?php

namespace MonologCreator;

use MonologCreator;
use Monolog;
use Predis\Client;

/**
 * Factory class to for creating monolog loggers with pre-configured array
 */
class Factory
{
    /**
     * optional, only needed for the redis handler
     * @var Client|null
     */
    private ?Client $predisClient = null;

    /**
     * saves already created loggers
     *
     * @var array
     */
    private array $logger = [];

    public function __construct(
        private readonly array $config
    ) {
    }

    /**
     * Creates a single Monolog\Logger object depend on assigned logger name
     * and configuration. Created loggers are cached for multi-usage.
     *
     * @throws MonologCreator\Exception
     */
    public function createLogger(string $name): Monolog\Logger
    {
        // short circuit for cached logger objects
        if (true === array_key_exists($name, $this->logger)) {
            return $this->logger[$name];
        }

        $loggerConfig  = $this->getLoggerConfig($name);
        $handlers      = $this->createHandlers($loggerConfig);
        $processors    = $this->createProcessors($loggerConfig);
        $logger        = new Monolog\Logger(
            $name,
            $handlers,
            $processors
        );

        // cache created logger
        $this->logger[$name] = $logger;

        return $logger;
    }

    /**
     * @throws MonologCreator\Exception
     */
    public function createHandlers(array $loggerConfig): array
    {
        $handlers         = [];
        $formatterFactory = new MonologCreator\Factory\Formatter(
            $this->config
        );
        $handlerFactory   = new MonologCreator\Factory\Handler(
            $this->config,
            $formatterFactory,
            $this->predisClient
        );

        foreach ($loggerConfig['handler'] as $handlerType) {
            $handlers[] = $handlerFactory->create(
                $handlerType,
                $loggerConfig['level']
            );
        }

        return $handlers;
    }

    /**
     * @throws MonologCreator\Exception
     */
    public function createProcessors(array $loggerConfig): array
    {
        $processors = [];

        if (
            false === array_key_exists('processors', $loggerConfig)
            || false === is_array($loggerConfig['processors'])
        ) {
            return $processors;
        }

        foreach ($loggerConfig['processors'] as $processor) {
            if ('web' === $processor) {
                $webProcessor = new Monolog\Processor\WebProcessor();
                $webProcessor->addExtraField('user_agent', 'HTTP_USER_AGENT');
                $webProcessor->addExtraField('client_ip', 'HTTP_X_CLIENT_IP');

                $processors[] = $webProcessor;
            } elseif ('requestId' === $processor) {
                $processors[] = new Processor\RequestId();
            } elseif ('extraFields' === $processor) {
                $extraFields = array();

                if (
                    true === array_key_exists('extraFields', $loggerConfig)
                    && true === \is_array($loggerConfig['extraFields'])
                ) {
                    $extraFields = $loggerConfig['extraFields'];
                }

                $processors[] = new Processor\ExtraFields($extraFields);
            } else {
                throw new MonologCreator\Exception(
                    'processor type: ' . $processor . ' is not supported'
                );
            }
        }

        return $processors;
    }

    /**
     * @throws MonologCreator\Exception
     */
    private function getLoggerConfig(string $name): array
    {
        if (false === array_key_exists('logger', $this->config)) {
            throw new MonologCreator\Exception("no logger configuration found");
        }

        if (false === array_key_exists('_default', $this->config['logger'])) {
            throw new MonologCreator\Exception(
                "no configuration found for logger: _default"
            );
        }

        $loggerConfig = $this->config['logger']['_default'];

        if (true === array_key_exists($name, $this->config['logger'])) {
            $loggerConfig  = $this->config['logger'][$name];
        }

        if (false === array_key_exists('handler', $loggerConfig)) {
            throw new MonologCreator\Exception(
                "no handler configured for logger: " . $name
            );
        }

        if (false === array_key_exists('level', $loggerConfig)) {
            throw new MonologCreator\Exception(
                "no level configured for logger: " . $name
            );
        }

        if (false === in_array(strtoupper($loggerConfig['level']), \Monolog\Level::NAMES)) {
            throw new MonologCreator\Exception(
                "invalid level: " . strtoupper($loggerConfig['level'])
            );
        }

        return $loggerConfig;
    }

    public function setPredisClient(Client $predisClient): void
    {
        $this->predisClient = $predisClient;
    }
}
