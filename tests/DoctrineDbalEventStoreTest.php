<?php

declare(strict_types=1);

namespace Papyrus\DoctrineDbalEventStore\Test;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Papyrus\DoctrineDbalEventStore\DoctrineDbalEventStore;
use Papyrus\DoctrineDbalEventStore\TableSchemaFactory;
use Papyrus\DoctrineDbalEventStore\Test\Stub\TestAggregateRootId;
use Papyrus\DoctrineDbalEventStore\Test\Stub\TestAnotherDomainEvent;
use Papyrus\DoctrineDbalEventStore\Test\Stub\TestDomainEvent;
use Papyrus\DomainEventRegistry\InMemory\InMemoryDomainEventRegistry;
use Papyrus\EventSourcing\DomainEvent;
use Papyrus\EventStore\EventStore\AggregateRootNotFoundException;
use Papyrus\EventStore\EventStore\DomainEventEnvelope;
use Papyrus\EventStore\EventStore\EventStoreFailedException;
use Papyrus\EventStore\EventStore\Metadata;
use Papyrus\Serializer\SerializableDomainEvent\SerializableDomainEventSerializer;
use Papyrus\Serializer\Serializer;

/**
 * @internal
 */
class DoctrineDbalEventStoreTest extends MockeryTestCase
{
    /**
     * @var MockInterface&Connection
     */
    private MockInterface $connection;

    private DoctrineDbalEventStore $eventStore;

