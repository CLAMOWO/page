<?php

// error_reporting(E_ALL);ini_set("display_errors", 1);



define( 'THEME_VERSION' , '7.0' );


// require widgets
require_once get_stylesheet_directory() . '/widgets/widget-index.php';


// require functions for admin
if( is_admin() ){
    require_once get_stylesheet_directory() . '/functions-admin.php';
}


// add link manager
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

// delete wp_head code
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'wp_generator');
remove_action('wp_head',  'wp_shortlink_wp_head', 10, 0 );
remove_action('template_redirect', 'wp_shortlink_header', 11 , 0 );



// WordPress Emoji Delete
remove_action( 'admin_print_scripts' ,	'print_emoji_detection_script');
remove_action( 'admin_print_styles'  ,	'print_emoji_styles');
remove_action( 'wp_head'             ,	'print_emoji_detection_script',	7);
remove_action( 'wp_print_styles'     ,	'print_emoji_styles');
remove_filter( 'the_content_feed'    ,	'wp_staticize_emoji');
remove_filter( 'comment_text_rss'    ,	'wp_staticize_emoji');
remove_filter( 'wp_mail'             ,	'wp_staticize_emoji_for_email');

// 禁止英文符号转中文符号
remove_filter( 'the_title', 'wptexturize' );


add_theme_support( 'post-formats', array( 'aside' ) ); 



// post thumbnail
if (function_exists('add_theme_support')) {
	add_theme_support('post-thumbnails');
	set_post_thumbnail_size(220, 150, true );
}

// hide admin bar
add_filter('show_admin_bar', 'hide_admin_bar');
function hide_admin_bar($flag) {
	return false;
}

// no self Pingback
add_action('pre_ping', '_noself_ping');
function _noself_ping(&$links) {
	$home = get_option('home');
	foreach ($links as $l => $link) {
		if (0 === strpos($link, $home)) {
			unset($links[$l]);
		}
	}
}

// reg nav
if (function_exists('register_nav_menus')){
    register_nav_menus( array(
		'nav'      => __('网站导航', 'haoui'),
		'nav_m'    => __('网站导航-手机端', 'haoui'),
		'fixnav_m' => __('屏幕底部导航-手机端', 'haoui'),
		'topmenu'  => __('顶部菜单', 'haoui'),
		'pagenav'  => __('页面左侧导航', 'haoui')
    ));
}

// reg sidebar
if (function_exists('register_sidebar')) {
	$sidebars = array(
		'gheader' => '侧边栏公共头部',
		'gfooter' => '侧边栏公共底部',
		'home'    => '首页侧边栏',
		'cat'     => '分类页侧边栏',
		'topic'   => '专题页侧边栏',
		'tag'     => '标签页侧边栏',
		'search'  => '搜索页侧边栏',
		'single'  => '文章页侧边栏',
	);

	if( function_exists('is_woocommerce') ){
		$sidebars['wooproduct'] = '商城产品页侧边栏';
		// $sidebars['woolist'] = '商城列表页侧边栏';
	}

	foreach ($sidebars as $key => $value) {
		register_sidebar(array(
			'name'          => $value,
			'id'            => $key,
			'before_widget' => '<div class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>'
		));
	};
}

function _hui($name, $default = false) {
	$option_name = 'dux';

	/*// Gets option name as defined in the theme
	if ( function_exists( 'optionsframework_option_name' ) ) {
		$option_name = optionsframework_option_name();
	}

	// Fallback option name
	if ( '' == $option_name ) {
		$option_name = get_option( 'stylesheet' );
		$option_name = preg_replace( "/\W/", "_", strtolower( $option_name ) );
	}*/

	// Get option settings from database
	$options = get_option( $option_name );

	// Return specific option
	if ( isset( $options[$name] ) ) {
		return $options[$name];
	}

	return $default;
}







// Avatar
////////////////////////////////////////////////////////////////////////////////////////////////////

if( _hui('gravatar_url') ){
	if( _hui('gravatar_url') == 'ssl' ){
	    add_filter('get_avatar', '_get_ssl2_avatar');
	}elseif( _hui('gravatar_url') == 'v2ex' ){
	    add_filter('get_avatar', '_get_v2ex_avatar');
	}
}

function _get_ssl2_avatar($avatar) {
    $avatar = preg_replace('/.*\/avatar\/(.*)\?s=([\d]+)&d=(.*).*/','<img src="https://secure.gravatar.com/avatar/$1?s=$2&d='.get_option('avatar_default').'" class="avatar avatar-$2">',$avatar);
    return $avatar;
}

function _get_v2ex_avatar($avatar) {
    $avatar = str_replace(array("www.gravatar.com/avatar", "secure.gravatar.com/avatar", "0.gravatar.com/avatar", "1.gravatar.com/avatar", "2.gravatar.com/avatar"), "cdn.v2ex.com/gravatar", $avatar);
    return $avatar;
}

add_filter( 'avatar_defaults', '_tb_new_avatar' );  
function _tb_new_avatar ($avatar_defaults) {
    $myavatar = _get_default_avatar();
    $avatar_defaults[$myavatar] = "DUX 默认头像";  
    return $avatar_defaults;
}

function _get_the_avatar($user_id = '', $user_email = '', $src = false, $size = 50) {
	$avatar = get_avatar($user_email, $size, get_option('avatar_default'));
	if ($src) {
		return $avatar;
	} else {
		return str_replace(' src=', ' data-src=', $avatar);
	}
}





