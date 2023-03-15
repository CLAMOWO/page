<?php
trait WPJAM_Call_Trait{
	protected static $_closures	= [];

	public static function add_dynamic_method($method, Closure $closure){
		if(is_callable($closure)){
			$name	= strtolower(get_called_class());

			self::$_closures[$name][$method]	= $closure;
		}
	}

	protected static function get_dynamic_method($method){
		$called	= get_called_class();
		$names	= array_values(class_parents($called));

		array_unshift($names, $called);

		foreach($names as $name){
			$name	= strtolower($name);

			if(isset(self::$_closures[$name][$method])){
				return self::$_closures[$name][$method];
			}
		}
	}

	public function call($method, ...$args){
		try{
			$called	= get_called_class();

			if(is_closure($method)){
				$callback	= $method->bindTo($this, $called);
			}elseif(is_callable([$this, $method])){
				$callback	= [$this, $method];
			}else{
				$closure	= self::get_dynamic_method($method);

				if($closure){
					$callback	= $closure->bindTo($this, $called);
				}else{
					trigger_error($called.':'.$method, true);

					return;
				}
			}

			return call_user_func_array($callback, $args);
		}catch(WPJAM_Exception $e){
			return $e->get_wp_error();
		}catch(Exception $e){
			return new WP_Error($e->getCode(), $e->getMessage());
		}
	}

	public function try($method, ...$args){
		if(is_callable([$this, $method])){
			try{
				$result	= call_user_func_array([$this, $method], $args);

				if(is_wp_error($result)){
					throw new WPJAM_Exception($result);
				}

				return $result;
			}catch(Exception $e){
				throw $e;
			}
		}

		trigger_error(get_called_class().':'.$method, true);
	}

	public function map($value, $method, ...$args){
		if($value && is_array($value)){
			foreach($value as &$item){
				$item	= $this->try($method, $item, ...$args);
			}
		}

		return $value;
	}
}

trait WPJAM_Items_Trait{
	public function get_items(){
		$items	= $this->_items;

		return is_array($items) ? $items : [];
	}

	public function update_items($items){
		$this->_items	= $items;

		return $this;
	}

	public function get_item_keys(){
		return array_keys($this->get_items());
	}

	public function item_exists($key){
		return array_key_exists($key, $this->get_items());
	}

	public function get_item($key){
		$items	= $this->get_items();

		return $items[$key] ?? null;
	}

	public function add_item(...$args){
		if(count($args) == 2){
			$key	= $args[0];
			$item	= $args[1];

			if($this->item_exists($key)){
				return new WP_Error('invalid_item_key', [$key, '添加']);
			}

			return $this->set_item($key, $item, 'add');
		}else{
			$item	= $args[0];
			$result	= $this->validate_item($item, null, 'add');

			if(is_wp_error($result)){
				return $result;
			}

			$items		= $this->get_items();
			$items[]	= $this->sanitize_item($item, null, 'add');

			return $this->update_items($items);
		}
	}

	public function edit_item($key, $item){
		if(!$this->item_exists($key)){
			return new WP_Error('invalid_item_key', [$key, '编辑']);
		}

		return $this->set_item($key, $item, 'edit');
	}

	public function replace_item($key, $item){
		if(!$this->item_exists($key)){
			return new WP_Error('invalid_item_key', [$key, '编辑']);
		}

		return $this->set_item($key, $item, 'replace');
	}

	public function set_item($key, $item, $action='set'){
		$result	= $this->validate_item($item, $key, $action);

		if(is_wp_error($result)){
			return $result;
		}

		$items			= $this->get_items();
		$items[$key]	= $this->sanitize_item($item, $key, $action);

		return $this->update_items($items);
	}

	public function delete_item($key){
		if(!$this->item_exists($key)){
			return new WP_Error('invalid_item_key', [$key, '删除']);
		}

		$result	= $this->validate_item(null, $key, 'delete');

		if(is_wp_error($result)){
			return $result;
		}

		$items	= $this->get_items();
		$items	= array_except($items, $key);
		$result = $this->update_items($items);

		if(!is_wp_error($result)){
			$this->after_delete_item($key);
		}

		return $result;
	}

	public function del_item($key){
		return $this->delete_item($key);
	}

	public function move_item($orders){
		$new_items	= [];
		$items		= $this->get_items();

		foreach($orders as $i){
			if(isset($items[$i])){
				$new_items[]	= array_pull($items, $i);
			}
		}

		return $this->update_items(array_merge($new_items, $items));
	}

	protected function validate_item($item=null, $key=null, $action=''){
		return true;
	}

	protected function sanitize_item($item, $id=null){
		return $item;
	}

	protected function after_delete_item($key){
	}
}

class WPJAM_Args implements ArrayAccess, IteratorAggregate, JsonSerializable{
	use WPJAM_Call_Trait;

	protected $args;
	protected $_archives	= [];

	public function __construct($args=[]){
		$this->args	= $args;
	}

	public function __get($key){
		return $this->offsetGet($key);
	}

	public function __set($key, $value){
		$this->offsetSet($key, $value);
	}

	public function __isset($key){
		return $this->offsetExists($key);
	}

	public function __unset($key){
		$this->offsetUnset($key);
	}

	#[ReturnTypeWillChange]
	public function offsetGet($key){
		$args	= $this->get_args();
		$value	= $args[$key] ?? null;

		if(is_null($value) && $key == 'args'){
			return $args;
		}

		return $value;
	}

