<?php
// register
function wpjam_register($group, ...$args){
	return WPJAM_Register::register_by_group($group, ...$args);
}

function wpjam_unregister($group, $name, $args=[]){
	WPJAM_Register::unregister_by_group($group, $name, $args);
}

function wpjam_get_registereds($group){
	return WPJAM_Register::get_by_group($group);
}

function wpjam_get_registered_object($group, $name){
	return $name ? WPJAM_Register::get_by_group($group, $name) : null;
}

function wpjam_preprocess_register_args($args){
	return WPJAM_Register::preprocess_args($args);
}

if(!function_exists('wpdb')){
	function wpdb(){
		return $GLOBALS['wpdb'];
	}
}

// handler
function wpjam_register_handler(...$args){
	if(count($args) >= 2){
		$name	= $args[0];
		$args	= $args[1];
	}else{
		$name	= null;
		$args	= $args[0];
	}

	if(is_object($args)){
		$object	= $args;

		if(!$name){
			return;
		}
	}else{
		$map	= ['option_items'=>'option_name', 'db'=>'table_name'];
		$type	= array_pull($args, 'type');

		if($type && isset($map[$type])){
			$type_name	= array_pull($args, $map[$type]) ?: $name;
		}else{
			foreach($map as $type_key => $name_key){
				$type_name	= array_pull($args, $name_key);

				if($type_name){
					$type	= $type_key;

					break;
				}
			}
		}

		if(empty($type_name)){
			return;
		}

		$name	= $name ?: $type_name;
		$class	= 'WPJAM_'.$type;
		$object	= new $class($type_name, $args);
	}

	wpjam_add_item('handler', $name, $object);

	return $object;
}

function wpjam_get_handler($name){
	return wpjam_get_item('handler', $name);
}

// LazyLoader
function wpjam_register_lazyloader($name, $args){
	if(!in_array($name, ['term_meta', 'comment_meta'])){
		return WPJAM_Lazyloader::register($name, $args);
	}
}

function wpjam_lazyload($name, $ids, ...$args){
	if(in_array($name, ['term_meta', 'comment_meta'])){
		$name	= wpjam_remove_postfix($name, '_meta');
		$object	= wp_metadata_lazyloader();
		$object->queue_objects($name, $ids);
	}else{
		$object	= WPJAM_Lazyloader::get($name);

		$object ? $object->queue_objects($ids, ...$args) : null;
	}
}

// Platform
function wpjam_register_platform($name, $args){
	return WPJAM_Platform::register($name, $args);
}

function wpjam_get_current_platform($names=[], $type='key'){
	return WPJAM_Platform::get_current($names, $type);
}

function wpjam_get_current_platforms(){
	return WPJAM_Path::get_platforms();
}

function wpjam_is_platform($name){
	return WPJAM_Platform::get($name)->verify();
}

function wpjam_get_platform_options($type='bit'){
	return WPJAM_Platform::get_options($type);
}

// Path
function wpjam_register_path($page_key, ...$args){
	return WPJAM_Path::create($page_key, ...$args);
}

function wpjam_unregister_path($page_key, $platform=''){
	return WPJAM_Path::remove($page_key, $platform);
}

function wpjam_get_path_object($page_key){
	return wpjam_path($page_key);
}

function wpjam_path($page_key, $platform=null, $args=[]){
	$object	= WPJAM_Path::get($page_key);

	if($platform){
		return $object ? $object->get_path($platform, $args) : '';
	}

	return $object;
}

function wpjam_get_path($platform, $page_key, $args=[]){
	if(is_array($page_key)){
		$args		= $page_key;
		$page_key	= array_pull($args, 'page_key');
	}

	return wpjam_path($page_key, $platform, $args);
}

function wpjam_get_paths($platform){
	return WPJAM_Path::get_by(['platform'=>$platform]);
}

function wpjam_get_tabbar_options($platform){
	return WPJAM_Path::get_tabbar_options($platform);
}

function wpjam_get_path_fields($platforms, $args=[]){
	return WPJAM_Path::get_path_fields($platforms, $args);
}

function wpjam_get_page_keys($platform){
	return WPJAM_Path::get_page_keys($platform);
}

