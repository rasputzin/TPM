<?php

declare(strict_types=1);

namespace TpmPsych\Migrations\Versions;

use TpmPsych\Migrations\MigrationInterface;

/**
 * Schema inițială: organizații + modulul "Administrarea instrumentelor"
 * (instrumente, versiuni, secțiuni, itemi, opțiuni de răspuns, scale,
 * reguli de cotare, seturi/valori de etalon, șabloane de raport).
 *
 * Tabelele pentru clienți/administrări/răspunsuri/rapoarte (fluxul de
 * completare efectivă) fac parte dintr-o migrație ulterioară, odată ce
 * modulul de administrare a instrumentelor e stabil — vezi
 * docs/psychology-platform/11-modul-administrare-instrumente.md.
 *
 * Status-urile (draft/active/etc.) sunt VARCHAR, nu ENUM: valorile permise
 * sunt validate la nivel de aplicație (Validators), nu la nivel de coloană,
 * pentru a evita blocajele de ALTER TABLE pe ENUM la evoluția schemei.
 */
final class Migration_2026_01_000_Initial implements MigrationInterface
{
    public function version(): string
    {
        return '2026_01_000';
    }

    public function up(\wpdb $wpdb, string $charsetCollate): void
    {
        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $p = $wpdb->prefix . 'tpm_';

        dbDelta("CREATE TABLE {$p}organizations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            slug varchar(190) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'active',
            plan varchar(50) DEFAULT NULL,
            settings_json longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}instruments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organization_id bigint(20) unsigned DEFAULT NULL,
            code varchar(50) NOT NULL,
            name varchar(190) NOT NULL,
            rights_holder varchar(190) NOT NULL,
            license_reference varchar(190) DEFAULT NULL,
            target_population varchar(190) DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY organization_id (organization_id),
            KEY status (status)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}instrument_versions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instrument_id bigint(20) unsigned NOT NULL,
            version_label varchar(50) NOT NULL,
            language varchar(10) NOT NULL DEFAULT 'ro',
            estimated_duration_minutes smallint(5) unsigned DEFAULT NULL,
            instructions_text longtext DEFAULT NULL,
            missing_response_policy_json longtext DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            published_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY instrument_version (instrument_id,version_label),
            KEY status (status)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}sections (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instrument_version_id bigint(20) unsigned NOT NULL,
            parent_section_id bigint(20) unsigned DEFAULT NULL,
            title varchar(190) DEFAULT NULL,
            order_index smallint(5) unsigned NOT NULL DEFAULT 0,
            branching_rule_json longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY instrument_version_id (instrument_version_id),
            KEY parent_section_id (parent_section_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instrument_version_id bigint(20) unsigned NOT NULL,
            section_id bigint(20) unsigned DEFAULT NULL,
            code varchar(50) NOT NULL,
            prompt_text longtext NOT NULL,
            item_type varchar(30) NOT NULL,
            is_reverse_scored tinyint(1) NOT NULL DEFAULT 0,
            is_required tinyint(1) NOT NULL DEFAULT 1,
            conditional_rule_json longtext DEFAULT NULL,
            order_index smallint(5) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY instrument_version_item (instrument_version_id,code),
            KEY section_id (section_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}response_options (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_id bigint(20) unsigned NOT NULL,
            label_text varchar(255) NOT NULL,
            raw_value decimal(10,4) NOT NULL,
            order_index smallint(5) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY item_id (item_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}scales (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instrument_version_id bigint(20) unsigned NOT NULL,
            parent_scale_id bigint(20) unsigned DEFAULT NULL,
            code varchar(50) NOT NULL,
            name varchar(190) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY instrument_version_scale (instrument_version_id,code),
            KEY parent_scale_id (parent_scale_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}scoring_rules (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scale_id bigint(20) unsigned NOT NULL,
            rule_type varchar(30) NOT NULL,
            formula_definition_json longtext NOT NULL,
            engine_version varchar(20) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY scale_id (scale_id),
            KEY status (status)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}norm_sets (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instrument_version_id bigint(20) unsigned NOT NULL,
            label varchar(190) NOT NULL,
            applicable_criteria_json longtext DEFAULT NULL,
            version_label varchar(50) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY instrument_version_id (instrument_version_id),
            KEY status (status)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}norm_entries (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            norm_set_id bigint(20) unsigned NOT NULL,
            scale_id bigint(20) unsigned NOT NULL,
            raw_score_min decimal(10,4) NOT NULL,
            raw_score_max decimal(10,4) NOT NULL,
            standard_score_type varchar(30) NOT NULL,
            standard_score_value decimal(10,4) NOT NULL,
            interpretation_range_code varchar(50) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY norm_scale_range (norm_set_id,scale_id,raw_score_min,raw_score_max)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}report_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organization_id bigint(20) unsigned NOT NULL,
            instrument_id bigint(20) unsigned DEFAULT NULL,
            version_label varchar(50) NOT NULL,
            layout_definition_json longtext NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY organization_id (organization_id),
            KEY instrument_id (instrument_id),
            KEY status (status)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$p}audit_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organization_id bigint(20) unsigned DEFAULT NULL,
            actor_type varchar(30) NOT NULL,
            actor_id bigint(20) unsigned DEFAULT NULL,
            event_type varchar(100) NOT NULL,
            entity_type varchar(100) NOT NULL,
            entity_id bigint(20) unsigned DEFAULT NULL,
            metadata_json longtext DEFAULT NULL,
            ip_hash char(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY entity (entity_type,entity_id),
            KEY created_at (created_at)
        ) {$charsetCollate};");

        $this->applyForeignKeys($wpdb, $p);
        $this->seedDefaultOrganization($wpdb, $p);
    }

    private function applyForeignKeys(\wpdb $wpdb, string $p): void
    {
        $constraints = [
            "ALTER TABLE {$p}instruments ADD CONSTRAINT fk_tpm_instruments_org FOREIGN KEY (organization_id) REFERENCES {$p}organizations (id) ON DELETE SET NULL",
            "ALTER TABLE {$p}instrument_versions ADD CONSTRAINT fk_tpm_iv_instrument FOREIGN KEY (instrument_id) REFERENCES {$p}instruments (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}sections ADD CONSTRAINT fk_tpm_sections_iv FOREIGN KEY (instrument_version_id) REFERENCES {$p}instrument_versions (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}sections ADD CONSTRAINT fk_tpm_sections_parent FOREIGN KEY (parent_section_id) REFERENCES {$p}sections (id) ON DELETE SET NULL",
            "ALTER TABLE {$p}items ADD CONSTRAINT fk_tpm_items_iv FOREIGN KEY (instrument_version_id) REFERENCES {$p}instrument_versions (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}items ADD CONSTRAINT fk_tpm_items_section FOREIGN KEY (section_id) REFERENCES {$p}sections (id) ON DELETE SET NULL",
            "ALTER TABLE {$p}response_options ADD CONSTRAINT fk_tpm_ro_item FOREIGN KEY (item_id) REFERENCES {$p}items (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}scales ADD CONSTRAINT fk_tpm_scales_iv FOREIGN KEY (instrument_version_id) REFERENCES {$p}instrument_versions (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}scales ADD CONSTRAINT fk_tpm_scales_parent FOREIGN KEY (parent_scale_id) REFERENCES {$p}scales (id) ON DELETE SET NULL",
            "ALTER TABLE {$p}scoring_rules ADD CONSTRAINT fk_tpm_sr_scale FOREIGN KEY (scale_id) REFERENCES {$p}scales (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}norm_sets ADD CONSTRAINT fk_tpm_ns_iv FOREIGN KEY (instrument_version_id) REFERENCES {$p}instrument_versions (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}norm_entries ADD CONSTRAINT fk_tpm_ne_normset FOREIGN KEY (norm_set_id) REFERENCES {$p}norm_sets (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}norm_entries ADD CONSTRAINT fk_tpm_ne_scale FOREIGN KEY (scale_id) REFERENCES {$p}scales (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}report_templates ADD CONSTRAINT fk_tpm_rt_org FOREIGN KEY (organization_id) REFERENCES {$p}organizations (id) ON DELETE CASCADE",
            "ALTER TABLE {$p}report_templates ADD CONSTRAINT fk_tpm_rt_instrument FOREIGN KEY (instrument_id) REFERENCES {$p}instruments (id) ON DELETE SET NULL",
        ];

        foreach ($constraints as $sql) {
            // idempotent: dacă a fost deja aplicată la o rulare anterioară, MySQL
            // întoarce eroarea "Duplicate foreign key constraint name", ignorată intenționat.
            $wpdb->query($sql);
        }
    }

    private function seedDefaultOrganization(\wpdb $wpdb, string $p): void
    {
        $existing = $wpdb->get_var("SELECT id FROM {$p}organizations WHERE id = 1");
        if ($existing !== null) {
            return;
        }

        $wpdb->insert(
            $p . 'organizations',
            [
                'id' => 1,
                'name' => 'Organizație implicită',
                'slug' => 'default',
                'status' => 'active',
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }
}
