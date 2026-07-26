<?php

declare(strict_types=1);

namespace TpmPsych\Migrations;

interface MigrationInterface
{
    /**
     * Identificator de versiune sortabil lexicografic, ex. "2026_01_000".
     */
    public function version(): string;

    public function up(\wpdb $wpdb, string $charsetCollate): void;
}