	#[ReturnTypeWillChange]
	public function offsetSet($key, $value){
		$this->filter_args();

		if(is_null($key)){
			$this->args[]		= $value;
		}else{
			$this->args[$key]	= $value;
		}
	}

	#[ReturnTypeWillChange]
	public function offsetExists($key){
		return array_key_exists($key, $this->get_args());
	}

	#[ReturnTypeWillChange]
	public function offsetUnset($key){
		$this->filter_args();

		unset($this->args[$key]);
	}

	public function getIterator(){
		return new ArrayIterator($this->get_args());
	}

	public function jsonSerialize(){
		return $this->get_args();
	}

	public function invoke(...$args){
		$invoke	= $this->invoke;

		if($invoke && is_closure($invoke)){
			return $this->call($invoke, ...$args);
		}
	}

	protected function error($errcode, $errmsg){
		return new WP_Error($errcode, $errmsg);
	}

	protected function filter_args(){
		return $this->args = $this->args ?: [];
	}

	public function get_args(){
		return $this->filter_args();
	}

	public function update_args($args){
		foreach($args as $key => $value){
			$this->offsetSet($key, $value);
		}

		return $this;
	}

	public function get_archives(){
		return $this->_archives;
	}

	public function archive(){
		array_push($this->_archives, $this->get_args());

		return $this;
	}

	public function restore(){
		if($this->_archives){
			$this->args	= array_pop($this->_archives);
		}

		return $this;
	}

	public function sandbox($callback, ...$args){
		$this->archive();

		$result	= call_user_func_array($callback, $args);

		$this->restore();

		return $result;
	}

	public function get_arg($key, $default=null){
		return array_get($this->get_args(), $key, $default);
	}

	public function update_arg($key, $value=null){
		$this->filter_args();

		array_set($this->args, $key, $value);

		return $this;
	}

	public function delete_arg($key){
		$this->args	= array_except($this->get_args(), $key);

		return $this;
	}

	public function pull($key, $default=null){
		$value	= $this->get_arg($key, $default);

		$this->delete_arg($key);

		return $value;
	}

	public function pulls($keys){
		$data	= wp_array_slice_assoc($this->get_args(), $keys);

		$this->delete_arg($keys);

		return $data;
	}
}

class WPJAM_Register extends WPJAM_Args{
	use WPJAM_Items_Trait;

	protected $name;
	protected $_group;
	protected $_filtered	= false;

	public function __construct($name, $args=[], $group=''){
		$this->name		= $name;
		$this->_group	= self::parse_group($group);

		if($this->is_active() || !empty($args['active'])){
			$args	= self::preprocess_args($args, $name);
		}

		$this->args	= $args;
	}

	#[ReturnTypeWillChange]
	public function offsetGet($key){
		if($key == 'name'){
			return $this->name;
		}else{
			return parent::offsetGet($key);
		}
	}

	#[ReturnTypeWillChange]
	public function offsetSet($key, $value){
		if($key != 'name'){
			parent::offsetSet($key, $value);
		}
	}

	#[ReturnTypeWillChange]
	public function offsetExists($key){
		if($key == 'name'){
			return true;
		}

		return parent::offsetExists($key);
	}

	protected function get_called_method($method, $type=null, $args=null){
		if($type == 'model'){
			$model	= $args ? array_get($args, 'model') : $this->model;

			if($model && method_exists($model, $method)){
				return [$model, $method];
			}
		}elseif($type == 'property'){
			if($this->$method && is_callable($this->$method)){
				return $this->$method;
			}
		}else{
			foreach(['model', 'property'] as $type){
				$called = $this->get_called_method($method, $type);

				if($called){
					return $called;
				}
			}
		}

		return null;
	}

	protected function call_method($method, ...$args){
		$called	= $this->get_called_method($method);

		if($called){
			return call_user_func_array($called, $args);
		}

		if(str_starts_with($method, 'filter_')){
			return $args[0] ?? null;
		}

		return null;
	}

	protected function method_exists($method, $type=null){
		return $this->get_called_method($method, $type) ? true : false;
	}

	protected function parse_args(){	// 子类实现
		return $this->args;
	}

	protected function get_filter(){
		$class	= strtolower(get_called_class());

		if($class == 'wpjam_register'){
			return 'wpjam_'.$this->_group.'_args';
		}else{
			return $class.'_args';
		}
	}

	protected function filter_args(){
		if(!$this->_filtered){
			$this->_filtered	= true;

			$args	= $this->parse_args();
			$args	= is_null($args) ? $this->args : $args;
			$filter	= $this->get_filter();

			if($filter){
				$args	= apply_filters($filter, $args, $this->name);
			}

			$this->args	= $args;
		}

		return $this->args;
	}

	public function get_arg($key='', $default=null){
		$value	= parent::get_arg($key, $default);

		if(is_null($value) && $this->model && $key && is_string($key) && strpos($key, '.') === false){
			$value	= $this->get_called_method('get_'.$key, 'model');
		}

		return $value;
	}

	public function get_item_arg($item_key, $key, $default=null){
		$item	= $this->get_item($item_key);

		if($item){
			if(isset($item[$key])){
				return $item[$key];
			}

			if(static::get_config('item_arg') == 'model'){
				return $this->get_called_method('get_'.$key, 'model', $item);
			}
		}

		return $default;
	}

	public function to_array(){
		return $this->get_args();
	}

	public function is_active(){
		return true;
	}

