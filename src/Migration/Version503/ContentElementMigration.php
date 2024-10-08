<?php

declare(strict_types=1);

/*
 * This file is part of Contao Theme SAC Pilatus.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-theme-sac-pilatus
 */

namespace Markocupic\SacEventToolBundle\Migration\Version503;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * @internal
 */
class ContentElementMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        if (!isset($columns['id']) || !isset($columns['type']) || !isset($columns['customtpl']) || !isset($columns['gallerytpl'])) {
            return false;
        }

        $runMigration = false;

        $hasOneA = $this->connection->fetchOne(
            'SELECT id FROM tl_content WHERE type="hyperlink" AND customTpl = "ce_hyperlink_bootstrap_button"',
        );

        $hasOneB = $this->connection->fetchOne(
            'SELECT id FROM tl_content WHERE type="gallery" AND galleryTpl = "gallery_bootstrap_col-4-figure-caption"',
        );

        $hasOneC = $this->connection->fetchOne(
            'SELECT id FROM tl_content WHERE type="gallery" AND galleryTpl = "gallery_bootstrap_col-4"',
        );

        if ($hasOneA || $hasOneB || $hasOneC) {
            $runMigration = true;
        }

        return $runMigration;
    }

    public function run(): MigrationResult
    {
        // hasOneA
        $this->swapTemplate('tl_content', 'hyperlink', 'customTpl', 'ce_hyperlink_bootstrap_button', 'content_element/hyperlink/bootstrap_button');

        // hasOneB: Migrate galleries
        $this->migrateGalleriesWithCaption();

        // hasOneC: Migrate galleries without caption
        $this->migrateGalleriesWithoutCaption();

        return $this->createResult(true);
    }

    protected function swapTemplate(string $table_name, string $content_element_type, string $field_name, string $template_old, string $template_new): int|string
    {
        return $this->connection->executeStatement(
            "UPDATE $table_name SET $field_name = '$template_new' WHERE type = :content_element_type AND $field_name = :template_old",
            [
                'content_element_type' => $content_element_type,
                'template_old' => $template_old,
            ],
            [
                'content_element_type' => Types::STRING,
                'template_name' => Types::STRING,
            ]
        );
    }

    protected function migrateGalleriesWithCaption(): int|string
    {
        return $this->connection->executeStatement(
            "UPDATE tl_content SET galleryTpl = '', customTpl = 'content_element/gallery/col_4_with_caption' WHERE type = 'gallery' AND galleryTpl = 'gallery_bootstrap_col-4-figure-caption'",
        );
    }

    protected function migrateGalleriesWithoutCaption(): int|string
    {
        return $this->connection->executeStatement(
            "UPDATE tl_content SET galleryTpl = '', customTpl = 'content_element/gallery/col_4' WHERE type = 'gallery' AND galleryTpl = 'gallery_bootstrap_col-4'",
        );
    }
}
