<?php
class WPJAM_Platform extends WPJAM_Register{
	public function verify(){
		return call_user_func($this->verify);
	}

	public static function get_options($type='bit'){
		$objects	= [];

		foreach(self::get_registereds() as $key => $object){
			if(!empty($object->bit)){
				$object->key	= $key;

				$objects[$object->bit]	= $object;
			}
		}

		if($type == 'key' || $type == 'name'){
			return wp_list_pluck($objects, 'title', 'key');
		}elseif($type == 'bit'){
			return wp_list_pluck($objects, 'title');
		}else{
			return wp_list_pluck($objects, 'bit');
		}
	}

	public static function get_current($platforms=[], $type='bit'){
		foreach(self::get_registereds() as $name => $object){
			if($object->verify()){
				$return	= $type == 'bit' ? $object->bit : $name;

				if(($platforms && in_array($return, $platforms))
					|| empty($platforms))
				{
					return $return;
				}
			}
		}

		return '';
	}

	public static function autoload(){
		self::register('weapp',		['bit'=>1,	'order'=>4,		'title'=>'小程序',	'verify'=>'is_weapp']);
		self::register('weixin',	['bit'=>2,	'order'=>4,		'title'=>'微信网页',	'verify'=>'is_weixin']);
		self::register('mobile',	['bit'=>4,	'order'=>8,		'title'=>'移动网页',	'verify'=>'wp_is_mobile']);
		self::register('web',		['bit'=>8,	'order'=>10,	'title'=>'网页',		'verify'=>'__return_true']);
		self::register('template',	['bit'=>8,	'order'=>10,	'title'=>'网页',		'verify'=>'__return_true']);
	}

	protected static function get_config($key){
		if($key == 'orderby'){
			return true;
		}elseif($key == 'order'){
			return 'ASC';
		}
	}
}

class WPJAM_Path extends WPJAM_Register{
	public function add_platform($platform, $args){
		$page_type	= $args['page_type'] ?? '';

		if($page_type && in_array($page_type, ['post_type', 'taxonomy']) && empty($args[$page_type])){
			$args[$page_type]	= $this->name;
		}

		if(isset($args['group']) && is_array($args['group'])){
			$group	= array_pull($args, 'group');

			if(isset($group['key'], $group['title'])){
				wpjam_add_item('path_group', $group['key'], ['title'=>$group['title'], 'options'=>[]]);

				$args['group']	= $group['key'];
			}
		}

		$args['platform']	= $args['path_type']	= $platform;

		$this->add_item($platform, $args);

		$this->args	= $this->args+$args;

		wpjam_add_item('platforms', $platform, $platform);
	}

	public function get_date_type_object($platform){
		$page_type	= $this->get_item_arg($platform, 'page_type');

		return $page_type ? wpjam_get_data_type_object($page_type) : null;
	}

	public function get_tabbar($platform){
		$tabbar	= $this->get_item_arg($platform, 'tabbar') ?: '';

		if($tabbar){
			if(!is_array($tabbar)){
				$tabbar	= ['text'=>$this->get_item_arg($platform, 'title')];
			}
		}

		return $tabbar;
	}

	public function get_raw_path($platform){
		return $this->get_item_arg($platform, 'path') ?: '';
	}

	public function get_path($platform, $path_args=[], $postfix='', $title=''){
		if($postfix && is_array($path_args)){
			$_args	= [];

			foreach($this->get_fields() as $field_key => $path_field){
				$_args[$field_key]	= $path_args[$field_key.$postfix] ?? '';
			}

			$path_args	= $_args;
		}

		$args	= $this->get_item($platform);

		if($args){
			$callback	= array_pull($args, 'callback');

			if(is_array($path_args)){
				$path_args	= array_filter($path_args, 'is_exists');
				$path_args	= wp_parse_args($path_args, $args);
			}

			if($callback){
				if(is_callable($callback) && is_array($path_args)){
					return call_user_func($callback, $path_args, $this->name) ?: '';
				}
			}else{
				$dt_object	= $this->get_date_type_object($platform);

				if($dt_object){
					if(is_array($path_args)){
						return $dt_object->get_path($path_args);
					}else{
						return $dt_object->get_path($path_args, $args);
					}
				}
			}

			if(isset($args['path'])){
				return $args['path'] ?: '';
			}
		}

		return new WP_Error('invalid_page_key', [$title]);
	}

