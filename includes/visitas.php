<?php
function vp_registrar_visita() {
    if (current_user_can('manage_options')) return;

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'visit_logs',
        [
            'visit_date' => current_time('mysql'),
            'url' => home_url($_SERVER['REQUEST_URI']),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : 'Directo',
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Desconocido'
        ],
        ['%s', '%s', '%s', '%s', '%s']
    );
}
add_action('template_redirect', 'vp_registrar_visita');

function vp_mostrar_visitas() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visit_logs';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'todas';

    // Lista de bots para filtrar visitas automatizadas
    $bot_patterns = [
        'bot', 'crawler', 'spider', 'slurp', 'archiver', 'fetcher',
        'pingdom', 'facebookexternalhit', 'linkedinbot', 'headless', 'mediapartners',
        'google', 'bing', 'yahoo', 'yandex', 'duckduckbot', 'baidu', 'seznam'
    ];
    $bot_regex = implode('|', $bot_patterns);

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

    // Mostrar las pestañas de filtro
    echo "<div class='wrap'>";
    echo "<h2>Historial de Visitas</h2>";
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='?page=vp-historial-visitas&tab=todas' class='nav-tab " . ($tab == 'todas' ? 'nav-tab-active' : '') . "'>Todas</a>";
    echo "<a href='?page=vp-historial-visitas&tab=dia' class='nav-tab " . ($tab == 'dia' ? 'nav-tab-active' : '') . "'>Por Día</a>";
    echo "<a href='?page=vp-historial-visitas&tab=url' class='nav-tab " . ($tab == 'url' ? 'nav-tab-active' : '') . "'>Por URL</a>";
    echo "<a href='?page=vp-historial-visitas&tab=referer' class='nav-tab " . ($tab == 'referer' ? 'nav-tab-active' : '') . "'>Por Referer</a>";
    echo "<a href='?page=vp-historial-visitas&tab=ip' class='nav-tab " . ($tab == 'ip' ? 'nav-tab-active' : '') . "'>Por IP</a>";
    echo "<a href='?page=vp-historial-visitas&tab=bots' class='nav-tab " . ($tab == 'bots' ? 'nav-tab-active' : '') . "'>Bots</a>";
    echo "<a href='?page=vp-historial-visitas&tab=humanos' class='nav-tab " . ($tab == 'humanos' ? 'nav-tab-active' : '') . "'>Humanos</a>";
    echo "</h2>";

    // Botón para eliminar registros antiguos
    echo "<form method='post' action='" . admin_url('admin-post.php') . "'>";
    echo "<input type='hidden' name='action' value='vp_eliminar_registros'>";
    echo "<input type='hidden' name='tabla' value='visit_logs'>";
    echo "<button type='submit' class='button button-secondary'>Eliminar registros de más de 10 días</button>";
    echo "</form>";

    // Mostrar la tabla de visitas
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
