<?php
class WPJAM_Post{
	use WPJAM_Instance_Trait;

	protected $id;
	protected $viewd	= false;

	protected function __construct($id){
		$this->id	= (int)$id;
	}

	public function __get($key){
		if(in_array($key, ['id', 'post_id'])){
			return $this->id;
		}elseif($key == 'views'){
			return (int)get_post_meta($this->id, 'views', true);
		}elseif($key == 'permalink'){
			return get_permalink($this->id);
		}elseif($key == 'ancestors'){
			return get_post_ancestors($this->id);
		}elseif($key == 'children'){
			return get_children($this->id);
		}elseif($key == 'viewable'){
			return is_post_publicly_viewable($this->id);
		}elseif($key == 'format'){
			return get_post_format($this->id) ?: '';
		}elseif($key == 'taxonomies'){
			return get_object_taxonomies($this->post);
		}elseif($key == 'type_object'){
			return wpjam_get_post_type_object($this->post_type);
		}elseif($key == 'thumbnail'){
			if($this->supports('thumbnail')){
				return get_the_post_thumbnail_url($this->id, 'full');
			}

			return '';
		}elseif($key == 'images'){
			if($this->supports('images')){
				return get_post_meta($this->id, 'images', true) ?: [];
			}

			return [];
		}else{
			$post	= get_post($this->id);

			if(in_array($key, ['post', 'data'])){
				return $post;
			}elseif(isset($post->{'post_'.$key})){
				return $post->{'post_'.$key};
			}elseif(isset($post->$key)){
				return $post->$key;
			}else{
				return wpjam_get_metadata('post', $this->id, $key, null);
			}
		}
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function save($data){
		$status	= array_get($data, 'post_status');
		$status	= $status ?: array_get($data, 'status');

		if($status == 'publish'){
			$result	= $this->is_publishable();

			if(is_wp_error($result) || !$result){
				return $result ?: new WP_Error('unpublishable', '不可发布');
			}
		}

		return self::update($this->id, $data, false);
	}

	public function set_status($status){
		return $this->save(['post_status'=>$status]);
	}

	public function publish(){
		return $this->set_status('publish');
	}

	public function unpublish(){
		return $this->set_status('draft');
	}

	public function is_publishable(){
		return true;
	}

	public function set_terms($terms='', $taxonomy='post_tag', $append=false){
		return wp_set_post_terms($this->id, $terms, $taxonomy, $append);
	}

	public function get_excerpt($length=0, $more=null){
		if($this->excerpt){
			return wp_strip_all_tags($this->excerpt, true);
		}

		$excerpt	= $this->get_content(true);
		$excerpt	= strip_shortcodes($excerpt);
		$excerpt	= excerpt_remove_blocks($excerpt);
		$excerpt	= wp_strip_all_tags($excerpt, true);
		$length		= $length ?: apply_filters('excerpt_length', 200);
		$more		= $more ?? apply_filters('excerpt_more', ' &hellip;');

		return mb_strimwidth($excerpt, 0, $length, $more, 'utf-8');
	}

	public function get_content($raw=false){
		$content	= get_the_content('', false, $this->post);

		return $raw ? $content : str_replace(']]>', ']]&gt;', apply_filters('the_content', $content));
	}

	public function get_author(){
		return wpjam_get_user($this->author);
	}

	public function get_unserialized(){
		$content	= $this->content;

		if($content){
			if(is_serialized($content)){
				$unserialized	= @unserialize($content);

				if(!$unserialized){
					$unserialized	= wpjam_unserialize($content);

					if($unserialized && is_array($unserialized)){
						$this->save(['content'=>$content]);
					}
				}

				return $unserialized ?: [];
			}else{
				// trigger_error(var_export($content, true));
			}
		}

		return [];
	}

	public function get_thumbnail_url($size='thumbnail', $crop=1){
		if($this->thumbnail){
			$thumbnail	= $this->thumbnail;
		}elseif($this->images){
			$thumbnail	= $this->images[0];
		}else{
			$thumbnail	= apply_filters('wpjam_post_thumbnail_url', '', $this->post);
		}

		if($thumbnail){
			if(!$size && $this->type_object){
				$size	= $this->type_object->get_size('thumbnail');
			}

			$size	= $size	?: 'thumbnail';

			return wpjam_get_thumbnail($thumbnail, $size, $crop);
		}

		return '';
	}

	public function get_first_image_url($size='full'){
		if($this->content){
			if(preg_match('/class=[\'"].*?wp-image-([\d]*)[\'"]/i', $this->content, $matches)){
				return wp_get_attachment_image_url($matches[1], $size);
			}

			if(preg_match('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $this->content, $matches)){
				return wpjam_get_thumbnail($matches[1], $size);
			}
		}

		return '';
	}

	public function get_images($large_size='', $thumbnail_size='', $full_size=true){
		$images	= [];

		if($this->images){
			$sizes	= [];
			$count	= count($this->images);

			if($count == 1){
				$image	= current($this->images);
				$query	= wpjam_parse_image_query($image);

				if(!$query){
					$query	= wpjam_get_image_size($image, 'url');
					$query	= $query ?: ['width'=>0, 'height'=>0];
					$image	= add_query_arg($query, $image);

					update_post_meta($this->id, 'images', [$image]);
				}

				$orientation	= $query['orientation']  ?? '';
			}else{
				$orientation	= '';
			}

			$sizes	= $this->type_object ? $this->type_object->get_sizes($orientation) : [];

			foreach(['large'=>$large_size, 'thumbnail'=>$thumbnail_size] as $key => $value){
				if($value === false){
					unset($sizes[$key]);
				}elseif($value){
					$sizes[$key]	= $value;
				}
			}

			foreach($this->images as $image){
				$image_arr = [];

				foreach($sizes as $name => $size){
					$image_arr[$name]	= wpjam_get_thumbnail($image, $size);

					if($name == 'thumbnail'){
						$query	= wpjam_parse_image_query($image);
						$size	= wpjam_parse_size($size);

						if($query && !empty($query['orientation'])){
							$image_arr['orientation']	= $query['orientation'];
						}

						foreach(['width', 'height'] as $key){
							if($query){
								$image_arr[$key]		= $query[$key] ?? 0;
							}

							$image_arr[$name.'_'.$key]	= $size[$key] ?? 0;
						}
					}
				}

				if($full_size){
					$sizes['full']		= true;
					$image_arr['full']	= wpjam_get_thumbnail($image);
				}

				$images[]	= count($sizes) == 1 ? current($image_arr) : $image_arr;
			}
		}

		return $images;
	}

	public function get_terms($taxonomy='post_tag', $parse=false){
		$terms	= get_the_terms($this->id, $taxonomy) ?: [];

		return $parse ? array_map('wpjam_get_term', $terms) : $terms;
	}

	public function get_related_query($args=[]){
		$post_type	= [$this->post_type];
		$tt_ids		= [];

		foreach($this->taxonomies as $taxonomy){
			$terms	= $taxonomy == 'post_format' ? [] : $this->get_terms($taxonomy);

			if($terms){
				$post_type	= array_merge($post_type, get_taxonomy($taxonomy)->object_type);
				$tt_ids		= array_merge($tt_ids, array_column($terms, 'term_taxonomy_id'));
			}
		}

		if(!$tt_ids){
			return false;
		}

		$query_vars	= wpjam_parse_query_vars([
			'related_query'		=> true,
			'cache_it'			=> 'query_vars',
			'post_status'		=> 'publish',
			'post__not_in'		=> [$this->id],
			'post_type'			=> array_unique($post_type),
			'term_taxonomy_ids'	=> array_unique(array_filter($tt_ids)),
		], $args);

		return wpjam_query($query_vars);
	}

	public function supports($feature){
		return $this->type_object ? $this->type_object->supports($feature) : false;
	}

	public function in_term($taxonomy, $terms=null){
		return is_object_in_term($this->id, $taxonomy, $terms);
	}

	public function in_taxonomy($taxonomy){
		return is_object_in_taxonomy($this->post, $taxonomy);
	}

	public function view($addon=1){
		if(!$this->viewd){	// 确保只加一次
			$this->viewd	= true;

			update_post_meta($this->id, 'views', $this->views + $addon);
		}
	}

	public function parse_for_json($args=[]){
		$args	= wp_parse_args($args, [
			'list_query'		=> false,
			'content_required'	=> false,
			'raw_content'		=> false,
		]);

		$size	= $args['thumbnail_size'] ?? ($args['size'] ?? null);
		$json	= array_merge(
			[
				'id'		=> $this->id,
				'type'		=> $this->type,
				'post_type'	=> $this->post_type,
				'status'	=> $this->status,
				'views'		=> $this->views,
				'icon'		=> $this->type_object ? (string)$this->type_object->icon : '',
				'title'		=> $this->supports('title') ? html_entity_decode(get_the_title($this->id)) : '',
				'excerpt'	=> $this->supports('excerpt') ? html_entity_decode(get_the_excerpt($this->id)) : '',
				'thumbnail'	=> $this->get_thumbnail_url($size),
				'user_id'	=> (int)$this->author,
			], 
			$this->parse_date('date', $args),
			$this->parse_date('modified'),
			$this->parse_password()
		);

		if($this->viewable){
			$json['name']		= urldecode($this->name);
			$json['post_url']	= str_replace(home_url(), '', $this->permalink);
		}

		if($this->supports('author')){
			$json['author']	= $this->get_author();
		}

		if($this->supports('page-attributes')){
			$json['menu_order']	= (int)$this->menu_order;
		}

		if($this->supports('post-formats')){
			$json['format']	= $this->format;
		}

		if($this->supports('images')){
			$json['images']	= $this->get_images();
		}

		if($args['list_query']){
			return $json;
		}

		foreach($this->taxonomies as $taxonomy){
			if($taxonomy != 'post_format' && is_taxonomy_viewable($taxonomy)){
				$json[$taxonomy]	= $this->get_terms($taxonomy, true);
			}
		}

		if($this->type_object){
			foreach($this->type_object->get_meta_options() as $mo_object){
				$json	= array_merge($json, $mo_object->prepare($this->id));
			}
		}

		$json	= array_merge($json, $this->parse_content($args));

		if(is_single($this->id)){
			$this->view();
		}

		return apply_filters('wpjam_post_json', $json, $this->id, $args);
	}

	protected function parse_content($args){
		$json	= [];

		if((is_single($this->id) || $args['content_required'])){
			if($this->supports('editor')){
				if($args['raw_content']){
					$json['raw_content']	= $this->content;
				}

				$json['content']	= $this->get_content();
				$json['multipage']	= (bool)$GLOBALS['multipage'];

				if($json['multipage']){
					$json['numpages']	= $GLOBALS['numpages'];
					$json['page']		= $GLOBALS['page'];
				}
			}else{

				if(is_serialized($this->content)){
					$json['content']	= $this->get_unserialized();
				}
			}
		}

		return $json;
	}

	protected function parse_date($type='date', $args=[]){
		if($type == 'modified'){
			$prefix		= 'modified_';
			$timestamp	= get_post_timestamp($this->id, 'modified');
		}else{
			$prefix		= '';
			$timestamp	= get_post_timestamp($this->id, 'date');
		}

		$parsed	= [
			$prefix.'timestamp'	=> $timestamp,
			$prefix.'time'		=> wpjam_human_time_diff($timestamp),
			$prefix.'date'		=> wpjam_date('Y-m-d', $timestamp),
		];

		if($type == 'date' && !$args['list_query'] && is_main_query()){
			$current_posts	= $GLOBALS['wp_query']->posts;

			if($current_posts && in_array($this->id, array_column($current_posts, 'ID'))){
				if(is_new_day()){
					$GLOBALS['previousday']	= $GLOBALS['currentday'];

					$parsed['day']	= wpjam_human_date_diff($parsed['date']);
				}else{
					$parsed['day']	= '';
				}
			}
		}

		return $parsed;
	}

	protected function parse_password(){
		if($this->password){
			return [
				'password_protected'	=> true,
				'password_required'		=> post_password_required($this->id),
			];
		}

		return [];
	}

	public static function get_instance($post=null, $post_type=null){
		try{
			$post	= wpjam_try([get_called_class(), 'validate'], $post, $post_type);

			$post_id	= $post->ID;
			$post_type	= get_post_type($post_id);
			$object		= wpjam_get_post_type_object($post_type);
			$model		= $object ? $object->model : null;

			if(!$model || !class_exists($model) || !is_subclass_of($model, 'WPJAM_Post')){
				$model	= 'WPJAM_Post';
			}

			return call_user_func([$model, 'instance'], $post_id);

		}catch(WPJAM_Exception $e){
			return null;
		}
	}

	public static function validate($post_id, $post_type=null){
		$post	= self::get_post($post_id);

		if(!$post || !($post instanceof WP_Post)){
			return new WP_Error('invalid_post');
		}

		if(!post_type_exists($post->post_type)){
			return new WP_Error('invalid_post_type');
		}

		$post_type	= $post_type ?? static::get_current_post_type();

		if($post_type && $post_type != 'any' && $post_type != $post->post_type){
			return new WP_Error('invalid_post_type');
		}

		return $post;
	}

	public static function get($post){
		$data	= self::get_post($post, ARRAY_A);

		if($data && is_serialized($data['post_content'])){
			$data['post_content']	= maybe_unserialize($data['post_content']);
		}

		return $data;
	}

	public static function insert($data){
		$result	= static::validate_data($data);

		if(is_wp_error($result)){
			return $result;
		}

		if(isset($data['post_type'])){
			if(!post_type_exists($data['post_type'])){
				return new WP_Error('invalid_post_type');
			}
		}else{
			$data['post_type']	= static::get_current_post_type() ?: 'post';
		}

		if(empty($data['post_status'])){
			$cap	= get_post_type_object($data['post_type'])->cap->publish_posts;

			$data['post_status']	= current_user_can($cap) ? 'publish' : 'draft';
		}

		$data	= static::sanitize_data($data);
		$data	= wp_parse_args($data, [
			'post_author'	=> get_current_user_id(),
			'post_date'		=> wpjam_date('Y-m-d H:i:s'),
		]);

		return wp_insert_post($data, true, true);
	}

	public static function update($post_id, $data, $validate=true){
		if($validate){
			$result	= self::validate($post_id);

			if(is_wp_error($result)){
				return $result;
			}
		}

		$result	= static::validate_data($data, $post_id);

		if(is_wp_error($result)){
			return $result;
		}

		$data	= static::sanitize_data($data, $post_id);

		return wp_update_post($data, true, true);
	}

	public static function delete($post_id, $force_delete=true){
		$result	= self::validate($post_id);

		if(is_wp_error($result)){
			return $result;
		}

		$result	= wp_delete_post($post_id, $force_delete);

		return $result ? true : new WP_Error('delete_error', '删除失败');
	}

	protected static function validate_data($data, $post_id=0){
		return true;
	}

	protected static function sanitize_data($data, $post_id=0){
		foreach([
			'title',
			'content',
			'excerpt',
			'name',
			'status',
			'author',
			'parent',
			'password',
			'date',
			'date_gmt',
			'modified',
			'modified_gmt'
		] as $key){
			if(!isset($data['post_'.$key]) && isset($data[$key])){
				$data['post_'.$key]	= $data[$key];
			}
		}

		if(isset($data['post_content']) && is_array($data['post_content'])){
			$data['post_content']	= maybe_serialize($data['post_content']);
		}

		if($post_id){
			$data['ID'] = $post_id;

			if(isset($data['post_date']) && !isset($data['post_date_gmt'])){
				$current_date_gmt	= get_post($post_id)->post_date_gmt;

				if($current_date_gmt && $current_date_gmt != '0000-00-00 00:00:00'){
					$data['post_date_gmt']	= get_gmt_from_date($data['post_date']);
				}
			}
		}

		return wp_slash($data);
	}

	public static function value_callback($meta_key, $post_id){
		return wpjam_get_metadata('post', $post_id, $meta_key);
	}

	public static function get_by_ids($post_ids){
		return static::update_caches($post_ids);
	}

	public static function prime_caches($posts, $args=[]){
		$post_ids	= $type_list = $authors = $attachment_ids = [];

		foreach($posts as $post){
			$post_type	= $post->post_type;
			$post_ids[]	= $post->ID;
			$authors[]	= $post->post_author;

			if(!isset($type_list[$post_type])){
				$type_list[$post_type]	= [];
			}

			$type_list[$post_type][]	= $post->ID;
		}

		if($type_list){
			foreach($type_list as $post_type => $type_ids){
				if(!empty($args['update_post_term_cache'])){
					update_object_term_cache($type_ids, $post_type);
				}else{
					wpjam_lazyload('post_term', $type_ids, $post_type);
				}
			}	
		}

		if(!empty($args['update_post_meta_cache'])){
			update_postmeta_cache($post_ids);
		}else{
			wpjam_lazyload('post_meta', $post_ids);
		}

		wpjam_lazyload('user', array_unique(array_filter($authors)));

		foreach($posts as $post){
			if(post_type_supports($post->post_type, 'thumbnail')){
				// $attachment_ids[]	= get_post_thumbnail_id($post_id);
				$attachment_ids[]	= get_post_meta($post->ID, '_thumbnail_id', true);
			}

			if($post->post_content && strpos($post->post_content, '<img') !== false){
				if(preg_match_all('/class=[\'"].*?wp-image-([\d]*)[\'"]/i', $post->post_content, $matches)){
					$attachment_ids	= array_merge($attachment_ids, $matches[1]);
				}
			}
		}

		$attachment_ids	= array_unique(array_filter($attachment_ids));

		wpjam_lazyload('attachment', $attachment_ids);
		// _prime_post_caches($attachment_ids,	false, true);
	}

	public static function update_caches($post_ids){
		$post_ids 	= array_filter($post_ids);
		$post_ids	= array_unique($post_ids);

		_prime_post_caches($post_ids, false, false);

		$cache_values	= wp_cache_get_multiple($post_ids, 'posts');

		static::prime_caches(array_filter($cache_values));

		return array_map('get_post', $cache_values);
	}

	public static function update_attachment_caches($attachment_ids){
		$attachment_ids = array_filter($attachment_ids);
		$attachment_ids	= array_unique($attachment_ids);

		_prime_post_caches($attachment_ids, false, false);

		wpjam_lazyload('post_meta', $attachment_ids);
	}

	public static function get_post($post, $output=OBJECT, $filter='raw'){
		if($post && is_numeric($post)){	// 不存在情况下的缓存优化
			$found	= false;
			$cache	= wp_cache_get($post, 'posts', false, $found);

			if($found){
				if(is_wp_error($cache)){
					return $cache;
				}elseif(!$cache){
					return null;
				}
			}else{
				$_post	= WP_Post::get_instance($post);

				if(!$_post){	// 防止重复 SQL 查询。
					wp_cache_add($post, false, 'posts', 10);
					return null;
				}
			}
		}

		return get_post($post, $output, $filter);
	}

	public static function get_current_post_type(){
		$model	= strtolower(get_called_class());
		$object = WPJAM_Post_Type::get_by_model($model);

		return $object ? $object->name : '';
	}

	public static function query_data($args, $layout=''){
		$post_type	= static::get_current_post_type();

		if($post_type){
			$args['post_type']	= $post_type;
		}

		if($layout != 'calendar'){
			if(isset($args['limit'])){
				$args['posts_per_page']	= $args['limit'];
			}

			foreach(['orderby', 'order', 's'] as $key){
				if(!isset($args[$key])){
					$value	= wpjam_get_data_parameter($key);

					if($value){
						$args[$key]	= $value;
					}
				}
			}
		}

		$args['post_status']	= wpjam_get_data_parameter('status', ['default'=>'any']);
	
		$query	= $GLOBALS['wp_query'];
		$query->query($args);

		if($layout == 'calendar'){
			$items	= [];

			foreach($query->posts as $post){
				$date	= explode(' ', $post->post_date)[0];
				$items	+= [$date=>$post];
			}

			return $items;
		}else{
			return ['items'=>$query->posts, 'total'=>$query->found_posts];
		}
	}

	public static function get_views(){
		$post_type	= static::get_current_post_type();
		$num_posts	= wp_count_posts($post_type);
		$status		= wpjam_get_data_parameter('status', ['default'=>'any']);

		$class		= ($status == 'any' && !wpjam_get_data_parameter('show_sticky')) ? 'current' : '';
		$label		= '全部'.'<span class="count">（'.array_sum((array)$num_posts).'）</span>';
		$views		= ['all'=>wpjam_get_list_table_filter_link([], $label, $class)];

		foreach(get_post_stati(['show_in_admin_status_list'=>true], 'objects') as $name => $object){
			$count	= $num_posts->$name ?? 0;

			if($count){
				$class	= $status == $name ? 'current' : '';
				$label	= $object->label.'<span class="count">（'.$count.'）</span>';

				$views[$name] = wpjam_get_list_table_filter_link(['status'=>$name], $label, $class);
			}	
		}

		return $views;
	}

	public static function filter_fields($fields, $id){
		if($id && !is_array($id) && !isset($fields['title']) && !isset($fields['post_title'])){
			$object	= wpjam_post($id);
			$field	= ['title'=>$object->type_object->label.'标题', 'type'=>'view', 'value'=>$object->title];
			$fields	= array_merge(['title'=>$field], $fields);
		}

		return $fields;
	}

	public static function get_meta($post_id, ...$args){
		// _deprecated_function(__METHOD__, 'WPJAM Basic 6.0', 'wpjam_get_metadata');
		return wpjam_get_metadata('post', $post_id, ...$args);
	}

	public static function update_meta($post_id, ...$args){
		// _deprecated_function(__METHOD__, 'WPJAM Basic 6.0', 'wpjam_update_metadata');
		return wpjam_update_metadata('post', $post_id, ...$args);
	}

	public static function update_metas($post_id, $data, $meta_keys=[]){
		return static::update_meta($post_id, $data, $meta_keys);
	}
}

class WPJAM_Post_Type extends WPJAM_Register{
	private $_fields;

	public function __get($key){
		$object	= get_post_type_object($this->name);

		if($key == 'name'){
			return $this->name;
		}elseif(property_exists('WP_Post_Type', $key) && $object){
			return $object->$key;
		}else{
			return $this->offsetGet($key);
		}
	}

	public function __set($key, $value){
		if($key == 'name'){
			return;
		}

		$object	= get_post_type_object($this->name);

		if(property_exists('WP_Post_Type', $key) && $object){
			$object->$key = $value;
		}else{
			$this->offsetSet($key, $value);
		}
	}

	public function parse_args(){
		if(!$this->plural){
			$this->plural	= $this->name.'s';
		}

		if(!doing_filter('register_post_type_args')){
			if(isset($this->taxonomies) && !$this->taxonomies){
				unset($this->taxonomies);
			}

			$this->args	= wp_parse_args($this->args, [
				'public'		=> true,
				'show_ui'		=> true,
				'hierarchical'	=> false,
				'rewrite'		=> true,
				'permastruct'	=> false,
				'supports'		=> ['title'],
				'by_wpjam'		=> true,
			]);

			if(is_admin() && $this->args['show_ui']){
				add_filter('post_type_labels_'.$this->name,	[$this, 'filter_labels']);
			}

			add_action('registered_post_type_'.$this->name,	[$this, 'registered_callback'], 10, 2);
		}
	}

	public function to_array(){
		$this->filter_args();

		if(doing_filter('register_post_type_args')){
			if(!$this->_builtin && $this->permastruct){
				$this->permastruct	= str_replace('%post_id%', '%'.$this->name.'_id%', $this->permastruct);

				if(strpos($this->permastruct, '%'.$this->name.'_id%')){
					if($this->hierarchical){
						$this->permastruct	= false;
					}else{
						$this->query_var	= $this->query_var ?? false;

						if(!$this->rewrite){
							$this->rewrite	= true;
						}
					}
				}
			}

			if($this->by_wpjam){
				if($this->hierarchical){
					$this->supports		= array_merge($this->supports, ['page-attributes']);
				}

				if($this->rewrite){
					$this->rewrite	= is_array($this->rewrite) ? $this->rewrite : [];
					$this->rewrite	= wp_parse_args($this->rewrite, ['with_front'=>false, 'feeds'=>false]);
				}
			}
		}

		return $this->args;
	}

	public function add_field(...$args){
		$fields	= is_array($args[0]) ? $args[0] : [$args[0]=>$args[1]];

		$this->_fields	= array_merge($this->get_fields(), $fields);

		return $this;
	}

	public function remove_field($key){
		$this->_fields	= array_except($this->get_fields(), $key);

		return $this;
	}

	public function get_fields(){
		if(is_null($this->_fields)){
			$fields	= [];

			if($this->supports('images')){
				$fields['images']	= ['title'=>'头图', 'type'=>'mu-img',	'item_type'=>'url', 'show_in_rest'=>false];

				if($this->images_sizes){
					$fields['images']['size']			= $this->images_sizes[0];
					$fields['images']['description']	= '尺寸：'.$this->images_sizes[0];
				}

				if($this->images_max_items){
					$fields['images']['max_items']		= $this->images_max_items;
				}
			}

			if($this->supports('video')){
				$fields['video']	= ['title'=>'视频',	'type'=>'url'];
			}

			$this->_fields	= $fields;
		}

		return $this->_fields;
	}

	public function get_support($feature){
		if($this->supports($feature)){
			$supports	= get_all_post_type_supports($this->name);
			$support	= $supports[$feature];

			if(is_array($support) && wp_is_numeric_array($support) && count($support) == 1){
				return current($support);
			}else{
				return $support;
			}
		}

		return false;
	}

	public function supports($feature){
		return post_type_supports($this->name, $feature);
	}

	public function get_sizes($orientation=null){
		$sizes	= [];

		if($this->images_sizes){
			$sizes['large']		= $this->images_sizes[0];
			$sizes['thumbnail']	= $this->images_sizes[1];

			if($orientation == 'landscape'){
				if(!empty($this->images_sizes[2])){
					$sizes['thumbnail']	= $this->images_sizes[2];
				}
			}elseif($orientation == 'portrait'){
				if(!empty($this->images_sizes[3])){
					$sizes['thumbnail']	= $this->images_sizes[3];
				}
			}

			return $sizes;
		}else{
			return [
				'large'		=> $this->get_size('large'),
				'thumbnail'	=> $this->get_size('thumbnail'),
			];
		}
	}

	public function get_size($type='thumbnail', $orientation=null){
		return $this->{$type.'_size'} ?: $type;
	}

	public function get_meta_options($args=[]){
		if(!WPJAM_Post_Option::get($this->name.'_base')){
			$fields	= $this->get_fields();

			if($fields){
				WPJAM_Post_Option::register($this->name.'_base', [
					'title'			=> '基础信息',
					'fields'		=> $fields,
					'list_table'	=> false,
					'order'			=> 100,
				]);
			}
		}

		$args[$this->name]	= function($object, $post_type){
			return $object->is_available($post_type);
		};

		return WPJAM_Post_Option::get_registereds($args);
	}

	public function get_taxonomies($output='objects'){
		$taxonomies	= get_object_taxonomies($this->name);

		if($output == 'names'){
			return $taxonomies;
		}

		$objects	= [];

		foreach($taxonomies as $taxonomy){
			$tax_object	= wpjam_get_taxonomy_object($taxonomy);

			if($tax_object){
				$objects[$taxonomy]	= $tax_object;
			}
		}

		return $objects;
	}

	public function in_taxonomy($taxonomy){
		return is_object_in_taxonomy($this->name, $taxonomy);
	}

	public function is_viewable(){
		return is_post_type_viewable($this->name);
	}

	public function filter_labels($labels){
		$_labels	= (array)($this->labels ?? []);
		$labels		= (array)$labels;
		$name		= $labels['name'];
		$search		= $this->hierarchical ? ['撰写新', '写文章', '页面', 'page', 'Page'] : ['撰写新', '写文章', '文章', 'post', 'Post'];
		$replace	= ['新建', '新建'.$name, $name, $name, ucfirst($name)];

		foreach ($labels as $key => &$label) {
			if($label && empty($_labels[$key])){
				if($key == 'all_items'){
					$label	= '所有'.$name;
				}elseif($key == 'archives'){
					$label	= $name.'归档';
				}elseif($label != $name){
					$label	= str_replace($search, $replace, $label);
				}
			}
		}

		return $labels;
	}

	public function registered_callback($post_type, $object){
		if($this->name == $post_type){
			if($this->permastruct){
				if(strpos($this->permastruct, '%'.$post_type.'_id%')){
					wpjam_set_permastruct($post_type, $this->permastruct);

					add_rewrite_tag('%'.$post_type.'_id%', '([0-9]+)', 'post_type='.$post_type.'&p=');

					remove_rewrite_tag('%'.$post_type.'%');
				}elseif(strpos($this->permastruct, '%postname%')){
					wpjam_set_permastruct($post_type, $this->permastruct);
				}
			}

			if($this->registered_callback && is_callable($this->registered_callback)){
				call_user_func($this->registered_callback, $post_type, $object);
			}
		}
	}

	public static function filter_register_args($args, $post_type){
		if(did_action('init') || empty($args['_builtin'])){
			$object	= self::get($post_type);

			if($object){
				$object->update_args($args);
			}else{
				$object	= self::register($post_type, $args);
			}

			return $object->to_array();
		}

		return $args;
	}

	public static function on_registered($post_type, $object){
		if(did_action('init')){
			(self::get($post_type))->registered_callback($post_type, $object);
		}
	}

	public static function filter_link($post_link, $post){
		$post_type	= get_post_type($post);

		if(array_search('%'.$post_type.'_id%', $GLOBALS['wp_rewrite']->rewritecode, true)){
			$post_link	= str_replace('%'.$post_type.'_id%', $post->ID, $post_link);
		}

		if(strpos($post_link, '%') !== false){
			foreach(get_object_taxonomies($post_type, 'objects') as $taxonomy => $taxonomy_object){
				if($taxonomy_object->rewrite){
					$tax_slug	= $taxonomy_object->rewrite['slug'];

					if(strpos($post_link, '%'.$tax_slug.'%') === false){
						continue;
					}

					if($terms = get_the_terms($post->ID, $taxonomy)){
						$post_link	= str_replace('%'.$tax_slug.'%', current($terms)->slug, $post_link);
					}else{
						$post_link	= str_replace('%'.$tax_slug.'%', $taxonomy, $post_link);
					}
				}
			}
		}

		return $post_link;
	}

	public static function filter_clauses($clauses, $wp_query){
		global $wpdb;

		if($wp_query->get('related_query')){
			if($term_taxonomy_ids = $wp_query->get('term_taxonomy_ids')){
				$clauses['fields']	.= ", count(tr.object_id) as cnt";
				$clauses['join']	.= "INNER JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id";
				$clauses['where']	.= " AND tr.term_taxonomy_id IN (".implode(",",$term_taxonomy_ids).")";
				$clauses['groupby']	.= " tr.object_id";
				$clauses['orderby']	= " cnt DESC, {$wpdb->posts}.ID DESC";
			}
		}else{
			$orderby	= $wp_query->get('orderby');
			$order		= $wp_query->get('order') ?: 'DESC';

			if($orderby == 'views'){
				$clauses['fields']	.= ", (COALESCE(jam_pm.meta_value, 0)+0) as {$orderby}";
				$clauses['join']	.= "LEFT JOIN {$wpdb->postmeta} jam_pm ON {$wpdb->posts}.ID = jam_pm.post_id AND jam_pm.meta_key = '{$orderby}' ";
				$clauses['orderby']	= "{$orderby} {$order}, " . $clauses['orderby'];
			}elseif(in_array($orderby, ['', 'date', 'post_date'])){
				$clauses['orderby']	.= ", {$wpdb->posts}.ID {$order}";
			}
		}

		return $clauses;
	}

	public static function filter_password_required($required, $post){
		if(!$required){
			return $required;
		}

		$hash	= wpjam_get_parameter('post_password', ['method'=>'REQUEST']);

		if(empty($hash) || 0 !== strpos($hash, '$P$B')){
			return true;
		}

		require_once ABSPATH . WPINC . '/class-phpass.php';

		$hasher	= new PasswordHash(8, true);

		return !$hasher->CheckPassword($post->post_password, $hash);
	}

	public static function filter_content_save_pre($content){
		if($content && is_serialized($content)){
			$filter		= 'content_save_pre';
			$hook		= 'wp_filter_post_kses';
			$priority	= wpjam_get_current_priority($filter);
			$var 		= 'content_save_pre_filter_removed';

			if($priority < 10){
				if(has_filter($filter, $hook)){
					remove_filter($filter, $hook);

					wpjam_set_current_var($var, true);
				}
			}else{
				if(wpjam_get_current_var($var)){
					add_filter($filter, $hook);

					wpjam_set_current_var($var, false);
				}
			}
		}

		return $content;
	}

	public static function autoload(){
		add_filter('posts_clauses',				[self::class, 'filter_clauses'], 1, 2);
		add_filter('post_type_link',			[self::class, 'filter_link'], 1, 2);
		add_filter('post_password_required',	[self::class, 'filter_password_required'], 10, 2);
		add_filter('content_save_pre',			[self::class, 'filter_content_save_pre'], 1);
		add_filter('content_save_pre',			[self::class, 'filter_content_save_pre'], 11);

		foreach(self::get_registereds() as $post_type => $object){
			if(!get_post_type_object($post_type)){
				register_post_type($post_type, $object->to_array());
			}
		}
	}

	protected static function get_config($key){
		if(in_array($key, ['menu_page', 'register_json'])){
			return true;
		}
	}

	public static function get_by_model($model){
		if($model != 'wpjam_post'){
			$parent	= get_parent_class($model);
			$parent	= ($parent && !is_a($parent, 'WPJAM_Post')) ? strtolower($parent) : '';

			foreach(self::get_registereds() as $object){
				if($object->model && is_string($object->model)){
					$object_model	= strtolower($object->model);

					if($object_model == $model || ($parent && $object_model == $parent)){
						return $object;
					}
				}
			}
		}

		return '';
	}

	public static function create($name, $args){
		$args	= wp_parse_args($args, ['init'=>true]);
		$object = self::register($name, $args);

		if(did_action('init')){
			register_post_type($name, $object->to_array());
		}

		return $object;
	}
}

class WPJAM_Post_Option extends WPJAM_Meta_Option{
	public function parse_args(){
		$args	= parent::parse_args();
		$args	= wp_parse_args($args, ['fields'=>[],	'priority'=>'default']);

		if(!isset($args['post_type'])){
			$post_types = array_pull($args, 'post_types');

			if($post_types){
				$args['post_type']	= $post_types;
			}
		}

		if(!isset($args['list_table']) && did_action('current_screen') && !in_array(get_current_screen()->base, ['edit', 'upload'])){
			$args['list_table']	= true;
		}

		return $args;
	}

	public function meta_box_cb($post, $meta_box){
		if($this->meta_box_cb){
			call_user_func($this->meta_box_cb, $post, $meta_box);
		}else{
			echo $this->summary ? wpautop($this->summary) : '';

			$args	= ['fields_type'=>$this->context == 'side' ? 'list' : 'table',];

			if($GLOBALS['current_screen']->action != 'add'){
				$args['id']	= $post->ID;

				if($this->data){
					$args['data']	= $this->data;
				}else{
					$args['value_callback']	= $this->value_callback;
				}
			}else{
				$args['id']	= false;
			}

			$this->get_fields($post->ID, 'object')->render($args);
		}
	}

	public function is_available($post_type){
		return is_null($this->post_type) || wpjam_compare($post_type, (array)$this->post_type);
	}

	public function add_meta_box($post_type, $context){
		$callback	= [$this, 'meta_box_cb'];
		$context	= $this->context ?: $context;

		add_meta_box($this->name, $this->title, $callback, $post_type, $context, $this->priority);
	}

	public function is_available_for_post_type($post_type){	// 兼容
		return $this->is_available($post_type);
	}

	public static function get_by_post_type($post_type){	// 兼容
		$object	= wpjam_get_post_type_object($post_type);

		return $object ? $object->get_meta_options() : [];
	}

	public static function autoload(){
		self::register_config(['orderby'=>'order']);
	}
}

class WPJAM_Query_Parser{
	private $wp_query;

	public function __construct($wp_query, &$args=[]){
		if(is_array($wp_query)){
			$query_vars		= self::parse_query_vars($wp_query, $args);

			$this->wp_query	= wpjam_query($query_vars);
		}else{
			$this->wp_query	= $wp_query;
		}
	}

	public function parse($args=[]){
		$parsed	= [];

		if(!$this->wp_query){
			return $parsed;
		}

		$filter	= array_pull($args, 'filter');
		$args	= array_merge($args, ['list_query'=>true]);

		if($this->wp_query->have_posts()){
			while($this->wp_query->have_posts()){
				$this->wp_query->the_post();

				$post_id	= get_the_ID();
				$json		= wpjam_get_post($post_id, $args);

				if($filter){

					$json	= apply_filters($filter, $json, $post_id, $args);

				}

				$parsed[]	= $json;
			}
		}

		wp_reset_postdata();

		return $parsed;
	}

	public function render($args=[]){
		$output	= '';

		if(!$this->wp_query){
			return $output;
		}

		$item_callback	= array_pull($args, 'item_callback');

		if(!$item_callback || !is_callable($item_callback)){
			$item_callback	= [$this, 'item_callback'];
		}

		$title_number	= array_pull($args, 'title_number');
		$total_number	= count($this->wp_query->posts);

		if($this->wp_query->have_posts()){
			while($this->wp_query->have_posts()){
				$this->wp_query->the_post();

				if($title_number){
					$args['title_number']	= zeroise($this->wp_query->current_post+1, strlen($total_number));
				}

				$output .= call_user_func($item_callback, get_the_ID(), $args);
			}
		}

		wp_reset_postdata();

		$wrap_callback	= array_pull($args, 'wrap_callback');

		if(!$wrap_callback || !is_callable($wrap_callback)){
			$wrap_callback	= [$this, 'wrap_callback'];
		}

		$output = call_user_func($wrap_callback, $output, $args);

		return $output;
	}

	public function item_callback($post_id, $args){
		$args	= wp_parse_args($args, [
			'title_number'	=> 0,
			'excerpt'		=> false,
			'thumb'			=> true,
			'size'			=> 'thumbnail',
			'thumb_class'	=> 'wp-post-image',
			'wrap'			=> '<li>%1$s</li>'
		]);

		$item	= get_the_title($post_id);

		if($args['title_number']){
			$item	= '<span class="title-number">'.$args['title_number'].'</span>. '.$item;
		}

		if($args['thumb'] || $args['excerpt']){
			$item = '<h4>'.$item.'</h4>';

			if($args['thumb']){
				$item	= get_the_post_thumbnail($post_id, $args['size'], ['class'=>$args['thumb_class']])."\n".$item;
			}

			if($args['excerpt']){
				$item	= $item."\n".wpautop(get_the_excerpt($post_id));
			}
		}

		$item	= '<a href="'.get_permalink($post_id).'" title="'.the_title_attribute(['post'=>$post_id, 'echo'=>false]).'">'.$item.'</a>';

		if($args['wrap']){
			$item	= sprintf($args['wrap'], $item)."\n";
		}

		return $item;
	}

	public function wrap_callback($output, $args){
		if(!$output){
			return '';
		}

		$args	= wp_parse_args($args, [
			'title'		=> '',
			'div_id'	=> '',
			'class'		=> '',
			'thumb'		=> true,
			'wrap'		=> '<ul %1$s>%2$s</ul>'
		]);

		if($args['thumb']){
			$args['class']	= $args['class'].' has-thumb';
		}

		$class	= $args['class'] ? ' class="'.$args['class'].'"' : '';

		if($args['wrap']){
			$output	= sprintf($args['wrap'], $class, $output)."\n";
		}

		if($args['title']){
			$output	= '<h3>'.$args['title'].'</h3>'."\n".$output;
		}

		if($args['div_id']){
			$output	= '<div id="'.$args['div_id'].'">'."\n".$output.'</div>'."\n";
		}

		return $output;
	}

	public static function parse_tax_query($taxonomy, $term_id){
		if($term_id == 'none'){
			return ['taxonomy'=>$taxonomy,	'field'=>'term_id',	'operator'=>'NOT EXISTS'];
		}else{
			return ['taxonomy'=>$taxonomy,	'field'=>'term_id',	'terms'=>[$term_id]];
		}
	}

	public static function parse_query_vars($query_vars, &$args=[]){
		$tax_query	= $query_vars['tax_query'] ?? [];
		$date_query	= $query_vars['date_query'] ?? [];

		$taxonomies	= array_values(get_taxonomies(['_builtin'=>false]));

		foreach(array_merge($taxonomies, ['category', 'post_tag']) as $taxonomy){
			$query_key	= wpjam_get_taxonomy_query_key($taxonomy);
			$term_id	= array_pull($query_vars, $query_key);

			if($term_id){
				if($taxonomy == 'category' && $term_id != 'none'){
					$query_vars[$query_key]	= $term_id;
				}else{
					$tax_query[]	= self::parse_tax_query($taxonomy, $term_id);
				}
			}
		}

		if(!empty($query_vars['taxonomy']) && empty($query_vars['term'])){
			$term_id	= array_pull($query_vars, 'term_id');

			if($term_id){
				if(is_numeric($term_id)){
					$taxonomy		= array_pull($query_vars, 'taxonomy');
					$tax_query[]	= self::parse_tax_query($taxonomy, $term_id);
				}else{
					$query_vars['term']	= $term_id;
				}
			}
		}

		foreach(['cursor'=>'before', 'since'=>'after'] as $key => $query_key){
			$value	= array_pull($query_vars, $key);

			if($value){
				$date_query[]	= [$query_key => wpjam_date('Y-m-d H:i:s', $value)];
			}
		}

		if($args){
			$post_type	= array_pull($args, 'post_type');
			$orderby	= array_pull($args, 'orderby');
			$number		= array_pull($args, 'number');
			$days		= array_pull($args, 'days');

			if($post_type){
				$query_vars['post_type']	= $post_type;
			}

			if($orderby){
				$query_vars['orderby']	= $orderby;
			}

			if($number){
				$query_vars['posts_per_page']	= $number;
			}

			if($days){
				$after	= wpjam_date('Y-m-d', time() - DAY_IN_SECONDS * $days).' 00:00:00';
				$column	= array_pull($args, 'column') ?: 'post_date_gmt';

				$date_query[]	= ['column'=>$column, 'after'=>$after];
			}
		}

		if($tax_query){
			$query_vars['tax_query']	= $tax_query;
		}

		if($date_query){
			$query_vars['date_query']	= $date_query;
		}

		return $query_vars;
	}
}
