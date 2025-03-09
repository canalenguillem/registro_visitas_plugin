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

// Cargar archivos de funcionalidad
require_once plugin_dir_path(__FILE__) . 'includes/activacion.php';
require_once plugin_dir_path(__FILE__) . 'includes/visitas.php';
require_once plugin_dir_path(__FILE__) . 'includes/clics.php';
require_once plugin_dir_path(__FILE__) . 'includes/busquedas.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';

// Registrar los hooks de activación y desactivación
register_activation_hook(__FILE__, 'vp_crear_tablas_plugin');
register_deactivation_hook(__FILE__, 'vp_eliminar_tablas_plugin');

// Cargar CSS y JS
function vp_cargar_recursos() {
    wp_enqueue_style('vp-estilos', plugin_dir_url(__FILE__) . 'assets/css/estilos.css');
    wp_enqueue_script('vp-scripts', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), false, true);
}
#add_action('admin_enqueue_scripts', 'vp_cargar_recursos');