    protected function setUp(): void
    {
        /** @var Serializer<DomainEvent> $serializer */
        $serializer = new SerializableDomainEventSerializer();

        $this->eventStore = new DoctrineDbalEventStore(
            $this->connection = Mockery::mock(Connection::class),
            TableSchemaFactory::create(),
            new InMemoryDomainEventRegistry([
                TestDomainEvent::class,
                TestAnotherDomainEvent::class,
            ]),
            $serializer,
        );

        parent::setUp();
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenQueryFailed(): void
    {
        $this->connection
            ->allows('createQueryBuilder->select->from->where->andWhere->setParameters->orderBy->executeQuery->fetchAllAssociative')
            ->andThrow(new Exception('Failed query'))
        ;

        self::expectException(EventStoreFailedException::class);

        iterator_to_array($this->eventStore->load(new TestAggregateRootId(), 10));
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenNotAggregateRootNotFound(): void
    {
        $this->connection
            ->allows('createQueryBuilder->select->from->where->andWhere->setParameters->orderBy->executeQuery->fetchAllAssociative')
            ->andReturn([])
        ;

        self::expectException(AggregateRootNotFoundException::class);

        iterator_to_array($this->eventStore->load(new TestAggregateRootId(), 10));
    }

    /**
     * @test
     */
    public function itShouldLoadAggregateRoot(): void
    {
        $this->connection
            ->expects('createQueryBuilder->select->from->where->andWhere->setParameters->orderBy->executeQuery->fetchAllAssociative')
            ->andReturn([
                [
                    'id' => '147828da-d896-4cda-9e4a-f21c0f691a32',
                    'aggregate_root_id' => '2af4804f-89cc-4c0d-b0f2-408d22897303',
                    'event_name' => 'test.domain_event',
                    'playhead' => 1,
                    'applied_at' => '2022-10-07 19:29:35.543728',
                    'metadata' => (string) json_encode(['some' => true, 'metadata' => 2.4]),
                    'payload' => (string) json_encode(['aggregateRootId' => '2af4804f-89cc-4c0d-b0f2-408d22897303']),
                ],
                [
                    'id' => '12e74a64-bc40-427d-bc74-0a9dbdb05826',
                    'aggregate_root_id' => 'f7bac034-870b-4ed9-827e-452e7e2ef630',
                    'event_name' => 'test.another_domain_event',
                    'playhead' => 2,
                    'applied_at' => '2022-10-07 19:30:40.758345',
                    'metadata' => (string) json_encode(['some' => false, 'metadata' => 'test']),
                    'payload' => (string) json_encode(['aggregateRootId' => 'f7bac034-870b-4ed9-827e-452e7e2ef630']),
                ],
            ])
        ;

        $envelopes = iterator_to_array($this->eventStore->load(new TestAggregateRootId(), 10));

        self::assertCount(2, $envelopes);

        /** @var DomainEventEnvelope $envelope1 */
        $envelope1 = $envelopes[0];
        self::assertSame(['some' => true, 'metadata' => 2.4], $envelope1->metadata->jsonSerialize());
        self::assertSame(1, $envelope1->playhead);
        self::assertSame('2022-10-07T19:29:35.543+00:00', $envelope1->appliedAt->format(\DateTimeInterface::RFC3339_EXTENDED));
        self::assertSame('147828da-d896-4cda-9e4a-f21c0f691a32', $envelope1->eventId);
        $event1 = $envelope1->event;
        self::assertInstanceOf(TestDomainEvent::class, $event1);
        self::assertSame('2af4804f-89cc-4c0d-b0f2-408d22897303', $event1->aggregateRootId);

        /** @var DomainEventEnvelope $envelope1 */
        $envelope2 = $envelopes[1];
        self::assertSame(['some' => false, 'metadata' => 'test'], $envelope2->metadata->jsonSerialize());
        self::assertSame(2, $envelope2->playhead);
        self::assertSame('2022-10-07T19:30:40.758+00:00', $envelope2->appliedAt->format(\DateTimeInterface::RFC3339_EXTENDED));
        self::assertSame('12e74a64-bc40-427d-bc74-0a9dbdb05826', $envelope2->eventId);
        $event2 = $envelope2->event;
        self::assertInstanceOf(TestAnotherDomainEvent::class, $event2);
        self::assertSame('f7bac034-870b-4ed9-827e-452e7e2ef630', $event2->aggregateRootId);
    }

    /**
     * @test
     */
    public function itShouldAppend(): void
    {
        $this->connection->expects('beginTransaction');
        $this->connection
            ->expects('createQueryBuilder->insert->setValue->setValue->setValue->setValue->setValue->setValue->setValue->setParameters')
            ->with([
                'id' => 'c09b1ae2-f560-4305-82aa-3402d9ce5fae',
                'aggregateRootId' => '8b0888ca-22ee-4970-ad50-564d2fcdcf2c',
                'eventName' => 'test.domain_event',
                'payload' => '{"aggregateRootId":"8b0888ca-22ee-4970-ad50-564d2fcdcf2c"}',
                'playhead' => 1,
                'metadata' => '{"key":"value"}',
                'appliedAt' => '2022-10-07 20:22:13.456326',
            ])->andReturn($queryBuilder1 = Mockery::mock(QueryBuilder::class));

        $queryBuilder1->expects('executeStatement');

        $this->connection
            ->expects('createQueryBuilder->insert->setValue->setValue->setValue->setValue->setValue->setValue->setValue->setParameters')
            ->with([
                'id' => 'd4e03a74-0380-4c77-a756-95e89a7598d3',
                'aggregateRootId' => '51ff60a5-cdb8-4983-96e4-36241befc12a',
                'eventName' => 'test.another_domain_event',
                'payload' => '{"aggregateRootId":"51ff60a5-cdb8-4983-96e4-36241befc12a"}',
                'playhead' => 2,
                'metadata' => '{"key":"non-value"}',
                'appliedAt' => '2022-10-07 20:23:45.673328',
            ])->andReturn($queryBuilder2 = Mockery::mock(QueryBuilder::class));

        $queryBuilder2->expects('executeStatement');

        $this->connection->expects('commit');
        $this->connection->expects('rollBack')->never();

        $this->eventStore->append(
            new TestAggregateRootId(),
            new DomainEventEnvelope(
                'c09b1ae2-f560-4305-82aa-3402d9ce5fae',
                new TestDomainEvent('8b0888ca-22ee-4970-ad50-564d2fcdcf2c'),
                1,
                new DateTimeImmutable('2022-10-07 20:22:13.456326'),
                (new Metadata())->withMetadata('key', 'value'),
            ),
            new DomainEventEnvelope(
                'd4e03a74-0380-4c77-a756-95e89a7598d3',
                new TestAnotherDomainEvent('51ff60a5-cdb8-4983-96e4-36241befc12a'),
                2,
                new DateTimeImmutable('2022-10-07 20:23:45.673328'),
                (new Metadata())->withMetadata('key', 'non-value'),
            ),
        );
    }

    /**
     * @test
     */
    public function itShouldRollbackIfAppendFailed(): void
    {
        $this->connection->expects('beginTransaction');
        $this->connection->expects('createQueryBuilder')->andThrow(new Exception('Failed query'));

        $this->connection->expects('commit')->never();
        $this->connection->expects('rollBack');

        self::expectException(EventStoreFailedException::class);

        $this->eventStore->append(
            new TestAggregateRootId(),
            new DomainEventEnvelope(
                'c09b1ae2-f560-4305-82aa-3402d9ce5fae',
                new TestDomainEvent('8b0888ca-22ee-4970-ad50-564d2fcdcf2c'),
                1,
                new DateTimeImmutable('2022-10-07 20:22:13.456326'),
                (new Metadata())->withMetadata('key', 'value'),
            ),
            new DomainEventEnvelope(
                'd4e03a74-0380-4c77-a756-95e89a7598d3',
                new TestAnotherDomainEvent('51ff60a5-cdb8-4983-96e4-36241befc12a'),
                2,
                new DateTimeImmutable('2022-10-07 20:23:45.673328'),
                (new Metadata())->withMetadata('key', 'non-value'),
            ),
        );
    }
}
