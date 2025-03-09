<?php
function vp_menu_admin() {
    add_menu_page('Visitas Plugin', 'Visitas Plugin', 'manage_options', 'vp-plugin', 'vp_pagina_principal', 'dashicons-chart-bar', 20);
    add_submenu_page('vp-plugin', 'Historial de Búsquedas', 'Búsquedas', 'manage_options', 'vp-historial-busquedas', 'vp_mostrar_busquedas');
    add_submenu_page('vp-plugin', 'Historial de Clics', 'Clics', 'manage_options', 'vp-historial-clicks', 'vp_mostrar_clicks');
    add_submenu_page('vp-plugin', 'Historial de Visitas', 'Visitas', 'manage_options', 'vp-historial-visitas', 'vp_mostrar_visitas');
}
add_action('admin_menu', 'vp_menu_admin');

function vp_pagina_principal() {
    echo "<div class='wrap'><h1>Visitas Plugin</h1><p>Desde aquí puedes gestionar las visitas y actividades de tu sitio.</p></div>";
}
