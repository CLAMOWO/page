<?php

function wppsmp_show_messages($msg) {
	 echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"><p><strong>'.$msg.'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button></div>';
}


function wppsmp_get_sitepath() {

    $res="";
    if(function_exists("get_home_path")) {
        $res = get_home_path();
    } else {
        $home = get_option( 'home' );
        if ( $home != '' && $home != get_option( 'siteurl' ) ) {
            $home_path = parse_url( $home );
            $home_path = $home_path['path'];
            $root = str_replace( $_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"] );
            $home_path = trailingslashit( $root.$home_path );
        } else {
            $home_path = ABSPATH;
        }

        $res = $home_path;
    }
    return $res;
}

function wppsmp_get_filestatus($filename) {
    clearstatcache();
    if(!is_writable($filename)) {
        if(!@chmod($filename, 0666)) {
            $pathtofilename = dirname($filename);
            if(!is_writable($pathtofilename)) {
                if(!@chmod($pathtofilename, 0666)) {
                    return false;
                }
            }
        }
    }
    return true;
}

function wppsmp_escape_xml($string) {
    return str_replace ( array ( '&', '"', "'", '<', '>'), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'), $string);
}

function wppsmp_get_mysql_timestamp($mysqlDateTime) {
    list($date, $hours) = explode(' ', $mysqlDateTime);
    list($year,$month,$day) = explode('-',$date);
    list($hour,$min,$sec) = explode(':',$hours);
    return mktime(intval($hour), intval($min), intval($sec), intval($month), intval($day), intval($year));
}

function wppsmp_xml_annotate() {
    $xml_author_annotate = '<!-- wppao-sitemap-->';
    $xw = '<!-- generated-on="'.date("Y-m-d H:i:s").'" -->';
    $xw = str_replace('-- ::','',$xw);

    return $xml_author_annotate.$xw;
}

