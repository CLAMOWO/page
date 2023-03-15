<?php
if(!function_exists('get_screen_option')){
	function get_screen_option($option, $key=null){
		$screen	= get_current_screen();

		if($screen){
			if(in_array($option, ['post_type', 'taxonomy'])){
				return $screen->$option ?? null;
			}

			return $screen->get_option($option, $key);
		}

		return null;
	}
}

function wpjam_add_screen_item($option, ...$args){
	$screen	= get_current_screen();

	if(!$screen){
		return;
	}

	$items	= $screen->get_option($option) ?: [];

	if(count($args) >= 2){
		$key	= $args[0];
		
		if(isset($items[$key])){
			return;	
		}

		$items[$key]	= $args[1];
	}else{
		$items[]		= $args[0];
	}

	$screen->add_option($option, $items);
}

function wpjam_register_builtin_page_load(...$args){
	return WPJAM_Admin_Load::create('builtin_page', ...$args);
}

function wpjam_register_plugin_page_load(...$args){
	return WPJAM_Admin_Load::create('plugin_page', ...$args);
}

function wpjam_builtin_page_load($screen){
	do_action('wpjam_builtin_page_load', $screen->base, $screen);	// 放弃ing

	WPJAM_Admin_Load::loads('builtin_page', $screen);
}

function wpjam_plugin_page_load($plugin_page, $current_tab){
	do_action('wpjam_plugin_page_load', $plugin_page, $current_tab);	// 放弃ing

	WPJAM_Admin_Load::loads('plugin_page', $plugin_page, $current_tab);
}

function wpjam_add_admin_ajax($name, $callback){
	return WPJAM_Admin_AJAX::add($name, $callback);
}

function wpjam_generate_query_data($query_args){
	$query_data	= [];

	foreach($query_args as $query_arg){
		$query_data[$query_arg]	= wpjam_get_data_parameter($query_arg);
	}

	return $query_data;
}

function wpjam_admin_add_error($message='', $type='success'){
	if(is_wp_error($message)){
		$message	= $message->get_error_message();
		$type		= 'error';
	}

	if($message && $type){
		wpjam_add_screen_item('admin_errors', ['message'=>$message, 'type'=>$type]);
	}
}

function wpjam_get_page_summary($type='page'){
	return get_screen_option($type.'_summary');
}

function wpjam_set_page_summary($summary, $type='page', $append=true){
	add_screen_option($type.'_summary', ($append ? get_screen_option($type.'_summary') : '').$summary);
}

function wpjam_set_plugin_page_summary($summary, $append=true){
	wpjam_set_page_summary($summary, 'page', $append);
}

function wpjam_set_builtin_page_summary($summary, $append=true){
	wpjam_set_page_summary($summary, 'page', $append);
}

function wpjam_get_plugin_page_setting($key='', $using_tab=false){
	$object = wpjam_get_current_var('plugin_page');

	if($object){
		$is_tab	= $object->function == 'tab';

		if(str_ends_with($key, '_name')){
			$using_tab	= $is_tab;
			$default	= $GLOBALS['plugin_page'];
		}else{
			$using_tab	= $using_tab ? $is_tab : false;
			$default	= null;
		}

		if($using_tab){
			$object	= wpjam_get_current_var('current_tab');
		}	
	}

	if(!$object){
		return null;
	}

	return $key ? ($object->$key ?: $default) : $object->to_array();
}

function wpjam_get_plugin_page_type(){
	return wpjam_get_plugin_page_setting('function');
}

function wpjam_get_current_tab_setting($key=''){
	return wpjam_get_plugin_page_setting($key, true);
}

function wpjam_admin_tooltip($text, $tooltip){
	return '<div class="wpjam-tooltip">'.$text.'<div class="wpjam-tooltip-text">'.wpautop($tooltip).'</div></div>';
}

function wpjam_get_referer(){
	$referer	= wp_get_original_referer();
	$referer	= $referer ?: wp_get_referer();
	$removable	= array_merge(wp_removable_query_args(), ['_wp_http_referer', 'action', 'action2', '_wpnonce']);

	return remove_query_arg($removable, $referer);	
}

function wpjam_register_page_action($name, $args){
	return WPJAM_Page_Action::register($name, $args);
}

function wpjam_unregister_page_action($name){
	WPJAM_Page_Action::unregister($name);
}

function wpjam_get_page_button($name, $args=[]){
	$object	= WPJAM_Page_Action::get($name);

	return $object ? $object->get_button($args) : '';
}

function wpjam_get_nonce_action($key){
	$prefix	= $GLOBALS['plugin_page'] ?? $GLOBALS['current_screen']->id;

	return $prefix.'-'.$key;
}

function wpjam_get_ajax_nonce(){
	return wpjam_get_parameter('_ajax_nonce',	['method'=>'POST']);
}

function wpjam_get_ajax_action_type(){
	return wpjam_get_parameter('action_type',	['method'=>'POST', 'sanitize_callback'=>'sanitize_key']);
}

function wpjam_register_list_table($name, $args=[]){
	return wpjam_add_item('list_table', $name, $args);
}

function wpjam_register_list_table_action($name, $args){
	return WPJAM_List_Table_Action::register($name, $args);
}

function wpjam_unregister_list_table_action($name){
	WPJAM_List_Table_Action::unregister($name);
}

function wpjam_register_list_table_column($name, $field){
	return WPJAM_List_Table_Column::register($name, $field);
}

function wpjam_unregister_list_table_column($name, $field=[]){
	WPJAM_List_Table_Column::unregister($name, $field);
}