// require no-category
if( _hui('no_categoty') && !function_exists('no_category_base_refresh_rules') ){

	register_activation_hook(__FILE__, 'no_category_base_refresh_rules');
	add_action('created_category', 'no_category_base_refresh_rules');
	add_action('edited_category', 'no_category_base_refresh_rules');
	add_action('delete_category', 'no_category_base_refresh_rules');
	function no_category_base_refresh_rules() {
	    global $wp_rewrite;
	    $wp_rewrite -> flush_rules();
	}

	register_deactivation_hook(__FILE__, 'no_category_base_deactivate');
	function no_category_base_deactivate() {
	    remove_filter('category_rewrite_rules', 'no_category_base_rewrite_rules');
	    // We don't want to insert our custom rules again
	    no_category_base_refresh_rules();
	}

	// Remove category base
	add_action('init', 'no_category_base_permastruct');
	function no_category_base_permastruct() {
	    global $wp_rewrite, $wp_version;
	    if (version_compare($wp_version, '3.4', '<')) {
	        // For pre-3.4 support
	        $wp_rewrite -> extra_permastructs['category'][0] = '%category%';
	    } else {
	        $wp_rewrite -> extra_permastructs['category']['struct'] = '%category%';
	    }
	}

	// Add our custom category rewrite rules
	add_filter('category_rewrite_rules', 'no_category_base_rewrite_rules');
	function no_category_base_rewrite_rules($category_rewrite) {
	    //var_dump($category_rewrite); // For Debugging

	    $category_rewrite = array();
	    $categories = get_categories(array('hide_empty' => false));
	    foreach ($categories as $category) {
	        $category_nicename = $category -> slug;
	        if ($category -> parent == $category -> cat_ID)// recursive recursion
	            $category -> parent = 0;
	        elseif ($category -> parent != 0)
	            $category_nicename = get_category_parents($category -> parent, false, '/', true) . $category_nicename;
	        $category_rewrite['(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
	        $category_rewrite['(' . $category_nicename . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
	        $category_rewrite['(' . $category_nicename . ')/?$'] = 'index.php?category_name=$matches[1]';
	    }
	    // Redirect support from Old Category Base
	    global $wp_rewrite;
	    $old_category_base = get_option('category_base') ? get_option('category_base') : 'category';
	    $old_category_base = trim($old_category_base, '/');
	    $category_rewrite[$old_category_base . '/(.*)$'] = 'index.php?category_redirect=$matches[1]';

	    //var_dump($category_rewrite); // For Debugging
	    return $category_rewrite;
	}

	// For Debugging
	//add_filter('rewrite_rules_array', 'no_category_base_rewrite_rules_array');
	//function no_category_base_rewrite_rules_array($category_rewrite) {
	//  var_dump($category_rewrite); // For Debugging
	//}

	// Add 'category_redirect' query variable
	add_filter('query_vars', 'no_category_base_query_vars');
	function no_category_base_query_vars($public_query_vars) {
	    $public_query_vars[] = 'category_redirect';
	    return $public_query_vars;
	}

	// Redirect if 'category_redirect' is set
	add_filter('request', 'no_category_base_request');
	function no_category_base_request($query_vars) {
	    //print_r($query_vars); // For Debugging
	    if (isset($query_vars['category_redirect'])) {
	        $catlink = trailingslashit(get_option('home')) . user_trailingslashit($query_vars['category_redirect'], 'category');
	        status_header(301);
	        header("Location: $catlink");
	        exit();
	    }
	    return $query_vars;
	}

}






// head code
add_action('wp_head', '_the_head');
function _the_head() {
	if( !_hui('seo_off') ){
		_the_keywords();
		_the_description();
	}
	_post_views_record();
	_the_head_css();
	_the_head_code();
}
function _the_head_code() {
	if (_hui('headcode')) {
		echo "\n<!--HEADER_CODE_START-->\n" . _hui('headcode') . "\n<!--HEADER_CODE_END-->\n";
	}

}
function _the_head_css() {
	$styles = '';

	if (_hui('site_gray')) {
		$styles .= "html{overflow-y:scroll;filter:progid:DXImageTransform.Microsoft.BasicImage(grayscale=1);-webkit-filter: grayscale(100%);}";
	}

	if (_hui('site_width') && _hui('site_width')!=='1200') {
		$styles .= ".container{max-width:"._hui('site_width')."px}";
	}

	$color = '';
	if (_hui('theme_skin') && _hui('theme_skin') !== '45B6F7') {
		$color = _hui('theme_skin');
	}

	if (_hui('theme_skin_custom') && _hui('theme_skin_custom') !== '#45B6F7') {
		$color = substr(_hui('theme_skin_custom'), 1);
	}

	if ($color) {
		$styles .= 'a:hover, .site-navbar li:hover > a, .site-navbar li.active a:hover, .site-navbar a:hover, .search-on .site-navbar li.navto-search a, .topbar a:hover, .site-nav li.current-menu-item > a, .site-nav li.current-menu-parent > a, .site-search-form a:hover, .branding-primary .btn:hover, .title .more a:hover, .excerpt h2 a:hover, .excerpt .meta a:hover, .excerpt-minic h2 a:hover, .excerpt-minic .meta a:hover, .article-content .wp-caption:hover .wp-caption-text, .article-content a, .article-nav a:hover, .relates a:hover, .widget_links li a:hover, .widget_categories li a:hover, .widget_ui_comments strong, .widget_ui_posts li a:hover .text, .widget_ui_posts .nopic .text:hover , .widget_meta ul a:hover, .tagcloud a:hover, .textwidget a, .textwidget a:hover, .sign h3, #navs .item li a, .url, .url:hover, .excerpt h2 a:hover span, .widget_ui_posts a:hover .text span, .widget-navcontent .item-01 li a:hover span, .excerpt-minic h2 a:hover span, .relates a:hover span,.fixnav > li.current-menu-item > a, .fixnav > li.current_page_item > a, .post-copyright-custom a{color: #'.$color.';}.btn-primary, .label-primary, .branding-primary, .post-copyright:hover, .article-tags a, .pagination ul > .active > a, .pagination ul > .active > span, .pagenav .current, .widget_ui_tags .items a:hover, .sign .close-link, .pagemenu li.active a, .pageheader, .resetpasssteps li.active, #navs h2, #navs nav, .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open > .dropdown-toggle.btn-primary, .tag-clouds a:hover{background-color: #'.$color.';}.btn-primary, .search-input:focus, #bdcs .bdcs-search-form-input:focus, #submit, .plinks ul li a:hover,.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open > .dropdown-toggle.btn-primary{border-color: #'.$color.';}.search-btn, .label-primary, #bdcs .bdcs-search-form-submit, #submit, .excerpt .cat{background-color: #'.$color.';}.excerpt .cat i{border-left-color:#'.$color.';}@media (max-width: 720px) {.site-navbar li.active a, .site-navbar li.active a:hover, .m-nav-show .m-icon-nav{color: #'.$color.';}}@media (max-width: 480px) {.pagination ul > li.next-page a{background-color:#'.$color.';}}.post-actions .action.action-like,.pagemenu li.current-menu-item > a{background-color: #'.$color.';}.catleader h1{border-left-color: #'.$color.';}.loop-product-filters ul .current-cat>a{color: #'.$color.';}';
	}

	if (_hui('csscode')) {
		$styles .= _hui('csscode');
	}

	if ($styles) {
		echo '<style>' . $styles . '</style>';
	}
}

// foot code
add_action('wp_footer', '_the_footer');
function _the_footer() {
	if (_hui('footcode')) {
		echo "<!--FOOTER_CODE_START-->\n" . _hui('footcode') . "\n<!--FOOTER_CODE_END-->\n";
	}
}

// excerpt length
add_filter('excerpt_length', '_excerpt_length');
function _excerpt_length($length) {
	return 120;
}

// smilies src
add_filter('smilies_src', '_smilies_src', 1, 10);
function _smilies_src($img_src, $img, $siteurl) {
	return get_stylesheet_directory_uri() . '/img/smilies/' . $img;
}

// load script and style
add_action('wp_enqueue_scripts', '_load_scripts');
function _load_scripts() {
	if (!is_admin()) {
		wp_deregister_script('jquery');

		// delete l10n.js
		wp_deregister_script('l10n');

		$purl = get_stylesheet_directory_uri();

		// common css
		_cssloader(array('bootstrap' => $purl.'/css/bootstrap.min.css', 'fontawesome' => $purl.'/css/font-awesome.min.css', 'main' => 'main'));

		// page css
		if (is_page_template('pages/user.php')) {
			_cssloader(array('user' => 'user'));
		}

		
		$jss = array(
            'no' => array(
                'jquery' => $purl.'/js/libs/jquery.min.js',
                'bootstrap' => $purl . '/js/libs/bootstrap.min.js'
            ),
            'baidu' => array(
                'jquery' => '//apps.bdimg.com/libs/jquery/1.9.1/jquery.min.js',
                'bootstrap' => '//apps.bdimg.com/libs/bootstrap/3.2.0/js/bootstrap.min.js'
            ),
            '360' => array(
                'jquery' => $purl.'/js/libs/jquery.min.js',
                'bootstrap' => $purl . '/js/libs/bootstrap.min.js'
            ),
            'he' => array(
                'jquery' => '//code.jquery.com/jquery-1.9.1.min.js',
                'bootstrap' => '//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js'
            )
        );
        wp_register_script( 'jquery', _hui('js_outlink') ? $jss[_hui('js_outlink')]['jquery'] : $purl.'/js/libs/jquery.min.js', false, THEME_VERSION, (_hui('jquery_bom')?true:false) );
        wp_enqueue_script( 'bootstrap', _hui('js_outlink') ? $jss[_hui('js_outlink')]['bootstrap'] : $purl . '/js/libs/bootstrap.min.js', array('jquery'), THEME_VERSION, true );
		_jsloader(array('loader'));
		
        // wp_enqueue_script( '_main', $purl . '/js/main.js', array(), THEME_VERSION, true );


	}
}
function _cssloader($arr) {
	foreach ($arr as $key => $item) {
		$href = $item;
		if (strstr($href, '//') === false) {
			$href = get_stylesheet_directory_uri() . '/css/' . $item . '.css';
		}
		wp_enqueue_style('_' . $key, $href, array(), THEME_VERSION, 'all');
	}
}
function _jsloader($arr) {
	foreach ($arr as $item) {
		wp_enqueue_script('_' . $item, get_stylesheet_directory_uri() . '/js/' . $item . '.js', array(), THEME_VERSION, true);
	}
}

function _get_default_avatar(){
	return get_stylesheet_directory_uri() . '/img/avatar-default.png';
}

function _get_delimiter(){
	return _hui('connector') ? _hui('connector') : '-';
}

function _get_price_pre(){
    return '&yen;';
}


function _get_post_price($before='',$after=''){
    global $post;
    $post_ID = $post->ID;
    $metas = get_post_meta($post_ID, 'price', true);
    return $before.$metas.$after;
}

function _get_post_meta_link(){
    global $post;
    $post_ID = $post->ID;
    $metas = get_post_meta($post_ID, 'link', true);
    return $metas;
}


function _post_target_blank(){
    return _hui('target_blank') ? ' target="_blank"' : '';
}

function _title() {
	global $new_title;
	if( $new_title ) return $new_title;

	global $paged;

	$html = '';
	$t = trim(wp_title('', false));

	if( _hui('seo_off') ){
		return $t;
	}

	if( (is_single() || is_page()) && get_the_subtitle(false) ){
		$t .= get_the_subtitle(false);
	}

	if ($t) {
		$html .= $t . _get_delimiter();
	}

	$html .= get_bloginfo('name');

	if (is_home()) {
		if(_hui('hometitle')){
            $html = _hui('hometitle');
            if ($paged > 1) {
                $html .= _get_delimiter() . '最新发布';
            }
        }else{
			if ($paged > 1) {
				$html .= _get_delimiter() . '最新发布';
			}else if( get_option('blogdescription') ){
				$html .= _get_delimiter() . get_option('blogdescription');
			}
		}
	}

	if( is_category() ){
		global $wp_query; 
		$cat_ID = get_query_var('cat');
		$cat_tit = _get_tax_meta($cat_ID, 'title');
		if( $cat_tit ){
			$html = $cat_tit;
		}
	}

	if( is_tag() ){
		global $wp_query; 
		$tag_ID = get_query_var('tag_id');
		$tag_tit = _get_tax_meta($tag_ID, 'title');
		if( $tag_tit ){
			$html = $tag_tit;
		}
	}

	if ( is_tax() ) { 
        global $wp_query;
        $data = $wp_query->get_queried_object();

        $title = _get_tax_meta($data->term_id, 'title');

        if( $title ){
            $html = $title;
        }else{
            $html = $data->name ._get_delimiter(). get_bloginfo('name');
        }
    }

	if( (is_single() || is_page()) && _hui('post_keywords_description_s') ){
		global $post;
	    $post_ID = $post->ID;
	    $seo_title = trim(get_post_meta($post_ID, 'title', true));
		if($seo_title){
			$html = $seo_title;
		}elseif( _hui('post_title_no_sitetitle_s') && is_single() ){
			$html = $t;
		}
	}

	if ($paged > 1) {
		$html .= _get_delimiter() . '第' . $paged . '页';
	}

	return $html;
}

function get_the_subtitle($span=true){
    global $post;
    $post_ID = $post->ID;
    $subtitle = get_post_meta($post_ID, 'subtitle', true);

    if( !empty($subtitle) ){
    	if( $span ){
        	return ' <span>'.$subtitle.'</span>';
        }else{
        	return ' '.$subtitle;
        }
    }else{
        return false;
    }
}



function _bodyclass() {
	$class = '';

	if( _hui('nav_fixed') && !is_page_template('pages/resetpassword.php') ){
		$class .= ' nav_fixed';
	}
	
	if( _hui('post_plugin_cat_m') ){
		$class .= ' m-excerpt-cat';
	}

	if( _hui('post_plugin_date_m') ){
		$class .= ' m-excerpt-time';
	}

	if( _hui('flinks_m_s') ){
		$class .= ' flinks-m';
	}

	if( _hui('topbar_off') ){
		$class .= ' topbar-off';
	}

	if ((is_single() || is_page()) && _hui('post_p_indent_s')) {
		$class .= ' p_indent';
	}

	if ((is_single() || is_page()) && comments_open()) {
		$class .= ' comment-open';
	}
	if (is_super_admin()) {
		$class .= ' logged-admin';
	}
	
	$class .= ' site-layout-'.(_hui('layout') ? _hui('layout') : '2');

	if( _hui('list_type')=='text' ){
		$class .= ' list-text';
	}

	if( _hui('text_justify_s') ){
		$class .= ' text-justify-on';
	}

	if( _hui('sidebar_m_s') ){
		$class .= ' m-sidebar';
	}

	if( _hui('list_thumb_left') ){
		$class .= ' m-list-thumb-left';
	}

	if( _hui('thumb_autoheight_s') ){
		$class .= ' thumb-autoheight';
	}

	if( _hui('user_page_s') ){
		$class .= ' m-user-on';
	}

	if( _hui('kefu') && _hui('kefu_m') && wp_is_mobile() && !has_nav_menu('fixnav_m') ){
		$class .= ' rollbar-m-on';
	}

	if( wp_is_mobile() && has_nav_menu('fixnav_m') ){
		$class .= ' fixnav-m-on';
	}

	if( is_category() ){
		_moloader('mo_is_minicat', false);
		if( mo_is_minicat() ){
			$class .= ' site-minicat';
		}
	}

	if( isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'firefox') ){
		$class .= ' is-firefox';
	}

	if( wp_is_mobile() ){
		$class .= ' is-phone';
	}

	return trim($class);
}

function _moloader($name = '', $apply = true) {
	if (!function_exists($name)) {
		include get_stylesheet_directory() . '/modules/' . $name . '.php';
	}

	if ($apply && function_exists($name)) {
		$name();
	}
}


function _the_menu($location = 'nav') {
	echo str_replace("</ul></div>", "", preg_replace("/<div[^>]*><ul[^>]*>/", "", wp_nav_menu(array('theme_location' => $location, 'echo' => false))));
}

function _the_logo() {
	$tag = is_home() ? 'h1' : 'div';
	$t = _hui('hometitle')?_hui('hometitle'):get_bloginfo('name') .(get_bloginfo('description') ? _get_delimiter() . get_bloginfo('description') : '');
	echo '<' . $tag . ' class="logo"><a href="' . get_bloginfo('url') . '" title="' . $t . '"><img src="'._hui('logo_src').'" alt="'.$t.'">' . get_bloginfo('name') . '</a></' . $tag . '>';
}

function _the_ads($name='', $class=''){
    if( !_hui($name.'_s') ) return;

    if( wp_is_mobile() ){
    	echo '<div class="orbui orbui-m '.$class.'">'._hui($name.'_m').'</div>';
    }else{
        echo '<div class="orbui '.$class.'">'._hui($name).'</div>';
    }
}


function _get_cat_root_id($cat){
    $this_category = get_category($cat); 
    while($this_category->category_parent){
        $this_category = get_category($this_category->category_parent);
    }
    return $this_category->term_id; 
}


function _post_views_record() {
	if (is_singular()) {
		global $post;
		$post_ID = $post->ID;
		if ($post_ID) {
			$post_views = (int) get_post_meta($post_ID, 'views', true);
			if (!update_post_meta($post_ID, 'views', ($post_views + 1))) {
				add_post_meta($post_ID, 'views', 1, true);
			}
		}
	}
}
function _get_post_views($before = '阅读(', $after = ')') {
	global $post;
	$post_ID = $post->ID;
	$views = (int) get_post_meta($post_ID, 'views', true);

	if( _hui('views_w_on') ){
		if( $views>=100000 ){
	        $views = '10W+';
	    }elseif( $views>=10000 ){
	        $views = '1W+';
	    }
    }

	return $before . $views . $after;
}

function _str_cut($str, $start, $width, $trimmarker) {
	$output = preg_replace('/^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $start . '}((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $width . '}).*/s', '\1', $str);
	return $output . $trimmarker;
}

function _get_excerpt($limit = 120, $after = '...') {
	$excerpt = get_the_excerpt();
	if (_new_strlen($excerpt) > $limit) {
		return _str_cut(strip_tags($excerpt), 0, $limit, $after);
	} else {
		return $excerpt;
	}
}

function _get_post_comments($before = '评论(', $after = ')') {
	return $before . get_comments_number('0', '1', '%') . $after;
}

function _new_strlen($str,$charset='utf-8') {        
    $n = 0; $p = 0; $c = '';
    $len = strlen($str);
    if($charset == 'utf-8') {
        for($i = 0; $i < $len; $i++) {
            $c = ord(substr($str,$i,1));
            if($c > 252) {
                $p = 5;
            } elseif($c > 248) {
                $p = 4;
            } elseif($c > 240) {
                $p = 3;
            } elseif($c > 224) {
                $p = 2;
            } elseif($c > 192) {
                $p = 1;
            } else {
                $p = 0;
            }
            $i+=$p;$n++;
        }
    } else {
        for($i = 0; $i < $len; $i++) {
            $c = ord(substr($str,$i,1));
            if($c > 127) {
                $p = 1;
            } else {
                $p = 0;
        }
            $i+=$p;$n++;
        }
    }        
    return $n;
}

function _get_post_thumbnail($size = 'thumbnail', $class = 'thumb') {
	global $post;
	$r_src = '';
	if (has_post_thumbnail()) {
        $domsxe = get_the_post_thumbnail();
        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim', $domsxe, $strResult, PREG_PATTERN_ORDER);  
        $images = $strResult[1];
        foreach($images as $src){
        	$r_src = $src;
            break;
        }
	}else{
	    $thumblink = get_post_meta($post->ID, 'thumblink', true);
		if( _hui('thumblink_s') && !empty($thumblink) ){
			$r_src = $thumblink;
		}
		elseif( _hui('thumb_postfirstimg_s') ){
			$content = $post->post_content;  
	        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim', $content, $strResult, PREG_PATTERN_ORDER);  
	        $images = $strResult[1];

	        foreach($images as $src){
		        if( _hui('thumb_postfirstimg_lastname') ){
		            $filetype = _get_filetype($src);
		            $src = rtrim($src, '.'.$filetype)._hui('thumb_postfirstimg_lastname').'.'.$filetype;
		        }

		        $r_src = $src;
		        break;
	        }
		}
    } 

	if( $r_src ){
		if( _hui('thumbnail_src') ){
    		return sprintf('<img data-src="%s" alt="%s" src="%s" class="thumb">', $r_src, $post->post_title._get_delimiter().get_bloginfo('name'), get_stylesheet_directory_uri().'/img/thumbnail.png');
		}else{
    		return sprintf('<img src="%s" alt="%s" class="thumb">', $r_src, $post->post_title._get_delimiter().get_bloginfo('name'));
		}
    }else{
    	return sprintf('<img data-thumb="default" src="%s" class="thumb">', get_stylesheet_directory_uri().'/img/thumbnail.png');
    }
}



function _get_filetype($filename) {
    $exten = explode('.', $filename);
    return end($exten);
}

function _get_attachment_id_from_src($link) {
	global $wpdb;
	$link = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $link);
	return $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE guid='$link'");
}



//关键字
function _the_keywords() {
	global $new_keywords;
	if( $new_keywords ) {
		echo "<meta name=\"keywords\" content=\"{$new_keywords}\">\n";
		return;
	}

	global $s, $post;
	$keywords = '';
	if (is_singular()) {
		if (get_the_tags($post->ID)) {
			foreach (get_the_tags($post->ID) as $tag) {
				$keywords .= $tag->name . ',';
			}
		}
		foreach (get_the_category($post->ID) as $category) {
			$keywords .= $category->cat_name . ',';
		}
		$keywords = substr_replace($keywords, '', -1);
		$the = trim(get_post_meta($post->ID, 'keywords', true));
		if ($the) {
			$keywords = $the;
		}
	} elseif (is_home()) {
		$keywords = _hui('keywords');
	} elseif (is_category()) {

		global $wp_query; 
		$cat_ID = get_query_var('cat');
		$keywords = _get_tax_meta($cat_ID, 'keywords');
		if( !$keywords ){
			$keywords = single_cat_title('', false);
		}

	} elseif (is_tag()) {

		global $wp_query; 
		$tag_ID = get_query_var('tag_id');
		$keywords = _get_tax_meta($tag_ID, 'keywords');
		if( !$keywords ){
			$keywords = single_tag_title('', false);
		}
	}elseif ( is_tax() ) { 
        global $wp_query;
        $data = $wp_query->get_queried_object();
        $keywords = _get_tax_meta($data->term_id, 'keywords');
        if( !$keywords ){
            $keywords = $data->name;
        }
	} elseif (is_search()) {
		$keywords = esc_html($s, 1);
	} else {
		$keywords = trim(wp_title('', false));
	}
	if ($keywords) {
		echo "<meta name=\"keywords\" content=\"{$keywords}\">\n";
	}
}

//网站描述
function _the_description() {
	global $new_description;
	if( $new_description ){
		echo "<meta name=\"description\" content=\"$new_description\">\n";
		return;
	}

	global $s, $post;
	$description = '';
	$blog_name = get_bloginfo('name');
	if (is_singular()) {
		if (!empty($post->post_excerpt)) {
			$text = $post->post_excerpt;
		} else {
			$text = $post->post_content;
		}
		$description = trim(str_replace(array("\r\n", "\r", "\n", "　", " "), " ", str_replace("\"", "'", strip_tags($text))));
		$description = mb_substr($description, 0, 160, 'utf-8');

		if (!$description) {
			$description = $blog_name . "-" . trim(wp_title('', false));
		}

		$the = trim(get_post_meta($post->ID, 'description', true));
		if ($the) {
			$description = $the;
		}
		
	} elseif (is_home()) {
		$description = _hui('description');
	} elseif (is_category()) {

		global $wp_query; 
		$cat_ID = get_query_var('cat');
		$description = _get_tax_meta($cat_ID, 'description');
		if( !$description ){
			$description = trim(strip_tags(category_description()));
		}

	} elseif (is_tag()) {

		global $wp_query; 
		$tag_ID = get_query_var('tag_id');
		$description = _get_tax_meta($tag_ID, 'description');
		if( !$description ){
			$description = trim(strip_tags(tag_description()));
		}
		$description .= single_tag_title('', false);
	}elseif ( is_tax() ) { 
        global $wp_query;
        $data = $wp_query->get_queried_object();
        $description = _get_tax_meta($data->term_id, 'description');
        if( !$description ){
            $description = $data->description;
        }
	} elseif (is_archive()) {
		$description = $blog_name . "'" . trim(wp_title('', false)) . "'";
	} elseif (is_search()) {
		$description = $blog_name . ": '" . esc_html($s, 1) . "' 的搜索結果";
	} else {
		$description = $blog_name . "'" . trim(wp_title('', false)) . "'";
	}
	
	echo "<meta name=\"description\" content=\"$description\">\n";
}

function _get_time_ago($ptime) {
	$ptime = strtotime($ptime);
	$etime = time() - $ptime;
	if ($etime < 1) {
		return '刚刚';
	}

	$interval = array(
		12 * 30 * 24 * 60 * 60 => '年前 (' . date('Y-m-d', $ptime) . ')',
		30 * 24 * 60 * 60 => '个月前 (' . date('m-d', $ptime) . ')',
		7 * 24 * 60 * 60 => '周前 (' . date('m-d', $ptime) . ')',
		24 * 60 * 60 => '天前',
		60 * 60 => '小时前',
		60 => '分钟前',
		1 => '秒前',
	);
	foreach ($interval as $secs => $str) {
		$d = $etime / $secs;
		if ($d >= 1) {
			$r = round($d);
			return $r . $str;
		}
	};
}



//评论回应邮件通知
add_action('comment_post', '_comment_mail_notify');
function _comment_mail_notify($comment_id) {
	$admin_notify = '1';// admin 要不要收回复通知 ( '1'=要 ; '0'=不要 )
	$admin_email = get_bloginfo('admin_email');// $admin_email 可改为你指定的 e-mail.
	$comment = get_comment($comment_id);
	$comment_author_email = trim($comment->comment_author_email);
	$parent_id = $comment->comment_parent ? $comment->comment_parent : '';
	global $wpdb;
	if ($wpdb->query("Describe {$wpdb->comments} comment_mail_notify") == '') {
		$wpdb->query("ALTER TABLE {$wpdb->comments} ADD COLUMN comment_mail_notify TINYINT NOT NULL DEFAULT 0;");
	}

	if (($comment_author_email != $admin_email && isset($_POST['comment_mail_notify'])) || ($comment_author_email == $admin_email && $admin_notify == '1')) {
		$wpdb->query("UPDATE {$wpdb->comments} SET comment_mail_notify='1' WHERE comment_ID='$comment_id'");
	}

	$notify = $parent_id ? get_comment($parent_id)->comment_mail_notify : '0';
	$spam_confirmed = $comment->comment_approved;
	if ($parent_id != '' && $spam_confirmed != 'spam' && $notify == '1') {
		$wp_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));// e-mail 发出点, no-reply 可改为可用的 e-mail.
		$to = trim(get_comment($parent_id)->comment_author_email);
		$subject = 'Hi，您在 [' . get_option("blogname") . '] 的留言有人回复啦！';

		$letter = (object) array(
			'author' => trim(get_comment($parent_id)->comment_author),
			'post' => get_the_title($comment->comment_post_ID),
			'comment' => trim(get_comment($parent_id)->comment_content),
			'replyer' => trim($comment->comment_author),
			'reply' => trim($comment->comment_content),
			'link' => htmlspecialchars(get_comment_link($parent_id)),
			'sitename' => get_option('blogname')
		);

		$message = '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse"><tbody><tr><td><table width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse"><tbody><tr><td><table width="100%" cellpadding="0" cellspacing="0" border="0"><tbody><tr><td width="73" align="left" valign="top" style="border-top:1px solid #d9d9d9;border-left:1px solid #d9d9d9;border-radius:5px 0 0 0"></td><td valign="top" style="border-top:1px solid #d9d9d9"><div style="font-size:14px;line-height:10px"><br><br><br><br></div><div style="font-size:18px;line-height:18px;color:#444;font-family:Microsoft Yahei">Hi, ' . $letter->author . '<br><br><br></div><div style="font-size:14px;line-height:22px;color:#444;font-weight:bold;font-family:Microsoft Yahei">您在' . $letter->sitename . '《' . $letter->post . '》的评论：</div><div style="font-size:14px;line-height:10px"><br></div><div style="font-size:14px;line-height:22px;color:#666;font-family:Microsoft Yahei">&nbsp; &nbsp;&nbsp; &nbsp; ' . $letter->comment . '</div><div style="font-size:14px;line-height:10px"><br><br></div><div style="font-size:14px;line-height:22px;color:#5DB408;font-weight:bold;font-family:Microsoft Yahei">' . $letter->replyer . ' 回复您：</div><div style="font-size:14px;line-height:10px"><br></div><div style="font-size:14px;line-height:22px;color:#666;font-family:Microsoft Yahei">&nbsp; &nbsp;&nbsp; &nbsp; ' . $letter->reply . '</div><div style="font-size:14px;line-height:10px"><br><br><br><br></div><div style="text-align:center"><a href="' . $letter->link . '" target="_blank" style="text-decoration:none;color:#fff;display:inline-block;line-height:44px;font-size:18px;background-color:#61B3E6;border-radius:3px;font-family:Microsoft Yahei">&nbsp; &nbsp;&nbsp; &nbsp;点击查看回复&nbsp; &nbsp;&nbsp; &nbsp;</a><br><br></div></td><td width="65" align="left" valign="top" style="border-top:1px solid #d9d9d9;border-right:1px solid #d9d9d9;border-radius:0 5px 0 0"></td></tr><tr><td style="border-left:1px solid #d9d9d9">&nbsp;</td><td align="left" valign="top" style="color:#999"><div style="font-size:8px;line-height:14px"><br><br></div><div style="min-height:1px;font-size:1px;line-height:1px;background-color:#e0e0e0">&nbsp;</div><div style="font-size:12px;line-height:20px;width:425px;font-family:Microsoft Yahei"><br><a href="' . _hui('letter_link_1') . '" target="_blank" style="text-decoration:underline;color:#61B3E6;font-family:Microsoft Yahei">' . _hui('letter_text_1') . '</a><br><a href="' . _hui('letter_link_2') . '" target="_blank" style="text-decoration:underline;color:#61B3E6;font-family:Microsoft Yahei">' . _hui('letter_text_2') . '</a><br>此邮件由' . $letter->sitename . '系统自动发出，请勿回复！</div></td><td style="border-right:1px solid #d9d9d9">&nbsp;</td></tr><tr><td colspan="3" style="border-bottom:1px solid #d9d9d9;border-right:1px solid #d9d9d9;border-left:1px solid #d9d9d9;border-radius:0 0 5px 5px"><div style="min-height:42px;font-size:42px;line-height:42px">&nbsp;</div></td></tr></tbody></table></td></tr><tr><td><div style="min-height:42px;font-size:42px;line-height:42px">&nbsp;</div></td></tr></tbody></table></td></tr></tbody></table>';

		$from = "From: \"" . get_option('blogname') . "\" <$wp_email>";
		$headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
		wp_mail($to, $subject, $message, $headers);
		//echo 'mail to ', $to, '<br/> ' , $subject, $message; // for testing
	}
}