	public function get_paths($platform, $query_args=[]){
		$paths		= [];
		$dt_object	= $this->get_date_type_object($platform);

		if($dt_object){
			$args		= $this->get_item($platform);
			$data_type	= $dt_object->name;

			if(!empty($args[$data_type])){
				$query_args[$data_type]	= $args[$data_type];
			}

			$items	= $dt_object->query_items($query_args) ?: [];

			foreach($items as $item){
				$path	= $this->get_path($platform, $item['value']);

				if($path && !is_wp_error($path)){
					$paths[]	= $path;
				}
			}
		}

		return $paths;
	}

	public function get_fields(){
		$fields	= [];

		foreach($this->get_items() as $platform => $args){
			$platform_fields	= array_pull($args, 'fields') ?: [];

			if($platform_fields){
				if(is_callable($platform_fields)){
					$platform_fields	= call_user_func($platform_fields, $args, $this->name);
				}
			}else{
				$dt_object	= $this->get_date_type_object($platform);

				if($dt_object){
					$platform_fields	= $dt_object->get_fields($args);
				}
			}

			if(is_array($platform_fields)){
				$fields	= array_merge($fields, $platform_fields);
			}
		}

		return $fields;
	}

	public function has($platforms, $operator='AND', $strict=false){
		foreach((array)$platforms as $platform){
			if($args = $this->get_item($platform)){
				$has	= isset($args['path']) || isset($args['callback']);

				if($strict && $has && isset($args['path']) && $args['path'] === false){
					$has	= false;
				}
			}else{
				$has	= false;
			}

			if($operator == 'AND'){
				if(!$has){
					return false;
				}
			}elseif($operator == 'OR'){
				if($has){
					return true;
				}
			}
		}

		if($operator == 'AND'){
			return true;
		}elseif($operator == 'OR'){
			return false;
		}
	}

	public static function get_platforms(){
		return array_values(wpjam_get_items('platforms'));
	}

	public static function parse($item, $platform, $backup=false){
		if($backup){
			$postfix	= '_backup';
			$default	= 'none';
			$title		= '备用';
		}else{
			$postfix	= '';
			$default	= '';
			$title		= '';
		}

		$page_key	= $item['page_key'.$postfix] ?? '';
		$page_key	= $page_key ?: $default;
		$parsed		= [];

		if($page_key == 'none'){
			if(!empty($item['video'])){
				$parsed['type']		= 'video';
				$parsed['video']	= $item['video'];
				$parsed['vid']		= wpjam_get_qqv_id($item['video']);
			}else{
				$parsed['type']		= 'none';
			}
		}elseif($page_key == 'external'){
			if(in_array($platform, ['web', 'template'])){
				$parsed['type']		= 'external';
				$parsed['url']		= $item['url'];
			}
		}elseif($page_key == 'web_view'){
			if(in_array($platform, ['web', 'template'])){
				$parsed['type']		= 'external';
				$parsed['url']		= $item['src'];
			}else{
				$parsed['type']		= 'web_view';
				$parsed['src']		= $item['src'];
			}
		}

		if(!$parsed && $page_key){
			$object	= self::get($page_key);

			if($object){
				$path	= $object->get_path($platform, $item, $postfix, $title);

				if(!is_wp_error($path)){
					if(is_array($path)){
						$parsed	= $path;
					}else{
						$parsed['type']		= '';
						$parsed['page_key']	= $page_key;
						$parsed['path']		= $path;
					}
				}
			}
		}

		return $parsed;
	}

