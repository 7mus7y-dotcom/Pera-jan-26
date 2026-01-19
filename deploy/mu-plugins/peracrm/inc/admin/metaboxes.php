<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/admin/metaboxes/timeline.php';

function peracrm_admin_disable_client_metaboxes()
{
    return defined('PERACRM_DISABLE_CLIENT_METABOXES') && PERACRM_DISABLE_CLIENT_METABOXES;
}

function peracrm_admin_is_metabox_disabled($constant)
{
    return defined($constant) && constant($constant);
}

function peracrm_register_metaboxes($post_type, $post)
{
    if ('crm_client' !== $post_type) {
        return;
    }

    $post_id = $post && isset($post->ID) ? (int) $post->ID : 0;
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] register crm_client metaboxes start post_id=%d', $post_id));
    }

    if (peracrm_admin_disable_client_metaboxes()) {
        if ($should_log) {
            error_log(sprintf('[peracrm] register crm_client metaboxes disabled post_id=%d', $post_id));
        }
        return;
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_PROFILE_METABOX')) {
    add_meta_box(
        'peracrm_client_profile',
        'Client Profile',
        'peracrm_render_client_profile_metabox',
        'crm_client',
        'normal',
        'high'
    );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_NOTES_METABOX')) {
    add_meta_box(
        'peracrm_notes',
        'Advisor Notes',
        'peracrm_render_notes_metabox',
        'crm_client',
        'normal',
        'default'
    );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_REMINDERS_METABOX')) {
    add_meta_box(
        'peracrm_reminders',
        'Reminders',
        'peracrm_render_reminders_metabox',
        'crm_client',
        'normal',
        'default'
    );
    }

    if ($post && peracrm_admin_is_crm_client_edit_screen($post->ID)
        && current_user_can('edit_post', $post->ID)
        && current_user_can('manage_options')
        && !peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_TIMELINE_METABOX')) {
        add_meta_box(
            'peracrm_client_timeline',
            'Timeline',
            'peracrm_render_timeline_metabox',
            'crm_client',
            'normal',
            'default'
        );
    }

    if ($post
        && current_user_can('edit_post', $post->ID)
        && !peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_ACTIVITY_METABOX')) {
        add_meta_box(
            'peracrm_activity_timeline',
            'Activity Timeline',
            'peracrm_render_activity_timeline_metabox',
            'crm_client',
            'normal',
            'default'
        );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_PROPERTIES_METABOX')) {
        add_meta_box(
            'peracrm_properties',
            'Linked Properties',
            'peracrm_render_properties_metabox',
            'crm_client',
            'side',
            'default'
        );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_ACCOUNT_METABOX')) {
        add_meta_box(
            'peracrm_account_link',
            'Account',
            'peracrm_render_account_metabox',
            'crm_client',
            'side',
            'default'
        );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_HEALTH_METABOX')) {
        add_meta_box(
            'peracrm_client_health',
            'Client Health',
            'peracrm_render_client_health_metabox',
            'crm_client',
            'side',
            'default'
        );
    }

    if (!peracrm_admin_is_metabox_disabled('PERACRM_DISABLE_ASSIGNED_ADVISOR_METABOX')) {
        add_meta_box(
            'peracrm_assigned_advisor',
            'Assigned Advisor',
            'peracrm_render_assigned_advisor_metabox',
            'crm_client',
            'side',
            'default'
        );
    }

    if ($should_log) {
        error_log(sprintf('[peracrm] register crm_client metaboxes end post_id=%d', $post_id));
    }
}