	public function match($args, $operator='AND'){
		if(static::get_config('data_type')){
			$slice	= wpjam_slice_data_type($args, true);

			if($this->data_type){
				$data_type	= $slice['data_type'] ?? '';
				$type_value	= $slice[$data_type] ?? '';

				if($data_type != $this->data_type){
					return false;
				}

				if($this->$data_type){
					if(is_callable($this->$data_type)){
						if(!wpjam_call($this->$data_type, $type_value, $this)){
							return false;
						}
					}else{
						if(!wpjam_compare($type_value, (array)$this->$data_type)){
							return false;
						}
					}
				}
			}
		}

		return $args ? wpjam_match($this, $args, $operator) : true;
	}

	public function add_menu_page($item_key=''){
		$cb_args	= [$this->name];

		if($item_key){
			$menu_page	= $this->get_item_arg($item_key, 'menu_page');
			$cb_args[]	= $item_key;
		}else{
			$menu_page	= $this->get_arg('menu_page');
		}

		if($menu_page){
			if(is_callable($menu_page)){
				$menu_page	= call_user_func_array($menu_page, $cb_args);
			}

			if(isset($menu_page['plugin_page']) && isset($menu_page['tab_slug'])){
				$tab_slug	= array_pull($menu_page, 'tab_slug');

				wpjam_register_plugin_page_tab($tab_slug, $menu_page);
			}else{
				$menu_slug	= array_pull($menu_page, 'menu_slug') ?: $this->name;

				wpjam_add_menu_page($menu_slug, $menu_page);
			}
		}

		if(!$item_key && static::get_config('item_arg')){
			foreach($this->get_item_keys() as $item_key){
				$this->add_menu_page($item_key);
			}
		}
	}

	protected static $_registereds	= [];
	protected static $_configs		= [];

	protected static function call_config($action, $key, $value=null){
		$name	= self::parse_group();

		if($name){
			if(!isset(self::$_configs[$name])){
				self::$_configs[$name] = new WPJAM_Args();
			}

			$object	= self::$_configs[$name];

			if($action == 'get'){
				return $object->get_arg($key, $value);
			}elseif($action == 'update'){
				$object->update_arg($key, $value);
			}elseif($action == 'delete'){
				$object->delete_arg($key);
			}
		}
	}

	protected static function get_config($key){
		return self::call_config('get', $key);
	}

	protected static function register_config($args){
		foreach($args as $key => $value){
			self::call_config('update', $key, $value);
		}
	}

	protected static function update_config($key, $value){
		self::call_config('update', $key, $value);
	}

	protected static function delete_config($key){
		self::call_config('delete', $key);
	}

	protected static function validate_name($name){
		if(empty($name)){
			trigger_error(self::class.'的注册 name 为空');
			return null;
		}elseif(is_numeric($name)){
			trigger_error(self::class.'的注册 name「'.$name.'」'.'为纯数字');
			return null;
		}elseif(!is_string($name)){
			trigger_error(self::class.'的注册 name「'.var_export($name, true).'」不为字符串');
			return null;
		}

		return $name;
	}

	protected static function parse_group($group=null){
		if($group){
			return strtolower($group);
		}else{
			$group	= wpjam_remove_prefix(strtolower(get_called_class()), 'wpjam_');

			return $group == 'register' ? '' : $group;
		}
	}

	public static function preprocess_args($args){
		$model_config	= static::get_config('model');
		$model_config	= $model_config	?? true;

		$model	= $model_config ? ($args['model'] ?? null) : null;
		$init	= array_pull($args, 'init');

		if($model || $init){
			$file	= array_pull($args, 'file');

			if($file && is_file($file)){
				include_once $file;
			}
		}

		if($model_config === 'object'){
			if(!$model){
				trigger_error('model 不存在');
			}

			if(!is_object($model)){
				if(!class_exists($model)){
					trigger_error('model 无效');
				}

				$model = $args['model']	= new $model($args);
			}
		}

		if($model){
			if(static::get_config('register_json')){
				if(method_exists($model, 'register_json')){
					add_action('wpjam_api',	[$model, 'register_json']);
				}
			}

			if(method_exists($model, 'add_hooks')){
				wpjam_call([$model, 'add_hooks']);
			}

			if($init === true || (is_null($init) && static::get_config('init'))){
				if(method_exists($model, 'init')){
					$init	= [$model, 'init'];
				}
			}
		}

		if($init && $init !== true){
			wpjam_load('init', $init);
		}

		if(is_admin() && static::get_config('menu_page')){
			add_action('wpjam_admin_init', [get_called_class(), 'add_menu_pages']);
		}

		return $args;
	}

	public static function get_by_group($group=null, $name=null, $args=[], $operator='AND'){
		if($name){
			$objects	= self::get_by_group($group, null, $args, $operator);

			if(static::get_config('data_type') && !isset($args['data_type'])){	
				$objects	= wp_filter_object_list($objects, ['name'=>$name]);

				if($objects && count($objects) == 1){
					return current($objects);
				}
			}else{
				if(isset($objects[$name])){
					return $objects[$name];
				}
			}

			return null;
		}

		$group		= self::parse_group($group);
		$objects	= self::$_registereds[$group] ?? [];

		if($args){
			$data_type	= static::get_config('data_type') && !empty($args['data_type']);
			$filtered	= [];

			foreach($objects as $name => $object){
				if($object->match($args, $operator)){
					if($data_type){
						$filtered[$object->name]	= $object;
					}else{
						$filtered[$name]	= $object;
					}
				}
			}

			return $filtered;
		}

		return $objects;
	}