	public static function validate($item, $platforms, $backup=false){
		if($backup){
			$postfix	= '_backup';
			$default	= 'none';
			$title		= '备用';
		}else{
			$postfix	= '';
			$default	= '';
			$title		= '';
		}

		$page_key	= $item['page_key'.$postfix] ?: $default;

		if($page_key == 'none'){
			return true;
		}elseif($page_key == 'web_view'){
			if(!$backup){
				$platforms	= array_diff($platforms, ['web','template']);
			}
		}

		$object	= self::get($page_key);

		if(!$object){
			return new WP_Error('invalid_page_key', [$title]);
		}

		foreach($platforms as $platform){
			$result	= $object->get_path($platform, $item, $postfix, $title);

			if(is_wp_error($result)){
				return $result;
			}
		}

		return true;
	}

	public static function get_path_fields($platforms, $args=[]){
		if(empty($platforms)){
			return [];
		}

		$platforms	= (array)$platforms;

		if(is_array($args)){
			$for	= array_pull($args, 'for');
			$strict	= false;
		}else{
			$for	= $args;
			$strict	= ($for == 'qrcode');
			$args	= [];
		}

		$options	= ['tabbar'=>['title'=>'菜单栏/常用', 'options'=>[]]]+wpjam_get_items('path_group')+['others'=>['title'=>'其他页面', 'options'=>[]]];

		$page_key_fields	= ['page_key'=>['type'=>'select', 'options'=>$options]];
		$page_key_options	= &$page_key_fields['page_key']['options'];

		$backup_required	= count($platforms) > 1 && !$strict;

		if($backup_required){
			$backup_fields	= ['page_key_backup'=>['type'=>'select', 'options'=>$options,	'description'=>'跳转页面不生效时将启用备用页面']];
			$show_if_keys	= [];
			$backup_options	= &$backup_fields['page_key_backup']['options'];
		}

		foreach(self::get_registereds($args) as $page_key => $object){
			if(!$object->has($platforms, 'OR', $strict)){
				continue;
			}

			$group	= $object->group ?: ($object->tabbar ? 'tabbar' : 'others');

			$page_key_options[$group]['options'][$object->name]	= $object->title;

			$path_fields	= $object->get_fields();

			foreach($path_fields as $field_key => $path_field){
				if(isset($path_field['show_if'])){
					$page_key_fields[$field_key]	= $path_field;
				}else{
					if(isset($page_key_fields[$field_key])){
						$page_key_fields[$field_key]['show_if']['value'][]	= $page_key;
					}else{
						$path_field['title']	= '';
						$path_field['show_if']	= ['key'=>'page_key','compare'=>'IN','value'=>[$page_key]];

						$page_key_fields[$field_key]	= $path_field;
					}
				}
			}

			if($backup_required){
				if($object->has($platforms, 'AND')){
					if(($page_key != 'module_page' && empty($path_fields)) || ($page_key == 'module_page' && $path_fields)){
						$backup_options[$group]['options'][$object->name]	= $object->title;
					}

					if($page_key == 'module_page' && $path_fields){
						foreach($path_fields as $field_key => $path_field){
							$path_field['show_if']	= ['key'=>'page_key_backup','value'=>$page_key];
							$backup_fields[$field_key.'_backup']	= $path_field;
						}
					}
				}else{
					if($page_key == 'web_view'){
						if(!$object->has(array_diff($platforms, ['web','template']), 'AND')){
							$show_if_keys[]	= $page_key;
						}
					}else{
						$show_if_keys[]	= $page_key;
					}
				}
			}
		}

		// 只有一个分组，则不分组显示
		if(count($page_key_fields['page_key']['options']) == 1){
			$page_key_fields['page_key']['options']	= current($page_key_fields['page_key']['options'])['options'];

			if($backup_required){
				$backup_fields['page_key_backup']['options']	= current($backup_fields['page_key']['options'])['options'];
			}
		}

		if($for == 'qrcode'){
			return ['page_key_set'=>['title'=>'页面',	'type'=>'fieldset',	'fields'=>$page_key_fields]];
		}else{
			$page_key_fields['page_key']['options']['tabbar']['options']['none']	= '只展示不跳转';

			$fields	= [];

			$fields['page_key_set']	= ['title'=>'页面',	'type'=>'fieldset',	'fields'=>$page_key_fields];

			if($backup_required){
				$show_if	= ['key'=>'page_key','compare'=>'IN','value'=>$show_if_keys];

				$backup_fields['page_key_backup']['options']['tabbar']['options']['none']	= '只展示不跳转';

				$fields['page_key_backup_set']	= ['title'=>'备用',	'type'=>'fieldset',	'fields'=>$backup_fields, 'show_if'=>$show_if];
			}

			return $fields;
		}
	}

