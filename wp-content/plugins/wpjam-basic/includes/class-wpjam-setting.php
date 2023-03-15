<?php
class WPJAM_Setting{
	use WPJAM_Instance_Trait;

	protected $type;
	protected $name;
	protected $blog_id;
	protected $values	= null;

	protected function __construct($type, $name, $blog_id=0){
		$this->type		= $type;
		$this->name		= $name;
		$this->blog_id	= $blog_id;
	}

	public function get_option($default=[]){
		if(is_null($this->values) || $default !== []){
			$this->values	= $this->action('get', $default);
		}

		return $this->values;
	}

	protected function action($action, $value=null){
		$args	= [$this->name];

		if(in_array($action, ['add', 'update'])){
			$args[]	= $value ? $this->sanitize_option($value) : $value;
		}elseif($action == 'get'){
			$args[]	= $value;
		}

		if($this->type == 'site_option'){
			$callback	= $action.'_site_option';
		}else{
			if($this->blog_id){
				$args		= array_merge([$this->blog_id], $args);
				$callback	= $action.'_blog_option';
			}else{
				$callback	= $action.'_option';
			}
		}

		$result	= call_user_func($callback, ...$args);

		if($action == 'get'){
			if($result !== $value){
				return $this->sanitize_option($result);
			}
		}else{
			$this->values	= null;
		}

		return $result;
	}

	public function update_option($value){
		return $this->action('update', $value);
	}

	public function add_option($value){
		return $this->action('add', $value);
	}

	public function delete_option(){
		return $this->action('delete');
	}

	public function get_setting($name){
		$values	= $this->get_option();

		if($name == ''){
			return $values;
		}

		if($values && is_array($values) && isset($values[$name])){
			$value	= $values[$name];

			if(is_wp_error($value)){
				return null;
			}

			if(is_string($value)){
				$value	= str_replace("\r\n", "\n", trim($value));
			}

			return $value;
		}else{
			return null;
		}
	}

	public function update_setting($name, $value){
		$values	= $this->get_option();

		return $this->update_option(array_merge($values, [$name => $value]));
	}

	public function delete_setting($name){
		$values	= $this->get_option();

		return $this->update_option(array_except($values, $name));
	}

	public static function get_instance($type, $name, $blog_id=0){
		if(is_multisite() && $type == 'option'){
			$blog_id	= (int)$blog_id;
			$blog_id	= $blog_id ?: get_current_blog_id();
		}else{
			$blog_id	= null;
		}

		return self::instance($type, $name, $blog_id);
	}

	public static function sanitize_option($value){
		return (is_wp_error($value) || empty($value)) ? [] : $value;
	}

	public static function __callStatic($method, $args){
		$function	= 'wpjam_'.$method;

		if(function_exists($function)){
			return call_user_func($function, ...$args);
		}
	}

	public static function get_option_settings(){	// 兼容代码
		return WPJAM_Option_Setting::get_registereds([], 'settings');
	}
}

class WPJAM_Option_Setting extends WPJAM_Register{
	public function get_setting($name='', $default=null, $blog_id=0){
		$value	= wpjam_get_setting($this->name, $name, $blog_id);

		if(is_null($value) && $this->site_default && is_multisite()){
			$value	= wpjam_get_site_setting($this->name, $name);
		}

		if(is_null($value)){
			if(is_null($default) && $this->field_default){
				$defaults	= $this->get_fields('object')->get_defaults();

				if($name){
					return $defaults[$name] ?? null;
				}else{
					return $defaults;
				}
			}else{
				return $default;
			}
		}

		return $value;
	}

	public function update_setting($name, $value, $blog_id=0){
		return wpjam_update_setting($this->name, $name, $value, $blog_id);
	}

	public function delete_setting($name, $blog_id=0){
		return wpjam_delete_setting($this->name, $name, $blog_id);
	}

	public function get_filter(){
		return null;
	}

	public function get_current_arg($key, $item_key=''){
		if(is_admin() && !$item_key){
			$item_key	= self::generate_item_key();
		}

		$item	= $item_key ? $this->get_item($item_key) : null;

		if($item){
			if(isset($item[$key])){
				return $item[$key];
			}else{
				return $this->get_called_method('get_'.$key, 'model', $item);
			}
		}

		return $this->get_arg($key);
	}

