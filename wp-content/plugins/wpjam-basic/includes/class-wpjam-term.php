<?php
class WPJAM_Term{
	use WPJAM_Instance_Trait;

	protected $id;

	protected function __construct($id){
		$this->id	= (int)$id;
	}

	public function __get($key){
		if(in_array($key, ['id', 'term_id'])){
			return $this->id;
		}elseif($key == 'term'){
			return get_term($this->id);
		}elseif($key == 'tax_object'){
			return wpjam_get_taxonomy_object($this->taxonomy);
		}elseif($key == 'ancestors'){
			return get_ancestors($this->id, $this->taxonomy, 'taxonomy');
		}elseif($key == 'children'){
			return get_term_children($this->id, $this->taxonomy);
		}elseif($key == 'object_type'){
			return $this->tax_object ? $this->tax_object->object_type : [];
		}elseif($key == 'level'){
			return $this->parent ? count($this->ancestors) : 0;
		}elseif($key == 'link'){
			return get_term_link($this->term);
		}else{
			$term	= $this->term;

			if(isset($term->$key)){
				return $term->$key;
			}else{
				return wpjam_get_metadata('term', $this->id, $key, null);
			}
		}
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function save($data){
		return self::update($this->id, $data, false);
	}

	public function is_object_in($object_id){
		return is_object_in_term($object_id, $this->taxonomy, $this->id);
	}

	public function set_object($object_id, $append=false){
		return wp_set_object_terms($object_id, [$this->id], $this->taxonomy, $append);
	}

	public function add_object($object_id){
		return wp_add_object_terms($object_id, [$this->id], $this->taxonomy);
	}

	public function remove_object($object_id){
		return wp_remove_object_terms($object_id, [$this->id], $this->taxonomy);
	}

	public function get_object_type(){
		return $this->object_type;
	}

	public function get_thumbnail_url($size='full', $crop=1){
		$thumbnail	= $this->thumbnail ?: apply_filters('wpjam_term_thumbnail_url', '', $this->term);

		if($thumbnail){
			if(!$size && $this->tax_object){
				$size	= $this->tax_object->thumbnail_size;
			}

			$size	= $size ?: 'thumbnail';

			return wpjam_get_thumbnail($thumbnail, $size, $crop);
		}

		return '';
	}

	public function parse_with_children($terms=null, $max_depth=0, $depth=0, $format=''){
		$children	= [];

		if($max_depth == 0 || $max_depth > $depth+1){
			if($terms && isset($terms[$this->id])){
				foreach($terms[$this->id] as $child){
					$object		= wpjam_term($child);
					$_children	= $object->parse_with_children($terms, $max_depth, $depth+1, $format);
					$children	= array_merge($children, $_children);
				}
			}
		}

		$term	= $this->parse_for_json();

		if($format == 'flat'){
			$term['name']	= str_repeat('&emsp;', $depth).$term['name'];

			return array_merge([$term], $children);
		}else{
			return [array_merge($term, ['children'=>$children])];
		}
	}

	public function parse_for_json(){
		$json	= [];

		$json['id']				= $this->id;
		$json['taxonomy']		= $this->taxonomy;
		$json['name']			= html_entity_decode($this->name);
		$json['count']			= (int)$this->count;
		$json['description']	= $this->description;

		if(is_taxonomy_viewable($this->taxonomy)){
			$json['slug']	= $this->slug;
		}

		if(is_taxonomy_hierarchical($this->taxonomy)){
			$json['parent']	= $this->parent;
		}

		if($this->tax_object){
			foreach($this->tax_object->get_meta_options() as $to_object){
				$json	= array_merge($json, $to_object->prepare($this->id));
			}
		}

		return apply_filters('wpjam_term_json', $json, $this->id);
	}

	public static function get_instance($term=null, $taxonomy=''){
		$term	= self::validate($term, $taxonomy);

		if(is_wp_error($term)){
			return null;
		}

		$term_id	= $term->term_id;
		$taxonomy	= $term->taxonomy;
		$tax_object	= wpjam_get_taxonomy_object($taxonomy);
		$model		= $tax_object ? $tax_object->model : '';

		if(!$model || !class_exists($model) || !is_subclass_of($model, 'WPJAM_Term')){
			$model	= 'WPJAM_Term';
		}

		return call_user_func([$model, 'instance'], $term_id);
	}

	public static function get($term){
		$data	= self::get_term($term, '', ARRAY_A);

		if($data && !is_wp_error($data)){
			$data['id']	= $data['term_id'];
		}

		return $data;
	}

	public static function insert($data){
		$result	= static::validate_data($data);

		if(is_wp_error($result)){
			return $result;
		}

		if(isset($data['taxonomy'])){
			$taxonomy	= $data['taxonomy'];

			if(!taxonomy_exists($taxonomy)){
				return new WP_Error('invalid_taxonomy');
			}
		}else{
			$taxonomy	= self::get_current_taxonomy();
		}

		$data	= static::sanitize_data($data);
		$name	= array_pull($data, 'name');
		$args	= wp_array_slice_assoc($data, ['parent', 'slug', 'description', 'alias_of']);
		$term	= wp_insert_term(wp_slash($name), $taxonomy, wp_slash($args));

		if(is_wp_error($term)){
			return $term;
		}

		self::meta_input($term['term_id'], $data);

		return $term['term_id'];
	}

	public static function update($term_id, $data, $validate=true){
		if($validate){
			$term	= self::validate($term_id);

			if(is_wp_error($term)){
				return $term;
			}
		}

		$result	= static::validate_data($data, $term_id);

		if(is_wp_error($result)){
			return $result;
		}

		$taxonomy	= $data['taxonomy'] ?? get_term_taxonomy($term_id);
		$data		= static::sanitize_data($data);
		$args		= wp_array_slice_assoc($data, ['name', 'parent', 'slug', 'description', 'alias_of']);
		$result		= $args ? wp_update_term($term_id, $taxonomy, wp_slash($args)) : true;

		if(!is_wp_error($result)){
			self::meta_input($term_id, $data);
		}

		return $result;
	}

	protected static function meta_input($term_id, $data){
		$meta_input	= array_pull($data, 'meta_input');

		if($meta_input && wpjam_is_assoc_array($meta_input)){
			wpjam_update_metadata('term', $term_id, $meta_input);
		}
	}

	public static function delete($term_id){
		$term	= self::validate($term_id);

		if(is_wp_error($term)){
			return $term;
		}

		return wp_delete_term($term_id, $term->taxonomy);
	}

	protected static function validate_data($data, $term_id=0){
		return true;
	}

	protected static function sanitize_data($data, $term_id=0){
		return $data;
	}

	public static function move($term_id, $data){
		$term	= get_term($term_id);

		$term_ids	= get_terms([
			'parent'	=> $term->parent,
			'taxonomy'	=> $term->taxonomy,
			'orderby'	=> 'name',
			'hide_empty'=> false,
			'fields'	=> 'ids'
		]);

		if(empty($term_ids) || !in_array($term_id, $term_ids)){
			return new WP_Error('invalid_term_id', [get_taxonomy($taxonomy)->label]);
		}

		$terms	= array_map(function($term_id){
			return ['id'=>$term_id, 'order'=>get_term_meta($term_id, 'order', true) ?: 0];
		}, $term_ids);

		$terms	= wp_list_sort($terms, 'order', 'DESC');
		$terms	= wp_list_pluck($terms, 'order', 'id');

		$next	= $data['next'] ?? false;
		$prev	= $data['prev'] ?? false;

		if(!$next && !$prev){
			return new WP_Error('error', '无效的位置');
		}

		unset($terms[$term_id]);

		if($next){
			if(!isset($terms[$next])){
				return new WP_Error('error', $next.'的值不存在');
			}

			$offset	= array_search($next, array_keys($terms));

			if($offset){
				$terms	= array_slice($terms, 0, $offset, true) +  [$term_id => 0] + array_slice($terms, $offset, null, true);
			}else{
				$terms	= [$term_id => 0] + $terms;
			}
		}else{
			if(!isset($terms[$prev])){
				return new WP_Error('error', $prev.'的值不存在');
			}

			$offset	= array_search($prev, array_keys($terms));
			$offset ++;

			if($offset){
				$terms	= array_slice($terms, 0, $offset, true) +  [$term_id => 0] + array_slice($terms, $offset, null, true);
			}else{
				$terms	= [$term_id => 0] + $terms;
			}
		}

		$count	= count($terms);
		foreach ($terms as $term_id => $order) {
			if($order != $count){
				update_term_meta($term_id, 'order', $count);
			}

			$count--;
		}

		return true;
	}

	public static function get_meta($term_id, ...$args){
		// _deprecated_function(__METHOD__, 'WPJAM Basic 6.0', 'wpjam_get_metadata');
		return wpjam_get_metadata('term', $term_id, ...$args);
	}

	public static function update_meta($term_id, ...$args){
		// _deprecated_function(__METHOD__, 'WPJAM Basic 6.0', 'wpjam_update_metadata');
		return wpjam_update_metadata('term', $term_id, ...$args);
	}

	public static function update_metas($term_id, $data, $meta_keys=[]){
		// _deprecated_function(__METHOD__, 'WPJAM Basic 6.0', 'wpjam_update_metadata');
		return self::update_meta($term_id, $data, $meta_keys);
	}

	public static function value_callback($meta_key, $term_id){
		return wpjam_get_metadata('term', $term_id, $meta_key);
	}

	public static function get_by_ids($term_ids){
		return self::update_caches($term_ids);
	}

	public static function update_caches($term_ids){
		if($term_ids){
			$term_ids 	= array_filter($term_ids);
			$term_ids 	= array_unique($term_ids);
		}

		if(empty($term_ids)) {
			return [];
		}

		_prime_term_caches($term_ids, false);

		$tids	= [];

		$cache_values	= wp_cache_get_multiple($term_ids, 'terms');

		foreach($term_ids as $term_id){
			if(empty($cache_values[$term_id])){
				wp_cache_add($term_id, false, 'terms', 10);	// 防止大量 SQL 查询。
			}else{
				$tids[]	= $term_id;
			}
		}

		wpjam_lazyload('term_meta', $tids);

		return $cache_values;
	}

	public static function get_term($term, $taxonomy='', $output=OBJECT, $filter='raw'){
		if($term && is_numeric($term)){
			$found	= false;
			$cache	= wp_cache_get($term, 'terms', false, $found);

			if($found){
				if(is_wp_error($cache)){
					return $cache;
				}elseif(!$cache){
					return null;
				}
			}else{
				$_term	= WP_Term::get_instance($term, $taxonomy);

				if(is_wp_error($_term)){
					return $_term;
				}elseif(!$_term){	// 不存在情况下的缓存优化，防止重复 SQL 查询。
					wp_cache_add($term, false, 'terms', 10);
					return null;
				}
			}
		}

		$term	= $term ?: get_queried_object();

		return get_term($term, $taxonomy, $output, $filter);
	}

	public static function get_current_taxonomy(){
		$model	= strtolower(get_called_class());

		if($model == 'wpjam_term'){
			return;
		}

		$parent	= get_parent_class($model);
		$parent	= ($parent && !is_a($parent, 'WPJAM_Term')) ? strtolower($parent) : '';

		foreach(WPJAM_Taxonomy::get_registereds() as $object){
			if($object->model && is_string($object->model)){
				$object_model	= strtolower($object->model);

				if($object_model == $model || ($parent && $object_model == $parent)){
					return $object->name;
				}
			}
		}
	}

	public static function validate($term_id, $taxonomy=null){
		$term	= self::get_term($term_id);

		if(!$term || !($term instanceof WP_Term)){
			return new WP_Error('invalid_term');
		}

		if(!taxonomy_exists($term->taxonomy)){
			return new WP_Error('invalid_taxonomy');
		}

		$taxonomy	= $taxonomy ?? self::get_current_taxonomy();

		if($taxonomy && $taxonomy != 'any' && $taxonomy != $term->taxonomy){
			return new WP_Error('invalid_taxonomy');
		}

		return $term;
	}

	public static function get_terms($args, $max_depth=null){
		if(is_string($args) || wp_is_numeric_array($args)){
			$term_ids	= wp_parse_id_list($args);

			if(!$term_ids){
				return [];
			}

			$args		= ['orderby'=>'include', 'include'=>$term_ids];
			$max_depth	= -1;
		}

		if($max_depth != -1){
			$taxonomy	= $args['taxonomy'] ?? '';
			$tax_object	= ($taxonomy && is_string($taxonomy)) ? wpjam_get_taxonomy_object($taxonomy) : null;

			if(!$tax_object){
				return [];
			}

			if($tax_object->hierarchical){
				$max_depth	= $max_depth ?? (int)$tax_object->levels;
			}else{
				$max_depth	= -1;
			}

			if(isset($args['child_of'])){
				$parent	= $args['child_of'];
			}else{
				$parent	= array_pull($args, 'parent');

				if($parent){
					$args['child_of']	= $parent;
				}
			}
		}

		$format	= array_pull($args, 'format');
		$args	= wp_parse_args($args, ['hide_empty'=>false]);
		$terms	= get_terms($args) ?: [];

		if(is_wp_error($terms) || empty($terms)){
			return $terms;
		}

		if($max_depth != -1){
			$top_level	= $children	= [];

			if($parent){
				$top_level[] = get_term($parent);
			}

			foreach($terms as $term){
				if($term->parent == 0){
					$top_level[] = $term;
				}elseif($max_depth != 1){
					$children[$term->parent][] = $term;
				}
			}

			$output	= [];

			foreach($top_level as $term){
				$object	= wpjam_term($term);
				$parsed	= $object->parse_with_children($children, $max_depth, 0, $format);
				$output	= array_merge($output, $parsed);
			}

			return $output;
		}else{
			foreach($terms as &$term){
				$object	= wpjam_term($term);
				$term	= $object->parse_for_json();
			}

			return $terms;
		}
	}

	public static function filter_fields($fields, $id){
		if($id && !is_array($id)){
			$object	= wpjam_term($id);

			if($object && $object->tax_object){
				$fields	= array_merge(['title'=>[
					'title'	=> $object->tax_object->label,
					'type'	=> 'view',
					'value'	=> $object->name
				]], $fields);
			}
		}

		return $fields;
	}
}

class WPJAM_Taxonomy extends WPJAM_Register{
	private $_fields;