	public static function get_tabbar_options($platform){
		$options	= [];

		foreach(self::get_registereds() as $page_key => $object){
			$tabbar	= $object->get_tabbar($platform);

			if($tabbar){
				$options[$page_key]	= $tabbar['text'];
			}
		}

		return $options;
	}

	public static function get_page_keys($platform){
		$page_keys	= [];

		foreach(self::get_registereds() as $page_key => $object){
			$path	= $object->get_raw_path($platform);

			if($path){
				$pos	= strrpos($path, '?');

				$page_keys[]	= [
					'page_key'	=> $page_key,
					'page'		=> $pos ? substr($path, 0, $pos) : $path,
				];
			}
		}

		return $page_keys;
	}

	public static function get_link_tag($parsed, $text){
		if($parsed['type'] == 'none'){
			return $text;
		}elseif($parsed['type'] == 'external'){
			return '<a href_type="web_view" href="'.$parsed['url'].'">'.$text.'</a>';
		}elseif($parsed['type'] == 'web_view'){
			return '<a href_type="web_view" href="'.$parsed['src'].'">'.$text.'</a>';
		}elseif($parsed['type'] == 'mini_program'){
			return '<a href_type="mini_program" href="'.$parsed['path'].'" appid="'.$parsed['appid'].'">'.$text.'</a>';
		}elseif($parsed['type'] == 'contact'){
			return '<a href_type="contact" href="" tips="'.$parsed['tips'].'">'.$text.'</a>';
		}elseif($parsed['type'] == ''){
			return '<a href_type="path" page_key="'.$parsed['page_key'].'" href="'.$parsed['path'].'">'.$text.'</a>';
		}
	}

	public static function get_by(...$args){
		$args		= is_array($args[0]) ? $args[0] : [$args[0] => $args[1]];
		$platform	= self::parse_platform($args);

		if($platform){
			$args[$platform]	= function($object, $platform){ return $object->has($platform); };
		}

		return self::get_registereds($args);
	}

	protected static function parse_platform(&$args){
		$platform	= array_pull($args, 'platform');

		return $platform ?: array_pull($args, 'path_type');
	}

	public static function create($page_key, ...$args){
		$object	= self::get($page_key);
		$object	= $object ?: self::register($page_key, []);

		if(count($args) == 2){
			$args	= $args[1]+['platform'=>$args[0]];
		}else{
			$args	= $args[0];
		}

		$args	= wp_is_numeric_array($args) ? $args : [$args];

		foreach($args as $_args){
			$platform	=  self::parse_platform($_args);

			if($platform){
				$object->add_platform($platform, $_args);
			}
		}

		return $object;
	}

	public static function remove($page_key, $platform=''){
		if($platform){
			$object	= self::get($page_key);

			if($object){
				$object->delete_item($platform);
			}

			return $object;
		}else{
			self::unregister($page_key);
		}
	}

	protected static function get_config($key){
		if(in_array($key, ['item_arg'])){
			return true;
		}
	}