//自动勾选
add_action('comment_form', '_comment_add_checkbox');
function _comment_add_checkbox() {
	echo '<label for="comment_mail_notify" class="checkbox inline hide" style="padding-top:0"><input type="checkbox" name="comment_mail_notify" id="comment_mail_notify" value="comment_mail_notify" checked="checked"/>有人回复时邮件通知我</label>';
}

//文章（包括feed）末尾加版权说明
// add_filter('the_content', '_post_copyright');
function _post_copyright($content) {
	_moloader('mo_is_minicat', false);

	if ( !is_page() && !mo_is_minicat() ) {
		if (_hui('ads_post_footer_s')) {
			$content .= '<p class="orbui-post-footer"><b>AD：</b><strong>【' . _hui('ads_post_footer_pretitle') . '】</strong><a'.(_hui('ads_post_footer_link_blank')?' target="_blank"':'').' href="' . _hui('ads_post_footer_link') . '">' . _hui('ads_post_footer_title') . '</a></p>';
		}

		if( _hui('post_copyright_s') ){
			$content .= '<p class="post-copyright">' . _hui('post_copyright') . '<a href="' . get_bloginfo('url') . '">' . get_bloginfo('name') . '</a> &raquo; <a href="' . get_permalink() . '">' . get_the_title() . '</a></p>';
		}
	}

	return $content;
}





