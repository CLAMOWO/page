<?php
/**
 * Plugin Name:       DarkMySite
 * Plugin URI:        https://darkmysite.com
 * Description:       Simplest way to enable dark mode on your website - DarkMySite.
 * Version:           1.2.1
 * Author:            DarkMySite - WP Dark Mode
 * Author URI:        https://darkmysite.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       darkmysite
 * Domain Path:       /languages
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

defined( 'DARKMYSITE_VERSION' ) or define( 'DARKMYSITE_VERSION', '1.2.1' );
defined( 'DARKMYSITE_PATH' ) or define( 'DARKMYSITE_PATH', plugin_dir_path( __FILE__ ) );
defined( 'DARKMYSITE_URL' ) or define( 'DARKMYSITE_URL', plugin_dir_url( __FILE__ ) );
defined( 'DARKMYSITE_BASE_FILE' ) or define( 'DARKMYSITE_BASE_FILE', __FILE__ );
defined( 'DARKMYSITE_BASE_PATH' ) or define( 'DARKMYSITE_BASE_PATH', plugin_basename(__FILE__) );
defined( 'DARKMYSITE_IMG_DIR' ) or define( 'DARKMYSITE_IMG_DIR', plugin_dir_url( __FILE__ ) . 'assets/img/' );
defined( 'DARKMYSITE_CSS_DIR' ) or define( 'DARKMYSITE_CSS_DIR', plugin_dir_url( __FILE__ ) . 'assets/css/' );
defined( 'DARKMYSITE_JS_DIR' ) or define( 'DARKMYSITE_JS_DIR', plugin_dir_url( __FILE__ ) . 'assets/js/' );
defined( 'DARKMYSITE_SERVER' ) or define( 'DARKMYSITE_SERVER', 'https://darkmysite.com' );




function darkmysite_check_premium_activation() {
    if ( !in_array( 'darkmysite-pro/darkmysite.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

        require_once DARKMYSITE_PATH . 'includes/DarkMySiteUtils.php';
        require_once DARKMYSITE_PATH . 'includes/DarkMySiteSettings.php';
        require_once DARKMYSITE_PATH . 'includes/DarkMySiteExternalSupport.php';
        require_once DARKMYSITE_PATH . 'backend/class-darkmysite-ajax.php';
        require_once DARKMYSITE_PATH . 'backend/class-darkmysite-admin.php';

        require_once DARKMYSITE_PATH . 'frontend/class-darkmysite-shortcode.php';
        require_once DARKMYSITE_PATH . 'frontend/class-darkmysite-ajax.php';
        require_once DARKMYSITE_PATH . 'frontend/class-darkmysite-client.php';


    }
}
add_action( 'darkmysite_pro_check_init', 'darkmysite_check_premium_activation', 10, 2 );
do_action( 'darkmysite_pro_check_init');