<?php

declare(strict_types=1);

namespace StudentAttendance\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create teachers table for teacher portal login';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('teachers');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('email', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('password_hash', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('assigned_class', 'string', ['length' => 100, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('teachers');
    }
}