function peracrm_render_client_profile_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox profile start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox profile end client=%d', $post_id));
        }
        return;
    }

    $profile = function_exists('peracrm_client_get_profile')
        ? peracrm_client_get_profile($post->ID)
        : [
            'status' => '',
            'client_type' => '',
            'preferred_contact' => '',
            'budget_min_usd' => '',
            'budget_max_usd' => '',
            'phone' => '',
            'email' => '',
        ];

    $status_options = [
        'enquiry' => 'Enquiry',
        'active' => 'Active',
        'dormant' => 'Dormant',
        'closed' => 'Closed',
    ];

    $type_options = [
        'citizenship' => 'Citizenship',
        'investor' => 'Investor',
        'lifestyle' => 'Lifestyle',
    ];

    $contact_options = [
        '' => 'No preference',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
    ];

    $phone = isset($profile['phone']) ? (string) $profile['phone'] : '';
    $email = isset($profile['email']) ? (string) $profile['email'] : '';

    $wa_link = '';
    $phone_trimmed = ltrim($phone);
    if ($phone_trimmed !== '' && strpos($phone_trimmed, '+') === 0) {
        $wa_digits = preg_replace('/\D+/', '', $phone_trimmed);
        if ($wa_digits !== '') {
            $wa_link = 'https://wa.me/' . $wa_digits;
        }
    }

    echo '<div class="peracrm-metabox">';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_save_client_profile');
    echo '<input type="hidden" name="action" value="peracrm_save_client_profile" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';

    echo '<p><label for="peracrm-status">Status</label></p>';
    echo '<p><select name="peracrm_status" id="peracrm-status" class="widefat">';
    foreach ($status_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($profile['status'] ?? '', $value, false),
            esc_html($label)
        );
    }
    echo '</select></p>';

    echo '<p><label for="peracrm-client-type">Client type</label></p>';
    echo '<p><select name="peracrm_client_type" id="peracrm-client-type" class="widefat">';
    foreach ($type_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($profile['client_type'] ?? '', $value, false),
            esc_html($label)
        );
    }
    echo '</select></p>';

    echo '<p><label for="peracrm-preferred-contact">Preferred contact</label></p>';
    echo '<p><select name="peracrm_preferred_contact" id="peracrm-preferred-contact" class="widefat">';
    foreach ($contact_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($profile['preferred_contact'] ?? '', $value, false),
            esc_html($label)
        );
    }
    echo '</select></p>';

    $budget_min = isset($profile['budget_min_usd']) ? $profile['budget_min_usd'] : '';
    $budget_max = isset($profile['budget_max_usd']) ? $profile['budget_max_usd'] : '';

    echo '<p><label for="peracrm-budget-min">Budget min (USD)</label></p>';
    echo '<p><input type="number" min="0" step="1" name="peracrm_budget_min_usd" id="peracrm-budget-min" class="widefat" value="' . esc_attr($budget_min) . '" /></p>';

    echo '<p><label for="peracrm-budget-max">Budget max (USD)</label></p>';
    echo '<p><input type="number" min="0" step="1" name="peracrm_budget_max_usd" id="peracrm-budget-max" class="widefat" value="' . esc_attr($budget_max) . '" /></p>';

    echo '<p><label for="peracrm-phone">Phone</label></p>';
    echo '<p><input type="text" name="peracrm_phone" id="peracrm-phone" class="widefat" value="' . esc_attr($phone) . '" /></p>';

    echo '<p><label for="peracrm-email">Email</label></p>';
    echo '<p><input type="email" name="peracrm_email" id="peracrm-email" class="widefat" value="' . esc_attr($email) . '" /></p>';

    echo '<p><button type="submit" class="button button-primary">Save Profile</button></p>';
    echo '</form>';

    echo '<hr />';
    echo '<div class="peracrm-quick-actions">';
    echo '<p><strong>Quick Actions</strong></p>';
    if ($phone !== '') {
        $tel_link = 'tel:' . rawurlencode($phone);
        echo '<p><a class="button" href="' . esc_url($tel_link) . '">' . esc_html('Call') . '</a></p>';
    }
    if ($wa_link !== '') {
        echo '<p><a class="button" href="' . esc_url($wa_link) . '" target="_blank" rel="noopener">' . esc_html('WhatsApp') . '</a></p>';
    }
    if ($email !== '') {
        $mailto_link = 'mailto:' . rawurlencode($email);
        echo '<p><a class="button" href="' . esc_url($mailto_link) . '">' . esc_html('Email') . '</a></p>';
    }
    if ($phone === '' && $wa_link === '' && $email === '') {
        echo '<p class="peracrm-empty">' . esc_html('Add a phone or email to enable quick actions.') . '</p>';
    }
    echo '</div>';

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox profile end client=%d', $post_id));
    }
}