	public function __get($key){
		$object	= get_taxonomy($this->name);

		if($key == 'name'){
			return $this->name;
		}elseif(property_exists('WP_Taxonomy', $key) && $object){
			return get_taxonomy($this->name)->$key;
		}else{
			return $this->offsetGet($key);
		}
	}

	public function __set($key, $value){
		if($key == 'name'){
			return;
		}

		$object	= get_taxonomy($this->name);

		if(property_exists('WP_Taxonomy', $key) && $object){
			$object->$key = $value;
		}else{
			$this->offsetSet($key, $value);
		}
	}

	public function parse_args(){
		if(!doing_filter('register_taxonomy_args')){
			$this->args = wp_parse_args($this->args, [
				'rewrite'			=> true,
				'show_ui'			=> true,
				'show_in_nav_menus'	=> false,
				'show_admin_column'	=> true,
				'hierarchical'		=> true,
				'by_wpjam'			=> true,
			]);

			if(is_admin() && $this->args['show_ui']){
				add_filter('taxonomy_labels_'.$this->name,	[$this, 'filter_labels']);
			}

			add_action('registered_taxonomy_'.$this->name,	[$this, 'registered_callback'], 10, 3);
		}

		if(empty($this->args['supports'])){
			$this->args['supports']	= ['slug', 'description', 'parent'];
		}

		if($this->name == 'category'){
			$this->args['plural']		= 'categories';
			$this->args['column_name']	= 'categories';
		}elseif($this->name == 'post_tag'){
			$this->args['plural']		= 'post_tags';
			$this->args['column_name']	= 'tags';
		}else{
			if(empty($this->args['plural'])){
				$this->args['plural']	= $this->name.'s';
			}

			$this->args['column_name']	= 'taxonomy-'.$this->name;
		}

		$this->args['id_query_var']	= wpjam_get_taxonomy_query_key($this->name);
	}