	public static function register_by_group($group, ...$args){
		$group			= self::parse_group($group);
		$registereds	= self::$_registereds[$group] ?? [];

		if(is_object($args[0])){
			$args	= $args[0];
			$name	= $args->name;
		}elseif(is_array($args[0])){
			$args	= $args[0];
			$name	= '__'.count($registereds);
		}else{
			$name	= self::validate_name($args[0]);
			$args	= $args[1] ?? [];

			if(is_null($name)){
				return null;
			}
		}

		if(is_object($args)){
			$object	= $args;
		}else{
			$object	= new static($name, $args, $group);
			$name	.= self::generate_postfix($args);
		}
		
		if(isset($registereds[$name])){
			trigger_error($group.'「'.$name.'」已经注册。');
		}

		$orderby	= static::get_config('orderby');

		if($orderby){
			$orderby	= $orderby === true ? 'order' : $orderby;
			$current	= $object->$orderby = $object->$orderby ?? 10;
			$order		= static::get_config('order');
			$order		= $order ? strtoupper($order) : 'DESC';
			$sorted		= [];

			foreach($registereds as $_name => $_registered){
				if(!isset($sorted[$name])){
					$value	= $current - $_registered->$orderby;
					$value	= $order == 'DESC' ? $value : (0 - $value);

					if($value > 0){
						$sorted[$name]	= $object;
					}
				}

				$sorted[$_name]	= $_registered;
			}

			$sorted[$name]	= $object;

			self::$_registereds[$group]	= $sorted;
		}else{
			self::$_registereds[$group][$name]	= $object;
		}

		return $object;
	}

	public static function unregister_by_group($group, $name, $args=[]){
		$group	= self::parse_group($group);
		$name	.= self::generate_postfix($args);

		if(isset(self::$_registereds[$group][$name])){
			unset(self::$_registereds[$group][$name]);
		}
	}

	public static function register(...$args){
		return self::register_by_group(null, ...$args);
	}

	public static function unregister($name){
		self::unregister_by_group(null, $name);
	}

	public static function get_registereds($args=[], $output='objects', $operator='and'){
		$objects	= self::get_by_group(null, null, $args, $operator);

		if($output == 'names'){
			return array_keys($objects);
		}elseif(in_array($output, ['args', 'settings'])){
			return array_map(function($registered){
				return $registered->to_array();
			}, $objects);
		}else{
			return $objects;
		}
	}

	public static function get_by(...$args){
		$args	= is_array($args[0]) ? $args[0] : [$args[0] => $args[1]];

		return self::get_registereds($args);
	}

	public static function get($name, $args=[]){
		return $name ? self::get_by_group(null, $name, $args) : null;
	}

	public static function exists($name){
		return self::get($name) ? true : false;
	}

	protected static function generate_postfix($args){
		if(static::get_config('data_type') && !empty($args['data_type'])){
			return '__'.md5(maybe_serialize(wpjam_slice_data_type($args)));
		}

		return '';
	}

	public static function add_menu_pages(){
		foreach(self::get_active() as $object){
			$object->add_menu_page();
		}
	}

	public static function get_setting_fields(){
		$fields	= [];

		foreach(self::get_registereds() as $name => $object){
			if(is_null($object->active)){
				$field	= $object->field ?: [];

				$fields[$name]	= wp_parse_args($field, [
					'title'			=> $object->title,
					'type'			=> 'checkbox',
					'description'	=> $object->description ?: '开启'.$object->title
				]);
			}
		}

		return $fields;
	}

	public static function get_active($key=null, ...$args){
		$return	= [];

		foreach(self::get_registereds() as $name => $object){
			$active	= $object->active ?? $object->is_active();

			if($active){
				if($key){
					$value	= $object->get_arg($key);

					if(is_callable($value)){
						$value	= call_user_func_array($value, $args);
					}

					if(!is_null($value)){
						$return[$name]	= $value;
					}
				}else{
					$return[$name]	= $object;
				}
			}
		}

		return $return;
	}

	public static function call_active($method, ...$args){
		if(str_starts_with($method, 'filter_')){
			$type	= 'filter_';
		}elseif(str_starts_with($method, 'get_')){
			$return	= [];
			$type	= 'get_';
		}else{
			$type	= '';
		}

		foreach(self::get_active() as $object){
			$result	= $object->call_method($method, ...$args);

			if(is_wp_error($result)){
				return $result;
			}

			if($type == 'filter_'){
				$args[0]	= $result;
			}elseif($type == 'get_'){
				if($result && is_array($result)){
					$return	= array_merge($return, $result);
				}
			}
		}

		if($type == 'filter_'){
			return $args[0];
		}elseif($type == 'get_'){
			return $return;
		}
	}

	public static function add_filter($hook_name, $method, $priority=10, $accepted_args=1){
		if(method_exists(get_called_class(), $method)){
			$callback	= [get_called_class(), $method];
		}else{
			$callback	= function(...$args) use($method){
				return self::call_active($method, ...$args);
			};
		}

		add_filter($hook_name, $callback, $priority, $accepted_args);
	}

	public static function add_action($hook_name, $method, $priority=10, $accepted_args=1){
		return self::add_filter($hook_name, $method, $priority, $accepted_args);
	}

	protected static function get_model($args){	// 兼容
		$file	= array_pull($args, 'file');

		if($file && is_file($file)){
			include_once $file;
		}

		return $args['model'] ?? null;
	}
}

