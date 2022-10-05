<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore\Test\Stub;

use Papyrus\EventSourcing\AggregateRootId;

final class TestAggregateRootId implements AggregateRootId
{
    public function __toString(): string
    {
        return '0c2f6e08-0b4d-4c66-8623-d45bcfb93208';
    }
}
