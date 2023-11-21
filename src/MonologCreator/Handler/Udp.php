<?php

namespace MonologCreator\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\SyslogUdp\UdpSocket;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Custom Monolog Handler to sent logs via UDP. Its based on
 * \Monolog\Handler\SyslogUdp\UdpSocket.
 *
 * @package Logger\Handler
 *
 * @@codeCoverageIgnore
 */
class Udp extends AbstractProcessingHandler
{
    public function __construct(
        private UdpSocket $socket,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $lines = $this->splitMessageIntoLines($record->formatted);

        foreach ($lines as $line) {
            $this->socket->write($line);
        }
    }

    public function close(): void
    {
        $this->socket->close();
    }

    private function splitMessageIntoLines(mixed $message): array
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }

        return preg_split('/$\R?^/m', $message);
    }

    public function setSocket(UdpSocket $socket): void
    {
        $this->socket = $socket;
    }
}
