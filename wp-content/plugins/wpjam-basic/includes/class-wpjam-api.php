<?php
class WPJAM_Route extends WPJAM_Register{
	public function callback($wp){
		$callback	= $this->callback;

		if(!$callback && $this->model && method_exists($this->model, 'redirect')){
			$callback	= [$this->model, 'redirect'];
		}

		if($callback && is_callable($callback)){
			$action	= wpjam_get_current_action($wp);

			return call_user_func($callback, $action, $this->name);
		}
	}

	public function add_rewrite_rule(){
		$rewrite_rule	= $this->get_arg('rewrite_rule');

		if($rewrite_rule && is_callable($rewrite_rule)){
			$rewrite_rule	= call_user_func($rewrite_rule, $this->name);
		}

		if($rewrite_rule){
			$rewrite_rule	= is_array(current($rewrite_rule)) ? $rewrite_rule : [$rewrite_rule];
			
			foreach($rewrite_rule as $rule){
				if(is_array($rule)){
					$rule[0]	= $GLOBALS['wp_rewrite']->root.$rule[0];

					add_rewrite_rule(...$rule);
				}
			}
		}
	}

	public static function on_parse_request($wp){
		$wp->query_vars	= wpjam_parse_query_vars($wp->query_vars);

		$module	= wpjam_get_current_module($wp);

		if($module){
			$object	= self::get($module);

			if($object){
				$object->callback($wp);
			}

			remove_action('template_redirect',	'redirect_canonical');

			add_filter('template_include',	[self::class, 'filter_template_include']);
		}
	}

	public static function filter_template_include($template){
		$module	= get_query_var('module');
		$action	= get_query_var('action');

		$file	= $action ? $action.'.php' : 'index.php';
		$file	= STYLESHEETPATH.'/template/'.$module.'/'.$file;
		$file	= apply_filters('wpjam_template', $file, $module, $action);

		return is_file($file) ? $file : $template;
	}

	public static function create($name, $args){
		if(!is_array($args) || wp_is_numeric_array($args)){
			$args	= is_callable($args) ? ['callback'=>$args] : (array)$args;
		}

		$object	= self::register($name, $args);

		if($object->get_arg('rewrite_rule')){
			wpjam_load('init',  [$object, 'add_rewrite_rule']);
		}

		return $object;
	}

	public static function filter_determine_current_user($user_id){
		if(empty($user_id)){
			$wpjam_user	= wpjam_get_current_user();

			if($wpjam_user && !empty($wpjam_user['user_id'])){
				return $wpjam_user['user_id'];
			}
		}

		return $user_id;
	}

	public static function filter_current_commenter($commenter){
		if(empty($commenter['comment_author_email'])){
			$wpjam_user	= wpjam_get_current_user();

			if($wpjam_user && !empty($wpjam_user['user_email'])){
				$commenter['comment_author_email']	= $wpjam_user['user_email'];
				$commenter['comment_author']		= $wpjam_user['nickname'];
			}
		}

		return $commenter;
	}

	public static function filter_pre_avatar_data($args, $id_or_email){
		$user_id 	= 0;
		$avatarurl	= '';
		$email		= '';

		if(is_object($id_or_email) && isset($id_or_email->comment_ID)){
			$id_or_email	= get_comment($id_or_email);
		}

		if(is_numeric($id_or_email)){
			$user_id	= $id_or_email;
		}elseif(is_string($id_or_email)){
			$email		= $id_or_email;
		}elseif($id_or_email instanceof WP_User){
			$user_id	= $id_or_email->ID;
		}elseif($id_or_email instanceof WP_Post){
			$user_id	= $id_or_email->post_author;
		}elseif($id_or_email instanceof WP_Comment){
			$user_id	= $id_or_email->user_id;
			$email		= $id_or_email->comment_author_email;
			$avatarurl	= get_comment_meta($id_or_email->comment_ID, 'avatarurl', true);
		}

		if(!$avatarurl && $user_id){
			$avatarurl	= get_user_meta($user_id, 'avatarurl', true);
		}

		if($avatarurl){
			$url	= wpjam_get_thumbnail($avatarurl, [$args['width'], $args['height']]);

			return array_merge($args, ['found_avatar'=>true,	'url'=>$url,]);
		}

		if($user_id){
			$args['user_id']	= $user_id;
		}

		if($email){
			$args['email']		= $email;
		}

		return $args;
	}

	public static function autoload(){
		$GLOBALS['wp']->add_query_var('module');
		$GLOBALS['wp']->add_query_var('action');
		$GLOBALS['wp']->add_query_var('term_id');

		self::create('json',	['model'=>'WPJAM_JSON']);
		self::create('txt',		['model'=>'WPJAM_Verify_TXT']);

		add_action('wpjam_api',		['WPJAM_JSON', 'register_default']);
		add_action('parse_request',	[self::class, 'on_parse_request']);

		// add_filter('determine_current_user',	[self::class, 'filter_determine_current_user']);
		add_filter('wp_get_current_commenter',	[self::class, 'filter_current_commenter']);
		add_filter('pre_get_avatar_data',		[self::class, 'filter_pre_avatar_data'], 10, 2);
	}
}

class WPJAM_JSON extends WPJAM_Register{
	private $response;

