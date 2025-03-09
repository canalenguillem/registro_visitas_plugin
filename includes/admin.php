<?php
function bt_menu_admin() {
    add_menu_page('Buscador Tracker', 'Buscador Tracker', 'manage_options', 'bt-plugin', 'bt_pagina_principal', 'dashicons-chart-bar', 20);
    add_submenu_page('bt-plugin', 'Historial de Búsquedas', 'Búsquedas', 'manage_options', 'bt-historial-busquedas', 'bt_mostrar_busquedas');
    add_submenu_page('bt-plugin', 'Historial de Clics', 'Clics', 'manage_options', 'bt-historial-clicks', 'bt_mostrar_clicks');
    add_submenu_page('bt-plugin', 'Historial de Visitas', 'Visitas', 'manage_options', 'bt-historial-visitas', 'bt_mostrar_visitas');
}
add_action('admin_menu', 'bt_menu_admin');

function bt_pagina_principal() {
    echo "<div class='wrap'><h1>Buscador Tracker</h1><p>Desde aquí puedes gestionar las búsquedas, clics y visitas de tu sitio.</p></div>";
}
