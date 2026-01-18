<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/helpers.php';
require_once PERACRM_INC . '/schema.php';
require_once PERACRM_INC . '/roles.php';
require_once PERACRM_INC . '/cpt.php';
require_once PERACRM_INC . '/favourites.php';
require_once PERACRM_INC . '/activity.php';
require_once PERACRM_INC . '/activity-capture.php';
require_once PERACRM_INC . '/health.php';
require_once PERACRM_INC . '/enquiry-ingest.php';

require_once PERACRM_INC . '/repositories/notes.php';
require_once PERACRM_INC . '/repositories/reminders.php';
require_once PERACRM_INC . '/repositories/activity.php';
require_once PERACRM_INC . '/repositories/client_property.php';

require_once PERACRM_INC . '/services/client_service.php';
require_once PERACRM_INC . '/services/activity_service.php';

if (is_admin()) {
    $admin_dir = __DIR__ . '/admin';
    $admin_files = [
        'admin.php',
        'metaboxes.php',
        'pages.php',
        'actions.php',
        'assets.php',
    ];

    foreach ($admin_files as $admin_file) {
        $admin_path = $admin_dir . '/' . $admin_file;
        if (file_exists($admin_path)) {
            require_once $admin_path;
        }
    }
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    peracrm_maybe_upgrade_schema();
});

add_action('init', function () {
    if (!is_user_logged_in()) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('edit_crm_clients')) {
        return;
    }

    peracrm_maybe_upgrade_schema();
}, 5);

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    peracrm_ensure_roles_and_caps();
});

add_action('init', 'peracrm_register_cpt_crm_client', 5);