	public function response(){
		$this->validate();
		$this->source();

		$this->response	= [
			'errcode'		=> 0,
			'current_user'	=> wpjam_try('wpjam_get_current_user', $this->pull('auth'))
		];

		if($_SERVER['REQUEST_METHOD'] != 'POST' && !str_ends_with($this->name, '.config')){
			foreach(['page_title', 'share_title', 'share_image'] as $key){
				$this->response[$key]	= (string)$this->$key;
			}
		}

		if($this->modules){
			$modules	= $this->modules;
			$modules	= wp_is_numeric_array($modules) ? $modules : [$modules];

			foreach($modules as $module){
				$result	= wpjam_parse_json_module($module);

				$this->merge_result($result);
			}
		}else{
			$result		= null;
			$callback	= $this->pull('callback');

			if($callback){
				if(is_callable($callback)){
					$result	= wpjam_try($callback, $this->args, $this->name);
				}
			}elseif($this->template){
				if(is_file($this->template)){
					$result	= include $this->template;
				}
			}else{
				$result	= $this->args;
			}

			$this->merge_result($result);
		}

		$response	= apply_filters('wpjam_json', $this->response, $this->args, $this->name);

		if($_SERVER['REQUEST_METHOD'] != 'POST' && !str_ends_with($this->name, '.config')){
			if(empty($response['page_title'])){
				$response['page_title']		= html_entity_decode(wp_get_document_title());
			}

			if(empty($response['share_title'])){
				$response['share_title']	= html_entity_decode(wp_get_document_title());
			}

			if(!empty($response['share_image'])){
				$response['share_image']	= wpjam_get_thumbnail($response['share_image'], '500x400');
			}
		}

		return $response;
	}

	private function validate(){
		if(isset($_GET['access_token']) || !is_super_admin()){
			$appid	= wpjam_get_parameter('appid');

			if($this->grant){
				$token	= wpjam_get_parameter('access_token', ['required'=>true]);
				$item 	= wpjam_grant('validate_token', $token);
				$appid	= $item['appid'];
			}

			$object	= wpjam_quota($appid);

			if($this->quota && $object->get($this->name) > $this->quota){
				wpjam_send_error_json('quota_exceeded', ['API 调用']);
			}

			$object->increment($this->name);
		}

		if($this->capability && !current_user_can($this->capability)){
			wpjam_send_error_json('bad_authentication');
		}
	}

	private function source(){
		$name	= wpjam_get_parameter('source');

		return $name ? wpjam_source($name) : null;
	}

	private function merge_result($result){
		if(is_wp_error($result)){
			self::send($result);
		}

		if(is_array($result)){
			foreach(['page_title', 'share_title', 'share_image'] as $key){
				if(!empty($this->response[$key]) && isset($result[$key])){
					unset($result[$key]);
				}
			}

			$this->response	= array_merge($this->response, $result);
		}
	}

	public static function redirect($action){
		if(!wpjam_doing_debug()){
			header('X-Content-Type-Options: nosniff');

			rest_send_cors_headers(false); 

			if('OPTIONS' === $_SERVER['REQUEST_METHOD']){
				status_header(403);
				exit;
			}

			$type	= wp_is_jsonp_request() ? 'javascript' : 'json';

			@header('Content-Type: application/'.$type.'; charset='.get_option('blog_charset'));
		}

		if(!str_starts_with($action, 'mag.')){
			return;
		}

		$name	= wpjam_remove_prefix($action, 'mag.');
		$name	= wpjam_remove_prefix($name, 'mag.');	// 兼容
		$name	= str_replace('/', '.', $name);
		$name	= apply_filters('wpjam_json_name', $name);

		wpjam_set_current_var('json', $name);

		$current_user	= wpjam_get_current_user();

		if($current_user && !empty($current_user['user_id'])){
			wp_set_current_user($current_user['user_id']);
		}

		do_action('wpjam_api', $name);

		$object	= self::get($name);

		if($object){
			self::send(wpjam_call([$object, 'response']));
		}else{
			self::send(['errcode'=>'invalid_api', 'errmsg'=>'接口未定义！']);
		}
	}

	public static function send($data=[], $status_code=null){
		$data	= wpjam_error($data);
		$result	= self::encode($data);

		if(!headers_sent() && !wpjam_doing_debug()){
			if(!is_null($status_code)){
				status_header($status_code);
			}

			if(wp_is_jsonp_request()){
				$result	= '/**/' . $_GET['_jsonp'] . '(' . $result . ')';

				$type	= 'javascript';
			}else{
				$type	= 'json';
			}

			@header('Content-Type: application/'.$type.'; charset='.get_option('blog_charset'));
		}

		echo $result;

		exit;
	}

