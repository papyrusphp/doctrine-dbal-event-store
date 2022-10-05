<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore\Test\Stub;

use Papyrus\Serializer\SerializableDomainEvent\SerializableDomainEvent;

final class TestDomainEvent implements SerializableDomainEvent
{
    public function __construct(
        public readonly string $aggregateRootId,
    ) {
    }

    public static function getEventName(): string
    {
        return 'test.domain_event';
    }

    public function getAggregateRootId(): string
    {
        return $this->aggregateRootId;
    }

    /**
     * @return array{aggregateRootId: string}
     */
    public function serialize(): mixed
    {
        return [
            'aggregateRootId' => $this->aggregateRootId,
        ];
    }

    /**
     * @param array{aggregateRootId: string} $payload
     */
    public static function deserialize(mixed $payload): SerializableDomainEvent
    {
        return new self($payload['aggregateRootId']);
    }
}