	public static function autoload(){
		self::create('home',		['platform'=>'template',	'title'=>'首页',			'path'=>home_url()]);
		self::create('category',	['platform'=>'template',	'title'=>'分类页',		'path'=>'',	'page_type'=>'taxonomy',	'taxonomy'=>'category']);
		self::create('post_tag',	['platform'=>'template',	'title'=>'标签页',		'path'=>'',	'page_type'=>'taxonomy',	'taxonomy'=>'post_tag']);
		self::create('author',		['platform'=>'template',	'title'=>'作者页',		'path'=>'',	'page_type'=>'author']);
		self::create('post',		['platform'=>'template',	'title'=>'文章详情页',	'path'=>'',	'page_type'=>'post_type',	'post_type'=>'post']);
		self::create('external', 	['platform'=>'template',	'title'=>'外部链接',		'path'=>'',	'fields'=>[
			'url'	=> ['title'=>'',	'type'=>'url',	'required'=>true,	'placeholder'=>'请输入外部链接地址，仅适用网页版。']
		]]);
	}
}

class WPJAM_Data_Type extends WPJAM_Register{
	public function __call($method, $args){
		if($this->get_called_method($method)){
			return $this->call_method($method, ...$args);
		}

		if(in_array($method, ['parse_value', 'validate_value', 'render_value', 'parse_item', 'query_label', 'filter_query_args'])){
			return $args[0];
		}elseif(in_array($method, ['get_field', 'get_fields'])){
			return [];
		}

		return null;
	}

	public function prepare_value($value, $parse, $args=[]){
		return $parse ? $this->parse_value($value, $args) : $value;
	}

	public function query_items($args){
		if(!$this->get_called_method('query_items')){
			return new WP_Error('undefined_method', ['query_items', '回调函数']);
		}

		$args	= array_filter($args, 'is_exists');
		$items	= $this->call_method('query_items', $args) ?: [];

		foreach($items as &$item){
			$item	= $this->parse_item($item, $args);
		}

		return array_values($items);
	}

	public function register_list_table_action($args){
		if(empty($args['builtin_class'])){
			$model	= $args['model'] ?? null;

			if($model && method_exists($model, 'get_actions')){
				$actions	= call_user_func([$model, 'get_actions']);
			}else{
				$actions	= $args['actions'] ?? [
					'add'		=> ['title'=>'新建',	'dismiss'=>true],
					'edit'		=> ['title'=>'编辑'],
					'delete'	=> ['title'=>'删除',	'direct'=>true, 'confirm'=>true,	'bulk'=>true],
				];
			}

			foreach($actions as $key => $action){
				wpjam_register_list_table_action($key, wp_parse_args($action, ['order'=>10.5]));
			}
		}

		$this->call_method('register_list_table_action', $args);
	}

	public function parse_query_args($args){
		$query_args	= $args['query_args'] ?? [];
		$query_args	= $query_args ? wp_parse_args($query_args) : [];

		if(!empty($args[$this->name])){
			$query_args[$this->name]	= $args[$this->name];
		}

		return $this->filter_query_args($query_args, $args);
	}

	public static function strip($args){
		$data_type	= array_pull($args, 'data_type');

		if($data_type){
			$args	= array_except($args, $data_type);
		}

		return $args;
	}

	public static function slice(&$args, $strip=false){
		$data_type	= array_get($args, 'data_type');

		if($data_type){
			$slice	= [
				'data_type'	=> $data_type,
				$data_type 	=> array_get($args, $data_type) ?: ''
			];
		}else{
			$slice	= [];
		}

		if($strip){
			$args	= self::strip($args);
		}

		return $slice;
	}

	public static function autoload(){
		self::register('post_type',	['model'=>'WPJAM_Post_Type_Data_Type',	'meta_type'=>'post']);
		self::register('taxonomy',	['model'=>'WPJAM_Taxonomy_Data_Type',	'meta_type'=>'term']);
		self::register('author',	['model'=>'WPJAM_Author_Data_Type',		'meta_type'=>'user']);
		self::register('model',		['model'=>'WPJAM_Model_Data_Type']);
		self::register('video',		['model'=>'WPJAM_Video_Data_Type']);
	}
}

class WPJAM_Post_Type_Data_Type{
	public static function filter_query_args($query_args, $args){
		if(!empty($args['size'])){
			$query_args['thumbnal_size']	= $args['size'];
		}

		return $query_args;
	}