function curPageURL() {
    $pageURL = 'http';

    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") 
    {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["HTTPS"] != "on") 
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } 
    else 
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}








// print_r( _get_tax_meta(21, 'style') );

function _get_tax_meta($id=0, $field=''){
    $ops = get_option( "_taxonomy_meta_$id" );

    if( empty($ops) ){
        return '';
    }

    if( empty($field) ){
        return $ops;
    }

    return isset($ops[$field]) ? $ops[$field] : '';
}


class __Tax_Cat{

    function __construct(){
        add_action( 'category_add_form_fields', array( $this, 'add_tax_field' ) );
        add_action( 'category_edit_form_fields', array( $this, 'edit_tax_field' ) );

        add_action( 'edited_category', array( $this, 'save_tax_meta' ), 10, 2 );
        add_action( 'create_category', array( $this, 'save_tax_meta' ), 10, 2 );
    }
 
    public function add_tax_field(){
        echo '
        	<div class="form-field">
                <label for="term_meta[style]">展示样式</label>
                <select name="term_meta[style]" id="term_meta[style]" class="postform">
                    <option value="default">默认</option>
                    <option value="product">产品</option>
                </select>
                <p class="description">选择后前台展示样式将有所不同</p>
            </div>
            <div class="form-field">
                <label for="term_meta[title]">SEO 标题</label>
                <input type="text" name="term_meta[title]" id="term_meta[title]" />
            </div>
            <div class="form-field">
                <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
                <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" />
            </div>
            <div class="form-field">
                <label for="term_meta[keywords]">SEO 描述（description）</label>
                <textarea name="term_meta[description]" id="term_meta[description]" rows="4" cols="40"></textarea>
            </div>
        ';
    }
 
