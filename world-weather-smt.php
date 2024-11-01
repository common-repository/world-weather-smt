<?php
/**
* Plugin Name: World Weather
* Plugin URI: https://swissmediatools.ch/web/extensions-wordpress/world-weather/
* Description: Affichage de données météo
* Version: 1.05
* Author: Swiss Media Tools
* Author URI: https://swissmediatools.ch/
* Text Domain: world-weather-smt
* Domain Path: /languages
*/

    if ( ! defined( 'ABSPATH' ) ) exit;

    //  define( 'WP_DEBUG', true );

    $pluginpath = plugin_dir_path( __FILE__ );
    $pluginwebpath = plugin_dir_url( __FILE__);
    $shortcode_cpt=0;
    $pluginalias = 'worldweather';

    //  Données importantes
    include_once($pluginpath.'common.php');
    //  Scripts SMT
    include_once($pluginpath.'smt.php');
    //  Scripts Worldweather
    include_once($pluginpath.'worldweather/worldweatherclass.php');
    //  Page d'options
    include_once($pluginpath.'worldweather/worldweatheroptions.php');
    //  Générateur de Shortcode
    include_once($pluginpath.'worldweather/shortcodegenerator.php');



    //  Base Wordpress
    function worldweather_register_settings() {
      $services = array();
      add_option( 'worldweather_basegeo', '');
      add_option( 'worldweather_services', $services);
      add_option( 'worldweather_cache', '60');
      register_setting( 'worldweather_options_group', 'worldweather_cache', 'worldweather_callback' );
      add_option( 'worldweather_mesure', 'm');
      register_setting( 'worldweather_options_group', 'worldweather_mesure', 'worldweather_callback' );
      add_option( 'worldweather_icons', 0);
      register_setting( 'worldweather_options_group', 'worldweather_icons', 'worldweather_callback' );
    }
    add_action( 'admin_init', 'worldweather_register_settings' );

    function worldweather_register_options_page() {
      add_options_page('World Weather', 'World Weather', 'manage_options', 'worldweather', 'worldweather_options_page');
    }
    add_action('admin_menu', 'worldweather_register_options_page');

    function worldweather_load_style() {
        global $pluginwebpath;
        wp_register_style( 'worldweather_style', $pluginwebpath . 'css/worldweather.css' );
        wp_enqueue_style('worldweather_style');
    }

    function load_worldweather_script()
    {
      global $pluginwebpath;
      wp_enqueue_script('worldweather_script', $pluginwebpath . 'js/worldweather.js', array('jquery'), true);
      wp_localize_script( 'worldweather_script', 'adminAjax', array('url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('worldweather-ajax-nonce')));
      worldweather_load_style();
    }

    function worldweather_ajax()
    {
        global $pluginpath;
        if (is_admin())
        {
          include($pluginpath.'worldweather/wpajax.php');
        }
    }

    function load_worldweather_admin_script()
    {
      global $pluginwebpath;
      wp_enqueue_script('worldweather_admin_script', $pluginwebpath . 'js/worldweatheradmin.js', array('jquery'), true);
      wp_localize_script( 'worldweather_admin_script', 'adminAjax', array('url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('worldweather-ajax-nonce')) );
      worldweather_load_style();
    }
    add_action('wp_enqueue_scripts', 'load_worldweather_script');
    add_action('admin_enqueue_scripts', 'load_worldweather_admin_script');
    add_action( 'wp_ajax_worldweather_ajax_request', 'worldweather_ajax' );
    add_action( 'wp_ajax_nopriv_worldweather_ajax_request', 'worldweather_ajax' );

    function world_weather_smt_get_current_version() {
    	return WORLD_WEATHER_SMT_CURRENT_VERSION;
    }

    //  Shortcode
    add_shortcode('worldweather', 'worldweather_shortcode');

    //  Widget
    function my_register_custom_widget() {	register_widget( 'worldweather_widget_class' ); }
    add_action( 'widgets_init', 'my_register_custom_widget' );

?>