	public static function query_items($args){
		if(!isset($args['s']) && isset($args['search'])){
			$args['s']	= $args['search'];
		}

		return get_posts(wp_parse_args($args, [
			'posts_per_page'	=> $args['number'] ?? 10,
			'suppress_filters'	=> false,
		])) ?: [];
	}

	public static function parse_item($post){
		return ['label'=>$post->post_title, 'value'=>$post->ID];
	}

	public static function query_label($post_id){
		if($post_id && is_numeric($post_id)){
			return get_the_title($post_id) ?: (int)$post_id;
		}

		return '';
	}

	public static function validate_value($value, $args){
		if(!$value){
			return null;
		}

		$current 	= is_numeric($value) ? get_post_type($value) : null;

		if($current){
			$post_type	= array_get($args, 'post_type') ?: $current;

			if(in_array($current, (array)$post_type, true)){
				return (int)$value;
			}
		}

		return new WP_Error('invalid_post_id', [$args['title']]);
	}

	public static function parse_value($value, $args=[]){
		return wpjam_get_post($value, $args);
	}

	public static function update_caches($ids){
		return WPJAM_Post::update_caches($ids);
	}

	public static function get_path(...$args){
		if(is_array($args[0])){
			$post_id	= null;
			$args		= $args[0];
		}else{
			$post_id	= (int)$args[0];
			$args		= $args[1];
		}

		$post_type	= $args['post_type'];
		$post_id	= $post_id ?? (int)($args[$post_type.'_id'] ?? 0);

		if(!$post_id){
			return new WP_Error('invalid_post_id', [get_post_type_object($post_type)->label]);
		}

		if($args['platform'] == 'template'){
			return get_permalink($post_id);
		}

		return str_replace('%post_id%', $post_id, $args['path']);
	}

	public static function get_field($args){
		$title		= array_pull($args, 'title');
		$post_type	= array_pull($args, 'post_type');

		if(is_null($title)){
			$object	= ($post_type && is_string($post_type)) ? get_post_type_object($post_type) : null;
			$title	= $object ? $object->labels->singular_name : '';
		}

		return wp_parse_args($args, [
			'title'			=> $title,
			'type'			=> 'text',
			'class'			=> 'all-options',
			'data_type'		=> 'post_type',
			'post_type'		=> $post_type,
			'placeholder'	=> '请输入'.$title.'ID或者输入关键字筛选',
			'show_in_rest'	=> ['type'=>'integer']
		]);
	}

	public static function get_fields($args){
		$post_type	= $args['post_type'];

		if(get_post_type_object($post_type)){
			return [$post_type.'_id' => self::get_field(['post_type'=>$post_type, 'required'=>true])];
		}

		return [];
	}

	public static function register_list_table_action($args){
		$pt_object		= wpjam_get_post_type_object($args['post_type']);
		$meta_options	= $pt_object ? $pt_object->get_meta_options() : [];

		foreach($meta_options as $object){
			$object->register_list_table_action();
		}
	}
}

class WPJAM_Taxonomy_Data_Type{
	public static function filter_query_args($query_args, $args){
		if($args['creatable']){
			$query_args['creatable']	= $args['creatable'];
		}

		unset($args['creatable']);

		return $query_args;
	}

	public static function query_items($args){
		return get_terms(wp_parse_args($args, [
			'number'		=> (isset($args['parent']) ? 0 : 10),
			'hide_empty'	=> 0
		])) ?: [];
	}

	public static function parse_item($term){
		if(is_object($term)){
			return ['label'=>$term->name, 'value'=>$term->term_id];
		}else{
			return ['label'=>$term['name'], 'value'=>$term['id']];
		}
	}

	public static function query_label($term_id, $args){
		if($term_id && is_numeric($term_id)){
			return get_term_field('name', $term_id, $args['taxonomy']) ?: (int)$term_id;
		}

		return '';
	}