    public function edit_tax_field( $term ){

        $term_id = $term->term_id;
        $term_meta = get_option( "_taxonomy_meta_$term_id" );

        $meta_style = isset($term_meta['style']) ? $term_meta['style'] : '';

        $meta_title = isset($term_meta['title']) ? $term_meta['title'] : '';
        $meta_keywords = isset($term_meta['keywords']) ? $term_meta['keywords'] : '';
        $meta_description = isset($term_meta['description']) ? $term_meta['description'] : '';
        
        echo '
        	<tr class="form-field">
                <th scope="row">
                    <label for="term_meta[style]">展示样式</label>
                    <td>
                        <select name="term_meta[style]" id="term_meta[style]" class="postform">
                            <option value="default" '. ('default'==$meta_style?'selected="selected"':'') .'>默认</option>
                            <option value="product" '. ('product'==$meta_style?'selected="selected"':'') .'>产品</option>
                        </select>
                        <p class="description">选择后前台展示样式将有所不同</p>
                    </td>
                </th>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[title]">SEO 标题</label>
                    <td>
                        <input type="text" name="term_meta[title]" id="term_meta[title]" value="'. $meta_title .'" />
                    </td>
                </th>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
                    <td>
                        <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" value="'. $meta_keywords .'" />
                    </td>
                </th>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[description]">SEO 描述（description）</label>
                    <td>
                        <textarea name="term_meta[description]" id="term_meta[description]" rows="4">'. $meta_description .'</textarea>
                    </td>
                </th>
            </tr>
        ';
    }
 
