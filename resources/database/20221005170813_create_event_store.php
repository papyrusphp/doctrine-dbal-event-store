<?php

declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class CreateEventStore extends AbstractMigration
{
    public function change(): void
    {
        $this->table('event_store', ['id' => false, 'primary_key' => 'id', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 36])
            ->addColumn('aggregate_root_id', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 36])
            ->addColumn('event_name', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 100])
            ->addColumn('payload', AdapterInterface::PHINX_TYPE_JSON)
            ->addColumn('playhead', AdapterInterface::PHINX_TYPE_INTEGER)
            ->addColumn('metadata', AdapterInterface::PHINX_TYPE_JSON)
            ->addColumn('applied_at', AdapterInterface::PHINX_TYPE_TIMESTAMP, ['limit' => 6])
            ->addIndex(['aggregate_root_id', 'playhead'], ['unique' => true])
            ->addIndex('event_name')
            ->create()
        ;
    }
}
