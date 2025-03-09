<?php
function vp_registrar_busqueda($search_term) {
    global $wpdb;
    if (!empty($search_term)) {
        $wpdb->insert(
            $wpdb->prefix . 'search_logs',
            ['search_term' => sanitize_text_field($search_term), 'search_date' => current_time('mysql'), 'user_ip' => $_SERVER['REMOTE_ADDR']],
            ['%s', '%s', '%s']
        );
    }
}
