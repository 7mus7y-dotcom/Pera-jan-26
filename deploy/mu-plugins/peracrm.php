<?php
/**
 * Plugin Name: PeraCRM MU Loader
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PERACRM_MAIN_FILE')) {
    define('PERACRM_MAIN_FILE', __FILE__);
}

$peracrm_entrypoint = __DIR__ . '/peracrm/peracrm.php';
if (file_exists($peracrm_entrypoint)) {
    require_once $peracrm_entrypoint;
}
