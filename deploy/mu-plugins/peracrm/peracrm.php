<?php
/**
 * Plugin Name: PeraCRM
 * Description: WordPress-native CRM framework.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PERACRM_VERSION', '0.1.0');
define('PERACRM_SCHEMA_VERSION', 1);

define('PERACRM_PATH', __DIR__);
define('PERACRM_INC', __DIR__ . '/inc');

require_once PERACRM_INC . '/bootstrap.php';

/**
 * Integration snippet for enquiry handlers:
 *
 * $client_id = peracrm_find_or_create_client_by_email($_POST['email'], [
 *     'first_name' => $_POST['first_name'] ?? '',
 *     'last_name' => $_POST['last_name'] ?? '',
 *     'phone' => $_POST['phone'] ?? '',
 *     'source' => 'form',
 *     'status' => 'enquiry',
 * ]);
 * peracrm_log_event($client_id, 'enquiry', [
 *     'form' => 'property_enquiry',
 *     'property_id' => (int) $property_id,
 * ]);
 * peracrm_client_property_link($client_id, $property_id, 'enquiry');
 */
