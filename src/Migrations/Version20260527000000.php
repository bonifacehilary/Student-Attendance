<?php

declare(strict_types=1);

namespace Mpemba\Crud\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Password Reset Tokens Table
 */
final class Version20260527000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create password_resets table for storing password reset tokens';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('password_resets');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('student_id', 'integer');
        $table->addColumn('token_hash', 'string', ['length' => 64]);
        $table->addColumn('expires_at', 'datetime');
        $table->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('used_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['student_id'], 'unique_student_reset');
        $table->addForeignKeyConstraint('students', ['student_id'], ['id'], [
            'onDelete' => 'CASCADE'
        ]);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('password_resets');
    }
}
