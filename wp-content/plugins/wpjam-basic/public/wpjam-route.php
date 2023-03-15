<?php
function wpjam_load($hooks, $callback){
	if(!$callback){
		return;
	}

	if(!wpjam_is_callable($callback)){
		return;
	}

	$todo	= [];

	foreach((array)$hooks as $hook){
		if(!did_action($hook)){
			$todo[]	= $hook;
		}
	}

	if(empty($todo)){
		call_user_func($callback);
	}elseif(count($todo) == 1){
		add_action(current($todo), $callback);
	}else{
		$object	= new WPJAM_Args([
			'hooks'		=> $todo, 
			'callback'	=> $callback,
			'invoke'	=> function(){
				foreach($this->hooks as $hook){
					if(!did_action($hook)){
						return;
					}
				}

				call_user_func($this->callback);
			}
		]);

		foreach($todo as $hook){
			add_action($hook, [$object, 'invoke']);
		}
	}
}

function wpjam_try($callback, ...$args){
	if(wpjam_is_callable($callback)){
		try{
			$result	= call_user_func_array($callback, $args);

			if(is_wp_error($result)){
				throw new WPJAM_Exception($result);
			}

			return $result;
		}catch(Exception $e){
			throw $e;
		}
	}
}

function wpjam_map($value, $callback, ...$args){
	if(wpjam_is_callable($callback)){
		if($value && is_array($value)){
			foreach($value as &$item){
				$item	= wpjam_try($callback, $item, ...$args);
			}
		}
	}

	return $value;
}

function wpjam_call($callback, ...$args){
	if(wpjam_is_callable($callback)){
		try{
			return call_user_func_array($callback, $args);
		}catch(WPJAM_Exception $e){
			return $e->get_wp_error();
		}catch(Exception $e){
			return new WP_Error($e->getCode(), $e->getMessage());
		}
	}
}

function wpjam_is_callable($callback){
	if(!is_callable($callback)){
		trigger_error('invalid_callback'.var_export($callback, true));
		return false;
	}

	return true;
}

function wpjam_hooks($hooks){
	if(is_callable($hooks)){
		$hooks	= call_user_func($hooks);
	}

	if(!$hooks || !is_array($hooks)){
		return;
	}

	if(is_array(current($hooks))){
		foreach($hooks as $hook){
			add_filter(...$hook);
		}
	}else{
		add_filter(...$hooks);
	}
}

function wpjam_get_current_priority($filter=null){
	$filter	= $filter ?: current_filter();
	$filter	= $GLOBALS['wp_filter'][$filter] ?? null;

	return $filter ? $filter->current_priority() : null;
}

function wpjam_autoload(){
	foreach(get_declared_classes() as $class){
		if(is_subclass_of($class, 'WPJAM_Register') && method_exists($class, 'autoload')){
			call_user_func([$class, 'autoload']);
		}
	}
}

function wpjam_activation(){
	$actives = get_option('wpjam-actives', null);

	if(is_array($actives)){
		foreach($actives as $active){
			if(is_array($active) && isset($active['hook'])){
				add_action($active['hook'], $active['callback']);
			}else{
				add_action('wp_loaded', $active);
			}
		}

		update_option('wpjam-actives', []);
	}elseif(is_null($actives)){
		update_option('wpjam-actives', []);
	}
}

function wpjam_register_activation($callback, $hook=null){
	$actives	= get_option('wpjam-actives', []);
	$actives[]	= $hook ? compact('hook', 'callback') : $callback;

	update_option('wpjam-actives', $actives);
}

function wpjam_ob_get_contents($callback, ...$args){
	ob_start();

	call_user_func_array($callback, $args);

	return ob_get_clean();
}

function wpjam_parse_method($model, $method, &$is_static=false, $id=null){
	if(is_object($model)){
		$object	= $model;
		$model	= get_class($object);
	}else{
		$object	= null;
	}

	if(!method_exists($model, $method)){
		return new WP_Error('undefined_method', [$model.'->'.$method.'()']);
	}

	$reflection	= new ReflectionMethod($model, $method);
	$is_public	= $reflection->isPublic();
	$is_static	= $reflection->isStatic();

	if($is_static){
		return $is_public ? [$model, $method] : $reflection->getClosure();
	}

	if(is_null($object)){
		if(is_null($id)){
			return new WP_Error('instance_required', '对象才能调用实例方法');
		}

		$object	= wpjam_get_model_object($model, $id);

		if(is_wp_error($object)){
			return $object;
		}
	}

	return $is_public ? [$object, $method] : $reflection->getClosure($object);
}

function wpjam_get_model_object($model, $id){
	if(!method_exists($model, 'get_instance')){
		return new WP_Error('undefined_method', [$model.'->get_instance()']);
	}

	$object	= call_user_func([$model, 'get_instance'], $id);

	return $object ?: new WP_Error('invalid_id', [$model]);
}

