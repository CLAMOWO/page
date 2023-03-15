<?php
class WPJAM_Admin{
	public static function get_screen_id(){
		static $screen_id;

		if(!isset($screen_id)){
			if(isset($_POST['screen_id'])){
				$screen_id	= $_POST['screen_id'];
			}elseif(isset($_POST['screen'])){
				$screen_id	= $_POST['screen'];	
			}else{
				$ajax_action	= $_REQUEST['action'] ?? '';

				if($ajax_action == 'fetch-list'){
					$screen_id	= $_GET['list_args']['screen']['id'];
				}elseif($ajax_action == 'inline-save-tax'){
					$screen_id	= 'edit-'.sanitize_key($_POST['taxonomy']);
				}elseif(in_array($ajax_action, ['get-comments', 'replyto-comment'])){
					$screen_id	= 'edit-comments';
				}else{
					$screen_id	= false;
				}
			}

			if($screen_id){
				if('-network' === substr($screen_id, -8)){
					if(!defined('WP_NETWORK_ADMIN')){
						define('WP_NETWORK_ADMIN', true);
					}
				}elseif('-user' === substr($screen_id, -5)){
					if(!defined('WP_USER_ADMIN')){
						define('WP_USER_ADMIN', true);
					}
				}
			}
		}

		return $screen_id;	
	}

	public static function init($plugin_page){
		$GLOBALS['plugin_page']	= $plugin_page;

		do_action('wpjam_admin_init');

		if($plugin_page){
			WPJAM_Menu_Page::render(false);
		}

		$screen_id	= self::get_screen_id();
			
		if($screen_id == 'upload'){
			$GLOBALS['hook_suffix']	= $screen_id;

			set_current_screen();
		}else{
			set_current_screen($screen_id);
		}
	}

	public static function on_admin_menu(){
		do_action('wpjam_admin_init');

		WPJAM_Menu_Page::render();
	}

	public static function on_admin_notices(){
		$errors	= get_screen_option('admin_errors') ?: [];

		foreach($errors as $error){
			echo '<div class="notice notice-'.$error['type'].' is-dismissible"><p>'.$error['message'].'</p></div>';
		}

		WPJAM_Admin_AJAX::delete_notice();

		$modal		= '';
		$notices	= wpjam_user_notice()->data;

		if(current_user_can('manage_options')){
			$notices	= array_merge($notices, wpjam_admin_notice()->data);
		}

		if($notices){
			uasort($notices, function($n, $m){ return $m['time'] <=> $n['time']; });
		}

		foreach($notices as $key => $notice){
			$notice = wp_parse_args($notice, [
				'type'		=> 'info',
				'class'		=> 'is-dismissible',
				'admin_url'	=> '',
				'notice'	=> '',
				'title'		=> '',
				'modal'		=> 0,
			]);

			$admin_notice	= trim($notice['notice']);

			if($notice['admin_url']){
				$admin_notice	.= $notice['modal'] ? "\n\n" : ' ';
				$admin_notice	.= '<a style="text-decoration:none;" href="'.add_query_arg(['notice_key'=>$key], home_url($notice['admin_url'])).'">点击查看<span class="dashicons dashicons-arrow-right-alt"></span></a>';
			}

			$admin_notice	= wpautop($admin_notice).wpjam_get_page_button('delete_notice', ['data'=>['notice_key'=>$key]]);

			if($notice['modal']){
				if(empty($modal)){	// 弹窗每次只显示一条
					$modal	= $admin_notice;
					$title	= $notice['title'] ?: '消息';

					echo '<div id="notice_modal" class="hidden" data-title="'.esc_attr($title).'">'.$modal.'</div>';
				}
			}else{
				echo '<div class="notice notice-'.$notice['type'].' '.$notice['class'].'">'.$admin_notice.'</div>';
			}
		}
	}

	public static function on_admin_init(){
		$plugin_page	= $_POST['plugin_page'] ?? null;

		self::init($plugin_page);	
	}

	public static function on_current_screen($screen=null){
		if(wpjam_get_current_var('plugin_page')){
			WPJAM_Menu_Page::load($screen);
		}else{
			WPJAM_Builtin_Page::load($screen);
		}
	}

