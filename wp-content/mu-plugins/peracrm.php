<?php
/**
 * Plugin Name: PeraCRM MU Loader
 */

if (!defined('ABSPATH')) {
    exit;
}

$peracrm_entrypoint = __DIR__ . '/peracrm/peracrm.php';
if (file_exists($peracrm_entrypoint)) {
    require_once $peracrm_entrypoint;
}