	public static function encode($data){
		return wp_json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	public static function decode($json, $assoc=true){
		$json	= wpjam_strip_control_characters($json);

		if(!$json){
			return new WP_Error('json_decode_error', 'JSON 内容不能为空！');
		}

		$result	= json_decode($json, $assoc);

		if(is_null($result)){
			$result	= json_decode(stripslashes($json), $assoc);

			if(is_null($result)){
				if(wpjam_doing_debug()){
					print_r(json_last_error());
					print_r(json_last_error_msg());
				}
				trigger_error('json_decode_error '. json_last_error_msg()."\n".var_export($json,true));
				return new WP_Error('json_decode_error', json_last_error_msg());
			}
		}

		return $result;
	}

	public static function register_default($json){
		if(self::get($json)){
			return;
		}

		if($json == 'post.list'){
			$modules	= [];
			$post_type	= wpjam_get_parameter('post_type');
			$args		= ['post_type'=>$post_type, 'action'=>'list', 'posts_per_page'=>10, 'output'=>'posts'];
			$modules[]	= ['type'=>'post_type',	'args'=>array_filter($args)];

			if($post_type && is_string($post_type)){
				foreach(get_object_taxonomies($post_type, 'objects') as $taxonomy => $tax_object){
					if($tax_object->hierarchical && $tax_object->public){
						$modules[]	= ['type'=>'taxonomy',	'args'=>['taxonomy'=>$taxonomy, 'hide_empty'=>0]];
					}
				}
			}

			self::register($json,	['modules'=>$modules]);
		}elseif($json == 'post.calendar'){
			self::register($json,	['modules'=>['type'=>'post_type',	'args'=>['action'=>'calendar', 'output'=>'posts']]]);
		}elseif($json == 'post.get'){
			self::register($json,	['modules'=>['type'=>'post_type',	'args'=>['action'=>'get', 'output'=>'post']]]);
		}elseif($json == 'media.upload'){
			self::register($json,	['modules'=>['type'=>'media',	'args'=>['media'=>'media']]]);
		}elseif($json == 'token.grant'){
			self::register($json,	['modules'=>['type'=>'token'],	'quota'=>1000]);
		}elseif($json == 'token.validate'){
			self::register($json,	['quota'=>10,	'grant'=>true]);
		}elseif($json == 'site.config'){
			self::register($json,	['modules'=>['type'=>'config']]);
		}
	}

	public static function get_rewrite_rule(){
		return [
			['api/([^/]+)/(.*?)\.json?$',	['module'=>'json', 'action'=>'mag.$matches[1].$matches[2]'], 'top'],
			['api/([^/]+)\.json?$', 		'index.php?module=json&action=$matches[1]', 'top'],
		];
	}

	public static function __callStatic($method, $args){
		if(in_array($method, ['parse_post_list_module', 'parse_post_get_module'])){
			$args	= $args[0] ?? [];
			$action	= str_replace(['parse_post_', '_module'], '', $method);

			return wpjam_parse_json_module(['type'=>'post_type', 'args'=>array_merge($args, ['action'=>$action])]);
		}
	}
}

class WPJAM_JSON_Module extends WPJAM_Args{
	public function parse($type, $args=null){
		if(!is_null($args)){
			$this->args	= $args;
		}

		if(method_exists($this, 'parse_'.$type)){
			return wpjam_call([$this, 'parse_'.$type]);
		}

		return $args;
	}

	public function parse_post_type(){
		$action	= $this->pull('action');

		if(!$action){
			return;
		}

		$wp	= $GLOBALS['wp'];

		if(isset($wp->raw_query_vars)){
			$wp->query_vars		= $wp->raw_query_vars;
		}else{
			$wp->raw_query_vars	= $wp->query_vars;
		}

		if($action == 'list'){
			return $this->parse_post_list();
		}elseif($action == 'calendar'){
			return $this->parse_post_calendar();
		}elseif($action == 'get'){
			return $this->parse_post_get();
		}elseif($action == 'upload'){
			return $this->parse_media('post_type');
		}
	}

	protected function parse_query_vars($query_vars){
		$post_type	= $query_vars['post_type'] ?? '';

		if(is_string($post_type) && strpos($post_type, ',') !== false){
			$query_vars['post_type']	= wp_parse_list($post_type);
		}

		$taxonomies	= $post_type ? get_object_taxonomies($post_type) : get_taxonomies(['public'=>true]);
		$taxonomies	= array_diff($taxonomies, ['post_format']);

		foreach($taxonomies as $taxonomy){	// taxonomy 参数处理，同时支持 $_GET 和 $query_vars 参数
			if($taxonomy == 'category'){
				if(empty($query_vars['cat'])){
					foreach(['category_id', 'cat_id'] as $cat_key){
						$term_id	= (int)wpjam_get_parameter($cat_key);

						if($term_id){
							$query_vars['cat']	= $term_id;
							break;
						}
					}
				}
			}else{
				$query_key	= wpjam_get_taxonomy_query_key($taxonomy);
				$term_id	= (int)wpjam_get_parameter($query_key);

				if($term_id){
					$query_vars[$query_key]	= $term_id;
				}
			}
		}

		$term_id	= (int)wpjam_get_parameter('term_id');
		$taxonomy	= wpjam_get_parameter('taxonomy');

		if($term_id && $taxonomy){
			$query_vars['term_id']	= $term_id;
			$query_vars['taxonomy']	= $taxonomy;
		}

		return wpjam_parse_query_vars($query_vars);
	}

	protected function parse_output($query_vars){
		$post_type	= $query_vars['post_type'] ?? '';

		if($post_type && is_string($post_type)){
			$object	= wpjam_get_post_type_object($post_type);
			$plural	= $object ? $object->plural : '';
			
			return $plural ?: $post_type.'s';
		}

		return 'posts';
	}

