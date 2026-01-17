<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_ensure_roles_and_caps()
{
    $advisor_role = get_role('advisor');
    if (!$advisor_role) {
        $advisor_role = add_role('advisor', 'Advisor', ['read' => true]);
    }

    $caps_admin = [
        'edit_crm_client',
        'read_crm_client',
        'delete_crm_client',
        'edit_crm_clients',
        'edit_others_crm_clients',
        'publish_crm_clients',
        'read_private_crm_clients',
        'delete_crm_clients',
        'delete_private_crm_clients',
        'delete_published_crm_clients',
        'delete_others_crm_clients',
        'edit_private_crm_clients',
        'edit_published_crm_clients',
    ];

    $caps_advisor = [
        'read_crm_client',
        'edit_crm_client',
        'edit_crm_clients',
        'read_private_crm_clients',
    ];

    $admin_role = get_role('administrator');
    if ($admin_role) {
        foreach ($caps_admin as $cap) {
            $admin_role->add_cap($cap);
        }
    }

    if ($advisor_role) {
        foreach ($caps_advisor as $cap) {
            $advisor_role->add_cap($cap);
        }
    }
}