function wpjam_call_method($object, $method, ...$args){
	$callback	= wpjam_parse_method($model, $method);

	if(is_wp_error($callback)){
		return $callback;
	}

	return call_user_func($callback, ...$args);
}

function wpjam_call_model_method($model, $method, $id, ...$args){
	$callback	= wpjam_parse_method($model, $method, $is_static, $id);

	if(is_wp_error($callback)){
		return $callback;
	}

	if($is_static){
		return call_user_func($callback, $id, ...$args);
	}else{
		return call_user_func($callback, ...$args);
	}
}

function wpjam_register_route_module($name, $args){
	return WPJAM_Route::create($name, $args);
}

function wpjam_is_module($module='', $action=''){
	$current_module	= wpjam_get_current_module();

	if($module){
		if($action && $action != wpjam_get_current_action()){
			return false;
		}

		return $module == $current_module;
	}else{
		return $current_module ? true : false;
	}
}

function wpjam_get_query_var($key, $wp=null){
	$wp	= $wp ?: $GLOBALS['wp'];

	return $wp->query_vars[$key] ?? null;
}

function wpjam_get_current_module($wp=null){
	return wpjam_get_query_var('module', $wp);
}

function wpjam_get_current_action($wp=null){
	return wpjam_get_query_var('action', $wp);
}

function wpjam_get_current_user($required=false){
	$user	= wpjam_get_current_var('user', $isset);

	if(!$isset){
		$user	= apply_filters('wpjam_current_user', null);

		wpjam_set_current_var('user', $user);
	}

	if($required){
		if(is_null($user)){
			return new WP_Error('bad_authentication');
		}
	}else{
		if(is_wp_error($user)){
			return null;
		}
	}

	return $user;
}

function wpjam_get_current_commenter(){
	$commenter	= wp_get_current_commenter();

	if(empty($commenter['comment_author_email'])){
		return new WP_Error('bad_authentication');
	}

	return $commenter;
}

function wpjam_json_encode($data){
	return WPJAM_JSON::encode($data, JSON_UNESCAPED_UNICODE);
}

function wpjam_json_decode($json, $assoc=true){
	return WPJAM_JSON::decode($json, $assoc);
}

function wpjam_send_json($data=[], $status_code=null){
	WPJAM_JSON::send($data, $status_code);
}

function wpjam_send_error_json($errcode, $errmsg=''){
	wpjam_send_json(new WP_Error($errcode, $errmsg));
}

function wpjam_grant($method=null, ...$args){
	$object	= WPJAM_Grant::instance();

	return $method ? wpjam_try([$object, $method], ...$args) : $object;
}

function wpjam_quota($appid){
	$object	= WPJAM_Cache_Items::instance_exists($appid);

	if(!$object){
		$object	= new WPJAM_Cache_Items($appid, ['group'=>'api_times', 'prefix'=>wpjam_date('Y-m-d')]);
		$object	= WPJAM_Cache_Items::add_instance($appid, $object);
	}

	return $object;
}

function wpjam_error($data=null){
	$object	= WPJAM_Error::instance();

	return isset($data) ? $object->parse($data) : $object;
}

function wpjam_parse_error($data){
	return wpjam_error($data);
}

function wpjam_register_error_setting($code, $message, $modal=[]){
	return WPJAM_Error::add($code, $message, $modal);
}

function wpjam_register_json($name, $args=[]){
	return WPJAM_JSON::register($name, $args);
}

function wpjam_register_api($name, $args=[]){
	return wpjam_register_json($name, $args);
}

function wpjam_get_json_object($name){
	return WPJAM_JSON::get($name);
}

function wpjam_parse_json_module($module){
	$object	= wpjam_get_current_var('json_module');

	if(is_null($object)){
		$object	= wpjam_set_current_var('json_module', new WPJAM_JSON_Module());
	}

	$type	= array_get($module, 'type');
	$args	= array_get($module, 'args', []);

	if(!is_array($args)){
		$args	= wpjam_parse_shortcode_attr(stripslashes_deep($args), 'module');
	}

	return $object->parse($type, $args);
}

function wpjam_get_current_json($return='name'){
	$json	= wpjam_get_current_var('json');

	return $return == 'object' ? wpjam_get_json_object($json) : $json;
}

function wpjam_is_json_request(){
	if(get_option('permalink_structure')){
		if(preg_match("/\/api\/(.*)\.json/", $_SERVER['REQUEST_URI'])){ 
			return true;
		}
	}else{
		if(isset($_GET['module']) && $_GET['module'] == 'json'){
			return true;
		}
	}

	return false;
}

function wpjam_register_source($name, $callback, $query_args=['source_id']){
	return WPJAM_Source::create($name, $callback, $query_args);
}

function wpjam_source($name){
	$object	= WPJAM_Source::get($name);

	return $object ? $object->callback() : null;
}

// wpjam_register_config($key, $value)
// wpjam_register_config($name, $args)
// wpjam_register_config($args)
// wpjam_register_config($name, $callback])
function wpjam_register_config(...$args){
	return WPJAM_Config::create(...$args);
}

