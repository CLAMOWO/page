<?php

if( !defined('WPPAO_PGS_PATH') ) {

    //Depend On PHP 7.1++

    define( 'WPPAO_PGS_VER', '1.0.0' );
    define( 'WPPAO_PGS_PATH', plugin_dir_path( __FILE__ ) );
    define( 'WPPAO_PGS_URI', plugins_url( '/', __FILE__ ) );

    require WPPAO_PGS_PATH . '/setting.php';

}