function peracrm_render_assigned_advisor_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox assigned_advisor start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox assigned_advisor end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_client_get_assigned_advisor_id')) {
        echo '<p class="peracrm-empty">Unavailable (missing helper).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox assigned_advisor end client=%d', $post_id));
        }
        return;
    }

    $advisor_id = (int) peracrm_client_get_assigned_advisor_id($post->ID);

    $advisor_name = 'Unassigned';
    if ($advisor_id > 0) {
        $advisor_user = get_userdata($advisor_id);
        if ($advisor_user) {
            $advisor_name = $advisor_user->display_name;
        }
    }

    $can_reassign = current_user_can('edit_post', $post->ID)
        && (current_user_can('manage_options') || current_user_can('peracrm_manage_assignments'));

    echo '<div class="peracrm-metabox">';
    echo '<p><strong>Current advisor:</strong> ' . esc_html($advisor_name) . '</p>';

    if (!$can_reassign) {
        echo '<p>You do not have permission to reassign advisors.</p>';
        echo '</div>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox assigned_advisor end client=%d', $post_id));
        }
        return;
    }

    $advisor_options = get_users([
        'fields' => ['ID', 'display_name'],
        'capability' => 'edit_crm_clients',
        'orderby' => 'display_name',
        'order' => 'ASC',
    ]);

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_reassign_client_advisor');
    echo '<input type="hidden" name="action" value="peracrm_reassign_client_advisor" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm-assigned-advisor">Advisor</label></p>';
    echo '<p><select name="peracrm_assigned_advisor" id="peracrm-assigned-advisor" class="widefat">';
    printf(
        '<option value="0"%s>%s</option>',
        selected($advisor_id, 0, false),
        esc_html('Unassigned')
    );
    foreach ($advisor_options as $advisor) {
        printf(
            '<option value="%1$d"%2$s>%3$s</option>',
            (int) $advisor->ID,
            selected($advisor_id, (int) $advisor->ID, false),
            esc_html($advisor->display_name)
        );
    }
    echo '</select></p>';
    echo '<p><button type="submit" class="button">Reassign</button></p>';
    echo '</form>';
    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox assigned_advisor end client=%d', $post_id));
    }
}

