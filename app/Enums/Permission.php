<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Permissions élémentaires du back-office.
 *
 * Chaque permission correspond à une capacité (« ability ») enregistrée dans
 * le portail d'autorisation ; les Policies s'appuient dessus.
 */
enum Permission: string
{
    use HasLabel;

    case ViewDashboard = 'view_dashboard';
    case ManageAppointments = 'manage_appointments';
    case ManageWorkshops = 'manage_workshops';
    case ManageRegistrations = 'manage_registrations';
    case ManageContent = 'manage_content';
    case ManageDirectory = 'manage_directory';
    case ManageContactRequests = 'manage_contact_requests';
    case ManagePartnershipRequests = 'manage_partnership_requests';
    case ManageUsers = 'manage_users';
    case ManageSettings = 'manage_settings';
    case ManageGdprRequests = 'manage_gdpr_requests';
    case ViewAuditLog = 'view_audit_log';
    case ExportData = 'export_data';
}
