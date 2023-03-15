<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!class_exists('DarkMySiteClient')) {
    class DarkMySiteClient
    {

        public $utils;
        public $settings;
        public $external_support;

        public $data_settings;
        public $unique_id;

        public function __construct()
        {
            $this->utils = new DarkMySiteUtils($this);
            $this->settings = new DarkMySiteSettings($this);
            $this->external_support = new DarkMySiteExternalSupport($this);
            new DarkMySiteClientAjax($this);
            new DarkMySiteShortcodeParser($this);

            $this->data_settings = $this->settings->get_all_darkmysite_settings();
            $this->unique_id = rand();

            if($this->data_settings["enable_switch_in_menu"] == "1"){
                add_filter('wp_nav_menu_items',array($this, 'darkmysite_switch_in_menu'), 10, 2);
            }

            add_action( 'wp_enqueue_scripts', array( $this, 'darkmysite_client_enqueue' ) );
            add_action( 'login_enqueue_scripts', array( $this, 'darkmysite_client_enqueue' ) );
            add_action( 'register_enqueue_scripts', array( $this, 'darkmysite_client_enqueue' ) );
            add_action( 'wp_head', array( $this, 'darkmysite_client_header_script' ), 1);
            add_action( 'login_head', array( $this, 'darkmysite_client_header_script' ), 1 );
            add_action( 'register_head', array( $this, 'darkmysite_client_header_script' ), 1 );
            add_action( 'wp_footer', array( $this, 'darkmysite_client_footer_script' ) );
            add_action( 'login_footer', array( $this, 'darkmysite_client_footer_script' ) );
            add_action( 'register_footer', array( $this, 'darkmysite_client_footer_script' ) );
        }


        function darkmysite_client_enqueue()
        {
            if($this->darkmysite_is_dark_mode_allowed()){
                wp_enqueue_style('darkmysite-client-main', DARKMYSITE_CSS_DIR.'client_main.css', array(), DARKMYSITE_VERSION);
                wp_enqueue_script( 'darkmysite-client-main', DARKMYSITE_JS_DIR.'client_main.js', array(), DARKMYSITE_VERSION);
            }
        }

        function darkmysite_is_dark_mode_allowed()
        {
            /* Disable if Oxygen Builder is Opened */
            if (isset( $_GET['ct_builder'] )) {
                if($_GET['ct_builder'] == "true"){
                    if (!isset( $_GET['oxygen_iframe'] )) {
                        return False;
                    }
                }
            }
            return True;
        }

        function darkmysite_client_header_script()
        {
            if($this->darkmysite_is_dark_mode_allowed()){
                include_once DARKMYSITE_PATH . "frontend/templates/header_script.php";
            }
        }

        function darkmysite_client_footer_script()
        {
            if($this->darkmysite_is_dark_mode_allowed()){
                include_once DARKMYSITE_PATH . "frontend/templates/footer_script.php";
            }
        }

        function darkmysite_switch_in_menu( $items, $args ) {
            if($this->darkmysite_is_dark_mode_allowed()){
                if(isset($args->menu->term_id)){
                    if($args->menu->term_id == $this->data_settings["switch_in_menu_location"]){
                        $items .=  '<li class="menu-item">'.do_shortcode($this->data_settings["switch_in_menu_shortcode"]).'</li>';
                    }
                }
            }
            return $items;
        }

    }
}

new DarkMySiteClient();