	public function to_array(){
		$this->filter_args();

		if(doing_filter('register_taxonomy_args')){
			if($this->permastruct){
				$this->permastruct	= str_replace('%term_id%', '%'.$this->name.'_id%', $this->permastruct);

				if(strpos($this->permastruct, '%'.$this->name.'_id%')){
					$this->supports		= array_diff($this->supports, ['slug']);
					$this->query_var	= $this->query_var ?? false;
				}

				if(!$this->rewrite){
					$this->rewrite	= true;
				}
			}

			if($this->levels == 1){
				$this->supports	= array_diff($this->supports, ['parent']);
			}else{
				$this->supports	= array_merge($this->supports, ['parent']);
			}

			if($this->rewrite && $this->by_wpjam){
				$this->rewrite	= is_array($this->rewrite) ? $this->rewrite : [];
				$this->rewrite	= wp_parse_args($this->rewrite, ['with_front'=>false, 'feed'=>false, 'hierarchical'=>false]);
			}
		}

		return $this->args;
	}

	public function add_field(...$args){
		$fields	= is_array($args[0]) ? $args[0] : [$args[0]=>$args[1]];

		$this->_fields	= array_merge($this->get_fields(), $fields);
	}

	public function remove_field($key){
		$this->_fields	= array_except($this->get_fields(), $key);
	}

