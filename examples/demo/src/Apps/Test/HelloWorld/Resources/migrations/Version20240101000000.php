<?php

/**
 * Doctrine migration for the HelloWorld demo component.
 *
 * @package App\Test\HelloWorld\Migrations
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace App\Test\HelloWorld\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the sample table used by the HelloWorld component.
 */
final class Version20240101000000 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Create sample table for HelloWorld component.';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_test__hello-world__sample (id VARCHAR(26) NOT NULL, PRIMARY KEY(id))');
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_test__hello-world__sample');
    }
}