	public function get_item_sections($item_key=''){
		$sections	= $this->get_current_arg('sections', $item_key);
		
		if(!is_null($sections)){
			if(is_callable($sections)){
				$sections	= call_user_func($sections, $this->name);
			}

			$sections	= is_array($sections) ? $sections : [];
		}else{
			$fields		= $this->get_current_arg('fields', $item_key);

			if(!is_null($fields)){
				$item_key	= $item_key ?: self::generate_item_key();
				$sections	= [$item_key => [
					'title'		=> $this->title, 	
					'fields'	=> $fields
				]];
			}else{
				$sections	= $this->args;
			}
		}

		foreach($sections as $section_id => &$section){
			if(is_array($section)){
				$section['fields']	= $section['fields'] ?? [];

				if(is_callable($section['fields'])){
					$section['fields']	= call_user_func($section['fields'], $section_id, $this->name);
				}
			}else{
				unset($sections[$section_id]);
			}
		}

		return $sections;
	}

	public function get_sections(){
		if(!$this->sections_filtered){
			$this->sections_filtered	= true;

			$sections	= $this->get_item_sections();

			if(!is_admin()){
				foreach($this->get_item_keys() as $item_key){
					$sections	+= $this->get_item_sections($item_key);
				}
			}

			$sections	= WPJAM_Option_Section::filter($sections, $this->name);

			$this->sections = apply_filters('wpjam_option_setting_sections', $sections, $this->name);
			$this->args		= apply_filters('wpjam_option_setting_args', $this->args, $this->name);
		}

		return $this->sections;
	}

	public function get_fields($type=''){
		if($type == 'object'){
			if(is_null($this->fields_object)){
				$this->fields_object	= wpjam_fields($this->get_fields());
			}

			return $this->fields_object;
		}else{
			return array_merge(...array_values(wp_list_pluck($this->get_sections(), 'fields')));;
		}
	}

	public function get_summary(){
		return $this->get_current_arg('summary');
	}

	public function prepare(){
		return $this->get_fields('object')->prepare(['value_callback'=>[$this, 'value_callback']]);
	}

	public function validate($value){
		return $this->get_fields('object')->validate($value);
	}

	public function value_callback($name='', $args=[]){
		$is_network_admin	= is_multisite() && is_network_admin();

		if($this->option_type == 'array'){
			if($is_network_admin){
				return wpjam_get_site_setting($this->name, $name);
			}else{
				return $this->get_setting($name);
			}
		}else{
			if($name){
				$callback	= $is_network_admin ? 'get_site_option' : 'get_option';
				$value		= call_user_func($callback, $name, null);

				return is_wp_error($value) ? null : $value;
			}else{
				return null;
			}
		}
	}

	public function register_settings(){
		if($this->capability && $this->capability != 'manage_options'){
			add_filter('option_page_capability_'.$this->option_page, [$this, 'filter_capability']);
		}

		$args		= ['sanitize_callback'	=> [$this, 'sanitize_callback']];
		$settings	= [];
		
		// 只需注册字段，add_settings_section 和 add_settings_field 可以在具体设置页面添加	
		if($this->option_type == 'single'){
			foreach($this->get_sections() as $section_id => $section){
				foreach($section['fields'] as $key => $field){
					if(wpjam_get_fieldset_type($field) == 'single'){
						foreach($field['fields'] as $sub_key => $sub_field){
							$settings[$sub_key]	= array_merge($args, ['field'=>$sub_field]);

							register_setting($this->option_group, $sub_key, $settings[$sub_key]);
						}

						continue;
					}

					$settings[$key]	= array_merge($args, ['field'=>$field]);

					register_setting($this->option_group, $key, $settings[$key]);
				}
			}
		}else{
			$settings[$this->name]	= array_merge($args, ['type'=>'object']);

			register_setting($this->option_group, $this->name, $settings[$this->name]);
		}

		return $settings;
	}

	public function filter_capability(){
		return $this->capability;
	}

	public function sanitize_callback($value){
		try{
			if($this->option_type == 'array'){
				$value		= $this->validate($value) ?: [];
				$current	= $this->value_callback();

				$value	= array_merge($current, $value);
				$value	= filter_deep($value, 'is_exists');
				$result	= $this->call_method('sanitize_callback', $value, $this->name);

				if(!is_null($result)){
					$value	= $result;
				}
			}else{
				$option		= str_replace('sanitize_option_', '', current_filter());
				$registered	= get_registered_settings();

				if(!isset($registered[$option])){
					return $value;
				}

				$fields	= [$option=>$registered[$option]['field']];
				$value	= wpjam_fields($fields)->validate([$option=>$value]);
				$value	= $value[$option] ?? null;
			}

			return $value;
		}catch(WPJAM_Exception $e){
			add_settings_error($option, $e->get_error_code(), $e->get_error_message());

			if($this->option_type == 'array'){
				return $current;
			}else{
				return get_option($option);
			}
		}
	}