function peracrm_render_notes_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox notes start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox notes end client=%d', $post_id));
        }
        return;
    }

    $can_manage = function_exists('peracrm_admin_user_can_manage') && peracrm_admin_user_can_manage();
    if (!$can_manage) {
        echo '<p>You do not have permission to view CRM notes.</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox notes end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_notes_table_exists') || !peracrm_notes_table_exists()) {
        echo '<p class="peracrm-empty">Unavailable (missing table).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox notes end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_notes_list') || !function_exists('peracrm_notes_count')) {
        echo '<p class="peracrm-empty">Unavailable (missing helper).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox notes end client=%d', $post_id));
        }
        return;
    }

    $limit = 20;
    $offset = isset($_GET['notes_offset']) ? absint($_GET['notes_offset']) : 0;
    $notes = peracrm_notes_list($post->ID, $limit, $offset);
    $total = peracrm_notes_count($post->ID);

    $base_url = add_query_arg(
        [
            'post' => $post->ID,
            'action' => 'edit',
        ],
        admin_url('post.php')
    );

    echo '<div class="peracrm-metabox">';

    if (empty($notes)) {
        echo '<p class="peracrm-empty">No notes yet.</p>';
    } else {
        echo '<ul class="peracrm-list">';
        foreach ($notes as $note) {
            $author = get_userdata((int) $note['advisor_user_id']);
            $author_name = $author ? $author->display_name : 'Advisor';
            printf(
                '<li><div class="peracrm-list__meta">%1$s · %2$s</div><div class="peracrm-list__body">%3$s</div></li>',
                esc_html(mysql2date('Y-m-d H:i', $note['created_at'])),
                esc_html($author_name),
                esc_html($note['note_body'])
            );
        }
        echo '</ul>';
    }

    $pagination = [];
    if ($offset > 0) {
        $new_offset = max(0, $offset - $limit);
        $pagination[] = '<a href="' . esc_url(add_query_arg('notes_offset', $new_offset, $base_url)) . '">Newer</a>';
    }
    if ($total > ($offset + $limit)) {
        $older_offset = $offset + $limit;
        $pagination[] = '<a href="' . esc_url(add_query_arg('notes_offset', $older_offset, $base_url)) . '">Older</a>';
    }
    if (!empty($pagination)) {
        echo '<p class="peracrm-pagination">' . implode(' | ', $pagination) . '</p>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_add_note');
    echo '<input type="hidden" name="action" value="peracrm_add_note" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_note_body">Add note</label></p>';
    echo '<p><textarea name="peracrm_note_body" id="peracrm_note_body" rows="4" class="widefat"></textarea></p>';
    echo '<p><button type="submit" class="button button-primary">Add Note</button></p>';
    echo '</form>';

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox notes end client=%d', $post_id));
    }
}