class WPJAM_Meta_Type extends WPJAM_Register{
	public function __construct($name, $args=[]){
		$name	= sanitize_key($name);
		$args	= wp_parse_args($args, [
			'table_name'		=> $name.'meta',
			'table'				=> $GLOBALS['wpdb']->prefix.$name.'meta',
			'object_callback'	=> 'wpjam_get_'.$name.'_object',
		]);

		if(!isset($GLOBALS['wpdb']->{$args['table_name']})){
			$GLOBALS['wpdb']->{$args['table_name']} = $args['table'];
		}
		
		parent::__construct($name, $args);

		wpjam_register_lazyloader($name.'_meta', [
			'filter'	=> 'get_'.$name.'_metadata',
			'callback'	=> [$this, 'update_cache']
		]);
	}

	public function __call($method, $args){
		if(str_ends_with($method, '_meta')){
			$method	= str_replace('_meta', '_data', $method);
		}elseif(str_contains($method, '_meta')){
			$method	= str_replace('_meta', '', $method);
		}else{
			return;
		}

		if(method_exists($this, $method)){
			return call_user_func_array([$this, $method], $args);
		}else{
			return null;
		}
	}

	public function lazyload_data($ids){
		wpjam_lazyload($this->name.'_meta', $ids);
	}

	public function get_object($id, ...$args){
		$callback	= $this->object_callback;

		if($callback && is_callable($callback)){
			return call_user_func($callback, $id, ...$args);
		}

		return null;
	}

	public function get_data($id, $meta_key='', $single=false){
		return get_metadata($this->name, $id, $meta_key, $single);
	}

	public function get_data_with_default($id, ...$args){
		if(is_array($args[0])){
			$meta_keys	= $args[0];
			$data		= [];

			if($id){
				foreach($this->parse_defaults($meta_keys) as $meta_key => $default){
					$data[$meta_key]	= $this->get_data_with_default($id, $meta_key, $default);
				}	
			}

			return $data;
		}else{
			$meta_key	= $args[0];
			$default	= $args[1] ?? null;

			if($id && metadata_exists($this->name, $id, $meta_key)){
				return $this->get_data($id, $meta_key, true);
			}

			return $default;
		}
	}

	public function add_data($id, $meta_key, $meta_value, $unique=false){
		return add_metadata($this->name, $id, $meta_key, wp_slash($meta_value), $unique);
	}

	public function update_data($id, $meta_key, $meta_value, $prev_value=''){
		return update_metadata($this->name, $id, $meta_key, wp_slash($meta_value), $prev_value);
	}

	public function update_data_with_default($id, ...$args){
		if(is_array($args[0])){
			$data		= $args[0];
			$defaults	= (isset($args[1]) && is_array($args[1])) ? $args[1] : array_keys($data);
			
			foreach($this->parse_defaults($defaults) as $meta_key => $default){
				$meta_value	= $data[$meta_key] ?? null;

				$this->update_data_with_default($id, $meta_key, $meta_value, $default);
			}

			return true;
		}else{
			$meta_key	= $args[0];
			$meta_value	= $args[1];
			$default	= $args[2] ?? null;

			if(is_array($meta_value)){
				if((!is_array($default) && $meta_value) 
					|| (is_array($default) && array_diff_assoc($default, $meta_value))
				){
					return $this->update_data($id, $meta_key, $meta_value);
				}else{
					return $this->delete_data($id, $meta_key);
				}
			}else{
				if(!is_null($meta_value)
					&& $meta_value !== ''
					&& ((is_null($default) && $meta_value)
						|| (!is_null($default) && $meta_value != $default)
					)
				){
					return $this->update_data($id, $meta_key, $meta_value);
				}else{
					return $this->delete_data($id, $meta_key);
				}
			}
		}
	}

	public function parse_defaults($defaults){
		$return	= [];

		foreach($defaults as $meta_key => $default){
			if(is_numeric($meta_key)){
				if(is_numeric($default)){
					continue;
				}

				$meta_key	= $default;
				$default	= null;
			}

			$return[$meta_key]	= $default;
		}

		return $return;
	}

	public function delete_data($id, $meta_key, $meta_value=''){
		return delete_metadata($this->name, $id, $meta_key, $meta_value);
	}

	public function delete_by_key($meta_key, $meta_value=''){
		return delete_metadata($this->name, null, $meta_key, $meta_value, true);
	}

	public function get_by_key(...$args){
		global $wpdb;

		if(empty($args)){
			return [];
		}

		if(is_array($args[0])){
			$meta_key	= $args[0]['meta_key'] ?? ($args[0]['key'] ?? '');
			$meta_value	= $args[0]['meta_value'] ?? ($args[0]['value'] ?? '');
		}else{
			$meta_key	= $args[0];
			$meta_value	= $args[1] ?? null;
		}

		$where	= [];

		if($meta_key){
			$where[]	= $wpdb->prepare('meta_key=%s', $meta_key);
		}

		if(!is_null($meta_value)){
			$where[]	= $wpdb->prepare('meta_value=%s', maybe_serialize($meta_value));
		}

		if(!$where){
			return [];
		}

		$where	= implode(' AND ', $where);
		$table	= _get_meta_table($this->name);
		$data	= $wpdb->get_results("SELECT * FROM {$table} WHERE {$where}", ARRAY_A) ?: [];

		foreach($data as &$item){
			$item['meta_value']	= maybe_unserialize($item['meta_value']);
		}

		return $data;
	}