    public function save_tax_meta( $term_id ){
 
        if ( isset( $_POST['term_meta'] ) ) {
            
            $term_meta = array();

            $term_meta['style'] = isset ( $_POST['term_meta']['style'] ) ? esc_sql( $_POST['term_meta']['style'] ) : '';
            $term_meta['title'] = isset ( $_POST['term_meta']['title'] ) ? esc_sql( $_POST['term_meta']['title'] ) : '';
            $term_meta['keywords'] = isset ( $_POST['term_meta']['keywords'] ) ? esc_sql( $_POST['term_meta']['keywords'] ) : '';
            $term_meta['description'] = isset ( $_POST['term_meta']['description'] ) ? esc_sql( $_POST['term_meta']['description'] ) : '';

            update_option( "_taxonomy_meta_$term_id", $term_meta );
 
        }
    }
 
}

if( !_hui('seo_off') ) $tax_cat = new __Tax_Cat();




class __Tax_Topic{

    function __construct(){
        add_action( 'topic_add_form_fields', array( $this, 'add_tax_field' ) );
        add_action( 'topic_edit_form_fields', array( $this, 'edit_tax_field' ) );

        add_action( 'edited_topic', array( $this, 'save_tax_meta' ), 10, 2 );
        add_action( 'create_topic', array( $this, 'save_tax_meta' ), 10, 2 );
    }
 
    public function add_tax_field(){

    	echo '
            <div class="form-field">
                <label for="term_meta[image]">展示图片地址</label>
                <input type="text" name="term_meta[image]" id="term_meta[image]" />
                <p class="description">强烈建议尺寸：900*500px。如果你想用其他尺寸，请确保全部的专题图片都是同一尺寸，这样会展示完美。</p>
            </div>
        ';
        
        if( !_hui('seo_off') ){
        	echo '
	            <div class="form-field">
	                <label for="term_meta[title]">SEO 标题</label>
	                <input type="text" name="term_meta[title]" id="term_meta[title]" />
	            </div>
	            <div class="form-field">
	                <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
	                <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" />
	            </div>
	            <div class="form-field">
	                <label for="term_meta[keywords]">SEO 描述（description）</label>
	                <textarea name="term_meta[description]" id="term_meta[description]" rows="4" cols="40"></textarea>
	            </div>
	        ';
        }
    }
 
