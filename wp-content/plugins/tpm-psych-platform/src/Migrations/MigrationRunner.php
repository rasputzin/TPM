<?php

declare(strict_types=1);

namespace TpmPsych\Migrations;

use TpmPsych\Migrations\Versions\Migration_2026_01_000_Initial;

/**
 * Rulează migrațiile neaplicate, idempotent. dbDelta() nu suportă fiabil
 * FOREIGN KEY, deci fiecare migrație aplică întâi schema de coloane prin
 * dbDelta(), apoi constrângerile FK prin ALTER TABLE direct.
 */
final class MigrationRunner
{
    private const TRACKING_TABLE = 'tpm_schema_migrations';

    /** @var list<class-string<MigrationInterface>> */
    private const MIGRATIONS = [
        Migration_2026_01_000_Initial::class,
    ];

    public function runPending(): void
    {
        global $wpdb;

        $this->ensureTrackingTable($wpdb);

        $applied = $this->appliedVersions($wpdb);
        $charsetCollate = $wpdb->get_charset_collate();

        $migrations = array_map(static fn (string $class): MigrationInterface => new $class(), self::MIGRATIONS);
        usort($migrations, static fn (MigrationInterface $a, MigrationInterface $b): int => $a->version() <=> $b->version());

        foreach ($migrations as $migration) {
            if (in_array($migration->version(), $applied, true)) {
                continue;
            }

            $migration->up($wpdb, $charsetCollate);

            $wpdb->insert(
                $wpdb->prefix . self::TRACKING_TABLE,
                [
                    'version' => $migration->version(),
                    'applied_at' => current_time('mysql', true),
                ],
                ['%s', '%s']
            );
        }
    }

    private function ensureTrackingTable(\wpdb $wpdb): void
    {
        $table = $wpdb->prefix . self::TRACKING_TABLE;
        $charsetCollate = $wpdb->get_charset_collate();

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$table} (
                version varchar(50) NOT NULL,
                applied_at datetime NOT NULL,
                PRIMARY KEY  (version)
            ) {$charsetCollate};"
        );
    }

    /** @return list<string> */
    private function appliedVersions(\wpdb $wpdb): array
    {
        $table = $wpdb->prefix . self::TRACKING_TABLE;

        $rows = $wpdb->get_col("SELECT version FROM {$table}");

        return is_array($rows) ? $rows : [];
    }
}