	public function get_fields(){
		if(is_null($this->_fields)){
			$fields	= [];

			if(in_array('thumbnail', $this->supports)){
				$fields['thumbnail']	= ['title'=>'缩略图'];

				if($this->thumbnail_type == 'image'){
					$fields['thumbnail']['type']		= 'image';
				}else{
					$fields['thumbnail']['type']		= 'img';
					$fields['thumbnail']['item_type']	= 'url';
				}

				if($this->thumbnail_size){
					$fields['thumbnail']['size']		= $this->thumbnail_size;
					$fields['thumbnail']['description']	= '尺寸：'.$this->thumbnail_size;
				}else{
					$fields['thumbnail']['size']		= 'thumbnail';
				}
			}

			$this->_fields	= $fields;
		}

		return $this->_fields;
	}

	public function is_object_in($object_type){
		return is_object_in_taxonomy($object_type, $this->name);
	}

	public function is_viewable(){
		return is_taxonomy_viewable($this->name);
	}

	public function add_support($feature){
		$this->supports	= array_merge($this->supports, [$feature]);

		return $this;
	}

	public function supports($feature){
		return in_array($feature, $this->supports);
	}

	public function get_meta_options(){
		if(!WPJAM_Term_Option::get($this->name.'_base')){
			$fields	= $this->get_fields();

			if($fields){
				WPJAM_Term_Option::register($this->name.'_base', [
					'taxonomy'		=> $this->name,
					'title'			=> '基础信息',
					'fields'		=> $fields,
					'list_table'	=> false,
				]);
			}
		}

		return WPJAM_Term_Option::get_registereds([$this->name=>function($object, $taxonomy){
			return $object->is_available($taxonomy);
		}]);
	}