	public function update_cache($ids){
		if($ids){
			update_meta_cache($this->name, $ids);
		}
	}

	public function create_table(){
		$table	= _get_meta_table($this->name);

		if($GLOBALS['wpdb']->get_var("show tables like '{$table}'") != $table){
			$column	= $this->name.'_id';

			$GLOBALS['wpdb']->query("CREATE TABLE {$table} (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				{$column} bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY  (meta_id),
				KEY {$column} ({$column}),
				KEY meta_key (meta_key(191))
			)");
		}
	}

	public static function autoload(){
		self::register('post');
		self::register('term');
		self::register('user');
		self::register('comment');

		if(is_multisite()){
			self::register('blog');
			self::register('site');
		}
	}
}

class WPJAM_Meta_Option extends WPJAM_Register{
	public function parse_args(){
		$args	= $this->args;

		if(empty($args['value_callback']) || !is_callable($args['value_callback'])){
			$args['value_callback']	= [$this, 'value_callback'];
		}

		if(empty($args['callback'])){
			$update_callback	= array_pull($args, 'update_callback');

			if($update_callback && is_callable($update_callback)){
				$args['callback']	= $update_callback;
			}
		}

		return $args;
	}

	public function get_meta_type(){
		return wpjam_remove_postfix($this->_group, '_option');
	}

	public function get_fields($id=null, $type=''){
		if(is_callable($this->fields)){
			$fields	= call_user_func($this->fields, $id, $this->name);

			return $type == 'object' ? WPJAM_Fields::create($fields) : $fields;
		}else{
			if($type == 'object'){
				if(is_null($this->fields_object)){
					$this->fields_object	= WPJAM_Fields::create($this->fields);
				}

				return $this->fields_object;
			}else{
				return $this->fields;
			}
		}
	}

	public function value_callback($meta_key, $id){
		return wpjam_get_metadata($this->get_meta_type(), $id, $meta_key);
	}

	public function prepare($id=null){
		if($this->callback){
			return [];
		}

		return $this->get_fields($id, 'object')->prepare([
			'value_callback'	=> $this->value_callback,
			'id'				=> $id
		]);
	}

	public function validate($id=null){
		return $this->get_fields($id, 'object')->validate();
	}

	public function callback($id, $data=null){
		if(is_null($data)){
			$data	= $this->validate($id);
		}

		if(is_wp_error($data)){
			return $data;
		}elseif(empty($data)){
			return true;
		}

		if($this->callback){
			if(is_callable($this->callback)){
				$fields	= $this->get_fields($id);
				$result	= call_user_func($this->callback, $id, $data, $fields);

				if(!is_wp_error($result) && $result === false){
					return new WP_Error('invalid_callback');
				}

				return $result;
			}else{
				return new WP_Error('invalid_callback');
			}
		}else{
			$defaults	= $this->get_fields($id, 'object')->get_defaults();

			return wpjam_update_metadata($this->get_meta_type(), $id, $data, $defaults);
		}
	}

	public function register_list_table_action(){
		if($this->title && $this->list_table){
			wpjam_register_list_table_action('set_'.$this->name, wp_parse_args($this->to_array(), [
				'page_title'	=> '设置'.$this->title,
				'submit_text'	=> '设置',
				'meta_type'		=> $this->get_meta_type(),
				'fields'		=> [$this, 'get_fields']
			]));
		}	
	}
}

class WPJAM_Lazyloader extends WPJAM_Register{
	private $pending_objects	= [];

	public function callback($check){
		if($this->pending_objects){
			if($this->accepted_args && $this->accepted_args > 1){
				foreach($this->pending_objects as $object){
					call_user_func($this->callback, $object['ids'], ...$object['args']);
				}
			}else{
				call_user_func($this->callback, $this->pending_objects);
			}

			$this->pending_objects	= [];
		}

		remove_filter($this->filter, [$this, 'callback']);

		return $check;
	}

	public function queue_objects($object_ids, ...$args){
		if(!$object_ids){
			return;
		}

		if($this->accepted_args && $this->accepted_args > 1){
			if((count($args)+1) >= $this->accepted_args){
				$key	= wpjam_json_encode($args);

				if(!isset($this->pending_objects[$key])){
					$this->pending_objects[$key]	= ['ids'=>[], 'args'=>$args];
				}

				$this->pending_objects[$key]['ids']	= array_merge($this->pending_objects[$key]['ids'], $object_ids);
				$this->pending_objects[$key]['ids']	= array_unique($this->pending_objects[$key]['ids']);
			}
		}else{
			$this->pending_objects	= array_merge($this->pending_objects, $object_ids);
			$this->pending_objects	= array_unique($this->pending_objects);
		}

		add_filter($this->filter, [$this, 'callback']);
	}

	public static function autoload(){
		self::register('user',			['filter'=>'wpjam_get_userdata',	'callback'=>'cache_users']);
		self::register('post_term',		['filter'=>'loop_start',	'callback'=>'update_object_term_cache',	'accepted_args'=>2]);
		self::register('attachment',	['filter'=>'loop_start',	'callback'=>['WPJAM_Post', 'update_attachment_caches']]);
	}
}

class WPJAM_Verification_Code extends WPJAM_Register{
	public function parse_args(){
		return wp_parse_args($this->args, [
			'failed_times'	=> 5,
			'cache_time'	=> MINUTE_IN_SECONDS*30,
			'interval'		=> MINUTE_IN_SECONDS,
			'cache'			=> wpjam_cache('verification_code', ['global'=>true, 'prefix'=>$this->name]),
		]);
	}

