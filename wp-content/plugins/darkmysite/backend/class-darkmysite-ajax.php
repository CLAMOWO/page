<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!class_exists('DarkMySiteAdminAjax')) {
    class DarkMySiteAdminAjax
    {

        public $base_admin;

        public function __construct($base_admin)
        {
            $this->base_admin = $base_admin;
            add_action( 'wp_ajax_darkmysite_update_settings', array($this, 'darkmysite_update_settings') );
        }

        public function darkmysite_update_settings() {
            include_once DARKMYSITE_PATH . "backend/api/update_settings.php";
            wp_die();
        }
        
    }
}