	/* 规则：
	** 1. 分成主的查询和子查询（$query_args['sub']=1）
	** 2. 主查询支持 $_GET 参数 和 $_GET 参数 mapping
	** 3. 子查询（sub）只支持 $query_args 参数
	** 4. 主查询返回 next_cursor 和 total_pages，current_page，子查询（sub）没有
	** 5. $_GET 参数只适用于 post.list
	** 6. term.list 只能用 $_GET 参数 mapping 来传递参数
	*/
	public function parse_post_list(){
		$output	= $this->pull('output');
		$sub	= $this->pull('sub');

		$is_main_query	= !$sub;	// 子查询不支持 $_GET 参数，置空之前要把原始的查询参数存起来

		if($is_main_query){
			$wp			= $GLOBALS['wp'];
			$query_vars	= array_merge($wp->query_vars, $this->args);

			$number	= (int)wpjam_get_parameter('number',	['fallback'=>'posts_per_page']);
			$offset	= (int)wpjam_get_parameter('offset');

			if($number && $number != -1){
				$query_vars['posts_per_page']	= $number > 100 ? 100 : $number;
			}

			if($offset){
				$query_vars['offset']	= $offset;
			}

			$orderby	= $query_vars['orderby'] ?? 'date';
			$use_cursor	= empty($query_vars['paged']) && empty($query_vars['s']) && !is_array($orderby) && in_array($orderby, ['date', 'post_date']);

			if($use_cursor){
				foreach(['cursor', 'since'] as $key){
					$query_vars[$key]	= (int)wpjam_get_parameter($key);

					if($query_vars[$key]){
						$query_vars['ignore_sticky_posts']	= true;
					}
				}
			}

			$query_vars	= $wp->query_vars = $this->parse_query_vars($query_vars);

			$wp->query_posts();

			$wp_query	= $GLOBALS['wp_query'];
		}else{
			$query_vars	= wpjam_parse_query_vars($this->args);
			$wp_query	= new WP_Query($query_vars);
		}

		$posts_json	= $_posts = [];

		while($wp_query->have_posts()){
			$wp_query->the_post();

			$_posts[]	= wpjam_get_post(get_the_ID(), $this->args);
		}

		if($is_main_query){
			if(is_category() || is_tag() || is_tax()){
				if($current_term = get_queried_object()){
					$taxonomy		= $current_term->taxonomy;
					$current_term	= wpjam_get_term($current_term, $taxonomy);

					$posts_json['current_taxonomy']		= $taxonomy;
					$posts_json['current_'.$taxonomy]	= $current_term;
				}else{
					$posts_json['current_taxonomy']		= null;
				}
			}elseif(is_author()){
				if($author = $wp_query->get('author')){
					$posts_json['current_author']	= wpjam_get_user($author);
				}else{
					$posts_json['current_author']	= null;
				}
			}

			$posts_json['total']		= (int)$wp_query->found_posts;
			$posts_json['total_pages']	= (int)$wp_query->max_num_pages;
			$posts_json['current_page']	= (int)($wp_query->get('paged') ?: 1);

			if($use_cursor){
				$posts_json['next_cursor']	= ($_posts && $wp_query->max_num_pages > 1) ? end($_posts)['timestamp'] : 0;
			}
		}

		$output	= $output ?: $this->parse_output($query_vars);

		$posts_json[$output]	= $_posts;

		return apply_filters('wpjam_posts_json', $posts_json, $wp_query, $output);
	}

	public function parse_post_calendar(){
		$output		= $this->pull('output');
		$wp			= $GLOBALS['wp'];
		$query_vars	= array_merge($wp->query_vars, $this->args);

		$year	= (int)wpjam_get_parameter('year') ?: wpjam_date('Y');
		$month	= (int)wpjam_get_parameter('month') ?: wpjam_date('m');
		$day	= (int)wpjam_get_parameter('day');

		$query_vars['year']		= $year;
		$query_vars['monthnum']	= $month;

		unset($query_vars['day']);

		$query_vars	= $wp->query_vars	= $this->parse_query_vars($query_vars);

		$wp->query_posts();

		$days	= $_posts	= [];

		while($GLOBALS['wp_query']->have_posts()){
			$GLOBALS['wp_query']->the_post();

			$_post	= wpjam_get_post(get_the_ID(), $this->args);
			$date	= explode(' ', $_post['date'])[0];
			$number	= explode('-', $date)[2];
			$days[]	= (int)$number;

			if($day && $number != $day){
				continue;
			}

			$_posts[$date]		= $_posts[$date] ?? [];
			$_posts[$date][]	= $_post;
		}

		$output	= $output ?: $this->parse_output($query_vars);

		return ['days'=>array_values(array_unique($days)), $output=>$_posts];
	}

