<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore\Test\Stub;

use Papyrus\DomainEventRegistry\DomainEventNameResolver\NamedDomainEvent;
use Papyrus\EventSourcing\DomainEvent;
use Papyrus\Serializer\SerializableDomainEvent\SerializableDomainEvent;

final class TestAnotherDomainEvent implements DomainEvent, NamedDomainEvent, SerializableDomainEvent
{
    public function __construct(
        public readonly string $aggregateRootId,
    ) {
    }

    public static function getEventName(): string
    {
        return 'test.another_domain_event';
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