function wpjam_parse_path_item($item, $platform, $parse_backup=true){
	$parsed	= WPJAM_Path::parse($item, $platform);

	if(empty($parsed) && $parse_backup && !empty($item['page_key_backup'])){
		$parsed	= WPJAM_Path::parse($item, $platform, true);
	}

	return $parsed ?: ['type'=>'none'];
}

function wpjam_validate_path_item($item, $platforms){
	$result	= WPJAM_Path::validate($item, $platforms);

	if(is_wp_error($result) && $result->get_error_code() == 'invalid_page_key' && count($platforms) > 1){
		return WPJAM_Path::validate($item, $platforms, true);	
	}

	return $result;
}

function wpjam_get_path_item_link_tag($parsed, $text){
	return WPJAM_Path::get_link_tag($parsed, $text);
}

// Items
function wpjam_get_items_object($name){
	$object	= wpjam_get_registered_object('items', $name);

	return $object ?: wpjam_register('items', $name, []);
}

function wpjam_get_items($name){
	return wpjam_get_items_object($name)->get_items();
}

function wpjam_get_item($name, $key){
	return wpjam_get_items_object($name)->get_item($key);
}

function wpjam_add_item($name, ...$args){
	return wpjam_get_items_object($name)->add_item(...$args);
}

// Data Type
function wpjam_register_data_type($name, $args=[]){
	return WPJAM_Data_Type::register($name, $args);
}

function wpjam_get_data_type_object($name){
	return WPJAM_Data_Type::get($name);
}

function wpjam_strip_data_type($args){
	return WPJAM_Data_Type::strip($args);
}

function wpjam_slice_data_type(&$args, $strip=false){
	return WPJAM_Data_Type::slice($args, $strip);
}

function wpjam_get_data_type_field($name, $args){
	$object	= wpjam_get_data_type_object($name);

	return $object ? $object->get_field($args) : [];
}

function wpjam_get_post_id_field($post_type='post', $args=[]){
	return WPJAM_Post_Type_Data_Type::get_field(array_merge($args, ['post_type'=>$post_type]));
}

function wpjam_get_authors($args=[], $return='users'){
	return get_users(array_merge($args, ['capability'=>'edit_posts']));
}

function wpjam_get_video_mp4($id_or_url){
	return WPJAM_Video_Data_Type::get_video_mp4($id_or_url);
}

function wpjam_get_qqv_mp4($vid){
	return WPJAM_Video_Data_Type::get_qqv_mp4($vid);
}

function wpjam_get_qqv_id($id_or_url){
	return WPJAM_Video_Data_Type::get_qqv_id($id_or_url);
}

// Setting
function wpjam_setting($type, $option, $blog_id=0){
	return WPJAM_Setting::get_instance($type, $option, $blog_id);
}

function wpjam_get_setting_object($type, $option, $blog_id=0){
	return wpjam_setting($type, $option, $blog_id);
}

function wpjam_get_setting($option, $name, $blog_id=0){
	return wpjam_setting('option', $option, $blog_id)->get_setting($name);
}

function wpjam_update_setting($option, $name, $value, $blog_id=0){
	return wpjam_setting('option', $option, $blog_id)->update_setting($name, $value);
}

function wpjam_delete_setting($option, $name, $blog_id=0){
	return wpjam_setting('option', $option, $blog_id)->delete_setting($name);
}

function wpjam_get_option($option, $blog_id=0, $default=[]){
	return wpjam_setting('option', $option, $blog_id)->get_option($default);
}

function wpjam_update_option($option, $value, $blog_id=0){
	return wpjam_setting('option', $option, $blog_id)->update_option($value);
}

function wpjam_get_site_setting($option, $name){
	return wpjam_setting('site_option', $option)->get_setting($name);
}

function wpjam_get_site_option($option, $default=[]){
	return wpjam_setting('site_option', $option)->get_option($default);
}

function wpjam_update_site_option($option, $value){
	return wpjam_setting('site_option', $option)->update_option($value);
}

function wpjam_sanitize_option_value($value){
	return WPJAM_Setting::sanitize_option($value);
}

// Option
function wpjam_register_option($name, $args=[]){
	return WPJAM_Option_Setting::create($name, $args);
}