function wpjam_register_plugin_page($name, $args){
	return WPJAM_Plugin_Page::register($name, $args);
}

function wpjam_register_plugin_page_tab($name, $args){
	return WPJAM_Tab_Page::register($name, $args);
}

function wpjam_get_list_table_setting($key){
	return isset($GLOBALS['wpjam_list_table']) ? $GLOBALS['wpjam_list_table']->$key : null;
}

function wpjam_get_list_table_filter_link($filters, $title, $class=''){
	return $GLOBALS['wpjam_list_table']->get_filter_link($filters, $title, $class);
}

function wpjam_get_list_table_row_action($name, $args=[]){
	return $GLOBALS['wpjam_list_table']->get_row_action($name, $args);
}

function wpjam_get_list_table_prev_action($name){
	return $GLOBALS['wpjam_list_table']->get_prev_action($name);
}

function wpjam_render_list_table_column_items($id, $items, $args=[]){
	return $GLOBALS['wpjam_list_table']->render_column_items($id, $items, $args);
}

function wpjam_register_dashboard($name, $args){
	return wpjam_add_item('dashboard', $name, $args);
}

function wpjam_register_dashboard_widget($name, $args){
	return wpjam_add_item('dashboard_widget', $name, $args);
}

function wpjam_get_admin_post_id(){
	if(isset($_GET['post'])){
		return (int)$_GET['post'];
	}elseif(isset($_POST['post_ID'])){
		return (int)$_POST['post_ID'];
	}else{
		return 0;
	}
}

function wpjam_admin_update_metadata($meta_type, $id, $data, $defaults){	// 优化
	if($meta_type  == 'post'){
		$post_data	= [];

		foreach(get_post($id, ARRAY_A) as $post_key => $old_value){
			$value	= array_pull($data, $post_key);

			if(!is_null($value)){
				unset($defaults[$post_key]);

				if($old_value != $value){
					$post_data[$post_key]	= $value;
				}
			}
		}

		if($post_data){
			$object	= wpjam_get_post_object($id);
			$result	= $object ? $object->save($post_data) : null;

			if(is_wp_error($result) || empty($data)){
				return $result;
			}
		}

		if(empty($data)){
			return true;
		}
	}

	if(count($data) == 1 && isset($data['meta_input']) && count($defaults) == 1 && isset($defaults['meta_input'])){
		$data		= $data['meta_input'];
		$defaults	= $defaults['meta_input'];
	}

	return wpjam_update_metadata($meta_type, $id, $data, $defaults);
}

function wpjam_line_chart($counts_array, $labels, $args=[], $type = 'Line'){
	WPJAM_Chart::line($counts_array, $labels, $args, $type);
}

function wpjam_bar_chart($counts_array, $labels, $args=[]){
	wpjam_line_chart($counts_array, $labels, $args, 'Bar');
}

function wpjam_donut_chart($counts, $args=[]){
	WPJAM_Chart::donut($counts, $args);
}

function wpjam_get_chart_parameter($key){
	return WPJAM_Chart::get_parameter($key);
}

function wpjam_get_current_screen_id(){
	if(did_action('current_screen')){
		return get_current_screen()->id;
	}elseif(wp_doing_ajax()){
		return WPJAM_Admin::get_screen_id();
	}
}

function wpjam_get_admin_menu_hook($type='action'){
	if(is_network_admin()){
		$prefix	= 'network_';
	}elseif(is_user_admin()){
		$prefix	= 'user_';
	}else{
		$prefix	= '';
	}

	if($type == 'action'){
		return $prefix.'admin_menu';
	}else{
		return 'wpjam_'.$prefix.'pages';
	}
}

add_action('plugins_loaded', function(){	// 内部的 hook 使用 优先级 9，因为内嵌的 hook 优先级要低
	wpjam_register_page_action('delete_notice', [
		'button_text'	=> '删除',
		'tag'			=> 'span',
		'class'			=> 'hidden delete-notice',
		'callback'		=> ['WPJAM_Admin_AJAX', 'delete_notice'],
		'direct'		=> true,
	]);

	if($GLOBALS['pagenow'] == 'options.php'){
		add_action('admin_action_update',	['WPJAM_Admin', 'on_admin_action_update'], 9);
	}elseif(wp_doing_ajax()){
		if(wpjam_get_current_screen_id()){
			add_action('admin_init',	['WPJAM_Admin', 'on_admin_init'], 9);

			wpjam_add_admin_ajax('wpjam-page-action',	['WPJAM_Admin_AJAX', 'page_action']);
			wpjam_add_admin_ajax('wpjam-upload',		['WPJAM_Admin_AJAX', 'upload']);
			wpjam_add_admin_ajax('wpjam-query',			['WPJAM_Admin_AJAX', 'query']);
		}
	}else{
		$menu_action	= wpjam_get_admin_menu_hook('action');

		add_action($menu_action,			['WPJAM_Admin', 'on_admin_menu'], 9);
		add_action('admin_notices',			['WPJAM_Admin', 'on_admin_notices']);
		add_action('admin_enqueue_scripts', ['WPJAM_Admin', 'on_admin_enqueue_scripts'], 9);
		add_action('print_media_templates', ['WPJAM_Field',	'print_media_templates'], 9);

		add_filter('set-screen-option', function($status, $option, $value){
			trigger_error('filter::set-screen-option -- delete 2023-06-01');
			return isset($_GET['page']) ? $value : $status;
		}, 9, 3);
	}

	add_action('current_screen',	['WPJAM_Admin', 'on_current_screen'], 9);
});
