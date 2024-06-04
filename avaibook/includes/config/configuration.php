<?php

function menu_settings_avaibook() {
    add_menu_page(
        'Configuración Avaibook', // Título de la página
        'Avaibook settings',                  // Título del menú
        'manage_options',             // Capacidad requerida para acceder al menú
        'avaibook_settings',         // Slug de la página
        'avaibook_add_api', // Función para renderizar la página
        'dashicons-admin-home',
        5
    );

    add_submenu_page(
        'Avaibook API configuration',
        'Configuracion',
        'Configuracion',
        'manage_options',
        'avaibook_settings',
        'avaibook_add_api'
    );
}
add_action('admin_menu', 'menu_settings_avaibook');