function wpjam_get_option_object($name){
	return WPJAM_Option_Setting::get($name);
}

function wpjam_add_option_section($option_name, ...$args){
	return WPJAM_Option_Section::add($option_name, ...$args);
}

function wpjam_add_option_section_fields($option_name, $section_id, $fields){
	return WPJAM_Option_Section::add($option_name, $section_id, ['fields'=>$fields]);
}

function wpjam_register_extend_option($name, $dir, $args=[]){
	return WPJAM_Extend::create($dir, $args, $name);
}

function wpjam_register_extend_type($name, $dir, $args=[]){
	return wpjam_register_extend_option($name, $dir, $args);
}

function wpjam_load_extends($dir, $args=[]){
	WPJAM_Extend::create($dir, $args);
}

function wpjam_get_file_summary($file){
	return WPJAM_Extend::get_file_summay($file);
}

function wpjam_get_extend_summary($file){
	return WPJAM_Extend::get_file_summay($file);
}

// Permastruct
function wpjam_get_permastruct($name){
	return $GLOBALS['wp_rewrite']->get_extra_permastruct($name);
}

function wpjam_set_permastruct($name, $value){
	return $GLOBALS['wp_rewrite']->extra_permastructs[$name]['struct']	= $value;
}

// Meta Type
function wpjam_register_meta_type($name, $args=[]){
	return WPJAM_Meta_Type::register($name, $args);
}

function wpjam_get_meta_type_object($name){
	return WPJAM_Meta_Type::get($name);
}

function wpjam_get_by_meta($meta_type, ...$args){
	$object	= wpjam_get_meta_type_object($meta_type);

	return $object ? $object->get_by_key(...$args) : [];
}

// wpjam_get_metadata($meta_type, $object_id, $meta_keys)
// wpjam_get_metadata($meta_type, $object_id, $meta_key, $default)
function wpjam_get_metadata($meta_type, $object_id, ...$args){
	$object	= wpjam_get_meta_type_object($meta_type);

	return $object ? $object->get_data_with_default($object_id, ...$args) : null;
}

// wpjam_update_metadata($meta_type, $object_id, $data, $defaults=[])
// wpjam_update_metadata($meta_type, $object_id, $meta_key, $meta_value, $default=null)
function wpjam_update_metadata($meta_type, $object_id, ...$args){
	$object	= wpjam_get_meta_type_object($meta_type);

	return $object ? $object->update_data_with_default($object_id, ...$args) : null;
}

// Post Type
function wpjam_register_post_type($name, $args=[]){
	return WPJAM_Post_Type::create($name, $args);
}

function wpjam_get_post_type_object($name){
	return WPJAM_Post_Type::get($name);
}

function wpjam_add_post_type_field($post_type, ...$args){
	$object	= WPJAM_Post_Type::get($post_type);

	return $object ? $object->add_field(...$args) : null;
}

function wpjam_remove_post_type_field($post_type, $key){
	$object	= WPJAM_Post_Type::get($post_type);

	return $object ? $object->remove_field($key) : null;
}

function wpjam_get_post_type_setting($post_type, $key, $default=null){
	$object	= WPJAM_Post_Type::get($post_type);

	return $object ? $object->get_arg($key, $default) : $default;
}

function wpjam_update_post_type_setting($post_type, $key, $value){
	$object	= WPJAM_Post_Type::get($post_type);

	return $object ? $object->update_arg($key, $value) : null;
}

// Post Option
function wpjam_register_post_option($meta_box, $args=[]){
	return WPJAM_Post_Option::register($meta_box, $args);
}

function wpjam_unregister_post_option($meta_box){
	WPJAM_Post_Option::unregister($meta_box);
}

// Post Column
function wpjam_register_posts_column($name, ...$args){
	if(is_admin()){
		$field	= is_array($args[0]) ? $args[0] : ['title'=>$args[0], 'callback'=>($args[1] ?? null)];

		return wpjam_register_list_table_column($name, array_merge($field, ['data_type'=>'post_type']));
	}
}

function wpjam_unregister_posts_column($name){
	if(is_admin() && did_action('current_screen')){
		wpjam_add_item(get_current_screen()->id.'_removed_columns', $name);
	}
}

