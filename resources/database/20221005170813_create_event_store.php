<?php

declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class CreateEventStore extends AbstractMigration
{
    public function change(): void
    {
        $this->table('event_store', ['id' => false, 'primary_key' => 'id', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 36, 'null' => false])
            ->addColumn('aggregate_root_id', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 36, 'null' => false])
            ->addColumn('event_name', AdapterInterface::PHINX_TYPE_STRING, ['limit' => 100, 'null' => false])
            ->addColumn('payload', AdapterInterface::PHINX_TYPE_JSON, ['null' => false])
            ->addColumn('playhead', AdapterInterface::PHINX_TYPE_INTEGER, ['null' => false])
            ->addColumn('metadata', AdapterInterface::PHINX_TYPE_JSON, ['null' => false])
            ->addColumn('applied_at', AdapterInterface::PHINX_TYPE_TIMESTAMP, ['limit' => 6, 'null' => false])
            ->addIndex(['aggregate_root_id', 'playhead'], ['unique' => true])
            ->addIndex('event_name')
            ->create()
        ;
    }
}
