<?php
function bp_crear_tablas_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tablas = [
        'search_logs' => "CREATE TABLE {$wpdb->prefix}search_logs (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(255) NOT NULL,
            search_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;",
        
        'click_logs' => "CREATE TABLE {$wpdb->prefix}click_logs (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            click_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;",
        
        'visit_logs' => "CREATE TABLE {$wpdb->prefix}visit_logs (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            visit_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            url VARCHAR(512) NOT NULL,
            referer VARCHAR(512) DEFAULT NULL,
            user_ip VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;"
    ];

    foreach ($tablas as $tabla => $sql) {
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$tabla'") != "{$wpdb->prefix}$tabla") {
            dbDelta($sql);
        }
    }
}

function bp_eliminar_tablas_plugin() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}search_logs, {$wpdb->prefix}click_logs, {$wpdb->prefix}visit_logs");
}
