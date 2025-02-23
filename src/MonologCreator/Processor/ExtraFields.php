<?php

namespace MonologCreator\Processor;

use Monolog\LogRecord;

/**
 * Allows adding additional high-level or special fields to the log output.
 *
 * @package MonologCreator\Processor
 * @author Sebastian Götze <s.goetze@bigpoint.net>
 */
class ExtraFields implements \Monolog\Processor\ProcessorInterface
{
    public function __construct(
        private readonly array $extraFields = array()
    ) {
    }

    /**
     * Adds extra fields to the record.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if (false === \is_array($record->extra)) {
            $record->extra = array();
        }

        // Add fields to record
        $record->extra = \array_merge($record->extra, $this->extraFields);

        return $record;
    }
}