	public static function validate_value($value, $args){
		if(!$value){
			return null;
		}

		$taxonomy	= $args['taxonomy'];
		$tax_object	= wpjam_get_taxonomy_object($taxonomy);

		if(is_numeric($value)){
			if(get_term($value, $taxonomy)){
				return (int)$value; 
			}
		}elseif(is_array($value)){
			$levels	= $tax_object ? $tax_object->levels : 0;
			$prev	= 0;

			for($level=0; $level < $levels; $level++){
				$_value	= $value['level_'.$level];

				if(!$_value){
					return $prev;
				}

				$prev	= $_value;
			}

			return $prev;
		}else{
			$result	= term_exists($value, $taxonomy);

			if($result){
				return is_array($result) ? $result['term_id'] : $result;
			}elseif(!empty($args['creatable'])){
				return WPJAM_Term::insert(['name'=>$value, 'taxonomy'=>$taxonomy]);
			}
		}

		return new WP_Error('invalid_term_id', [$args['title']]);
	}

	public static function parse_value($value, $args=[]){
		return wpjam_get_term($value, $args);
	}

	public static function render_value($value, $args){
		$taxonomy	= $args['taxonomy'];
		$tax_object	= wpjam_get_taxonomy_object($taxonomy);
		$levels		= $tax_object ? $tax_object->levels : 0;

		if($levels && $value){
			$ancestors	= get_ancestors($value, $taxonomy, 'taxonomy');
			$term_ids	= array_merge([$value], $ancestors);
			$term_ids	= array_reverse($term_ids);
			$terms		= wpjam_get_terms(['taxonomy'=>$taxonomy, 'hide_empty'=>0]);

			$value		= [];

			for($level=0; $level < $levels; $level++){
				$term_id	= $term_ids[$level] ?? 0;

				if($level == 0){
					$value['level_'.$level]	=  ['value'=>$term_id, 'parent'=>0];
				}else{
					if(!$parent){
						break;
					}

					foreach($terms as $term){
						if($term['id'] == $parent){
							$terms	= $term['children'];
						}
					}

					$value['level_'.$level]	=  [
						'value'		=> $term_id,
						'parent'	=> $parent,
						'items'		=> array_map([self::class, 'parse_item'], $terms)
					];
				}

				$parent	= $term_id;
			}
		}

		return $value;
	}

	public static function update_caches($ids){
		return WPJAM_Term::update_caches($ids);
	}

	public static function get_path(...$args){
		if(is_array($args[0])){
			$args	= $args[0];
		}else{
			$args	= array_merge($args[1], ['term_id'=>$args[0]]);
		}

		$tax_object	= wpjam_get_taxonomy_object($args['taxonomy']);

		return $tax_object ? $tax_object->get_path($args) : '';
	}

	public static function get_field($args){
		$taxonomy	= array_pull($args, 'taxonomy');
		$tax_object	= ($taxonomy && is_string($taxonomy)) ? wpjam_get_taxonomy_object($taxonomy) : null;

		return $tax_object ? $tax_object->get_id_field($args) : [];
	}

	public static function get_fields($args){
		$taxonomy	= $args['taxonomy'];
		$tax_object	= wpjam_get_taxonomy_object($args['taxonomy']);

		return $tax_object ? $tax_object->get_id_field(['required'=>true, 'wrap'=>true]) : [];
	}

	public static function register_list_table_action($args){
		$tax_object		= wpjam_get_taxonomy_object($args['taxonomy']);
		$meta_options	= $tax_object ? $tax_object->get_meta_options() : [];

		foreach($meta_options as $object){
			$object->register_list_table_action();
		}
	}
}

class WPJAM_Author_Data_Type{
	public static function get_path(...$args){
		if(is_array($args[0])){
			$args	= $args[0];
			$author	= (int)array_pull($args, 'author');
		}else{
			$author	= $args[0];
			$args	= $args[1];
		}

		if(!$author){
			return new WP_Error('invalid_author', ['作者']);
		}

		if($args['platform'] == 'template'){
			return get_author_posts_url($author);
		}

		return str_replace('%author%', $author, $args['path']);
	}

	public static function get_fields(){
		return ['author' => ['title'=>'',	'type'=>'select',	'options'=>wp_list_pluck(wpjam_get_authors(), 'display_name', 'ID')]];
	}
}