// Post
function wpjam_post($post){
	return WPJAM_Post::get_instance($post);
}

function wpjam_get_post_object($post, $post_type=null){
	return WPJAM_Post::get_instance($post, $post_type);
}

function wpjam_validate_post($post_id, $post_type=null){
	return WPJAM_Post::validate($post_id, $post_type);
}

function wpjam_get_post($post, $args=[]){
	$object	= wpjam_post($post);

	return $object ? $object->parse_for_json($args) : null;
}

function wpjam_get_post_views($post=null){
	$object	= wpjam_post($post);

	return $object ? $object->views : 0;
}

function wpjam_update_post_views($post=null, $addon=1){
	$object	= wpjam_post($post);

	return $object ? $object->view($addon) : null;
}

function wpjam_get_post_excerpt($post=null, $length=0, $more=null){
	$object	= wpjam_post($post);

	return $object ? $object->get_excerpt($length, $more) : '';
}

function wpjam_get_post_content($post=null, $raw=false){
	$object	= wpjam_post($post);

	return $object ? $object->get_content($raw) : '';
}

function wpjam_get_post_images($post=null, $large='', $thumbnail='', $full=true){
	$object	= wpjam_post($post);

	return $object ? $object->get_images($large, $thumbnail, $full) : [];
}

function wpjam_get_post_thumbnail_url($post=null, $size='full', $crop=1){
	$object	= wpjam_post($post);

	return $object ? $object->get_thumbnail_url($size, $crop) : '';
}

function wpjam_get_post_first_image_url($post=null, $size='full'){
	$object	= wpjam_post($post);

	return $object ? $object->get_first_image_url($size) : '';
}

function wpjam_get_posts($post_ids, $args=[]){
	$posts = WPJAM_Post::get_by_ids($post_ids);

	if($posts && $args){
		$args	= is_array($args) ? $args : [];

		foreach($posts as &$_post){
			$_post	= wpjam_get_post($_post, $args);
		}
	}

	return array_values($posts);
}

// Post Query
function wpjam_query($args=[]){
	return new WP_Query(wp_parse_args($args, [
		'no_found_rows'			=> true,
		'ignore_sticky_posts'	=> true,
		'cache_it'				=> true
	]));
}

function wpjam_parse_query_vars($query_vars, &$args=[]){
	return WPJAM_Query_Parser::parse_query_vars($query_vars, $args);
}

function wpjam_parse_query($wp_query, $args=[], $parse=true){
	$object	= new WPJAM_Query_Parser($wp_query, $args);

	return $parse ? $object->parse($args) : $object->render($args);
}

function wpjam_render_query($wp_query, $args=[]){
	return wpjam_parse_query($wp_query, $args, false);
}

function wpjam_related_posts($args=[]){
	echo wpjam_get_related_posts(null, $args, false);
}

function wpjam_get_related_posts($post=null, $args=[], $parse=false){
	$wp_query	= wpjam_get_related_posts_query($post, $args);

	if($parse){
		$args['filter']	= 'wpjam_related_post_json';
	}

	return wpjam_parse_query($wp_query, $args, $parse);
}

// wpjam_get_related_posts_query($number);
// wpjam_get_related_posts_query($post_id, $args);
function wpjam_get_related_posts_query(...$args){
	if(count($args) <= 1){
		$post	= get_the_ID();
		$args	= ['number'=>$args[0] ?? 5];
	}else{
		$post	= $args[0];
		$args	= $args[1];
	}

	$object	= wpjam_post($post);

	return $object ? $object->get_related_query($args) : false;
}

function wpjam_get_new_posts($args=[], $parse=false){
	return wpjam_parse_query([
		'posts_per_page'	=> 5,
		'orderby'			=> 'date',
	], $args, $parse);
}

function wpjam_get_top_viewd_posts($args=[], $parse=false){
	return wpjam_parse_query([
		'posts_per_page'	=> 5,
		'orderby'			=> 'meta_value_num',
		'meta_key'			=> 'views',
	], $args, $parse);
}