    public function edit_tax_field( $term ){

        $term_id = $term->term_id;
        $term_meta = get_option( "_taxonomy_meta_$term_id" );

        $meta_title = isset($term_meta['image']) ? $term_meta['image'] : '';
        echo '
	        <tr class="form-field">
	            <th scope="row">
	                <label for="term_meta[image]">展示图片地址</label>
	                <td>
	                    <input type="text" name="term_meta[image]" id="term_meta[image]" value="'. $meta_title .'" />
	                    <p class="description">强烈建议尺寸：900*500px。如果你想用其他尺寸，请确保全部的专题图片都是同一尺寸，这样会展示完美。</p>
	                </td>
	            </th>
	        </tr>
	    ';

        if( !_hui('seo_off') ){
        	$meta_title = isset($term_meta['title']) ? $term_meta['title'] : '';
	        $meta_keywords = isset($term_meta['keywords']) ? $term_meta['keywords'] : '';
	        $meta_description = isset($term_meta['description']) ? $term_meta['description'] : '';
	        
	        echo '
	            <tr class="form-field">
	                <th scope="row">
	                    <label for="term_meta[title]">SEO 标题</label>
	                    <td>
	                        <input type="text" name="term_meta[title]" id="term_meta[title]" value="'. $meta_title .'" />
	                    </td>
	                </th>
	            </tr>
	            <tr class="form-field">
	                <th scope="row">
	                    <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
	                    <td>
	                        <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" value="'. $meta_keywords .'" />
	                    </td>
	                </th>
	            </tr>
	            <tr class="form-field">
	                <th scope="row">
	                    <label for="term_meta[description]">SEO 描述（description）</label>
	                    <td>
	                        <textarea name="term_meta[description]" id="term_meta[description]" rows="4">'. $meta_description .'</textarea>
	                    </td>
	                </th>
	            </tr>
	        ';
	    }
    }
 
    public function save_tax_meta( $term_id ){
 
        if ( isset( $_POST['term_meta'] ) ) {
            
            $term_meta = array();

	        $term_meta['image'] = isset ( $_POST['term_meta']['image'] ) ? esc_sql( $_POST['term_meta']['image'] ) : '';
            
            if( !_hui('seo_off') ){
	            $term_meta['title'] = isset ( $_POST['term_meta']['title'] ) ? esc_sql( $_POST['term_meta']['title'] ) : '';
	            $term_meta['keywords'] = isset ( $_POST['term_meta']['keywords'] ) ? esc_sql( $_POST['term_meta']['keywords'] ) : '';
	            $term_meta['description'] = isset ( $_POST['term_meta']['description'] ) ? esc_sql( $_POST['term_meta']['description'] ) : '';
	        }

            update_option( "_taxonomy_meta_$term_id", $term_meta );
 
        }
    }
 
}

$tax_cat = new __Tax_Topic();



class __Tax_Tag{

    function __construct(){
        add_action( 'post_tag_add_form_fields', array( $this, 'add_tax_field' ) );
        add_action( 'post_tag_edit_form_fields', array( $this, 'edit_tax_field' ) );

        add_action( 'edited_post_tag', array( $this, 'save_tax_meta' ), 10, 2 );
        add_action( 'create_post_tag', array( $this, 'save_tax_meta' ), 10, 2 );
    }
 
    public function add_tax_field(){
        echo '
            <div class="form-field">
                <label for="term_meta[title]">SEO 标题</label>
                <input type="text" name="term_meta[title]" id="term_meta[title]" />
            </div>
            <div class="form-field">
                <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
                <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" />
            </div>
            <div class="form-field">
                <label for="term_meta[keywords]">SEO 描述（description）</label>
                <textarea name="term_meta[description]" id="term_meta[description]" rows="4" cols="40"></textarea>
            </div>
        ';
    }
 
    public function edit_tax_field( $term ){

        $term_id = $term->term_id;
        $term_meta = get_option( "_taxonomy_meta_$term_id" );

        $meta_title = isset($term_meta['title']) ? $term_meta['title'] : '';
        $meta_keywords = isset($term_meta['keywords']) ? $term_meta['keywords'] : '';
        $meta_description = isset($term_meta['description']) ? $term_meta['description'] : '';
        
        echo '
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[title]">SEO 标题</label>
                    <td>
                        <input type="text" name="term_meta[title]" id="term_meta[title]" value="'. $meta_title .'" />
                    </td>
                </th>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[keywords]">SEO 关键字（keywords）</label>
                    <td>
                        <input type="text" name="term_meta[keywords]" id="term_meta[keywords]" value="'. $meta_keywords .'" />
                    </td>
                </th>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_meta[description]">SEO 描述（description）</label>
                    <td>
                        <textarea name="term_meta[description]" id="term_meta[description]" rows="4">'. $meta_description .'</textarea>
                    </td>
                </th>
            </tr>
        ';
    }
 
    public function save_tax_meta( $term_id ){
 
        if ( isset( $_POST['term_meta'] ) ) {
            
            $term_meta = array();

            $term_meta['title'] = isset ( $_POST['term_meta']['title'] ) ? esc_sql( $_POST['term_meta']['title'] ) : '';
            $term_meta['keywords'] = isset ( $_POST['term_meta']['keywords'] ) ? esc_sql( $_POST['term_meta']['keywords'] ) : '';
            $term_meta['description'] = isset ( $_POST['term_meta']['description'] ) ? esc_sql( $_POST['term_meta']['description'] ) : '';

            update_option( "_taxonomy_meta_$term_id", $term_meta );
 
        }
    }
 
}
 
if( !_hui('seo_off') ) $tax_tag = new __Tax_Tag();



function hui_breadcrumbs(){
    if( !is_single() ) return false;
    $categorys = get_the_category();
    if( $categorys ){
	    $category = $categorys[0];
	    return '当前位置：<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a> <small>></small> '.get_category_parents($category->term_id, true, ' <small>></small> ').(!_hui('breadcrumbs_single_text')?get_the_title():'正文');
    }else{
    	return false;
    }
}



function hui_get_post_like($class='', $pid='', $text=''){
    $pid = $pid ? $pid : get_the_ID();
    $text = $text ? $text : __('赞', 'haoui');
    $like = get_post_meta( $pid, 'like', true );
    if( hui_is_my_like($pid) ) {
        $class .= ' actived';
    }
    return '<a href="javascript:;" etap="like" class="'.$class.'" data-pid="'.$pid.'"><i class="fa fa-thumbs-o-up"></i>'.$text.'(<span>'.($like ? $like : 0).'</span>)</a>';
}

function hui_is_my_like($pid=''){
    if( !is_user_logged_in() ) return false;
    $pid = $pid ? $pid : get_the_ID();
    $likes = get_user_meta( get_current_user_id(), 'like-posts', true );
    if( $likes && !is_array($likes) ){
    	$likes = unserialize($likes);
    }
    if( !is_array($likes) ){
    	$likes = array();
    }
    return in_array($pid, $likes) ? true : false;
}



if( _hui('search_title') && !is_admin() ){
	add_filter( 'posts_search', 'hui_search_by_title_only', 500, 2 );
}
function hui_search_by_title_only( $search ){
    global $wpdb, $wp_query;

    if ( empty( $search ) )
        return $search;
 
    $q = $wp_query->query_vars;    

    $n = ! empty( $q['exact'] ) ? '' : '%';
 
    $search =
    $searchand = '';
 
    foreach ( (array) $q['search_terms'] as $term ) {
        $term = esc_sql( $wpdb->esc_like( $term ) );
        $search .= "{$searchand}($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
        $searchand = ' AND ';
    }
 
    if ( ! empty( $search ) ) {
        $search = " AND ({$search}) ";
        if ( ! is_user_logged_in() )
            $search .= " AND ($wpdb->posts.post_password = '') ";
    }
 
    return $search;
}








function hui_ip(){
	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
	    return $_SERVER["HTTP_CLIENT_IP"];
	} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
	    return $_SERVER["HTTP_X_FORWARDED_FOR"];
	} elseif (!empty($_SERVER["REMOTE_ADDR"])){
	    return $_SERVER["REMOTE_ADDR"];
	}
	return "none";
}

