<?php
/**
 * Plugin Name: PeraCRM MU Loader
 */

if (!defined('ABSPATH')) {
    exit;
}

$peracrm_entrypoint = WP_CONTENT_DIR . '/mu-plugins/peracrm/peracrm.php';
if (file_exists($peracrm_entrypoint)) {
    require_once $peracrm_entrypoint;
}