	public function ajax_response(){
		$option_page	= wpjam_get_data_parameter('option_page');
		$nonce			= wpjam_get_data_parameter('_wpnonce');

		if($option_page != $this->option_page || !wp_verify_nonce($nonce, $option_page.'-options')){
			return new WP_Error('invalid_nonce');
		}

		$capability	= $this->capability ?: 'manage_options';

		if(!current_user_can($capability)){
			return new WP_Error('bad_authentication');
		}

		$options	= $this->register_settings();

		if(empty($options)){
			return new WP_Error('error', '字段未注册');
		}

		$option_action		= wpjam_get_parameter('option_action', ['method'=>'POST']);
		$is_network_admin	= is_multisite() && is_network_admin();

		foreach($options as $option => $args){
			$option = trim($option);

			if($option_action == 'reset'){
				delete_option($option);
			}else{
				$value	= wpjam_get_data_parameter($option);

				if($this->update_callback && is_callable($this->update_callback)){
					call_user_func($this->update_callback, $option, $value, $is_network_admin);
				}else{
					$callback	= $is_network_admin ? 'update_site_option' : 'update_option';

					if($this->option_type == 'array'){
						$callback	= 'wpjam_'.$callback;
					}else{
						$value		= is_wp_error($value) ? null : $value;
					}

					call_user_func($callback, $option, $value);
				}
			}
		}

		if($settings_errors = get_settings_errors()){
			$errmsg = '';

			foreach($settings_errors as $key => $details){
				if (in_array($details['type'], ['updated', 'success', 'info'])) {
					continue;
				}

				$errmsg	.= $details['message'].'&emsp;';
			}

			return new WP_Error('update_error', $errmsg);
		}else{
			$response	= $this->response ?? ($this->ajax ? $option_action : 'redirect');
			$errmsg		= $option_action == 'reset' ? '设置已重置。' : '设置已保存。';

			return ['type'=>$response,	'errmsg'=>$errmsg];
		}
	}

	public static function generate_item_key($args=null){
		if(is_null($args)){
			$item_key	= $GLOBALS['plugin_page'] ?? '';
		}else{
			$item_key	= $args['plugin_page'] ?? '';
		}

		if($item_key){
			if(is_null($args)){
				$current_tab	= $GLOBALS['current_tab'] ?? '';
			}else{
				$current_tab	= $args['current_tab'] ?? '';
			}

			if($current_tab){
				$item_key	.= ':'.$current_tab;
			}
		}

		return $item_key;
	}

	public static function create($name, $args){
		$args	= is_callable($args) ? call_user_func($args, $name) : $args;
		$args	= apply_filters('wpjam_register_option_args', $args, $name);
		$args	= wp_parse_args($args, [
			'option_group'	=> $name, 
			'option_page'	=> $name, 
			'option_type'	=> 'array',
			'capability'	=> 'manage_options',
			'ajax'			=> true,
			'init'			=> true,
		]);

		$object		= self::get($name);
		$item_key	= self::generate_item_key($args);

		if($object){
			if(!$item_key || $object->get_item($item_key)){
				trigger_error('option_setting'.'「'.$name.'」已经注册。'.var_export($args, true));
			}else{
				$object->add_item($item_key, self::preprocess_args($args));
			}
		}else{
			if($args['option_type'] == 'array' && !doing_filter('sanitize_option_'.$name)){
				if(is_null(get_option($name, null))){
					add_option($name, []);
				}
			}

			$object	= self::register($name, $args);

			if($item_key){
				$object->add_item($item_key, $args);
			}
		}

		return $object;
	}

	protected static function get_config($key){
		if($key == 'menu_page'){
			return true;
		}elseif($key == 'item_arg'){
			return 'model';
		}
	}

	public static function get_by_model($model){
		if($model != 'wpjam_option_model'){
			$parent	= get_parent_class($model);
			$parent	= ($parent && !is_a($parent, 'WPJAM_Option_Model')) ? strtolower($parent) : '';

			foreach(self::get_registereds() as $object){
				if($object->model && is_string($object->model)){
					$object_model	= strtolower($object->model);

					if($object_model == $model || ($parent && $object_model == $parent)){
						return $object;
					}
				}

				foreach($object->get_items() as $item){
					if(!empty($item['model']) && is_string($item['model'])){
						$object_model	= strtolower($item['model']);

						if($object_model == $model || ($parent && $object_model == $parent)){
							return $object;
						}
					}
				}
			}
		}
	}
}