function wpjam_get_related_object_ids($tt_ids, $number, $page=1){
	$id_str		= implode(',', array_map('intval', $tt_ids));
	$cache_key	= 'related_object_ids:'.$id_str.':'.$page.':'.$number;
	$object_ids	= wp_cache_get($cache_key, 'terms');

	if($object_ids === false){
		$object_ids	= $GLOBALS['wpdb']->get_col('SELECT object_id, count(object_id) as cnt FROM '.$GLOBALS['wpdb']->term_relationships.' WHERE term_taxonomy_id IN ('.$id_str.') GROUP BY object_id ORDER BY cnt DESC, object_id DESC LIMIT '.(($page-1) * $number).', '.$number);

		wp_cache_set($cache_key, $object_ids, 'terms', DAY_IN_SECONDS);
	}

	return $object_ids;
}


// Taxonomy
function wpjam_register_taxonomy($name, ...$args){
	return WPJAM_Taxonomy::create($name, ...$args);
}

function wpjam_get_taxonomy_object($name){
	return WPJAM_Taxonomy::get($name);
}

function wpjam_add_taxonomy_field($taxonomy, ...$args){
	$object	= WPJAM_Taxonomy::get($taxonomy);

	return $object ? $object->add_field(...$args) : null;
}

function wpjam_remove_taxonomy_field($taxonomy, $key){
	$object	= WPJAM_Taxonomy::get($taxonomy);

	return $object ? $object->remove_field($key) : null;
}

function wpjam_get_taxonomy_setting($taxonomy, $key, $default=null){
	$object	= WPJAM_Taxonomy::get($taxonomy);

	return $object ? $object->get_arg($key, $default) : $default;
}

function wpjam_update_taxonomy_setting($taxonomy, $key, $value){
	$object	= WPJAM_Taxonomy::get($taxonomy);

	return $object ? $object->update_arg($key, $value) : null;
}

function wpjam_get_taxonomy_query_key($taxonomy){
	$query_keys	= ['category'=>'cat', 'post_tag'=>'tag_id'];

	return $query_keys[$taxonomy] ?? $taxonomy.'_id';
}

function wpjam_get_term_id_field($taxonomy='category', $args=[]){
	$object	= WPJAM_Taxonomy::get($taxonomy);

	return $object ? $object->get_id_field($args) : [];
}

// Term Option
function wpjam_register_term_option($name, $args=[]){
	return WPJAM_Term_Option::register($name, $args);
}

function wpjam_unregister_term_option($name){
	WPJAM_Term_Option::unregister($name);
}

// Term Column
function wpjam_register_terms_column($name, ...$args){
	if(is_admin()){
		$field	= is_array($args[0]) ? $args[0] : ['title'=>$args[0], 'callback'=>($args[1] ?? null)];

		return wpjam_register_list_table_column($name, array_merge($field, ['data_type'=>'taxonomy']));
	}
}

function wpjam_unregister_terms_column($name){
	if(is_admin() && did_action('current_screen')){
		wpjam_add_item(get_current_screen()->id.'_removed_columns', $name);
	}
}

// Term
function wpjam_term($term){
	return WPJAM_Term::get_instance($term);
}

function wpjam_get_term_object($term, $taxonomy=''){
	return WPJAM_Term::get_instance($term, $taxonomy);
}

function wpjam_validate_term($term_id, $taxonomy=''){
	return WPJAM_Term::validate($term_id, $taxonomy);
}

function wpjam_get_term($term, $taxonomy=''){
	$object	= wpjam_term($term, $taxonomy);

	return $object ? $object->parse_for_json() : null;
}

if(!function_exists('get_term_taxonomy')){
	function get_term_taxonomy($id){
		$term	= get_term($id);

		return ($term && !is_wp_error($term)) ? $term->taxonomy : null;
	}
}

function wpjam_get_term_thumbnail_url($term=null, $size='full', $crop=1){
	$object	= wpjam_term($term);

	return $object ? $object->get_thumbnail_url($size, $crop) : '';
}

function wpjam_get_term_level($term){
	$object	= wpjam_term($term);

	return $object ? $object->level : '';
}

function wpjam_get_terms($args, $max_depth=null){
	return WPJAM_Term::get_terms($args, $max_depth);
}

