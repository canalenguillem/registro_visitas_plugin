<?php

/**
 * Plugin Name: Buscador Tracker
 * Plugin URI: https://tusitio.com
 * Description: Registra y muestra las búsquedas realizadas, los clics y las visitas en el sitio.
 * Version: 1.3
 * Author: Tu Nombre
 * Author URI: https://tusitio.com
 */

if (!defined('ABSPATH')) {
    exit; // Evita acceso directo
}

// **1️⃣ Crear las tablas al activar el plugin**
function bt_crear_tablas_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 📌 Tabla para registrar búsquedas
    $table_search = $wpdb->prefix . 'search_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_search'") != $table_search) {
        $sql_search = "CREATE TABLE $table_search (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(255) NOT NULL,
            search_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_search);
    }

    // 📌 Tabla para registrar clics
    $table_clicks = $wpdb->prefix . 'click_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_clicks'") != $table_clicks) {
        $sql_clicks = "CREATE TABLE $table_clicks (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            click_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_clicks);
    }

    // 📌 Tabla para registrar visitas
    $table_visits = $wpdb->prefix . 'visit_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_visits'") != $table_visits) {
        $sql_visits = "CREATE TABLE $table_visits (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            visit_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            url VARCHAR(512) NOT NULL,
            referer VARCHAR(512) DEFAULT NULL,
            user_ip VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,  -- 🆕 Nueva columna para el User-Agent
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_visits);
    }
}
register_activation_hook(__FILE__, 'bt_crear_tablas_plugin');