class WPJAM_Option_Section extends WPJAM_Register{
	public static function filter($sections, $option_name){
		foreach(self::get_by('option_name', $option_name) as $object){
			foreach($object->sections as $section_id => $section){
				if(!empty($section['fields']) && is_callable($section['fields'])){
					$section['fields']	= call_user_func($section['fields'], $section_id, $option_name);
				}

				if(isset($sections[$section_id])){
					$sections[$section_id]	= merge_deep($sections[$section_id], $section);
				}else{
					if(isset($section['title']) && isset($section['fields'])){
						$sections[$section_id]	= $section;
					}
				}
			}
		}

		return $sections;
	}

	public static function add($option_name, ...$args){
		$sections	= is_array($args[0]) ? $args[0] : [$args[0]=>$args[1]];
		$count		= count(self::get_by('option_name', $option_name));

		return self::register($option_name.':'.$count, ['option_name'=>$option_name, 'sections'=>$sections]);
	}
}

class WPJAM_Option_Model{
	protected static function call_method($method, ...$args){
		$object	= WPJAM_Option_Setting::get_by_model(strtolower(get_called_class()));

		return $object ? call_user_func_array([$object, $method], $args) : null;
	}

	public static function get_setting($name='', $default=null){
		return self::call_method('get_setting', $name) ?? $default;
	}

	public static function update_setting($name, $value){
		return self::call_method('update_setting', $name, $value);
	}

	public static function delete_setting($name){
		return self::call_method('delete_setting', $name);
	}
}

class WPJAM_Extend{
	protected $dir;
	protected $args;
	protected $name;
	
	protected function __construct($dir, $args=[], $name=''){
		$this->dir	= $dir;
		$this->args	= $args;
		$this->name	= $name;
	
		if($this->hook){
			$priority	= $this->priority ?? 10;

			add_action($this->hook, [$this, 'load'], $priority);
		}else{
			$this->load();
		}
	}

	public function __get($key){
		if(in_array($key, ['name', 'dir'])){
			return $this->$key;
		}else{
			return $this->args[$key] ?? null;
		}
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function parse_file($extend){
		if($extend == '.' || $extend == '..'){
			return '';
		}

		$file	= '';

		if($this->hierarchical){
			if(is_dir($this->dir.'/'.$extend)){
				$file	= $this->dir.'/'.$extend.'/'.$extend.'.php';
			}
		}else{
			if(pathinfo($extend, PATHINFO_EXTENSION) == 'php'){
				$file	= $this->dir.'/'.$extend;
			}
		}

		return ($file && is_file($file)) ? $file : '';
	}

	public function load_file($extend){
		$file	= $this->parse_file($extend);

		if($file){
			include $file;
		}
	}

	public function load(){
		if($this->name){
			if(is_admin()){
				$summary	= $this->summary ?: '';

				if($this->sitewide && is_multisite() && is_network_admin()){
					$summary	.= $summary ? '，' : '';
					$summary	.= '在管理网络激活将整个站点都会激活！';
				}

				wpjam_register_option($this->name, array_merge($this->args, [
					'fields'	=> [$this, 'get_fields'],
					'ajax'		=> false,
					'summary'	=> $summary,
				]));
			}

			foreach($this->get_data() as $extend => $value){
				$this->load_file($extend);
			}
		}else{
			if($handle = opendir($this->dir)){
				while(false !== ($extend = readdir($handle))){
					$this->load_file($extend);
				}

				closedir($handle);
			}
		}
	}

	public function get_data($type=''){
		if($type == 'blog'){
			$data	= wpjam_get_option($this->name);
			$data	= $data ? array_filter($data) : [];
		}elseif($type == 'site'){
			$data	= wpjam_get_site_option($this->name);
			$data	= $data ? array_filter($data) : [];
		}else{
			$data	= $this->get_data('blog');

			if($this->sitewide && is_multisite()){
				$data	= array_merge($data, $this->get_data('site'));
			}
		}

		return $data;
	}

	public function get_fields(){
		$values	= $this->get_data('blog');

		if(is_multisite() && $this->sitewide){
			$sitewide	= $this->get_data('site');

			if(is_network_admin()){
				$values	= $sitewide;
			}
		}

		$fields	= [];

		if($handle = opendir($this->dir)){
			while(false  !== ($extend = readdir($handle))){
				if(is_multisite() && $this->sitewide && !is_network_admin()){
					if(!empty($sitewide[$extend])){
						continue;
					}
				}

				$file	= $this->parse_file($extend);
				$data	= $this->get_file_data($file);

				if($data && ($data['Name'] || $data['PluginName'])){
					$title	= $data['Name'] ?: $data['PluginName'];
					// $uri	= $data['URI'] ?: $data['PluginURI'];
					$uri	= $data['URI'];

					if($uri){
						$title	= '<a href="'.$uri.'" target="_blank">'.$title.'</a>';
					}

					$fields[$extend] = [
						'title'			=> $title,
						'type'			=> 'checkbox',
						'value'			=> !empty($values[$extend]) ? 1 : 0,
						'description'	=> $data['Description']
					];
				}
			}

			closedir($handle);
		}

		return wp_list_sort($fields, 'value', 'DESC', true);
	}

	public static function get_file_data($file){
		return $file ? get_file_data($file, [
			'Name'			=> 'Name',
			'URI'			=> 'URI',
			'PluginName'	=> 'Plugin Name',
			'PluginURI'		=> 'Plugin URI',
			'Version'		=> 'Version',
			'Description'	=> 'Description'
		]) : [];
	}

	public static function get_file_summay($file){
		$data	= self::get_file_data($file);

		foreach(['URI', 'Name'] as $key){
			if(empty($data[$key])){
				$data[$key]	= $data['Plugin'.$key] ?? '';
			}
		}

		$summary	= str_replace('。', '，', $data['Description']);
		$summary	.= '详细介绍请点击：<a href="'.$data['URI'].'" target="_blank">'.$data['Name'].'</a>。';

		return $summary;
	}

	public static function create($dir, $args=[], $name=''){
		if($dir && is_dir($dir)){
			new self($dir, $args, $name);
		}
	}
}

class WPJAM_Notice{
	use WPJAM_Instance_Trait;