// User
function wpjam_user($user){
	return WPJAM_User::get_instance($user);
}

function wpjam_get_user_object($user){
	return wpjam_user($user);
}

function wpjam_get_user($user, $size=96){
	$object	= wpjam_user($user);

	return $object ? $object->parse_for_json($size) : null;
}

// Bind
function wpjam_register_bind($type, $appid, $args){
	$object	= wpjam_get_bind_object($type, $appid);

	return $object ?: WPJAM_Bind::create($type, $appid, $args);
}

function wpjam_get_bind_object($type, $appid){
	return WPJAM_Bind::get($type.':'.$appid);
}

// User Signup
function wpjam_register_user_signup($name, $args){
	return WPJAM_User_Signup::create($name, $args);
}

function wpjam_get_user_signups($args=[], $output='objects', $operator='and'){
	return WPJAM_User_Signup::get_registereds($args, $output, $operator);
}

function wpjam_get_user_signup_object($name){
	return WPJAM_User_Signup::get($name);
}

// AJAX
function wpjam_register_ajax($name, $args){	
	return WPJAM_AJAX::register($name, $args);
}

function wpjam_get_ajax_data_attr($name, $data=[], $return=null){
	$object	= WPJAM_AJAX::get($name);

	return $object ? $object->get_attr($data, $return) : ($return ? null : []);
}

function wpjam_ajax_enqueue_scripts(){
	WPJAM_AJAX::enqueue_scripts();
}

// Capability
function wpjam_register_capability($cap, $map_meta_cap){
	return WPJAM_Capability::create($cap, $map_meta_cap);
}

// Verification Code
function wpjam_generate_verification_code($key, $group='default'){
	$object	= WPJAM_Verification_Code::get_instance($group);

	return $object->generate($key);
}

function wpjam_verify_code($key, $code, $group='default'){
	$object	= WPJAM_Verification_Code::get_instance($group);

	return $object->verify($key, $code);
}

function wpjam_register_verification_code_group($name, $args=[]){
	return WPJAM_Verification_Code::register($name, $args);
}

// Verify TXT
function wpjam_register_verify_txt($name, $args){
	return WPJAM_Verify_TXT::register($name, $args);
}

// Gravatar
function wpjam_register_gravatar_services($name, $args){
	return WPJAM_Gravatar::register($name, $args);
}

// Google Font
function wpjam_register_google_font_services($name, $args){
	return WPJAM_Google_Font::register($name, $args);
}

// Upgrader
function wpjam_register_plugin_updater($hostname, $update_url){
	return WPJAM_Updater::create('plugin', $hostname, $update_url);
}

function wpjam_register_theme_updater($hostname, $update_url){
	return WPJAM_Updater::create('theme', $hostname, $update_url);
}

// Notice
function wpjam_admin_notice($blog_id=0){
	return WPJAM_Notice::get_instance('admin_notice', $blog_id);
}

function wpjam_add_admin_notice($notice, $blog_id=0){
	$object	= wpjam_admin_notice($blog_id);

	return $object ? $object->insert($notice) : null;
}

function wpjam_user_notice($user_id=0){
	return WPJAM_Notice::get_instance('user_notice', $user_id);
}

function wpjam_add_user_notice($user_id, $notice){
	$object	= wpjam_user_notice($user_id);

	return $object ? $object->insert($notice) : null;
}

// Menu Page
function wpjam_add_menu_page($menu_slug, $args=[]){
	wpjam_hooks(array_pull($args, 'hooks'));

	if(is_admin()){
		if(!empty($args['menu_title'])){
			WPJAM_Menu_Page::add($menu_slug, $args);
		}
	}else{
		if(isset($args['function']) && $args['function'] == 'option'){
			if(!empty($args['sections']) || !empty($args['fields'])){
				$option_name	= $args['option_name'] ?? $menu_slug;

				wpjam_register_option($option_name, $args);
			}
		}
	}
}

function wpjam_add_tab_page($tab_slug, $args=[]){
	wpjam_hooks(array_pull($args, 'hooks'));

	if(is_admin()){
		return wpjam_register_plugin_page_tab($tab_slug, $args);
	}
}

