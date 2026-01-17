<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_log_event($client_id, $event_type, array $payload = [])
{
    $payload['ts'] = peracrm_now_mysql();

    return peracrm_activity_insert($client_id, $event_type, $payload);
}