	private $id;
	private $type;

	protected function __construct($type='', $id=0){
		$this->type	= $type;
		$this->id	= $id;
	}

	public function get_store_key(){
		if(str_starts_with($this->type, 'user_')){
			return 'wpjam_'.wpjam_remove_prefix($this->type, 'user_').'s';
		}else{
			return 'wpjam_notices';
		}
	}

	public function __get($key){
		if($key == 'data'){
			$store_key	= $this->get_store_key();

			if($this->type == 'admin_notice'){
				$data	= is_multisite() ? get_blog_option($this->id, $store_key) : get_option($store_key);
			}else{
				$data	= get_user_meta($this->id, $store_key, true);
			}

			return $data ? array_filter($data, [$this, 'filter_item']) : [];
		}
	}

	public function __set($key, $value){
		if($key == 'data'){
			$store_key	= $this->get_store_key();

			if($this->type == 'admin_notice'){
				if(empty($value)){
					return is_multisite() ? delete_blog_option($this->id, $store_key) : delete_option($store_key);
				}else{
					return is_multisite() ? update_blog_option($this->id, $store_key, $value) : update_option($store_key, $value);
				}
			}else{
				if(empty($value)){
					return delete_user_meta($this->id, $store_key);
				}else{
					return update_user_meta($this->id, $store_key, $value);
				}
			}
		}
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function filter_item($item){
		if($item['time'] > time() - MONTH_IN_SECONDS * 3){
			return trim($item['notice']);
		}

		return false;
	}

	public function insert($item){
		$data	= $this->data;

		if(!is_array($item)){
			$item = ['notice'=>$item];
		}

		$key	= $item['key'] ?? '';
		$key	= $key ?: md5(maybe_serialize($item));

		$data[$key]	= wp_parse_args($item, ['notice'=>'', 'type'=>'error', 'time'=>time()]);

		$this->data	= $data;

		return true;
	}

	public function update($key, $item){
		if(isset($this->data[$key])){
			$this->data[$key]	= $item;
		}

		return true;
	}

	public function delete($key){
		$this->data	= array_except($this->data, $key);

		return true;
	}

	public static function get_instance($type, $id=null){
		if($type == 'admin_notice'){
			if(is_multisite()){
				$id	= $id ?: get_current_blog_id();

				if(!get_site($id)){
					return;
				}
			}else{
				$id	= null;
			}
		}elseif($type == 'user_notice'){
			$id	= $id ?: get_current_user_id();

			if(!get_userdata($id)){
				return;
			}
		}else{
			return;
		}

		return self::instance($type, $id);
	}

	public static function add($item){	// 兼容函数
		return wpjam_add_admin_notice($item);
	}
}