function peracrm_render_reminders_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox reminders start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox reminders end client=%d', $post_id));
        }
        return;
    }

    $can_manage = function_exists('peracrm_admin_user_can_manage') && peracrm_admin_user_can_manage();
    if (!$can_manage) {
        echo '<p>You do not have permission to view CRM reminders.</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox reminders end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_reminders_table_exists') || !peracrm_reminders_table_exists()) {
        echo '<p class="peracrm-empty">Unavailable (missing table).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox reminders end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_reminders_list_for_client') || !function_exists('peracrm_reminders_count_for_client')) {
        echo '<p class="peracrm-empty">Unavailable (missing helper).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox reminders end client=%d', $post_id));
        }
        return;
    }

    $limit = 20;
    $offset = isset($_GET['reminders_offset']) ? absint($_GET['reminders_offset']) : 0;
    $reminders = peracrm_reminders_list_for_client($post->ID, $limit, $offset, null);
    $total = peracrm_reminders_count_for_client($post->ID, null);

    $advisor_ids = [];
    foreach ($reminders as $reminder) {
        if (!empty($reminder['advisor_user_id'])) {
            $advisor_ids[] = (int) $reminder['advisor_user_id'];
        }
    }
    $advisor_ids = array_values(array_unique($advisor_ids));
    $advisor_map = [];
    if (!empty($advisor_ids)) {
        $advisors = get_users([
            'include' => $advisor_ids,
            'fields' => ['ID', 'display_name'],
        ]);
        foreach ($advisors as $advisor) {
            $advisor_map[(int) $advisor->ID] = $advisor->display_name;
        }
    }

    $base_url = add_query_arg(
        [
            'post' => $post->ID,
            'action' => 'edit',
        ],
        admin_url('post.php')
    );

    echo '<div class="peracrm-metabox">';

    if (empty($reminders)) {
        echo '<p class="peracrm-empty">No reminders yet.</p>';
    } else {
        echo '<ul class="peracrm-list">';
        foreach ($reminders as $reminder) {
            $due_at = mysql2date('Y-m-d H:i', $reminder['due_at']);
            $status = isset($reminder['status']) ? $reminder['status'] : 'pending';
            $status_label = ucfirst($status);
            $advisor_name = isset($advisor_map[(int) $reminder['advisor_user_id']]) ? $advisor_map[(int) $reminder['advisor_user_id']] : 'Advisor';
            $note_excerpt = $reminder['note'] ? wp_trim_words($reminder['note'], 20, '…') : '';

            echo '<li>';
            echo '<div class="peracrm-list__meta">Due ' . esc_html($due_at) . ' · <span class="peracrm-status peracrm-status--' . esc_attr($status) . '">' . esc_html($status_label) . '</span> · ' . esc_html($advisor_name) . '</div>';
            if ($note_excerpt !== '') {
                echo '<div class="peracrm-list__body">' . esc_html($note_excerpt) . '</div>';
            }

            if ($status === 'pending') {
                echo '<div class="peracrm-list__actions">';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
                wp_nonce_field('peracrm_update_reminder_status');
                echo '<input type="hidden" name="action" value="peracrm_update_reminder_status" />';
                echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
                echo '<input type="hidden" name="peracrm_status" value="done" />';
                echo '<button type="submit" class="button">Mark done</button>';
                echo '</form>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
                wp_nonce_field('peracrm_update_reminder_status');
                echo '<input type="hidden" name="action" value="peracrm_update_reminder_status" />';
                echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
                echo '<input type="hidden" name="peracrm_status" value="dismissed" />';
                echo '<button type="submit" class="button">Dismiss</button>';
                echo '</form>';
                echo '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    }

    $pagination = [];
    if ($offset > 0) {
        $new_offset = max(0, $offset - $limit);
        $pagination[] = '<a href="' . esc_url(add_query_arg('reminders_offset', $new_offset, $base_url)) . '">Newer</a>';
    }
    if ($total > ($offset + $limit)) {
        $older_offset = $offset + $limit;
        $pagination[] = '<a href="' . esc_url(add_query_arg('reminders_offset', $older_offset, $base_url)) . '">Older</a>';
    }
    if (!empty($pagination)) {
        echo '<p class="peracrm-pagination">' . implode(' | ', $pagination) . '</p>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_add_reminder');
    echo '<input type="hidden" name="action" value="peracrm_add_reminder" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_due_at">Due date</label></p>';
    echo '<p><input type="datetime-local" name="peracrm_due_at" id="peracrm_due_at" class="widefat" /></p>';
    echo '<p><label for="peracrm_reminder_note">Note</label></p>';
    echo '<p><textarea name="peracrm_reminder_note" id="peracrm_reminder_note" rows="3" class="widefat"></textarea></p>';
    echo '<p><button type="submit" class="button button-primary">Add Reminder</button></p>';
    echo '</form>';

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox reminders end client=%d', $post_id));
    }
}

function peracrm_render_client_health_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox health start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox health end client=%d', $post_id));
        }
        return;
    }

    $can_manage = function_exists('peracrm_admin_user_can_manage') && peracrm_admin_user_can_manage();
    if (!$can_manage) {
        echo '<p>You do not have permission to view client health.</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox health end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_client_health_get')) {
        echo '<p class="peracrm-empty">Health data unavailable.</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox health end client=%d', $post_id));
        }
        return;
    }

    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();
    if (!$has_activity_table && !$has_reminders_table) {
        echo '<p class="peracrm-empty">Unavailable (missing table).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox health end client=%d', $post_id));
        }
        return;
    }

    $health = peracrm_client_health_get($post->ID);
    $badge = function_exists('peracrm_client_health_badge_html')
        ? peracrm_client_health_badge_html($health)
        : esc_html(isset($health['label']) ? $health['label'] : 'None');

    $last_activity_ts = isset($health['last_activity_ts']) ? (int) $health['last_activity_ts'] : 0;
    $last_activity = $last_activity_ts
        ? human_time_diff($last_activity_ts, current_time('timestamp')) . ' ago'
        : 'No activity recorded.';
    $last_activity_title = $last_activity_ts
        ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $last_activity_ts)
        : '';

    $open_reminders = isset($health['open_reminders']) ? (int) $health['open_reminders'] : 0;
    $overdue_reminders = isset($health['overdue_reminders']) ? (int) $health['overdue_reminders'] : 0;
    $explain = isset($health['explain']) ? $health['explain'] : '';

    echo '<div class="peracrm-metabox">';
    echo '<p>' . $badge . '</p>';
    if ($explain !== '') {
        echo '<p>' . esc_html($explain) . '</p>';
    }
    echo '<ul class="peracrm-list">';
    echo '<li><strong>Last activity:</strong> ';
    if ($last_activity_title) {
        echo '<span title="' . esc_attr($last_activity_title) . '">' . esc_html($last_activity) . '</span>';
    } else {
        echo esc_html($last_activity);
    }
    echo '</li>';
    echo '<li><strong>Open reminders:</strong> ' . esc_html((string) $open_reminders) . '</li>';
    echo '<li><strong>Overdue reminders:</strong> ' . esc_html((string) $overdue_reminders) . '</li>';
    echo '</ul>';
    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox health end client=%d', $post_id));
    }
}