function bt_registrar_visita() {
    // No registrar visitas de administradores
    if (current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'visit_logs';

    // Obtener la URL visitada
    $url = isset($_SERVER['REQUEST_URI']) ? home_url($_SERVER['REQUEST_URI']) : 'Desconocida';

    // Obtener el referer (de qué página viene el usuario)
    $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : 'Directo';

    // Obtener la IP del usuario
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Obtener el User-Agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Desconocido';

    // Insertar en la base de datos
    $wpdb->insert(
        $table_name,
        array(
            'visit_date' => current_time('mysql'),
            'url'        => $url,
            'referer'    => $referer,
            'user_ip'    => $user_ip,
            'user_agent' => $user_agent
        ),
        array('%s', '%s', '%s', '%s', '%s')
    );
}
add_action('template_redirect', 'bt_registrar_visita');



// **2️⃣ Función para eliminar registros antiguos**
function bt_eliminar_registros_antiguos() {
    if (!isset($_POST['tabla']) || !in_array($_POST['tabla'], ['search_logs', 'click_logs', 'visit_logs'])) {
        wp_die('Tabla no válida');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . sanitize_text_field($_POST['tabla']);
    
    $wpdb->query("DELETE FROM $table_name WHERE DATE(visit_date) < DATE_SUB(CURDATE(), INTERVAL 10 DAY)");

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}
add_action('admin_post_bt_eliminar_registros', 'bt_eliminar_registros_antiguos');


// **3️⃣ Función para mostrar historial de visitas con pestañas**
// **3️⃣ Mostrar historial de visitas con pestañas**
function bt_mostrar_visitas() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visit_logs';

    // Obtener el filtro activo
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'todas';

    // Expresión regular para identificar bots en el User-Agent
    $bot_patterns = [
        'bot', 'crawler', 'spider', 'slurp', 'archiver', 'fetcher',
        'pingdom', 'facebookexternalhit', 'linkedinbot', 'headless', 'mediapartners',
        'google', 'bing', 'yahoo', 'yandex', 'duckduckbot', 'baidu', 'seznam'
    ];
    $bot_regex = implode('|', $bot_patterns);

    // Definir las consultas según la pestaña seleccionada
    switch ($tab) {
        case 'dia':
            $query = "SELECT DATE(visit_date) as fecha, COUNT(*) as total FROM $table_name GROUP BY fecha ORDER BY fecha DESC";
            $columns = ['Fecha', 'Total Visitas'];
            break;
        case 'url':
            $query = "SELECT url, COUNT(*) as total FROM $table_name GROUP BY url ORDER BY total DESC";
            $columns = ['URL', 'Total Visitas'];
            break;
        case 'referer':
            $query = "SELECT referer, COUNT(*) as total FROM $table_name GROUP BY referer ORDER BY total DESC";
            $columns = ['Referer', 'Total Visitas'];
            break;
        case 'ip':
            $query = "SELECT user_ip, COUNT(*) as total FROM $table_name GROUP BY user_ip ORDER BY total DESC";
            $columns = ['IP', 'Total Visitas'];
            break;
        case 'bots':
            $query = "SELECT user_agent, COUNT(*) as total 
                      FROM $table_name 
                      WHERE user_agent REGEXP '$bot_regex' 
                      GROUP BY user_agent 
                      ORDER BY total DESC";
            $columns = ['User-Agent (Bot)', 'Total Visitas'];
            break;
        case 'humanos':
            $query = "SELECT id, visit_date, url, referer, user_ip, user_agent 
                      FROM $table_name 
                      WHERE user_agent NOT REGEXP '$bot_regex' 
                      ORDER BY visit_date DESC LIMIT 50";
            $columns = ['ID', 'Fecha', 'URL', 'Referer', 'IP', 'User-Agent'];
            break;
        case 'todas':
        default:
            $query = "SELECT id, visit_date, url, referer, user_ip, user_agent FROM $table_name ORDER BY visit_date DESC LIMIT 50";
            $columns = ['ID', 'Fecha', 'URL', 'Referer', 'IP', 'User-Agent'];
            break;
    }

    $resultados = $wpdb->get_results($query);

    // Mostrar las pestañas
    echo "<div class='wrap'>";
    echo "<h2>Historial de Visitas</h2>";
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='?page=bt-historial-visitas&tab=todas' class='nav-tab " . ($tab == 'todas' ? 'nav-tab-active' : '') . "'>Todas</a>";
    echo "<a href='?page=bt-historial-visitas&tab=dia' class='nav-tab " . ($tab == 'dia' ? 'nav-tab-active' : '') . "'>Por Día</a>";
    echo "<a href='?page=bt-historial-visitas&tab=url' class='nav-tab " . ($tab == 'url' ? 'nav-tab-active' : '') . "'>Por URL</a>";
    echo "<a href='?page=bt-historial-visitas&tab=referer' class='nav-tab " . ($tab == 'referer' ? 'nav-tab-active' : '') . "'>Por Referer</a>";
    echo "<a href='?page=bt-historial-visitas&tab=ip' class='nav-tab " . ($tab == 'ip' ? 'nav-tab-active' : '') . "'>Por IP</a>";
    echo "<a href='?page=bt-historial-visitas&tab=bots' class='nav-tab " . ($tab == 'bots' ? 'nav-tab-active' : '') . "'>Por Bots</a>";
    echo "<a href='?page=bt-historial-visitas&tab=humanos' class='nav-tab " . ($tab == 'humanos' ? 'nav-tab-active' : '') . "'>Por Humanos</a>";
    echo "</h2>";

    // Botón para eliminar registros antiguos
    echo "<form method='post' action='" . admin_url('admin-post.php') . "'>";
    echo "<input type='hidden' name='action' value='bt_eliminar_registros'>";
    echo "<button type='submit' class='button button-secondary'>Eliminar registros de más de 10 días</button>";
    echo "</form>";

    // Mostrar la tabla
    if ($resultados) {
        echo "<table class='widefat striped'><thead><tr>";
        foreach ($columns as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr></thead><tbody>";

        foreach ($resultados as $fila) {
            echo "<tr>";
            foreach ($fila as $dato) {
                echo "<td>{$dato}</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay registros aún.</p>";
    }

    echo "</div>";
}



// ** Mostrar historial de clics **
function bt_mostrar_clicks() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'click_logs';

    $resultados = $wpdb->get_results("SELECT * FROM $table_name ORDER BY click_date DESC LIMIT 50");

    echo "<div class='wrap'><h2>Historial de Clics</h2>";

    if ($resultados) {
        echo "<table class='widefat striped'><thead><tr><th>ID</th><th>Fecha</th><th>IP</th></tr></thead><tbody>";
        foreach ($resultados as $fila) {
            echo "<tr>
                    <td>{$fila->id}</td>
                    <td>{$fila->click_date}</td>
                    <td>{$fila->user_ip}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay clics registrados aún.</p>";
    }
    echo "</div>";
}
function bt_mostrar_busquedas() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'search_logs';
    $resultados = $wpdb->get_results("SELECT * FROM $table_name ORDER BY search_date DESC LIMIT 50");

    echo "<div class='wrap'><h1>Historial de Búsquedas</h1>";

    if ($resultados) {
        echo "<table class='widefat striped'><thead><tr><th>ID</th><th>Búsqueda</th><th>Fecha</th><th>IP</th></tr></thead><tbody>";
        foreach ($resultados as $fila) {
            echo "<tr>
                    <td>{$fila->id}</td>
                    <td>{$fila->search_term}</td>
                    <td>{$fila->search_date}</td>
                    <td>{$fila->user_ip}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay búsquedas registradas aún.</p>";
    }
    echo "</div>";
}
// Función para la página principal del plugin
function bt_pagina_principal() {
    echo "<div class='wrap'><h1>Bienvenido a En Guillem</h1>";
    echo "<p>Desde aquí puedes gestionar herramientas personalizadas.</p>";
    echo "<p><a href='admin.php?page=bt-historial-busquedas' class='button button-primary'>Ver Historial de Búsquedas</a></p>";
    echo "<p><a href='admin.php?page=bt-historial-clicks' class='button button-secondary'>Ver Historial de Clics</a></p>";
    echo "<p><a href='admin.php?page=bt-historial-visitas' class='button button-secondary'>Ver Historial de Visitas</a></p>";
    echo "</div>";
}


// **4️⃣ Agregar menú "En Guillem" y submenús**
function bt_menu_admin() {
    add_menu_page('Configuración de En Guillem', 'En Guillem', 'manage_options', 'enguillemplug', 'bt_pagina_principal', 'dashicons-admin-generic', 20);
    add_submenu_page('enguillemplug', 'Historial de Búsquedas', 'Búsquedas', 'manage_options', 'bt-historial-busquedas', 'bt_mostrar_busquedas');
    add_submenu_page('enguillemplug', 'Historial de Clics', 'Clics', 'manage_options', 'bt-historial-clicks', 'bt_mostrar_clicks');
    add_submenu_page('enguillemplug', 'Historial de Visitas', 'Visitas', 'manage_options', 'bt-historial-visitas', 'bt_mostrar_visitas');
}
add_action('admin_menu', 'bt_menu_admin');

// ** Registrar Clics desde AJAX **
function bt_registrar_click() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'click_logs';

    // No registrar clics de administradores
    if (current_user_can('manage_options')) {
        wp_send_json_success("Clic no registrado (administrador)");
        return;
    }

    // Obtener la IP del usuario
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Insertar el clic en la base de datos
    $wpdb->insert(
        $table_name,
        array(
            'click_date' => current_time('mysql'),
            'user_ip'    => $user_ip,
        ),
        array('%s', '%s')
    );

    wp_send_json_success("Clic registrado correctamente");
}

// ** Registrar las funciones AJAX en WordPress **
add_action('wp_ajax_registrar_click', 'bt_registrar_click');
add_action('wp_ajax_nopriv_registrar_click', 'bt_registrar_click'); // Permitir a usuarios no logueados

// ** Registrar Búsquedas en la Base de Datos **
function bt_registrar_busqueda($search_term) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'search_logs';

    if (!empty($search_term)) {
        $wpdb->insert(
            $table_name,
            array(
                'search_term' => sanitize_text_field($search_term),
                'search_date' => current_time('mysql'),
                'user_ip' => $_SERVER['REMOTE_ADDR'],
            ),
            array('%s', '%s', '%s')
        );
    }
}