function hui_v_captcha($Ticket, $Randstr){
	$CaptchaAppId = _hui('yzm_appid');
	$AppSecretKey = _hui('yzm_appsecretkey');
	$secretId     = _hui('yzm_secretid');
	$secretKey    = _hui('yzm_secretkey');

	if( !$CaptchaAppId || !$AppSecretKey || !$secretId || !$secretKey || !$Ticket || !$Randstr ){
		return false;
	}

	$CaptchaAppId = (int) $CaptchaAppId;

	$host         = "captcha.tencentcloudapi.com";
	$service      = "captcha";
	$version      = "2019-07-22";
	$action       = "DescribeCaptchaResult";
	$timestamp    = time();

	$payload = array(
		'CaptchaType'  => 9,
		'Ticket'       => $Ticket,
		'Randstr'      => $Randstr,
		'UserIp'       => hui_ip(),
		'CaptchaAppId' => $CaptchaAppId,
		'AppSecretKey' => $AppSecretKey,
    );

	$algorithm = "TC3-HMAC-SHA256";

	// step 1: build canonical request string
	$httpRequestMethod = "POST";
	$canonicalUri = "/";
	$canonicalQueryString = "";
	$canonicalHeaders = "content-type:application/json\n"."host:".$host."\n";
	$signedHeaders = "content-type;host";
	

	$hashedRequestPayload = hash("SHA256", json_encode($payload));
	$canonicalRequest = $httpRequestMethod."\n"
	    .$canonicalUri."\n"
	    .$canonicalQueryString."\n"
	    .$canonicalHeaders."\n"
	    .$signedHeaders."\n"
	    .$hashedRequestPayload;
	// echo $canonicalRequest.PHP_EOL;

	// step 2: build string to sign
	$date = gmdate("Y-m-d", $timestamp);
	$credentialScope = $date."/".$service."/tc3_request";
	$hashedCanonicalRequest = hash("SHA256", $canonicalRequest);
	$stringToSign = $algorithm."\n"
	    .$timestamp."\n"
	    .$credentialScope."\n"
	    .$hashedCanonicalRequest;
	// echo $stringToSign.PHP_EOL;

	// step 3: sign string
	$secretDate = hash_hmac("SHA256", $date, "TC3".$secretKey, true);
	$secretService = hash_hmac("SHA256", $service, $secretDate, true);
	$secretSigning = hash_hmac("SHA256", "tc3_request", $secretService, true);
	$signature = hash_hmac("SHA256", $stringToSign, $secretSigning);
	// echo $signature.PHP_EOL;

	// step 4: build authorization
	$authorization = $algorithm
	    ." Credential=".$secretId."/".$credentialScope
	    .", SignedHeaders=content-type;host, Signature=".$signature;
	// echo $authorization.PHP_EOL;
	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://'.$host);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpRequestMethod);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: '.$authorization,
        'Content-Type: application/json',
        'Host: '.$host,
        'X-TC-Action: '.$action,
        'X-TC-Version: '.$version,
        'X-TC-Timestamp: '.$timestamp,
    ));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	$output = curl_exec($ch);
	curl_close($ch);

	$output = json_decode($output);

	// print_r($output);

	// https://cloud.tencent.com/document/product/1110/36926#3.-.E8.BE.93.E5.87.BA.E5.8F.82.E6.95.B0
	if( isset($output->Response) && isset($output->Response->CaptchaCode) && $output->Response->CaptchaCode == 1 ){
		return true;
	}
	return false;
}



// MD5 FILENAME
if ( _hui('newfilename') && !function_exists('_new_filename') ) :

    function _new_filename($filename) {
        $info = pathinfo($filename);
        $ext = empty($info['extension']) ? '' : '.' . $info['extension'];
        $name = basename($filename, $ext);
        return substr(md5($name), 0, 15) . $ext;
    }
    add_filter('sanitize_file_name', '_new_filename', 10);

endif;




function hui_is_post_new(){
	if( !_hui('list_post_new') || !_hui('list_post_new_limit') ){
		return false;
	}

	global $post;

	date_default_timezone_set('PRC');

	$tm = (int)_hui('list_post_new_limit');

	if( strtotime(get_the_date('Y-m-d H:i:s')) >= time() - 3600*$tm ){
		return true;
	}

	return false;
}



function hui_is_post_modified(){
	if( !_hui('single_update_tip_on') ){
		return false;
	}

	global $post;

	date_default_timezone_set('PRC');

	$mt = strtotime(get_the_modified_date('Y-m-d H:i:s'));
	$tm = (int)_hui('single_update_tip_time');

	if( $mt > strtotime(get_the_date('Y-m-d H:i:s')) && (time() - $mt < 3600*$tm) ){
		return true;
	}

	return false;
}




add_filter('upload_mimes', 'hui_upload_mimes');
function hui_upload_mimes($mimes = array()) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['ico'] = 'image/x-icon';
    $mimes['webp'] = 'image/webp';
    return $mimes;
}



add_action('comment_unapproved_to_approved', 'tb_comment_approved');
function tb_comment_approved($comment){
    if ( !_hui('kill_comment_s') && _hui('comment_approved_mail_s') && is_email($comment->comment_author_email) ){
        $title = _hui('comment_approved_mail_title');
        $title = str_replace("%SITENAME%", get_bloginfo('name'), $title);
        
        $body = _hui('comment_approved_mail_body');
        $body = str_replace("\n", '<br>', $body);
        $body = str_replace("%SITENAME%", get_bloginfo('name'), $body);
        $body = str_replace("%SITELINK%", get_bloginfo('url'), $body);
        $body = str_replace("%POSTNAME%", get_the_title($comment->comment_post_ID), $body);
        $body = str_replace("%POSTLINK%", get_permalink($comment->comment_post_ID), $body);
        $body = str_replace("%COMMENT%", strip_tags($comment->comment_content), $body);
        $body = str_replace("%COMMENTLINK%", get_comment_link($comment->comment_ID), $body);

        @wp_mail($comment->comment_author_email, $title, $body, "Content-Type: text/html; charset=UTF-8");
    }
}



add_filter('login_headerurl', 'tb_login_headerurl');
function tb_login_headerurl() {
    return get_bloginfo('url');
}

add_filter('login_headertext', 'tb_headertitle');
function tb_headertitle() {
    return get_bloginfo('name');
}

add_action("login_head", "tb_login_head");
function tb_login_head() {
    if( _hui('login_logo_src') ){
    	echo '<style type="text/css">body.login #login h1 a {background:url('. _hui('login_logo_src') .') no-repeat 50% 50% transparent;height: 60px;width: 200px;padding:0;}</style>';
    }
}



add_action( 'init', 'tb_topic_tax', 0 );
function tb_topic_tax(){
    $name = '专题';
    register_taxonomy( 'topic', array('post'), array(   
        'labels' => array(
            'singular_name'              => $name,
            'menu_name'                  => $name,  
            'name'                       => $name, 
            'search_items'               => '搜索'.$name,
            'popular_items'              => '热门'.$name,
            'all_items'                  => '所有'.$name,
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => '编辑', 
            'update_item'                => '更新',
            'add_new_item'               => '添加'.$name,
            'new_item_name'              => '名称',
            'separate_items_with_commas' => '按逗号分开',
            'add_or_remove_items'        => '添加或删除'.$name,  
            'choose_from_most_used'      => '从经常使用的'.$name.'中选择',  
        ),
        'show_ui'               => true,  
        'show_admin_column'     => true,
        'hierarchical'          => true,  
        'query_var'             => true,  
        'update_count_callback' => '_update_post_term_count',
        'rewrite'               => array( 'slug'  => 'topic', 'with_front'  => false )
    ));
}