function wpjam_get_config(){
	return WPJAM_Config::get_data();
}

function wpjam_get_parameter($name, $args=[]){
	$object	= wpjam_get_current_var('parameter_object');

	if(is_null($object)){
		$object	= wpjam_set_current_var('parameter_object', new WPJAM_Parameter());
	}

	return $object->get_value($name, $args);
}

function wpjam_get_post_parameter($name, $args=[]){
	return wpjam_get_parameter($name, array_merge($args, ['method'=>'POST']));
}

function wpjam_get_request_parameter($name, $args=[]){
	return wpjam_get_parameter($name, array_merge($args, ['method'=>'REQUEST']));
}

function wpjam_get_data_parameter($name='', $args=[]){
	return wpjam_get_parameter($name, array_merge($args, ['data_parameter'=>true]));
}

function wpjam_method_allow($method, $send=true){
	if($_SERVER['REQUEST_METHOD'] != strtoupper($method)){
		$wp_error = new WP_Error('method_not_allow', '接口不支持 '.$_SERVER['REQUEST_METHOD'].' 方法，请使用 '.$method.' 方法！');

		return $send ? wpjam_send_json($wp_error): $wp_error;
	}

	return true;
}

function wpjam_http_request($url, $args=[], $err_args=[], &$headers=null){
	$object	= wpjam_get_current_var('request_object');

	if(is_null($object)){
		$object	= wpjam_set_current_var('request_object', new WPJAM_Request());
	}

	try{
		return $object->request($url, $args, $err_args, $headers);
	}catch(WPJAM_Exception $e){
		return $e->get_wp_error();
	}
}

function wpjam_remote_request($url, $args=[], $err_args=[], &$headers=null){
	return wpjam_http_request($url, $args, $err_args, $headers);
}

wpjam_load_extends(WPJAM_BASIC_PLUGIN_DIR.'components', [
	'hook'		=> 'wpjam_loaded',
	'priority'	=> 0,
]);

wpjam_register_extend_option('wpjam-extends', WPJAM_BASIC_PLUGIN_DIR.'extends', [
	'sitewide'	=> true,
	'ajax'		=> false,
	'hook'		=> 'plugins_loaded',
	'priority'	=> 1,
	'menu_page'	=> [
		'parent'		=> 'wpjam-basic',
		'menu_title'	=> '扩展管理',
		'order'			=> 3,
		'function'		=> 'option',
	]
]);

wpjam_load_extends(get_template_directory().'/extends', [
	'hierarchical'	=>	true,
	'hook'			=> 'plugins_loaded',
	'priority'		=> 0,
]);

wpjam_register_error_setting('invalid_menu_page',	'页面%s「%s」未定义。');
wpjam_register_error_setting('invalid_item_key',	'「%s」已存在，无法%s。');
wpjam_register_error_setting('invalid_page_key',	'无效的%s页面。');
wpjam_register_error_setting('invalid_name',		'%s不能为纯数字。');
wpjam_register_error_setting('invalid_nonce',		'验证失败，请刷新重试。');
wpjam_register_error_setting('invalid_code',		'验证码错误。');
wpjam_register_error_setting('invalid_password',	'两次输入的密码不一致。');
wpjam_register_error_setting('incorrect_password',	'密码错误。');
wpjam_register_error_setting('bad_authentication',	'无权限');
wpjam_register_error_setting('value_required',		'%s的值为空或无效。');
wpjam_register_error_setting('undefined_method',	['WPJAM_Error', 'callback']);
wpjam_register_error_setting('quota_exceeded',		['WPJAM_Error', 'callback']);

add_action('plugins_loaded', 'wpjam_activation', 0);

add_action('init',	'wpjam_autoload');

add_filter('register_post_type_args',	['WPJAM_Post_Type', 'filter_register_args'], 999, 2);
add_filter('register_taxonomy_args',	['WPJAM_Taxonomy', 'filter_register_args'], 999, 3);

if(version_compare($GLOBALS['wp_version'], '6.0', '<')){
	add_action('registered_post_type',	['WPJAM_Post_Type', 'on_registered'], 1, 2);
	add_action('registered_taxonomy',	['WPJAM_Taxonomy', 'on_registered'], 1, 3);
}

if(wpjam_is_json_request()){
	ini_set('display_errors', 0);

	remove_filter('the_title', 'convert_chars');

	remove_action('init', 'wp_widgets_init', 1);
	remove_action('init', 'maybe_add_existing_user_to_blog');
	remove_action('init', 'check_theme_switched', 99);

	remove_action('plugins_loaded', 'wp_maybe_load_widgets', 0);
	remove_action('plugins_loaded', 'wp_maybe_load_embeds', 0);
	remove_action('plugins_loaded', '_wp_customize_include');
	remove_action('plugins_loaded', '_wp_theme_json_webfonts_handler');

	remove_action('wp_loaded', '_custom_header_background_just_in_time');
	remove_action('wp_loaded', '_add_template_loader_filters');
}