	public function parse_post_get(){
		global $wp, $wp_query;

		$post_type	= $this->post_type ?: wpjam_get_parameter('post_type');

		if(!$post_type || $post_type == 'any'){
			$post_id	= $this->id ?: (int)wpjam_get_parameter('id', ['required'=>true]);
			$post_type	= get_post_type($post_id);

			if(!$post_type){
				wpjam_send_error_json('invalid_parameter', ['id']);
			}
		}else{
			if(!post_type_exists($post_type)){
				wpjam_send_error_json('invalid_post_type');
			}

			$post_id	= $this->id ?: (int)wpjam_get_parameter('id');

			if($post_id && get_post_type($post_id) != $post_type){
				wpjam_send_error_json('invalid_parameter', ['id']);
			}
		}

		if($this->post_status){
			$wp->set_query_var('post_status', $this->post_status);
		}

		$wp->set_query_var('post_type', $post_type);
		$wp->set_query_var('cache_results', true);

		if($post_id){
			$wp->set_query_var('p', $post_id);
			$wp->query_posts();
		}else{
			if($this->orderby == 'rand'){
				$wp->set_query_var('orderby', 'rand');
				$wp->set_query_var('posts_per_page', 1);
				$wp->query_posts();
			}else{
				$hierarchical	= is_post_type_hierarchical($post_type);
				$name_key		= $hierarchical ? 'pagename' : 'name';

				$wp->set_query_var($name_key,	wpjam_get_parameter($name_key,	['required'=>true]));

				$wp->query_posts();

				if(!$this->post_status && !$wp_query->have_posts()){
					$post_id	= apply_filters('old_slug_redirect_post_id', null);

					if(!$post_id){
						wpjam_send_error_json('invalid_post');
					}

					$wp->set_query_var('post_type', 'any');
					$wp->set_query_var('p', $post_id);
					$wp->set_query_var('name', '');
					$wp->set_query_var('pagename', '');
					$wp->query_posts();
				}
			}
		}

		if(!$wp_query->have_posts()){
			wpjam_send_error_json('invalid_parameter');
		}

		$wp_query->the_post();

		$_post		= wpjam_get_post(get_the_ID(), $this->args);
		$post_json	= array_pulls($_post, ['share_title', 'share_image', 'share_data']);
		$output		= $this->output ?: $_post['post_type'];

		$post_json[$output]	= $_post;

		return $post_json;
	}

	public function parse_media($type=''){
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$media	= $this->media ?: 'media';
		$output	= $this->output ?? 'url';

		if(!isset($_FILES[$media])){
			wpjam_send_error_json('invalid_parameter', ['media']);
		}

		if($type == 'post_type'){
			$pid	= (int)wpjam_get_parameter('post_id',	['method'=>'POST', 'default'=>0]);
			$id		= wpjam_try('media_handle_upload', $media, $pid);
			$url	= wp_get_attachment_url($id);
			$query	= wpjam_get_image_size($id);
		}else{
			$upload	= wpjam_try('wpjam_upload', $media);
			$url	= $upload['url'];
			$query	= wpjam_get_image_size($upload['file'], 'file');
		}

		if($query){
			$url	= add_query_arg($query, $url);
		}

		return $output ? [$output => $url] : $url;
	}

	public function parse_taxonomy(){
		$tax_object	= wpjam_get_taxonomy_object($this->taxonomy);

		if(!$tax_object){
			wpjam_send_error_json('invalid_taxonomy');
		}

		$mapping	= $this->pull('mapping');
		$mapping	= $mapping ? wp_parse_args($mapping) : [];

		if($mapping && is_array($mapping)){
			foreach($mapping as $key => $get){
				$value	= wpjam_get_parameter($get);

				if($value){
					$this->$key	= $value;
				}
			}
		}

		$number		= (int)$this->pull('number');
		$output		= $this->pull('output');
		$output		= $output ?: $tax_object->plural;
		$max_depth	= $this->pull('max_depth');

		$terms	= wpjam_get_terms($this->args, $max_depth);

		if($terms && $number){
			$paged	= $this->pull('paged') ?: 1;
			$offset	= $number * ($paged-1);

			$terms_json['current_page']	= (int)$paged;
			$terms_json['total_pages']	= ceil(count($terms)/$number);
			$terms = array_slice($terms, $offset, $number);
		}

		$terms	= $terms ? array_values($terms) : [];

		return [$output	=> $terms];
	}

	public function parse_setting(){
		if(!$this->option_name){
			return null;
		}

		$option_name	= $this->option_name;
		$setting_name	= $this->setting_name ?? ($this->setting ?? '');

		$output	= $this->output ?: ($setting_name ?: $option_name);
		$object = wpjam_get_option_object($option_name);

		if($object){
			$value	= $object->prepare();

			if($object->option_type == 'single'){
				$value	= $value[$option_name] ?? null;

				return [$output	=> $value];
			}
		}else{
			$value	= wpjam_get_option($option_name);
		}

		if($setting_name){
			$value	= $value[$setting_name] ?? null;
		}

		return [$output	=> $value];
	}	

	public function parse_data_type(){
		$data_type	= $this->pull('data_type');
		$object		= wpjam_get_data_type_object($data_type);

		if(!$object){
			return new WP_Error('invalid_data_type');
		}

		$query_args	= $this->args['query_args'] ?? $this->args;
		$query_args	= $query_args ? wp_parse_args($query_args) : [];
		$query_args	= array_merge($query_args, ['search'=>wpjam_get_parameter('s')]);

		return ['items'=>$object->query_items($query_args)];
	}

	public function parse_token(){
		$appid	= wpjam_get_parameter('appid',	['required'=>true]);
		$secret	= wpjam_get_parameter('secret', ['required'=>true]);
		$token	= wpjam_grant('reset_token', $appid, $secret);

		return ['access_token'=>$token, 'expires_in'=>7200];
	}

	public function parse_config(){
		return wpjam_get_config();
	}
}

class WPJAM_Source extends WPJAM_Register{
	public function callback(){
		$query_vars	= [];

		foreach($this->query_args as $query_key){
			$query_vars[$query_key]	= wpjam_get_parameter($query_key);
		}

		call_user_func($this->callback, $query_vars);
	}