	public function is_over($key){
		if($this->failed_times && (int)$this->cache->get($key.':failed_times') > $this->failed_times){
			return new WP_Error('quota_exceeded', ['尝试的失败次数', '请15分钟后重试。']);
		}

		return false;
	}

	public function generate($key){
		if($over = $this->is_over($key)){
			return $over;
		}

		if($this->interval && $this->cache->get($key.':time') !== false){
			return new WP_Error('error', '验证码'.((int)($this->interval/60)).'分钟前已发送了。');
		}

		$code = rand(100000, 999999);

		$this->cache->set($key.':code', $code, $this->cache_time);

		if($this->interval){
			$this->cache->set($key.':time', time(), MINUTE_IN_SECONDS);
		}

		return $code;
	}

	public function verify($key, $code){
		if($over = $this->is_over($key)){
			return $over;
		}

		$current	= $this->cache->get($key.':code');

		if(!$code || $current === false){
			return new WP_Error('invalid_code');
		}

		if($code != $current){
			if($this->failed_times){
				$failed_times	= $this->cache->get($key.':failed_times') ?: 0;
				$failed_times	= $failed_times + 1;

				$this->cache->set($key.':failed_times', $failed_times, $this->cache_time/2);
			}

			return new WP_Error('invalid_code');
		}

		return true;
	}

	public static function get_instance($name, $args=[]){
		return self::get($name) ?: self::register($name, $args);
	}
}

class WPJAM_Capability extends WPJAM_Register{
	public static function filter_map_meta_cap($caps, $cap, $user_id, $args){
		if(!in_array('do_not_allow', $caps) && $user_id){
			$object = self::get($cap);

			if($object){
				return call_user_func($object->map_meta_cap, $user_id, $args, $cap);
			}
		}

		return $caps;
	}

	public static function create($cap, $map_meta_cap){
		if(!has_filter('map_meta_cap', [self::class, 'filter_map_meta_cap'])){
			add_filter('map_meta_cap', [self::class, 'filter_map_meta_cap'], 10, 4);
		}

		return self::register($cap, ['map_meta_cap'=>$map_meta_cap]);
	}
}

class WPJAM_AJAX extends WPJAM_Register{
	public function __construct($name, $args=[]){
		parent::__construct($name, $args);

		add_action('wp_ajax_'.$name, [$this, 'callback']);

		if(!empty($args['nopriv'])){
			add_action('wp_ajax_nopriv_'.$name, [$this, 'callback']);
		}
	}

	public function create_nonce($args=[]){
		$nonce_action	= $this->name;

		if($this->nonce_keys){
			foreach($this->nonce_keys as $key){
				if(!empty($args[$key])){
					$nonce_action	.= ':'.$args[$key];
				}
			}
		}

		return wp_create_nonce($nonce_action);
	}

	public function verify_nonce(){
		$nonce_action	= $this->name;

		if($this->nonce_keys){
			foreach($this->nonce_keys as $key){
				if($value = wpjam_get_data_parameter($key)){
					$nonce_action	.= ':'.$value;
				}
			}
		}

		$nonce	= wpjam_get_parameter('_ajax_nonce', ['method'=>'POST']);

		return wp_verify_nonce($nonce, $nonce_action);
	}

	public function callback(){
		if(!$this->callback || !is_callable($this->callback)){
			wp_die('0', 400);
		}

		if($this->verify !== false && !$this->verify_nonce()){
			wpjam_send_error_json('invalid_nonce');
		}

		wpjam_send_json(call_user_func($this->callback));
	}

	public function get_attr($data=[], $return=null){
		$attr	= ['action'=>$this->name, 'data'=>$data];

		if($this->verify !== false){
			$attr['nonce']	= $this->create_nonce($data);
		}

		return $return ? $attr : wpjam_attr($attr, 'data');
	}