function peracrm_render_activity_timeline_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox activity start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox activity end client=%d', $post_id));
        }
        return;
    }

    if (!current_user_can('edit_post', $post->ID)) {
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox activity end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        echo '<p class="peracrm-empty">Unavailable (missing table).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox activity end client=%d', $post_id));
        }
        return;
    }

    if (!function_exists('peracrm_activity_list') || !function_exists('peracrm_activity_count')) {
        echo '<p class="peracrm-empty">Unavailable (missing helper).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox activity end client=%d', $post_id));
        }
        return;
    }

    $event_labels = [
        'view_property' => 'Viewed property',
        'login' => 'Logged in',
        'account_visit' => 'Visited account area',
        'enquiry' => 'Submitted enquiry',
    ];

    $allowed_filters = array_keys($event_labels);
    $activity_type = isset($_GET['activity_type']) ? sanitize_key(wp_unslash($_GET['activity_type'])) : '';
    if (!in_array($activity_type, $allowed_filters, true)) {
        $activity_type = '';
    }

    $limit = 50;
    $offset = isset($_GET['activity_offset']) ? absint($_GET['activity_offset']) : 0;

    $activity = peracrm_activity_list($post->ID, $limit, $offset, $activity_type ?: null);
    $total = peracrm_activity_count($post->ID, $activity_type ?: null);

    $base_args = [
        'post' => $post->ID,
        'action' => 'edit',
    ];
    if ($activity_type) {
        $base_args['activity_type'] = $activity_type;
    }
    $base_url = add_query_arg($base_args, admin_url('post.php'));

    echo '<div class="peracrm-metabox">';

    echo '<form method="get" action="' . esc_url(admin_url('post.php')) . '" class="peracrm-inline-form">';
    echo '<input type="hidden" name="post" value="' . esc_attr($post->ID) . '" />';
    echo '<input type="hidden" name="action" value="edit" />';
    echo '<label for="peracrm_activity_type" class="screen-reader-text">Filter activity</label>';
    echo '<select name="activity_type" id="peracrm_activity_type">';
    echo '<option value="">All activity</option>';
    foreach ($event_labels as $type => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($type),
            selected($activity_type, $type, false),
            esc_html($label)
        );
    }
    echo '</select> ';
    echo '<button type="submit" class="button">Filter</button>';
    echo '</form>';

    if (empty($activity)) {
        echo '<p class="peracrm-empty">No activity recorded yet.</p>';
    } else {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Activity</th><th>Context</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        foreach ($activity as $event) {
            $payload = peracrm_json_decode($event['event_payload']);
            $property_id = isset($payload['property_id']) ? absint($payload['property_id']) : 0;
            $context = '&mdash;';
            if ($property_id > 0) {
                $title = get_the_title($property_id);
                if (!$title) {
                    $title = 'Property #' . $property_id;
                }
                $edit_link = get_edit_post_link($property_id, '');
                if ($edit_link) {
                    $context = '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a>';
                } else {
                    $context = esc_html($title);
                }
            }
            $event_type = $event['event_type'];
            $label = isset($event_labels[$event_type]) ? $event_labels[$event_type] : $event_type;
            printf(
                '<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
                esc_html($label),
                $context,
                esc_html(mysql2date('Y-m-d H:i', $event['created_at']))
            );
        }
        echo '</tbody></table>';

        $pagination = [];
        if ($offset > 0) {
            $new_offset = max(0, $offset - $limit);
            $pagination[] = '<a href="' . esc_url(add_query_arg('activity_offset', $new_offset, $base_url)) . '">Newer</a>';
        }
        if ($total > ($offset + $limit)) {
            $older_offset = $offset + $limit;
            $pagination[] = '<a href="' . esc_url(add_query_arg('activity_offset', $older_offset, $base_url)) . '">Older</a>';
        }
        if (!empty($pagination)) {
            echo '<p class="peracrm-pagination">' . implode(' | ', $pagination) . '</p>';
        }
    }

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox activity end client=%d', $post_id));
    }
}

