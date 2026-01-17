<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/helpers.php';
require_once PERACRM_INC . '/schema.php';
require_once PERACRM_INC . '/roles.php';
require_once PERACRM_INC . '/cpt.php';

require_once PERACRM_INC . '/repositories/notes.php';
require_once PERACRM_INC . '/repositories/reminders.php';
require_once PERACRM_INC . '/repositories/activity.php';
require_once PERACRM_INC . '/repositories/client_property.php';

require_once PERACRM_INC . '/services/client_service.php';
require_once PERACRM_INC . '/services/activity_service.php';

if (is_admin()) {
    require_once PERACRM_INC . '/admin/admin.php';
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    peracrm_maybe_upgrade_schema();
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    peracrm_ensure_roles_and_caps();
});

add_action('init', 'peracrm_register_cpt_crm_client', 5);
