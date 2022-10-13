<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Generator;
use Papyrus\DomainEventRegistry\DomainEventNameResolver\DomainEventNameResolver;
use Papyrus\DomainEventRegistry\DomainEventRegistry;
use Papyrus\EventSourcing\AggregateRootId;
use Papyrus\EventSourcing\DomainEvent;
use Papyrus\EventStore\EventStore\AggregateRootNotFoundException;
use Papyrus\EventStore\EventStore\DomainEventEnvelope;
use Papyrus\EventStore\EventStore\EventStore;
use Papyrus\EventStore\EventStore\EventStoreFailedException;
use Papyrus\EventStore\EventStore\Metadata;
use Papyrus\Serializer\Serializer;
use Throwable;

final class DoctrineDbalEventStore implements EventStore
{
    /**
     * @param Serializer<DomainEvent> $serializer
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly TableSchema $tableSchema,
        private readonly DomainEventRegistry $domainEventRegistry,
        private readonly Serializer $serializer,
        private readonly DomainEventNameResolver $domainEventNameResolver,
    ) {
    }

    public function load(AggregateRootId $aggregateRootId, int $playhead = 0): Generator
    {
        try {
            $results = $this->connection->createQueryBuilder()
                ->select('*')
                ->from($this->tableSchema->tableName)
                ->where(sprintf('%s >= :playhead', $this->tableSchema->playheadFieldName))
                ->andWhere(sprintf('%s = :aggregateRootId', $this->tableSchema->aggregateRootIdFieldName))
                ->setParameters([
                    'playhead' => $playhead,
                    'aggregateRootId' => $aggregateRootId,
                ])
                ->orderBy($this->tableSchema->aggregateRootIdFieldName, 'asc')
                ->executeQuery()
                ->fetchAllAssociative()
            ;
        } catch (Throwable $exception) {
            throw EventStoreFailedException::withAggregateRootId($aggregateRootId, $exception);
        }

        if (count($results) === 0) {
            throw AggregateRootNotFoundException::withAggregateRootId($aggregateRootId);
        }

        try {
            foreach ($results as $result) {
                /** @var string $eventId */
                $eventId = $result[$this->tableSchema->eventIdFieldName];
                /** @var string $eventName */
                $eventName = $result[$this->tableSchema->eventNameFieldName];
                /** @var int $playhead */
                $playhead = $result[$this->tableSchema->playheadFieldName];
                /** @var string $appliedAt */
                $appliedAt = $result[$this->tableSchema->appliedAtFieldName];
                /** @var string $rawMetadata */
                $rawMetadata = $result[$this->tableSchema->metadataFieldName];
                /** @var array<string, mixed> $metadata */
                $metadata = json_decode($rawMetadata, true, flags: JSON_THROW_ON_ERROR);
                /** @var string $payload */
                $payload = $result[$this->tableSchema->payloadFieldName];

                yield new DomainEventEnvelope(
                    $eventId,
                    $this->serializer->deserialize(
                        json_decode($payload, true, flags: JSON_THROW_ON_ERROR),
                        $this->domainEventRegistry->retrieve($eventName),
                    ),
                    $playhead,
                    new DateTimeImmutable($appliedAt),
                    Metadata::fromPayload($metadata),
                );
            }
        } catch (Throwable $exception) {
            throw EventStoreFailedException::withAggregateRootId($aggregateRootId, $exception);
        }
    }

    public function append(AggregateRootId $aggregateRootId, DomainEventEnvelope ...$messages): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($messages as $envelope) {
                $this->connection->createQueryBuilder()
                    ->insert($this->tableSchema->tableName)
                    ->setValue($this->tableSchema->eventIdFieldName, ':id')
                    ->setValue($this->tableSchema->aggregateRootIdFieldName, ':aggregateRootId')
                    ->setValue($this->tableSchema->eventNameFieldName, ':eventName')
                    ->setValue($this->tableSchema->payloadFieldName, ':payload')
                    ->setValue($this->tableSchema->playheadFieldName, ':playhead')
                    ->setValue($this->tableSchema->metadataFieldName, ':metadata')
                    ->setValue($this->tableSchema->appliedAtFieldName, ':appliedAt')
                    ->setParameters([
                        'id' => $envelope->eventId,
                        'aggregateRootId' => $envelope->event->getAggregateRootId(),
                        'eventName' => $this->domainEventNameResolver->resolve($envelope->event),
                        'payload' => json_encode($this->serializer->serialize($envelope->event), JSON_THROW_ON_ERROR),
                        'playhead' => $envelope->playhead,
                        'metadata' => json_encode($envelope->metadata, JSON_THROW_ON_ERROR),
                        'appliedAt' => $envelope->appliedAt->format($this->tableSchema->appliedAtFieldFormat),
                    ])
                    ->executeStatement()
                ;
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw EventStoreFailedException::withAggregateRootId($aggregateRootId, $exception);
        }
    }
}