class WPJAM_Video_Data_Type{
	public static function get_video_mp4($id_or_url){
		if(filter_var($id_or_url, FILTER_VALIDATE_URL)){
			if(preg_match('#http://www.miaopai.com/show/(.*?).htm#i',$id_or_url, $matches)){
				return 'http://gslb.miaopai.com/stream/'.esc_attr($matches[1]).'.mp4';
			}elseif(preg_match('#https://v.qq.com/x/page/(.*?).html#i',$id_or_url, $matches)){
				return self::get_qqv_mp4($matches[1]);
			}elseif(preg_match('#https://v.qq.com/x/cover/.*/(.*?).html#i',$id_or_url, $matches)){
				return self::get_qqv_mp4($matches[1]);
			}else{
				return wpjam_zh_urlencode($id_or_url);
			}
		}else{
			return self::get_qqv_mp4($id_or_url);
		}
	}

	public static function get_qqv_id($id_or_url){
		if(filter_var($id_or_url, FILTER_VALIDATE_URL)){
			foreach([
				'#https://v.qq.com/x/page/(.*?).html#i',
				'#https://v.qq.com/x/cover/.*/(.*?).html#i'
			] as $pattern){
				if(preg_match($pattern,$id_or_url, $matches)){
					return $matches[1];
				}
			}

			return '';
		}else{
			return $id_or_url;
		}
	}

	public static function get_qqv_mp4($vid){
		if(strlen($vid) > 20){
			return new WP_Error('error', '无效的腾讯视频');
		}

		$mp4 = wp_cache_get($vid, 'qqv_mp4');

		if($mp4 === false){
			$response	= wpjam_remote_request('http://vv.video.qq.com/getinfo?otype=json&platform=11001&vid='.$vid, [
				'timeout'				=> 4,
				'json_decode_required'	=> false
			]);

			if(is_wp_error($response)){
				return $response;
			}

			$response	= trim(substr($response, strpos($response, '{')),';');
			$response	= wpjam_try('wpjam_json_decode', $response);

			if(empty($response['vl'])){
				return new WP_Error('error', '腾讯视频不存在或者为收费视频！');
			}

			$u		= $response['vl']['vi'][0];
			$p0		= $u['ul']['ui'][0]['url'];
			$p1		= $u['fn'];
			$p2		= $u['fvkey'];
			$mp4	= $p0.$p1.'?vkey='.$p2;

			wp_cache_set($vid, $mp4, 'qqv_mp4', HOUR_IN_SECONDS*6);
		}

		return $mp4;
	}

	public static function parse_value($value, $args=[]){
		return self::get_video_mp4($value);
	}
}

class WPJAM_Model_Data_Type{
	public static function filter_query_args($query_args, $args){
		$model	= $query_args['model'] ?? null;

		if(!$model || !class_exists($model)){
			wp_die(' model 未定义');
		}

		return $query_args;
	}

	public static function query_items($args){
		$model	= array_pull($args, 'model');
		$args	= array_except($args, ['label_key', 'id_key']);
		$args	= wp_parse_args($args, ['number'=>10]);
		$query	= call_user_func([$model, 'query'], $args);

		return $query->items;
	}

	public static function parse_item($item, $args){
		$label_key	= array_pull($args, 'label_key', 'title');
		$id_key		= array_pull($args, 'id_key', 'id');

		return ['label'=>$item[$label_key], 'value'=>$item[$id_key]];
	}

	public static function query_label($id, $args){
		$model	= array_pull($args, 'model');

		if($id && $model && class_exists($model)){
			if($data = call_user_func([$model, 'get'], $id)){
				$label_key	= $args['label_key'];

				return $data[$label_key] ?: $id;
			}
		}

		return '';
	}

	public static function validate_value($value, $args){
		if(!$value){
			return null;
		}

		$model	= array_pull($args, 'model');

		if($model && class_exists($model) && call_user_func([$model, 'get'], $value)){
			return $value;
		}

		return new WP_Error('invalid_'.$model.'_id', [$args['title']]);
	}
}