	public static function create($name, $callback, $query_args=['source_id']){
		return self::register($name, ['callback'=>$callback, 'query_args'=>$query_args]);
	}
}

class WPJAM_Config extends WPJAM_Register{
	public static function get_data(){
		$data	= [];

		foreach(self::get_registereds() as $object){
			if($object->callback){
				$name	= $object->name;

				if(str_starts_with($name, '__')){
					$_data	= call_user_func($object->callback);
				}else{
					$_data	= [$name => call_user_func($object->callback, $name)];
				}
			}else{
				$_data	= $object->to_array();
			}

			$data	= array_merge($data, $_data);
		}

		return $data;
	}

	public static function create(...$args){
		if(count($args) >= 2){
			$name	= $args[0];
			$args	= $args[1];
			$args	= is_callable($args) ? ['callback'=>$args] : [$name=>$args];

			return self::register($name, $args);
		}else{
			$args	= $args[0];
			$args	= is_callable($args) ? ['callback'=>$args] : $args;

			return self::register($args);
		}
	}
}

class WPJAM_Grant extends WPJAM_Option_Items{
	protected function __construct(){
		$items	= get_option('wpjam_grant');

		if(is_array($items) && $items){
			if(isset($items['appid'])){
				$items	= [$items];
			}

			$_items	= [];

			if(wp_is_numeric_array($items)){
				foreach($items as $item){
					if($item && is_array($item) && isset($item['appid'])){
						$appid	= $item['appid'];

						$_items[$appid]	= $item;
					}
				}

				update_option('wpjam_grant', $_items);
			}
		}

		parent::__construct('wpjam_grant', ['total'=>3, 'primary_key'=>'appid', 'primary_title'=>'AppID']);
	}

	public function validate_token($token){
		foreach($this->get_items() as $item){
			if(isset($item['token']) && $item['token'] == $token && (time()-$item['time'] < 7200)){
				return $item;
			}
		}

		return new WP_Error('illegal_access_token');
	}

	public function reset_token($appid, $secret){
		$item	= $this->get($appid);

		if(!$item || empty($item['secret']) || $item['secret'] != md5($secret)){
			return new WP_Error('invalid_appsecret');
		}

		$token	= wp_generate_password(64, false, false);
		$result	= $this->update($appid, [
			'token'	=> $token,
			'time'	=> time()
		]);

		return is_wp_error($result) ? $result : $token;
	}

	public function reset_secret($appid){
		$secret	= strtolower(wp_generate_password(32, false, false));
		$result	= $this->update($appid, [
			'secret'	=> md5($secret),
			'token'		=> '',
			'time'		=> ''
		]);

		return is_wp_error($result) ? $result : $secret;
	}

	public function create(){
		$items	= $this->get_items();

		do{
			$appid	= 'jam'.strtolower(wp_generate_password(15, false, false));
		}while(isset($items[$appid]));

		return $this->insert(['appid'=>$appid], true);
	}
}

class WPJAM_Error extends WPJAM_Option_Items{
	protected function __construct(){
		parent::__construct('wpjam_errors', ['primary_key'=>'errcode', 'primary_title'=>'代码']);
	}

	public function filter($data){
		$error	= $this->get($data['errcode']);

		if($error){
			$data['errmsg']	= $error['errmsg'];

			if(!empty($error['show_modal'])){
				if(!empty($error['modal']['title']) && !empty($error['modal']['content'])){
					$data['modal']	= $error['modal'];
				}
			}
		}else{
			if(empty($data['errmsg'])){
				$object = wpjam_get_items_object('error');
				$item	= $object->get_item($data['errcode']);

				if($item && $item['message']){
					$data['errmsg']	= $item['message'];

					if($item['modal']){
						$data['modal']	= $item['modal'];
					}
				}
			}
		}

		return $data;
	}

	public function parse($data){
		if(is_wp_error($data)){
			$errdata	= $data->get_error_data();
			$data		= [
				'errcode'	=> $data->get_error_code(),
				'errmsg'	=> $data->get_error_message(),
			];

			if($errdata){
				$errdata	= is_array($errdata) ? $errdata : ['errdata'=>$errdata];
				$data 		= $data + $errdata;
			}
		}else{
			if($data === true){
				return ['errcode'=>0];
			}elseif($data === false || is_null($data)){
				return ['errcode'=>'-1', 'errmsg'=>'系统数据错误或者回调函数返回错误'];
			}elseif(is_array($data)){
				if(!$data || !wp_is_numeric_array($data)){
					$data	= wp_parse_args($data, ['errcode'=>0]);
				}
			}
		}

		if(!empty($data['errcode'])){
			$data	= $this->filter($data);
		}

		return $data;
	}

	public static function add($code, $message, $modal=[]){
		$object = wpjam_get_items_object('error');

		$object->add_item($code, ['message'=>$message, 'modal'=>$modal]);

		if(!$object->action_added){
			add_action('wp_error_added', [self::class, 'on_wp_error_added'], 10, 4);

			$object->action_added	= true;
		}
	}