	public function get_path($args){
		$query_key	= $this->id_query_var;
		$term_id	= $args['term_id'] ?? ($args[$query_key] ?? 0);
		$term_id	= (int)$term_id;

		if(!$term_id){
			return new WP_Error('invalid_term_id', [$this->label]);
		}

		if($args['platform'] == 'template'){
			return get_term_link($term_id, $taxonomy);
		}

		return str_replace('%term_id%', $term_id, $args['path']);
	}

	public function get_id_field($args){
		$title	= $this->label;
		$levels	= $this->levels;
		$count	= wp_count_terms(['taxonomy'=>$this->name]);
		$type	= $args['type'] ?? '';
		$wrap	= $args['wrap'] ?? false;

		if($type == 'mu-text'){
			$levels	= 0;
		}

		if($this->hierarchical && ($levels > 1 || !is_admin() || (is_admin() && $count <= 30))){
			$option_all	= array_pull($args, 'option_all', true);

			if($option_all !== false){
				$option_all	= $option_all === true ? '所有' : $option_all;
			}

			if($levels > 1 && $type == ''){
				$fields	= [];

				for($level=0; $level < $levels; $level++){
					if($level == 0){
						$terms		= wpjam_get_terms(['taxonomy'=>$this->name, 'hide_empty'=>0], 1);
						$options	= $terms ? array_column($terms, 'name', 'id') : [];
						$show_if	= [];
					}else{
						$options	= [];
						$show_if	= ['key'=>'level_'.($level-1), 'compare'=>'!=', 'value'=>0, 'query_arg'=>'parent'];
					}

					if($option_all){
						$options	= [''=>$option_all]+$options;
					}

					$sub_key	= 'level_'.$level;

					$fields[$sub_key]	= [
						'data-sub_key'	=> $sub_key,
						'type'			=> 'select', 
						'options'		=> $options,
						'show_if'		=> $show_if,
						'show_in_rest'	=> ['type'=>'integer']
					];

					if($level > 0){
						$fields[$sub_key]	+= [
							'data_type'	=> 'taxonomy',
							'taxonomy'	=> $this->name,
						];
					}
				}

				$field	= wp_parse_args($args, [
					'title'			=> $title,
					'type'			=> 'fieldset',
					'fieldset_type'	=> 'array',
					'class'			=> 'cascading-dropdown init',
					'fields'		=> $fields,
					'data_type'		=> 'taxonomy',
					'taxonomy'		=> $this->name,
					'show_in_rest'	=> ['type'=>'integer']
				]);
			}else{
				if($type == 'mu-text'){
					$args['item_type']	= 'select';
				}

				$terms		= wpjam_get_terms(['taxonomy'=>$this->name, 'hide_empty'=>0, 'format'=>'flat']);
				$options	= $terms ? array_column($terms, 'name', 'id') : [];

				if($option_all){
					$options	= [''=>$option_all]+$options;
				}

				$field	= wp_parse_args($args, [
					'title'			=> $title,
					'type'			=> 'select',
					'options'		=> $options,
					'show_in_rest'	=> ['type'=>'integer']
				]);
			}
		}else{
			$field	= wp_parse_args($args, [
				'title'			=> $title,
				'type'			=> 'text',
				'class'			=> 'all-options',
				'data_type'		=> 'taxonomy',
				'taxonomy'		=> $this->name,
				'placeholder'	=> '请输入'.$title.'ID或者输入关键字筛选',
				'show_in_rest'	=> ['type'=>'integer']
			]);
		}

		return $wrap ? [$this->id_query_var => $field] : $field;
	}