function peracrm_render_properties_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox properties start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox properties end client=%d', $post_id));
        }
        return;
    }

    $relation_types = [
        'favourite' => 'Favourites',
        'enquiry' => 'Enquiries',
        'viewed' => 'Viewed',
        'portfolio' => 'Portfolio',
    ];

    echo '<div class="peracrm-metabox">';

    foreach ($relation_types as $relation => $label) {
        $count = peracrm_admin_get_client_property_count($post->ID, $relation);
        $items = peracrm_client_property_list($post->ID, $relation, 10);

        echo '<div class="peracrm-property-group">';
        echo '<strong>' . esc_html($label) . '</strong> (' . esc_html($count) . ')';

        if (empty($items)) {
            echo '<p class="peracrm-empty">No properties.</p>';
        } else {
            echo '<ul class="peracrm-list">';
            foreach ($items as $item) {
                $property_id = (int) $item['property_id'];
                $edit_link = get_edit_post_link($property_id, '');
                $title = get_the_title($property_id);
                if (!$title) {
                    $title = 'Property #' . $property_id;
                }
                if ($edit_link) {
                    echo '<li><a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a></li>';
                } else {
                    echo '<li>' . esc_html($title) . '</li>';
                }
            }
            echo '</ul>';
        }

        echo '</div>';
    }

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox properties end client=%d', $post_id));
    }
}

function peracrm_render_account_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox account start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox account end client=%d', $post_id));
        }
        return;
    }

    $linked_user_id = peracrm_admin_find_linked_user_id($post->ID);
    $linked_user = $linked_user_id ? get_userdata($linked_user_id) : null;

    echo '<div class="peracrm-metabox">';

    if ($linked_user) {
        $edit_link = get_edit_user_link($linked_user->ID);
        echo '<p><strong>Linked user</strong></p>';
        if ($edit_link) {
            echo '<p><a href="' . esc_url($edit_link) . '">User #' . esc_html($linked_user->ID) . '</a></p>';
        } else {
            echo '<p>User #' . esc_html($linked_user->ID) . '</p>';
        }
        echo '<p>Email: ' . esc_html($linked_user->user_email) . '</p>';
        echo '<p>Username: ' . esc_html($linked_user->user_login) . '</p>';
    } else {
        echo '<p><strong>Not linked</strong></p>';
        echo '<p>No user account is linked to this CRM client.</p>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_link_user');
    echo '<input type="hidden" name="action" value="peracrm_link_user" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_user_search">Search user (email or username)</label></p>';
    echo '<p><input type="text" name="peracrm_user_search" id="peracrm_user_search" class="widefat" /></p>';
    echo '<p><button type="submit" class="button button-primary">Link user</button></p>';
    echo '</form>';

    if ($linked_user) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
        wp_nonce_field('peracrm_unlink_user');
        echo '<input type="hidden" name="action" value="peracrm_unlink_user" />';
        echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
        echo '<p><button type="submit" class="button">Unlink</button></p>';
        echo '</form>';
    }

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox account end client=%d', $post_id));
    }
}