	public static function on_wp_error_added($code, $message, $data, $wp_error){
		if($code && (!$message || is_array($message)) && count($wp_error->get_error_messages($code)) <= 1){
			$object = wpjam_get_items_object('error');
			$item	= $object->get_item($code);

			if($item && $item['message']){
				if($item['modal']){
					$data	= is_array($data) ? $data : [];
					$data	= array_merge($data, ['modal'=>$item['modal']]);
				}

				if(is_callable($item['message'])){
					$message	= call_user_func($item['message'], $message, $code);
				}else{
					$message	= is_array($message) ? sprintf($item['message'], ...$message) : $item['message'];
				}
			}elseif(str_starts_with($code, 'invalid_')){
				$msg	= is_array($message) ? implode($message) : '';
				$name	= wpjam_remove_prefix($code, 'invalid_');

				if($name == 'parameter'){
					$message	= $msg ? '无效的参数：'.$msg.'。' : '参数错误。';
				}elseif($name == 'callback'){
					$message	= '无效的回调函数'.($msg ? '：' : '').$msg.'。';
				}else{
					$map	= [
						'id'			=> ' ID',
						'appsecret'		=> '密钥',
						'post_type'		=> '文章类型',
						'post'			=> '文章',
						'taxonomy'		=> '分类法',
						'term'			=> '分类',
						'user_id'		=> '用户 ID',
						'user'			=> '用户',
						'comment_type'	=> '评论类型',
						'comment_id'	=> '评论 ID',
						'comment'		=> '评论',
						'type'			=> '类型',
						'signup_type'	=> '登录方式',
						'action'		=> '操作',
						'email'			=> '邮箱地址',
						'data_type'		=> '数据类型',
						'submit_button'	=> '提交按钮',
						'qrcode'		=> '二维码',
					];

					$message	= '无效的'.$msg.($map[$name] ?? ' '.ucwords($name));
				}
			}elseif(str_starts_with($code, 'illegal_')){
				$name	= wpjam_remove_prefix($code, 'illegal_');
				$map	= [
					'access_token'	=> 'Access Token ',
					'refresh_token'	=> 'Refresh Token ',
					'verify_code'	=> '验证码',
				];

				$message	= ($map[$name] ?? ucwords($name).' ').'无效或已过期。';
			}elseif(str_ends_with($code, '_occupied')){
				$name	= wpjam_remove_postfix($code, '_occupied');
				$map	= [
					'phone'		=> '手机号码',
					'email'		=> '邮箱地址',
					'nickname'	=> '昵称',
				];

				$message	= ($map[$name] ?? ucwords($name).' ').'已被其他账号使用。';
			}

			if($message){
				$wp_error->remove($code);
				$wp_error->add($code, $message, $data);
			}
		}
	}

	public static function callback($args, $code){
		if($code == 'undefined_method'){
			if(count($args) >= 2){
				return sprintf('「%s」%s未定义', ...$args);
			}elseif(count($args) == 1){
				return sprintf('%s方法未定义', ...$args);
			}
		}elseif($code == 'quota_exceeded'){
			if(count($args) >= 2){
				return sprintf('%s超过上限：%s', ...$args);
			}elseif(count($args) == 1){
				return sprintf('%s超过上限', ...$args);
			}else{
				return '超过上限';
			}
		}
	}
}

class WPJAM_Parameter extends WPJAM_Args{
	private $_data;
	private $_input;

	public function get_value($name, $args=null){
		if(!is_null($args)){
			$this->args	= $args;
		}

		$value	= $this->get_by_name($name);
		$value	= $value ?? $this->get_fallback();
		$result	= $this->validate_value($value, $name);

		if(is_wp_error($result)){
			return $this->send === false ? $result : wpjam_send_json($result);
		}

		return $this->sanitize_value($value);
	}

	private function get_by_name($name){
		if($this->data_parameter){
			if($name && isset($_GET[$name])){
				return wp_unslash($_GET[$name]);
			}else{
				return $this->get_data($name);
			}
		}else{
			$method	= strtoupper($this->method) ?: 'GET';

			if($method == 'GET'){
				if(isset($_GET[$name])){
					return wp_unslash($_GET[$name]);
				}
			}else{
				if($method == 'POST'){
					if(isset($_POST[$name])){
						return wp_unslash($_POST[$name]);
					}
				}else{
					if(isset($_REQUEST[$name])){
						return wp_unslash($_REQUEST[$name]);
					}
				}

				if(empty($_POST)){
					$input	= self::get_input();

					return $input[$name] ?? null;
				}
			}
		}

		return null;
	}

	private function get_fallback(){
		if($this->fallback){
			foreach(array_filter((array)$this->fallback) as $fallback){
				$value	= $this->get_by_name($fallback);

				if(!is_null($value)){
					return $value;
				}
			}
		}

		return $this->default;
	}

	private function get_input(){
		if(is_null($this->_input)){
			$input	= file_get_contents('php://input');
			$input	= is_string($input) ? @wpjam_json_decode($input) : $input;

			$this->_input	= is_array($input) ? $input : [];
		}

		return $this->_input;
	}

	private function get_data($name){
		if(is_null($this->_data)){
			$this->_data	= $this->sandbox(function(){
				$args		= ['method'=>'POST', 'sanitize_callback'=>'wp_parse_args', 'default'=>[]];
				$data		= $this->get_value('data', $args);
				$defaults	= $this->get_value('defaults', $args);

				return merge_deep($defaults, $data);
			});
		}

		if($name){
			return $this->_data[$name] ?? null;
		}else{
			return $this->_data;
		}
	}

