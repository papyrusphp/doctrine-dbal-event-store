<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore;

final class TableSchema
{
    public function __construct(
        public readonly string $tableName,
        public readonly string $eventIdFieldName,
        public readonly string $aggregateRootIdFieldName,
        public readonly string $eventNameFieldName,
        public readonly string $payloadFieldName,
        public readonly string $playheadFieldName,
        public readonly string $metadataFieldName,
        public readonly string $appliedAtFieldName,
        public readonly string $appliedAtFieldFormat,
    ) {
    }
}