	public function dropdown(){
		$query_key	= $this->id_query_var;
		$selected	= wpjam_get_data_parameter($query_key);

		if(is_null($selected)){
			if($this->query_var){
				$term_slug	= wpjam_get_data_parameter($this->query_var);
			}elseif(wpjam_get_data_parameter('taxonomy') == $this->name){
				$term_slug	= wpjam_get_data_parameter('term');
			}else{
				$term_slug	= '';
			}

			$term 		= $term_slug ? get_term_by('slug', $term_slug, $this->name) : null;
			$selected	= $term ? $term->term_id : '';
		}

		if($this->hierarchical){
			wp_dropdown_categories([
				'taxonomy'			=> $this->name,
				'show_option_all'	=> $this->labels->all_items,
				'show_option_none'	=> '没有设置',
				'option_none_value'	=> 'none',
				'name'				=> $query_key,
				'selected'			=> $selected,
				'hierarchical'		=> true
			]);
		}else{
			echo wpjam_field([
				'key'			=> $query_key,
				'value'			=> $selected,
				'type'			=> 'text',
				'data_type'		=> 'taxonomy',
				'taxonomy'		=> $this->name,
				'placeholder'	=> '请输入'.$this->label,
				'title'			=> '',
				'class'			=> ''
			]);
		}
	}

	public function link_replace($link, $term_id){
		$permastruct	= $GLOBALS['wp_rewrite']->get_extra_permastruct($this->name);

		if(empty($permastruct) || strpos($permastruct, '/%'.$this->name.'_id%')){
			$term		= get_term($term_id);
			$query_str	= $this->query_var ? $this->query_var.'='.$term->slug : 'taxonomy='.$this->name.'&#038;term='.$term->slug;
			$link		= str_replace($query_str, $this->id_query_var.'='.$term->term_id, $link);
		}

		return $link;
	}

	public function registered_callback($taxonomy, $object_type, $args){
		if($this->name == $taxonomy){
			// print_r($this->name."\n");

			if($this->permastruct){
				if(strpos($this->permastruct, '%'.$taxonomy.'_id%')){
					wpjam_set_permastruct($taxonomy, $this->permastruct);

					add_rewrite_tag('%'.$taxonomy.'_id%', '([^/]+)', 'taxonomy='.$taxonomy.'&term_id=');

					remove_rewrite_tag('%'.$taxonomy.'%');
				}elseif(strpos($this->permastruct, '%'.$args['rewrite']['slug'].'%')){
					wpjam_set_permastruct($taxonomy, $this->permastruct);
				}
			}

			if($this->registered_callback && is_callable($this->registered_callback)){
				call_user_func($this->registered_callback, $taxonomy, $object_type, $args);
			}
		}
	}

