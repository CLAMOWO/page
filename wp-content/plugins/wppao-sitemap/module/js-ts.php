<?php
    /**
     * javascipt推送
     * wppao.com update by 缘殊
     * 2020-5-25
     */
    function wppsmp_jsts(){
        $reslut = '<script>';
        $reslut .= wppsmp_get_setting('zd_js_code');
        $reslut .= '</script>';
        echo $reslut;
    }

    if(wppsmp_get_setting('zd_js')){
        add_filter('wp_footer','wppsmp_jsts');
    }