	public static function enqueue_scripts(){
		if(wpjam_get_current_var('ajax_enqueued')){
			return;
		}

		wpjam_set_current_var('ajax_enqueued', true);

		$scripts	= '
			if(typeof ajaxurl == "undefined"){
				var ajaxurl	= "'.admin_url('admin-ajax.php').'";
			}

			jQuery(function($){
				if(window.location.protocol == "https:"){
					ajaxurl	= ajaxurl.replace("http://", "https://");
				}

				$.fn.extend({
					wpjam_submit: function(callback){
						let _this	= $(this);

						$.post(ajaxurl, {
							action:			$(this).data(\'action\'),
							_ajax_nonce:	$(this).data(\'nonce\'),
							data:			$(this).serialize()
						},function(data, status){
							callback.call(_this, data);
						});
					},
					wpjam_action: function(callback){
						let _this	= $(this);

						$.post(ajaxurl, {
							action:			$(this).data(\'action\'),
							_ajax_nonce:	$(this).data(\'nonce\'),
							data:			$(this).data(\'data\')
						},function(data, status){
							callback.call(_this, data);
						});
					}
				});
			});
		';

		$scripts	= str_replace("\n\t\t\t", "\n", $scripts);

		if(did_action('wpjam_static') && !wpjam_is_login()){
			wpjam_register_static('wpjam-script',	['title'=>'AJAX 基础脚本', 'type'=>'script',	'source'=>'value',	'value'=>$scripts]);
		}else{
			wp_enqueue_script('jquery');
			wp_add_inline_script('jquery', $scripts);
		}
	}
}

class WPJAM_Verify_TXT extends WPJAM_Register{
	public function get_data($key=''){
		$data	= wpjam_get_setting('wpjam_verify_txts', $this->name) ?: [];

		if($key){
			return $data[$key] ?? '';
		}

		return $data;
	}

	public function set_data($data){
		return wpjam_update_setting('wpjam_verify_txts', $this->name, $data) || true;
	}

	public function get_fields(){
		$data	= $this->get_data();

		return [
			'name'	=>['title'=>'文件名称',	'type'=>'text',	'required', 'value'=>$data['name'] ?? '',	'class'=>'all-options'],
			'value'	=>['title'=>'文件内容',	'type'=>'text',	'required', 'value'=>$data['value'] ?? '']
		];
	}

	public static function __callStatic($method, $args){
		$name	= $args[0];

		if($object = self::get($name)){
			if(in_array($method, ['get_name', 'get_value'])){
				return $object->get_data(str_replace('get_', '', $method));
			}elseif($method == 'set' || $method == 'set_value'){
				return $object->set_data(['name'=>$args[1], 'value'=>$args[2]]);
			}
		}
	}

	public static function filter_root_rewrite_rules($root_rewrite){
		if(empty($GLOBALS['wp_rewrite']->root)){
			$home_path	= parse_url(home_url());

			if(empty($home_path['path']) || '/' == $home_path['path']){
				$root_rewrite	= array_merge(['([^/]+)\.txt?$'=>'index.php?module=txt&action=$matches[1]'], $root_rewrite);
			}
		}

		return $root_rewrite;
	}

	public static function get_rewrite_rule(){
		add_filter('root_rewrite_rules',	[self::class, 'filter_root_rewrite_rules']);
	}

	public static function redirect($action){
		if($values = wpjam_get_option('wpjam_verify_txts')){
			$name	= str_replace('.txt', '', $action).'.txt';

			foreach($values as $key => $value) {
				if($value['name'] == $name){
					header('Content-Type: text/plain');
					echo $value['value'];

					exit;
				}
			}
		}
	}
}

class WPJAM_Gravatar extends WPJAM_Register{
	public function replace($url){
		if(is_ssl()){
			$search	= 'https://secure.gravatar.com/avatar/';
		}else{
			$search = [
				'http://0.gravatar.com/avatar/',
				'http://1.gravatar.com/avatar/',
				'http://2.gravatar.com/avatar/',
			];
		}

		return str_replace($search, $this->url, $url);
	}

	public static function get_fields(){
		$options	= wp_list_pluck(self::get_registereds(), 'title');
		$options	= [''=>'默认服务']+preg_filter('/$/', '加速服务', $options)+['custom'=>'自定义加速服务'];

		return [
			'gravatar'			=>['options'=>$options],
			'gravatar_custom'	=>['type'=>'text',	'show_if'=>['key'=>'gravatar','value'=>'custom'],	'placeholder'=>'请输入 Gravatar 加速服务地址']
		];
	}

	public static function autoload(){
		self::register('cravatar',	['title'=>'Cravatar',	'url'=>'https://cravatar.cn/avatar/']);
		self::register('geekzu',	['title'=>'极客族',		'url'=>'https://sdn.geekzu.org/avatar/']);
		self::register('loli',		['title'=>'loli',		'url'=>'https://gravatar.loli.net/avatar/']);
		self::register('sep_cc',	['title'=>'sep.cc',		'url'=>'https://cdn.sep.cc/avatar/']);
	}
}

class WPJAM_Google_Font extends WPJAM_Register{
	public function replace($html){
		$search	= preg_filter('/^/', '//', array_values(self::get_domains()));

		return str_replace($search, $this->replace, $html);
	}

	public static function get_domains(){
		return [
			'googleapis_fonts'			=> 'fonts.googleapis.com',
			'googleapis_ajax'			=> 'ajax.googleapis.com',
			'googleusercontent_themes'	=> 'themes.googleusercontent.com',
			'gstatic_fonts'				=> 'fonts.gstatic.com'
		];
	}

	public static function get_fields(){
		$options	= wp_list_pluck(self::get_registereds(), 'title');
		$options	= [''=>'默认服务']+preg_filter('/$/', '加速服务', $options)+['custom'=>'自定义加速服务'];
		$fields		= ['google_fonts'=>['options'=>$options]];

		foreach(self::get_domains() as $key => $domain){
			$fields[$key]	= ['type'=>'text',	'show_if'=>['key'=>'google_fonts','value'=>'custom'],	'placeholder'=>'请输入'.$domain.'加速服务地址'];
		}

		return $fields;
	}

	public static function autoload(){
		self::register('geekzu',	['title'=>'极客族',	'replace'=>[
			'//fonts.geekzu.org',
			'//gapis.geekzu.org/ajax',
			'//gapis.geekzu.org/g-themes',
			'//gapis.geekzu.org/g-fonts'
		]]);

		self::register('loli',		['title'=>'loli',	'replace'=>[
			'//fonts.loli.net',
			'//ajax.loli.net',
			'//themes.loli.net',
			'//gstatic.loli.net'
		]]);

		self::register('ustc',		['title'=>'中科大',	'replace'=>[
			'//fonts.lug.ustc.edu.cn',
			'//ajax.lug.ustc.edu.cn',
			'//google-themes.lug.ustc.edu.cn',
			'//fonts-gstatic.lug.ustc.edu.cn'
		]]);
	}
}