	public function filter_labels($labels){
		$_labels	= (array)($this->labels ?? []);
		$labels		= (array)$labels;
		$name		= $labels['name'];

		if($this->hierarchical){
			$search		= ['目录', '分类', 'categories', 'Categories', 'Category'];
			$replace	= ['', $name, $name, $name.'s', ucfirst($name).'s', ucfirst($name)];
		}else{
			$search		= ['标签', 'Tag', 'tag'];
			$replace	= [$name, ucfirst($name), $name];
		}

		foreach($labels as $key => &$label){
			if($label && empty($_labels[$key]) && $label != $name){
				$label	= str_replace($search, $replace, $label);
			}
		}

		return $labels;
	}

	public static function autoload(){
		add_filter('pre_term_link',	[self::class, 'filter_link'], 1, 2);

		foreach(self::get_registereds() as $taxonomy => $object){
			if(!get_taxonomy($taxonomy)){
				register_taxonomy($taxonomy, $object->object_type, $object->to_array());
			}
		}
	}

	protected static function get_config($key){
		if(in_array($key, ['menu_page', 'register_json'])){
			return true;
		}
	}

	public static function filter_register_args($args, $taxonomy, $object_type){
		if(did_action('init') || empty($args['_builtin'])){
			$object	= self::get($taxonomy);

			if($object){
				$object->update_args($args);
			}else{
				$object	= self::register($taxonomy, array_merge($args, ['object_type'=>$object_type]));

				add_action('registered_taxonomy_'.$taxonomy, [$object, 'registered_callback'], 10, 3);
			}

			return $object->to_array();
		}

		return $args;
	}

	public static function on_registered($taxonomy, $object_type, $args){
		if(did_action('init')){
			(self::get($taxonomy))->registered_callback($taxonomy, $object_type, $args);
		}
	}

	public static function filter_link($term_link, $term){
		if(array_search('%'.$term->taxonomy.'_id%', $GLOBALS['wp_rewrite']->rewritecode, true)){
			$term_link	= str_replace('%'.$term->taxonomy.'_id%', $term->term_id, $term_link);
		}

		return $term_link;
	}

	public static function create($name, ...$args){
		if(count($args) == 2){
			$args	= array_merge($args[1], ['object_type'=>$args[0]]);
		}else{
			$args	= $args[0];
		}

		$object	= self::register($name, $args);

		if(did_action('init')){
			register_taxonomy($name, $object->object_type, $object->to_array());
		}

		return $object;
	}
}

class WPJAM_Term_Option extends WPJAM_Meta_Option{
	public function __construct($name, $args=[]){
		if(is_callable($args)){
			trigger_error('callable_term_option_args');
			$args	= ['fields'=>$fields];
		}elseif(!isset($args['fields'])){
			$args['fields']		= [$name => array_except($args, 'taxonomy')];
			$args['from_field']	= true;
		}

		parent::__construct($name, $args);
	}

	public function parse_args(){
		$args	= parent::parse_args();

		if(!isset($args['taxonomy'])){
			$taxonomies	= array_pull($args, 'taxonomies');

			if($taxonomies){
				$args['taxonomy']	= $taxonomies;
			}
		}

		if(!isset($args['list_table']) && did_action('current_screen') && get_current_screen()->base != 'edit-tags'){
			$args['list_table']	= true;
		}

		return $args;
	}

	public function is_available($taxonomy){
		return is_null($this->taxonomy) || wpjam_compare($taxonomy, (array)$this->taxonomy);
	}

	public function is_available_for_taxonomy($taxonomy){	// 兼容
		return $this->is_available($taxonomy);
	}

	public static function get_by_taxonomy($taxonomy){		// 兼容
		$object	= wpjam_get_taxonomy_object($taxonomy);

		return $object ? $object->get_meta_options() : [];
	}
}