<?php

declare(strict_types=1);

namespace TpmPsych\Authorization;

/**
 * Roluri și capabilities custom, prefixate tpm_ pentru a nu se amesteca cu
 * managementul de conținut al site-ului Divi. Vezi
 * docs/psychology-platform/04-roluri-si-permisiuni.md pentru matricea completă.
 *
 * Acest checkpoint implementează doar capabilities necesare modulului de
 * administrare a instrumentelor (tpm_manage_instruments); restul rolurilor
 * (psychologist, supervisor, operator) și capabilities lor sunt definite aici
 * ca schelet, dar vor fi exercitate abia la modulele de clienți/administrări.
 */
final class Capabilities
{
    public const MANAGE_SYSTEM = 'tpm_manage_system';
    public const MANAGE_INSTRUMENTS = 'tpm_manage_instruments';
    public const MANAGE_USERS = 'tpm_manage_users';
    public const MANAGE_ORGANIZATIONS = 'tpm_manage_organizations';
    public const MANAGE_TEMPLATES = 'tpm_manage_templates';
    public const VIEW_AUDIT_LOG = 'tpm_view_audit_log';
    public const MANAGE_OWN_CLIENTS = 'tpm_manage_own_clients';
    public const CREATE_ADMINISTRATIONS = 'tpm_create_administrations';
    public const VIEW_ASSIGNED_REPORTS = 'tpm_view_assigned_reports';
    public const APPROVE_REPORTS = 'tpm_approve_reports';
    public const VIEW_ASSIGNED_RESPONSES = 'tpm_view_assigned_responses';
    public const VIEW_SUPERVISED_REPORTS = 'tpm_view_supervised_reports';
    public const MANAGE_INVITATIONS_ADMIN_ONLY = 'tpm_manage_invitations_admin_only';
    public const VIEW_CLIENT_CONTACT_INFO = 'tpm_view_client_contact_info';

    private const ROLE_TECH_ADMIN = 'tpm_tech_admin';
    private const ROLE_PLATFORM_ADMIN = 'tpm_platform_admin';
    private const ROLE_PSYCHOLOGIST = 'tpm_psychologist';
    private const ROLE_SUPERVISOR = 'tpm_supervisor';
    private const ROLE_OPERATOR = 'tpm_operator';

    public static function register(): void
    {
        add_role(self::ROLE_TECH_ADMIN, 'Administrator tehnic (TPM)', [
            'read' => true,
            self::MANAGE_SYSTEM => true,
        ]);

        add_role(self::ROLE_PLATFORM_ADMIN, 'Administrator platformă (TPM)', [
            'read' => true,
            self::MANAGE_INSTRUMENTS => true,
            self::MANAGE_USERS => true,
            self::MANAGE_ORGANIZATIONS => true,
            self::MANAGE_TEMPLATES => true,
            self::VIEW_AUDIT_LOG => true,
        ]);

        add_role(self::ROLE_PSYCHOLOGIST, 'Psiholog (TPM)', [
            'read' => true,
            self::MANAGE_OWN_CLIENTS => true,
            self::CREATE_ADMINISTRATIONS => true,
            self::VIEW_ASSIGNED_REPORTS => true,
            self::APPROVE_REPORTS => true,
            self::VIEW_ASSIGNED_RESPONSES => true,
        ]);

        add_role(self::ROLE_SUPERVISOR, 'Supervizor (TPM)', [
            'read' => true,
            self::VIEW_SUPERVISED_REPORTS => true,
        ]);

        add_role(self::ROLE_OPERATOR, 'Operator administrativ (TPM)', [
            'read' => true,
            self::MANAGE_INVITATIONS_ADMIN_ONLY => true,
            self::VIEW_CLIENT_CONTACT_INFO => true,
        ]);

        // WP administrator existent al site-ului primește acces de platform_admin
        // implicit, ca să poți administra instrumentele din start, fără să creezi
        // manual un cont "tpm_platform_admin" separat. Poate fi restrâns ulterior.
        $administrator = get_role('administrator');
        if ($administrator !== null) {
            foreach ([self::MANAGE_INSTRUMENTS, self::MANAGE_USERS, self::MANAGE_ORGANIZATIONS, self::MANAGE_TEMPLATES, self::VIEW_AUDIT_LOG] as $cap) {
                $administrator->add_cap($cap);
            }
        }
    }
}
