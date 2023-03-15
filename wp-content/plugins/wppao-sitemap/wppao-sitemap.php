<?php
/*
Plugin Name:Wppao Sitemap
Plugin URI: https://wppao.com/posts/380.html
Description: 生成网站SEO所需要的Sitemap网站地图，支持xml和html格式的网站地图。
Author: 缘殊
Version: 1.2.0
Author URI: https://wppao.com/
Text Domain: wppao-sitemap
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

//内部开发代号wppsmp

if(!defined('ABSPATH')){
    return;
}

/**
 * DEFINE /
 */
define('WPPAO_SITEMAP_KEY','wppsmp_options');
define('WPPAO_SITEMAP_VERSION', '1.2.0' );
define('WPPAO_SITEMAP_DIR', plugin_dir_path( __FILE__ ) );
define('WPPAO_SITEMAP_URI', plugins_url( '/', __FILE__ ) );
define('WPPAO_SITEMAP_HOST', site_url());

// Module
require WPPAO_SITEMAP_DIR.'module/helper.php';
require WPPAO_SITEMAP_DIR.'module/smpxml.php';
require WPPAO_SITEMAP_DIR.'module/smphtml.php';
require WPPAO_SITEMAP_DIR.'module/support.php';
require WPPAO_SITEMAP_DIR.'module/newmap.php';
require WPPAO_SITEMAP_DIR.'module/auto.php';
require WPPAO_SITEMAP_DIR.'module/baidu-zd.php';
require WPPAO_SITEMAP_DIR.'module/js-ts.php';

// Options
if(!class_exists('WPPAO_PGS_SETTING')){
    include (WPPAO_SITEMAP_DIR . 'options/initialization.php' );
}

$WppaoSitemap_Plugin = array(
    'slug' => 'wppaositemap',
    'name' => 'WP泡网站地图',
    'plugin_id' => 'wppao_sitemap',
    'ver' => WPPAO_SITEMAP_VERSION,
    'title' => 'WP泡网站地图',
    'icon' => WPPAO_SITEMAP_URI.'imgs/plugin_icon.png',
    'position' => 30,
    'key' => WPPAO_SITEMAP_KEY,
    'basename' => plugin_basename( __FILE__ ),
    'option' => array('domain' => 'wppao.com',
        'version' => WPPAO_SITEMAP_VERSION,
        'option'  => array(
            array("title" => "Sitemap设置","type" => "title","first" =>"1","desc" => "设置好之后，在页面:<a href='".WPPAO_SITEMAP_HOST."/wp-admin/admin.php?page=wppaositemap_helper'>调试页面</a>进行调试。"),
            array( "title" => "XML地图", "name" => "xml_sitemap", "desc" => "是否开启XML地图生成功能", "type" => "toggle", "std" => "false"),
            array( "title" => "HTML地图", "name" => "html_sitemap", "desc" => "是否开启HTML地图生成功能", "type" => "toggle", "std" => "false"),
            array( "title" => "文章发布自动更新", "name" => "update_new_posts", "desc" => "开启后每次新文章发布后，网站地图自动更新", "type" => "toggle", "std" => "false"),
            array( "title" => "近期文章包含", "name" => "include_new_posts", "desc" => "网站地图是否只包含500个近期文章", "type" => "toggle", "std" => "false"),
            array( "title" => "覆盖的页面", "name" => "include_pages", "desc" => "哪些页面需要包含进网站地图", "type" => "checkbox", 'options'=>array('posts'=>'文章','pages'=>'页面','tags'=>'标签','categorys'=>'目录'), "std" => "false"),
            array( "title" => "百度推送设置","type" => "title","first" =>"1","desc" => "百度主动推送，填入对应Token开启"),
            array( "title" => "推送Token", "name" => "zd_token", "desc" => "要有Token才能主动推送,主动推送和天级收录公用一个Token，注意：目前天级收录支持手动选择提交。", "type" => "text", "std" => ""),
            array( "title" => "文章发布主动推送", "name" => "zd_submit", "desc" => "是否开启百度主动推送生成功能，开启后文章发布就会主动推送百度", "type" => "toggle", "std" => "false"),
            array( "title" => "自动推送设置","type" => "title","first" =>"1","desc" => "请复制站长平台的JS推送代码到这里，目前支持百度和360。"),
            array( "title" => "推送js", "name" => "zd_js_code", "desc" => "复制的时候不要带上&lt;script&gt;&lt;/script&gt;", "type" => "textarea", "std" => ""),
            array( "title" => "自动推送激活", "name" => "zd_js", "desc" => "是否开启站长平台的Javascipt代码自动推送生成功能，开启后在页面自动加入你设置的代码。", "type" => "toggle", "std" => "false"),
        )
    ),
    'submenu' => array(
        array("title"=>"网站地图调试","slug"=>"_helper","func"=>"wppsmp_setting_helper"),
    ),
);

$GLOBALS['WppaoSitemap_Plugin'] = new WPPAO_PGS_SETTING($WppaoSitemap_Plugin);

function wppsmp_get_setting($option){
    $options = get_option(WPPAO_SITEMAP_KEY);
    $value = '';
    if(is_array($options)){
        if(array_key_exists($option,$options)){
            $value = $options[$option];
            return $value;
        }
    }else{
        return $value;
    }
}


