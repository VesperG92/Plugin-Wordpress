<?php

/*
Plugin Name: Avaibook_test
Plugin URI: https://proveedoresdev.smooply.com/
Description: Avaibook API
Version: 1.0.0
Author: Smooply
Author URI: smooply.com
Text Domain: avaibook_test
Domain Path: /languages
License: GPL 2+
License URI: smooply.com
*/

define('PLUGIN_FILE', __FILE__);

require_once('includes/config/configuration.php');
require_once('includes/config/configuration_main.php');

require_once('includes/classes/class_conect_api.php');
require_once('includes/classes/class_busqueda.php');

require_once('includes/buscar_propiedades.php');

function shortcode_avaibook()
{
    formulario_de_busqueda();
}
add_shortcode('avaibook_shortcode', 'shortcode_avaibook');


function avaibook_test_load_textdomain() 
{
    load_plugin_textdomain('avaibook_test', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'avaibook_test_load_textdomain');


function agregar_estilos_personalizados() 
{
    wp_enqueue_style('estilos-personalizados', plugins_url('includes/styles.css', __FILE__));
    wp_enqueue_script( 'form-script', plugins_url( 'includes/form_script.js', __FILE__ ), array(), '1.0', true );
}
add_action('wp_enqueue_scripts', 'agregar_estilos_personalizados');