	public static function on_admin_enqueue_scripts(){
		$screen	= get_current_screen();
		
		if($screen->base == 'customize'){
			return;
		}elseif($screen->base == 'post'){
			wp_enqueue_media(['post'=>wpjam_get_admin_post_id()]);
		}else{
			wp_enqueue_media();
		}

		$ver	= get_plugin_data(WPJAM_BASIC_PLUGIN_FILE)['Version'];
		$static	= WPJAM_BASIC_PLUGIN_URL.'static';

		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');

		wp_enqueue_style('wpjam-style',		$static.'/style.css',	['wp-color-picker', 'editor-buttons'], $ver);
		wp_enqueue_script('wpjam-script',	$static.'/script.js',	['jquery', 'thickbox', 'wp-backbone', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-ui-autocomplete', 'wp-color-picker'], $ver);
		wp_enqueue_script('wpjam-form',		$static.'/form.js',		['wpjam-script', 'mce-view'], $ver);

		$setting	= [
			'screen_base'	=> $screen->base,
			'screen_id'		=> $screen->id,
			'post_type'		=> $screen->post_type,
			'taxonomy'		=> $screen->taxonomy,
		];

		// print_r($screen);

		$params	= array_except($_REQUEST, wp_removable_query_args());
		$params	= array_except($params, ['page', 'tab', '_wp_http_referer', '_wpnonce']);
		$params	= array_filter($params, 'is_populated');

		if($GLOBALS['plugin_page']){
			$setting['plugin_page']	= $GLOBALS['plugin_page'];
			$setting['current_tab']	= $GLOBALS['current_tab'] ?? null;
			$setting['admin_url']	= $GLOBALS['current_admin_url'] ?? '';

			$query_data		= wpjam_get_plugin_page_setting('query_data') ?: [];
			$_query_data	= wpjam_get_current_tab_setting('query_data') ?: [];
			$query_data		= array_merge($query_data, $_query_data);

			if($query_data){
				$params	= array_except($params, array_keys($query_data));

				$setting['query_data']	= array_map('sanitize_textarea_field', $query_data);
			}
		}else{
			$args	= [];

			foreach(['post_type', 'taxonomy'] as $query_key){
				if($screen->$query_key){
					$args[$query_key]	= wpjam_get_parameter($query_key);
				}
			}

			$args	= array_filter($args);

			$setting['admin_url']	= set_url_scheme('http://'.$_SERVER['HTTP_HOST'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

			if($args){
				$setting['admin_url']	= add_query_arg($args, $setting['admin_url']);
			}
		}

		if($params){
			if(isset($params['data'])){
				$params['data']	= urldecode($params['data']);
			}

			$params	= map_deep($params, 'sanitize_textarea_field');
		}

		$setting['params']	= $params ?: new stdClass();

		if(!empty($GLOBALS['wpjam_list_table'])){
			$setting['list_table']	= $screen->get_option('wpjam_list_table');
		}

		wp_localize_script('wpjam-script', 'wpjam_page_setting', $setting);
	}

	public static function on_admin_action_update(){
		// 为了实现多个页面使用通过 option 存储。这个可以放弃了，使用 AJAX + Redirect
		// 注册设置选项，选用的是：'admin_action_' . $_REQUEST['action'] hook，
		// 因为在这之前的 admin_init 检测 $plugin_page 的合法性

		$referer_origin	= parse_url(wpjam_get_referer());

		if(!empty($referer_origin['query'])){
			$referer_args	= wp_parse_args($referer_origin['query']);

			if(!empty($referer_args['page'])){
				self::init($referer_args['page']);	// 实现多个页面使用同个 option 存储。
			}
		}
	}
}

class WPJAM_Admin_Load extends WPJAM_Register{
	public function is_available(...$args){
		if($this->type == 'plugin_page'){
			$plugin_page	= $args[0];
			$current_tab	= $args[1];

			if($this->plugin_page){
				if(is_callable($this->plugin_page)){
					return call_user_func($this->plugin_page, $plugin_page, $current_tab);
				}

				if(!wpjam_compare($plugin_page, (array)$this->plugin_page)){
					return false;
				}
			}

			if($this->current_tab){
				if(!$current_tab || !wpjam_compare($current_tab, (array)$this->current_tab)){
					return false;
				}
			}else{
				if($current_tab){
					return false;
				}
			}
		}elseif($this->type == 'builtin_page'){
			$screen	= $args[0];

			if($this->screen && is_callable($this->screen)){
				return call_user_func($this->screen, $screen);
			}

			foreach(['base', 'post_type', 'taxonomy'] as $key){
				if($this->$key){
					if(!wpjam_compare($screen->$key, (array)$this->$key)){
						return false;
					}
				}
			}
		}

		return true;
	}

	public function load(...$args){
		if($this->is_available(...$args)){
			if($this->page_file){
				$files	= (array)$this->page_file;

				foreach($files as $file){
					if(is_file($file)){
						include $file;
					}
				}
			}

			if($this->callback && is_callable($this->callback)){
				call_user_func($this->callback, ...$args);
			}
		}
	}

	public static function loads($type, ...$args){
		foreach(self::get_registereds(['type'=>$type]) as $object){
			$object->load(...$args);
		}
	}

	public static function create($type, ...$args){
		$args	= is_array($args[0]) ? $args[0] : $args[1];
		return self::register(array_merge($args, ['type'=>$type]));
	}

	protected static function get_config($key){
		if($key == 'orderby'){
			return true;
		}
	}
}

class WPJAM_Admin_AJAX extends WPJAM_Args{
	public function callback(){
		$callback	= $this->callback;

		if(!$callback || !is_callable($callback)){
			wp_die('0', 400);
		}

		wpjam_send_json(wpjam_call($callback));
	}

	public static function add($name, $callback){
		$object	= new self(compact('callback'));

		add_action('wp_ajax_'.$name, [$object, 'callback']);
	}

	public static function page_action(){
		$action	= wpjam_get_parameter('page_action', ['method'=>'POST']);
		$object	= WPJAM_Page_Action::get($action);

		if($object){
			return $object->callback();
		}

		wpjam_page_action_compact($action);
	}

	public static function upload(){
		$name	= wpjam_get_parameter('file_name', ['method'=>'POST']);

		return wpjam_upload($name, $relative=true);
	}

	public static function query(){
		$data_type	= wpjam_get_parameter('data_type', ['method'=>'POST']);
		$object		= $data_type ? wpjam_get_data_type_object($data_type) : null;
		$items		= [];

		if($object){
			$args	= wpjam_get_parameter('query_args', ['method'=>'POST', 'default'=>[]]);
			$items	= $object->query_items($args) ?: [];

			if(is_wp_error($items)){
				$items	= [['label'=>$items->get_error_message(), 'value'=>$items->get_error_code()]];
			}
		}

		return ['items'=>$items];
	}

	public static function delete_notice(){
		$key = wpjam_get_data_parameter('notice_key');

		if($key){
			wpjam_user_notice()->delete($key);

			if(current_user_can('manage_options')){
				wpjam_admin_notice()->delete($key);
			}

			wpjam_send_json(['notice_key'=>$key]);
		}
	}
}

class WPJAM_Page_Action extends WPJAM_Register{
	protected function create_nonce(){
		return wp_create_nonce(wpjam_get_nonce_action($this->name));
	}

	protected function verify_nonce(){
		$nonce	= wpjam_get_ajax_nonce();
		$action	= wpjam_get_nonce_action($this->name);

		return wp_verify_nonce($nonce, $action);
	}

	public function parse_args(){
		return wp_parse_args($this->args, ['response'=>$this->name]);
	}

	public function current_user_can($type=''){
		$capability	= $this->capability ?? ($type ? 'manage_options' : 'read');

		return current_user_can($capability, $this->name);
	}

	public function callback(){
		$action_type	= wpjam_get_ajax_action_type();

		if($action_type == 'form'){
			$form	= $this->get_form();
			$width	= $this->width ?: 720;
			$modal	= $this->modal_id ?: 'tb_modal';
			$title	= wpjam_get_parameter('page_title',	['method'=>'POST']);

			if(!$title){
				foreach(['page_title', 'button_text', 'submit_text'] as $key){
					if(!empty($this->$key) && !is_array($this->$key)){
						$title	= $this->$key;
						break;
					}
				}
			}

			return ['form'=>$form, 'width'=>$width, 'modal_id'=>$modal, 'page_title'=>$title];
		}

		if(!$this->verify_nonce()){
			return new WP_Error('invalid_nonce');
		}

		if(!$this->current_user_can($action_type)){
			return new WP_Error('bad_authentication');
		}

		$response	= ['type'=>$this->response];

		if($action_type == 'submit'){
			$submit_name	= wpjam_get_parameter('submit_name',	['method'=>'POST', 'default'=>$this->name]);
			$submit_button	= $this->get_submit_button($submit_name);

			if(!$submit_button){
				return new WP_Error('invalid_submit_button');
			}

			$callback	= $submit_button['callback'] ?: $this->callback;

			$response['type']	= $submit_button['response'];
		}else{
			$submit_name	= null;
			$callback		= $this->callback;
		}

		if(!$callback || !is_callable($callback)){
			return new WP_Error('invalid_callback');
		}

		if($this->validate){
			$data	= wpjam_get_data_parameter();
			$fields	= $this->get_fields();

			if($fields){
				$data	= wpjam_fields($fields)->validate($data);
			}

			$result	= wpjam_try($callback, $data, $this->name, $submit_name);
		}else{
			$result	= wpjam_try($callback, $this->name, $submit_name);
		}

		if(is_array($result)){
			$response	= array_merge($response, $result);
		}elseif($result === false || is_null($result)){
			$response	= new WP_Error('invalid_callback', ['返回错误']);
		}elseif($result !== true){
			if($this->response == 'redirect'){
				$response['url']	= $result;
			}else{
				$response['data']	= $result;
			}
		}

		return apply_filters('wpjam_ajax_response', $response);
	}

	public function get_button($args=[]){
		if(!$this->current_user_can()){
			return '';
		}

		$args	= array_merge($this->args, $args);
		$class	= $args['class'] ?? 'button-primary large';

		if(!empty($args['page_title'])){
			$title	= $args['page_title'];
		}else{
			$title	= $args['button_text'] ?? '保存';
		}

		$tag	= $args['tag'] ?? 'a';

		return wpjam_wrap_tag($args['button_text'], $tag, [
			'title'	=> $title,
			'class'	=> $class.' wpjam-button',
			'style'	=> $args['style'] ?? '',
			'data'	=> [
				'action'	=> $this->name,
				'nonce'		=> $this->create_nonce(),
				'title'		=> $title,
				'data'		=> $args['data'] ?? [],
				'direct'	=> $args['direct'] ?? false,
				'confirm'	=> $args['confirm'] ?? false
			]
		]);
	}

	public function get_fields(){
		$fields	= $this->fields;

		if($fields && is_callable($fields)){
			$fields	= wpjam_try($fields, $this->name);
		}

		return $fields ?: [];
	}

	public function get_data(){
		$data		= $this->data ?: [];
		$callback	= $this->data_callback;

		if($callback && is_callable($callback)){
			$_data	= wpjam_try($callback, $this->name, $this->get_fields());

			return array_merge($data, $_data);
		}

		return $data;
	}

	public function get_form(){
		if(!$this->current_user_can()){
			return '';
		}

		$fields	= $this->get_fields();
		$data	= $this->get_data();
		$fields	= wpjam_fields($fields, array_merge($this->args, ['data'=>$data, 'echo'=>false]));
		$button	= $this->get_submit_button(null, true);

		return wpjam_wrap_tag($fields.$button, 'form', [
			'method'	=> 'post',
			'action'	=> '#',
			'id'		=> $this->form_id ?: 'wpjam_form',
			'data'		=> [
				'action'	=> $this->name,
				'nonce'		=> $this->create_nonce()
			]
		]);
	}

	protected function get_submit_button($name=null, $render=false){
		if($name){
			$button	= $this->get_submit_button();

			return $button[$name] ?? [];
		}

		$button	= $this->submit_text ?? $this->page_title;

		if(!$button){
			return $render ? '' : [];
		}

		if(is_callable($button)){
			$button	= call_user_func($button, $this->name);
		}

		if(!is_array($button)){
			$button	= [$this->name => $button];
		}

		foreach($button as $name => &$item){
			$item	= is_array($item) ? $item : ['text'=>$item];
			$item	= wp_parse_args($item, ['response'=>$this->response, 'class'=>'primary', 'callback'=>'']);

			if($render){
				$item	= get_submit_button($item['text'], $item['class'], $name, false);
			}
		}

		return $render ? wpjam_wrap_tag(implode("\n", $button), 'p', ['submit']) : $button;
	}

	public static function get_nonce_action($key){	// 兼容
		return wpjam_get_nonce_action($key);
	}
}