	private function validate_value($value, $name){
		if($this->validate_callback){
			if(is_callable($this->validate_callback)){
				$result	= call_user_func($this->validate_callback, $value);

				if($result === false){
					return new WP_Error('invalid_parameter', [$name]);
				}elseif(is_wp_error($result)){
					return $result;
				}
			}
		}else{
			if($this->required){
				if(is_null($value)){
					return new WP_Error('missing_parameter', '缺少参数：'.$name);
				}
			}

			if($this->length){
				if(is_numeric($this->length) && mb_strlen($value) < $this->length){
					return new WP_Error('invalid_parameter', [$name]);
				}
			}
		}

		return true;
	}

	private function sanitize_value($value){
		if($this->sanitize_callback){
			if(is_callable($this->sanitize_callback)){
				return call_user_func($this->sanitize_callback, $value);
			}
		}else{
			if($this->type == 'int' && !is_null($value)){
				return (int)$value;
			}
		}

		return $value;
	}
}

class WPJAM_Request extends WPJAM_Args{
	public function request($url, $args=null, $err_args=[], &$headers=null){
		if(!is_null($args)){
			$this->args	= $args;
		}

		$this->args	= wp_parse_args($this->args, [
			'body'			=> [],
			'headers'		=> [],
			'sslverify'		=> false,
			'blocking'		=> true,	// 如果不需要立刻知道结果，可以设置为 false
			'stream'		=> false,	// 如果是保存远程的文件，这里需要设置为 true
			'filename'		=> null,	// 设置保存下来文件的路径和名字
			// 'headers'	=> ['Accept-Encoding'=>'gzip;'],	//使用压缩传输数据
			// 'compress'	=> false,
		]);

		if($this->method){
			$this->method	= strtoupper($this->method);
		}else{
			$this->method	= $this->body ? 'POST' : 'GET';
		}

		if($this->method == 'GET'){
			$response	= wp_remote_get($url, $this->args);
		}elseif($this->method == 'FILE'){
			$response	= (new WP_Http_Curl())->request($url, wp_parse_args($this->args, [
				'method'			=> $this->body ? 'POST' : 'GET',
				'sslcertificates'	=> ABSPATH.WPINC.'/certificates/ca-bundle.crt',
				'user-agent'		=> 'WordPress',
				'decompress'		=> true,
			]));
		}else{
			$encode_required	= $this->pull('json_encode_required', $this->pull('need_json_encode'));

			if($encode_required){
				if(is_array($this->body)){
					$this->body	= $this->body ?: new stdClass;
					$this->body	= wpjam_json_encode($this->body);
				}

				if($this->method == 'POST' && empty($this->headers['Content-Type'])){
					$this->headers	+= ['Content-Type'=>'application/json'];
				}
			}

			$response	= wp_remote_request($url, $this->args);
		}

		if(wpjam_doing_debug()){
			print_r($response);
		}

		if(is_wp_error($response)){
			trigger_error($url."\n".$response->get_error_code().' : '.$response->get_error_message()."\n".var_export($this->body,true));
			return $response;
		}

		$errcode	= $response['response']['code'] ?? 0;

		if($errcode && $errcode != 200){
			return new WP_Error($errcode, '远程服务器错误：'.$errcode.' - '.$response['response']['message']);
		}

		if(!$this->blocking){
			return true;
		}

		$this->url	= $url;
		$body		= $response['body'];
		$headers	= $response['headers'];

		return $this->decode($body, $headers, $err_args);
	}

	public function decode($body, $headers, $err_args=[]){
		$disposition	= $headers['content-disposition'] ?? '';
		$content_type	= $headers['content-type'] ?? '';
		$content_type	= is_array($content_type) ? implode(' ', $content_type) : $content_type;

		if($disposition && strpos($disposition, 'attachment;') !== false){
			if(!$this->stream){
				return 'data:'.$content_type.';base64, '.base64_encode($body);
			}
		}else{
			if($content_type && strpos($content_type, '/json')){
				$decode_required	= true;
			}else{
				$decode_required	= $this->pull('json_decode_required', $this->pull('need_json_decode', ($this->stream ? false : true)));
			}

			if($decode_required){
				if($this->stream){
					$body	= file_get_contents($this->filename);
				}

				if(empty($body)){
					trigger_error(var_export($body, true).var_export($headers, true));
				}else{
					$body	= wpjam_json_decode($body);
				}
			}
		}

		$err_args	= wp_parse_args($err_args,  [
			'errcode'	=>'errcode',
			'errmsg'	=>'errmsg',
			'detail'	=>'detail',
			'success'	=>'0',
		]);

		if(isset($body[$err_args['errcode']]) && $body[$err_args['errcode']] != $err_args['success']){
			$errcode	= array_pull($body, $err_args['errcode']);
			$errmsg		= array_pull($body, $err_args['errmsg']);
			$detail		= array_pull($body, $err_args['detail']);
			$detail		= is_null($detail) ? array_filter($body) : $detail;

			if(apply_filters('wpjam_http_response_error_debug', true, $errcode, $errmsg, $detail)){
				trigger_error($this->url."\n".$errcode.' : '.$errmsg."\n".($detail ? var_export($detail,true)."\n" : '').var_export($this->body,true));
			}

			return new WP_Error($errcode, $errmsg, $detail);
		}

		return $body;
	}
}

class WPJAM_API{
	public static function __callStatic($method, $args){
		$function	= 'wpjam_'.$method;

		if(function_exists($function)){
			return call_user_func($function, ...$args);
		}
	}

	public static function get_apis(){	// 兼容
		return WPJAM_JSON::get_registereds();
	}
}