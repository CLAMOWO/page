<?php
class WPJAM_Builtin_Page{
	public static function load($screen){
		wpjam_builtin_page_load($screen);

		if(!wp_doing_ajax() && wpjam_get_page_summary()){
			add_filter('wpjam_html', [self::class, 'filter_html']);
		}

		$method	= 'load_'.str_replace('-', '_', $screen->base).'_page';

		if(method_exists(self::class, $method)){
			call_user_func([self::class, $method]);
		}
	}

	public static function filter_html($html){
		return str_replace('<hr class="wp-header-end">', '<hr class="wp-header-end">'.wpautop(wpjam_get_page_summary()), $html);
	}

	public static function load_post_page(){
		$edit_form_hook	= $GLOBALS['typenow'] == 'page' ? 'edit_page_form' : 'edit_form_advanced';

		add_action($edit_form_hook,			[self::class, 'on_edit_form'], 99);
		add_action('add_meta_boxes',		[self::class, 'on_add_meta_boxes'], 10, 2);
		add_action('wp_after_insert_post',	[self::class, 'on_after_insert_post'], 999, 2);
	}

	public static function load_term_page(){
		add_action($GLOBALS['taxnow'].'_edit_form_fields',	[self::class, 'on_taxonomy_edit_form_fields']);
	}

	public static function load_edit_page(){
		if($GLOBALS['typenow'] && post_type_exists($GLOBALS['typenow'])){
			$GLOBALS['wpjam_list_table']	= new WPJAM_Posts_List_Table();
		}
	}

	public static function load_upload_page(){
		$mode	= get_user_option('media_library_mode', get_current_user_id()) ?: 'grid';

		if(isset($_GET['mode']) && in_array($_GET['mode'], ['grid', 'list'], true)){
			$mode	= $_GET['mode'];
		}

		if($mode == 'grid'){
			return;
		}

		self::load_edit_page();
	}

	public static function load_edit_tags_page(){
		if($GLOBALS['taxnow'] && taxonomy_exists($GLOBALS['taxnow'])){
			add_action('edited_term',	[self::class, 'on_edited_term'], 10, 3);

			if(wp_doing_ajax()){
				if($_POST['action'] == 'add-tag'){
					add_filter('pre_insert_term',	[self::class, 'filter_pre_insert_term'], 10, 2);
					add_action('created_term', 		[self::class, 'on_created_term'], 10, 3);
				}
			}else{
				add_action($GLOBALS['taxnow'].'_add_form_fields', 	[self::class, 'on_taxonomy_add_form_fields']);
			}

			$GLOBALS['wpjam_list_table']	= new WPJAM_Terms_List_Table();
		}
	}

	public static function load_users_page(){
		$GLOBALS['wpjam_list_table']	= new WPJAM_Users_List_Table();
	}

	public static function on_edit_form($post){
		// 下面代码 copy 自 do_meta_boxes
		$context	= 'wpjam';
		$page		= $GLOBALS['current_screen']->id;
		$meta_boxes	= $GLOBALS['wp_meta_boxes'][$page][$context] ?? [];

		if(empty($meta_boxes)) {
			return;
		}

		$nav_tab_title	= '';
		$meta_box_count	= 0;

		foreach(['high', 'core', 'default', 'low'] as $priority){
			if(empty($meta_boxes[$priority])){
				continue;
			}

			foreach ((array)$meta_boxes[$priority] as $meta_box) {
				if(empty($meta_box['id']) || empty($meta_box['title'])){
					continue;
				}

				$meta_box_count++;
				$meta_box_title	= $meta_box['title'];
				$nav_tab_title	.= '<li><a class="nav-tab" href="#tab_'.$meta_box['id'].'">'.$meta_box_title.'</a></li>';
			}
		}

		if(empty($nav_tab_title)){
			return;
		}

		echo '<div id="'.htmlspecialchars($context).'-sortables">';
		echo '<div id="'.$context.'" class="postbox tabs">' . "\n";

		if($meta_box_count == 1){
			echo '<div class="postbox-header">';
			echo '<h2 class="hndle">'.$meta_box_title.'</h2>';
			echo '</div>';
		}else{
			echo '<h2 class="nav-tab-wrapper"><ul>'.$nav_tab_title.'</ul></h2>';
		}

		echo '<div class="inside">';

		foreach (['high', 'core', 'default', 'low'] as $priority) {
			if (!isset($meta_boxes[$priority])){
				continue;
			}

			foreach ((array) $meta_boxes[$priority] as $meta_box) {
				if(empty($meta_box['id']) || empty($meta_box['title'])){
					continue;
				}

				echo '<div id="tab_'.$meta_box['id'].'">';
				call_user_func($meta_box['callback'], $post, $meta_box);
				echo "</div>\n";
			}
		}

		echo "</div>\n";

		echo "</div>\n";
		echo "</div>";
	}

	public static function on_add_meta_boxes($post_type, $post){
		$context		= use_block_editor_for_post_type($post_type) ? 'normal' : 'wpjam';
		$pt_object		= wpjam_get_post_type_object($post_type);
		$meta_options	= $pt_object ? $pt_object->get_meta_options() : [];

		foreach($meta_options as $object){
			if($object->list_table !== 'only' && $object->title){
				$object->add_meta_box($post_type, $context);
			}
		}
	}

	public static function on_after_insert_post($post_id, $post){
		// 非 POST 提交不处理
		// 自动草稿不处理
		// 自动保存不处理
		// 预览不处理
		if($_SERVER['REQUEST_METHOD'] != 'POST'
			|| $post->post_status == 'auto-draft'
			|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			|| (!empty($_POST['wp-preview']) && $_POST['wp-preview'] == 'dopreview')
		){
			return;
		}

		$post_type		= get_post_type($post_id);
		$pt_object		= wpjam_get_post_type_object($post_type);
		$meta_options	= $pt_object ? $pt_object->get_meta_options() : [];

		foreach($meta_options as $object){
			if($object->list_table !== 'only'){
				$result	= $object->callback($post_id);

				if(is_wp_error($result)){
					wp_die($result);
				}
			}
		}
	}

	public static function taxonomy_form_fields($taxonomy, $action, $args){
		$tax_object		= wpjam_get_taxonomy_object($taxonomy);
		$meta_options	= $tax_object ? $tax_object->get_meta_options() : [];

		foreach($meta_options as $object){
			if($object->list_table !== 'only' && (!$object->action || $object->action == $action)){
				$args['value_callback']	= $object->value_callback;

				$object->get_fields($args['id'], 'object')->render($args);
			}
		}
	}

	public static function on_taxonomy_add_form_fields($taxonomy){
		self::taxonomy_form_fields($taxonomy, 'add', [
			'fields_type'	=> 'div',
			'wrap_class'	=> 'form-field',
			'id'			=> false,
		]);
	}

	public static function on_taxonomy_edit_form_fields($term){
		self::taxonomy_form_fields($term->taxonomy, 'edit', [
			'fields_type'	=> 'tr',
			'wrap_class'	=> 'form-field',
			'id'			=> $term->term_id,
		]);
	}

	public static function update_taxonomy_data($taxonomy, $action, $term_id=null){
		$tax_object		= wpjam_get_taxonomy_object($taxonomy);
		$meta_options	= $tax_object ? $tax_object->get_meta_options() : [];

		foreach($meta_options as $object){
			if($object->list_table !== 'only' && (!$object->action || $object->action == $action)){
				if($term_id){
					$result	= $object->callback($term_id);
				}else{
					$result	= $object->validate();
				}
			}
		}
	}

	public static function filter_pre_insert_term($term, $taxonomy){
		$result	= self::update_taxonomy_data($taxonomy, 'add');

		return is_wp_error($result) ? $result : $term;
	}

	public static function on_created_term($term_id, $tt_id, $taxonomy){
		$result	= self::update_taxonomy_data($taxonomy, 'add', $term_id);

		if(is_wp_error($result)){
			wp_die($result);
		}
	}

 	public static function on_edited_term($term_id, $tt_id, $taxonomy){
 		$result	= self::update_taxonomy_data($taxonomy, 'edit', $term_id);
		
		if(is_wp_error($result)){
			wp_die($result);
		}
	}
}

class WPJAM_Builtin_List_Table extends WPJAM_List_Table{
	public function filter_bulk_actions($bulk_actions=[]){
		return array_merge($bulk_actions, $this->get_bulk_actions());
	}

	public function filter_columns($columns){
		if($this->columns){
			$columns	= array_merge(array_slice($columns, 0, -1), $this->columns, array_slice($columns, -1));
		}

		$removed	= wpjam_get_items(get_current_screen()->id.'_removed_columns');

		return array_except($columns, $removed);
	}

	public function filter_custom_column($value, $name, $id){
		return $this->get_column_value($id, $name, $value);
	}

	public function filter_sortable_columns($sortable_columns){
		return array_merge($sortable_columns, $this->get_sortable_columns());
	}

	public function filter_html($html){
		return $this->single_row_replace($html);
	}

	public function get_single_row($id){		
		return apply_filters('wpjam_single_row', parent::get_single_row($id), $id);
	}

	public function get_list_table(){
		return $this->single_row_replace(parent::get_list_table());
	}

	public function single_row_replace($html){
		return preg_replace_callback('/<tr id="'.$this->singular.'-(\d+)".*?>.*?<\/tr>/is', function($matches){
			return apply_filters('wpjam_single_row', $matches[0], $matches[1]);
		}, $html);
	}

	public function wp_list_table(){
		if(!isset($GLOBALS['wp_list_table'])){
			$GLOBALS['wp_list_table'] = _get_list_table($this->builtin_class, ['screen'=>get_current_screen()]);
		}

		return $GLOBALS['wp_list_table'];
	}

	public function prepare_items(){
		$data	= wpjam_get_data_parameter();

		foreach($data as $key=>$value){
			$_GET[$key]	= $_POST[$key]	= $value;
		}

		$this->wp_list_table()->prepare_items();
	}
}

class WPJAM_Posts_List_Table extends WPJAM_Builtin_List_Table{
	public function __construct(){
		$screen			= get_current_screen();
		$post_type		= $screen->post_type;
		$type_object	= wpjam_get_post_type_object($post_type);

		if(!$type_object){
			return;
		}

		$args	= [
			'title'			=> $type_object->label,
			'type_object'	=> $type_object,
			'singular'		=> 'post',
			'capability'	=> 'edit_post',
			'data_type'		=> 'post_type',
			'post_type'		=> $post_type,
			'model'			=> 'WPJAM_Post'
		];

		if($post_type == 'attachment'){
			$row_actions_filter		= 'media_row_actions';
			$column_filter_part		= 'media';

			$args['builtin_class']	= 'WP_Media_List_Table';
		}else{
			$row_actions_filter		= $type_object->hierarchical ? 'page_row_actions' : 'post_row_actions';
			$column_filter_part		= $post_type.'_posts';

			$args['builtin_class']	= 'WP_Posts_List_Table';

			add_filter('map_meta_cap',	[$this, 'filter_map_meta_cap'], 10, 4);
		}

		if(wp_doing_ajax()){
			wpjam_add_admin_ajax('wpjam-list-table-action',	[$this, 'ajax_response']);
		}

		if(!wp_doing_ajax() || (wp_doing_ajax() && $_POST['action']=='inline-save')){
			add_filter('wpjam_html',	[$this, 'filter_html']);
		}

		add_action('pre_get_posts',	[$this, 'on_pre_get_posts']);

		add_filter('bulk_actions-'.$screen->id,	[$this, 'filter_bulk_actions']);

		add_filter($row_actions_filter,	[$this, 'filter_row_actions'], 1, 2);

		add_action('manage_'.$column_filter_part.'_custom_column',	[$this, 'on_custom_column'], 10, 2);

		add_filter('manage_'.$column_filter_part.'_columns',	[$this, 'filter_columns']);
		add_filter('manage_'.$screen->id.'_sortable_columns',	[$this, 'filter_sortable_columns']);

		// 一定要最后执行
		$this->_args	= $this->parse_args($args);
	}

	public function __get($name){
		if($name == 'post_type'){
			return get_current_screen()->$name;
		}else{
			return parent::__get($name);
		}
	}

	public function filter_map_meta_cap($caps, $cap, $user_id, $args){
		if($cap == 'edit_post' && empty($args[0])){
			return $this->type_object->map_meta_cap ? [$this->type_object->cap->edit_posts] : [$this->type_object->cap->$cap];
		}

		return $caps;
	}

	public function call_model_list_action($id, $data, $list_action){
		if($id && get_post($id)){
			$post_data	= [];

			foreach(get_post($id, ARRAY_A) as $post_key => $old_value){
				$value	= array_pull($data, $post_key);

				if(!is_null($value) && $old_value != $value){
					$post_data[$post_key]	= $value;
				}
			}

			if($post_data){
				$result	= WPJAM_Post::update($id, $post_data);

				if(is_wp_error($result) || empty($data)){
					return $result;
				}
			}

			if(empty($data)){
				return true;
			}
		}

		return parent::call_model_list_action($id, $data, $list_action);
	}

	public function prepare_items(){
		$_GET['post_type']	= $this->post_type;

		parent::prepare_items();
	}

	public function list_table(){
		$wp_list_table	= $this->wp_list_table();

		if($this->post_type == 'attachment'){
			echo '<form id="posts-filter" method="get">';

			$wp_list_table->views();	
		}else{
			$wp_list_table->views();

			echo '<form id="posts-filter" method="get">';

			$status	= wpjam_get_data_parameter('post_status', ['default'=>'all']);

			echo wpjam_field(['key'=>'post_status',	'type'=>'hidden',	'class'=>'post_status_page',	'value'=>$status]);
			echo wpjam_field(['key'=>'post_type',	'type'=>'hidden',	'class'=>'post_type_page',		'value'=>$this->post_type]);

			if($show_sticky	= wpjam_get_data_parameter('show_sticky')){
				echo wpjam_field(['key'=>'show_sticky', 'type'=>'hidden', 'value'=>1]);
			}

			$wp_list_table->search_box($this->type_object->labels->search_items, 'post');
		}

		$wp_list_table->display(); 

		echo '</form>';
	}

	public function single_row($raw_item){
		global $post, $authordata;

		if($post = is_numeric($raw_item) ? get_post($raw_item) : $raw_item){
			$authordata = get_userdata($post->post_author);

			if($post->post_type == 'attachment'){
				$post_owner = (get_current_user_id() == $post->post_author) ? 'self' : 'other';

				echo '<tr id="post-'.$post->ID.'" class="'.trim(' author-' . $post_owner . ' status-' . $post->post_status).'">';

				$this->wp_list_table()->single_row_columns($post);

				echo '</tr>';
			}else{
				$this->wp_list_table()->single_row($post);
			}
		}
	}

	public function filter_bulk_actions($bulk_actions=[]){
		$split	= array_search((isset($bulk_actions['trash']) ? 'trash' : 'untrash'), array_keys($bulk_actions), true);

		return array_merge(array_slice($bulk_actions, 0, $split), $this->get_bulk_actions(), array_slice($bulk_actions, $split));
	}

	public function filter_row_actions($row_actions, $post){
		foreach($this->get_row_actions($post->ID) as $key => $row_action){
			$object	= $this->get_object($key);
			$status	= get_post_status($post);

			if($status == 'trash'){
				if($object->post_status && in_array($status, (array)$object->post_status)){
					$row_actions[$key]	= $row_action;
				}
			}else{
				if(is_null($object->post_status) || in_array($status, (array)$object->post_status)){
					$row_actions[$key]	= $row_action;
				}
			}
		}

		foreach(['trash', 'view'] as $key){
			$row_actions[$key]	= array_pull($row_actions, $key);
		}

		return array_merge(array_filter($row_actions), ['id'=>'ID: '.$post->ID]);
	}

	public function on_custom_column($name, $post_id){
		echo $this->get_column_value($post_id, $name, null) ?: '';
	}

	public function filter_html($html){
		if(!wp_doing_ajax()){
			$object	= $this->get_object('add');

			if($object){
				$button	= $object->get_row_action(['class'=>'page-title-action']);
				$html	= preg_replace('/<a href=".*?" class="page-title-action">.*?<\/a>/i', $button, $html);
			}
		}

		return parent::filter_html($html);
	}

	public function on_pre_get_posts($wp_query){
		if($sortable_columns = $this->get_sortable_columns()){
			$orderby	= $wp_query->get('orderby');

			if($orderby && is_string($orderby) && isset($sortable_columns[$orderby])){
				if($object = WPJAM_List_Table_Column::get($orderby)){
					$orderby_type	= $object->sortable_column ?? 'meta_value';

					if(in_array($orderby_type, ['meta_value_num', 'meta_value'])){
						$wp_query->set('meta_key', $orderby);
						$wp_query->set('orderby', $orderby_type);
					}else{
						$wp_query->set('orderby', $orderby);
					}
				}
			}
		}
	}
}

class WPJAM_Terms_List_Table extends WPJAM_Builtin_List_Table{
	public function __construct(){
		$screen		= get_current_screen();
		$taxonomy	= $screen->taxonomy;
		$tax_object	= wpjam_get_taxonomy_object($taxonomy);

		if(!$tax_object){
			return;
		}

		$this->tax_object	= $tax_object;

		$args	= [
			'tax_object'	=> $tax_object,
			'title'			=> $tax_object->label,
			'capability'	=> $tax_object->cap->edit_terms,
			'levels'		=> $tax_object->levels,
			'hierarchical'	=> $tax_object->hierarchical,
			'singular'		=> 'tag',
			'data_type'		=> 'taxonomy',
			'taxonomy'		=> $taxonomy,
			'post_type'		=> $screen->post_type,
			'model'			=> 'WPJAM_Term',
			'builtin_class'	=> 'WP_Terms_List_Table',
		];

		if($tax_object->hierarchical){
			if($tax_object->sortable){
				$args['sortable']	= [
					'items'			=> $this->get_sorteable_items(),
					'action_args'	=> ['row_action'=>false, 'callback'=>['WPJAM_Term', 'move']]
				];

				add_filter('edit_'.$taxonomy.'_per_page',	[$this, 'filter_per_page']);
			}

			if(!is_null($tax_object->levels)){
				wpjam_register_list_table_action('children', ['title'=>'下一级']);

				add_filter('pre_insert_term',	[$this, 'filter_pre_insert_term'], 10, 2);
			}
		}

		if(wp_doing_ajax()){
			wpjam_add_admin_ajax('wpjam-list-table-action',	[$this, 'ajax_response']);
		}
		
		if(!wp_doing_ajax() || (wp_doing_ajax() && in_array($_POST['action'], ['inline-save-tax', 'add-tag']))){
			add_filter('wpjam_html',	[$this, 'filter_html']);
		}

		add_action('parse_term_query',	[$this, 'on_parse_term_query'], 0);

		add_filter('bulk_actions-'.$screen->id,	[$this, 'filter_bulk_actions']);
		add_filter($taxonomy.'_row_actions',	[$this, 'filter_row_actions'], 1, 2);

		add_filter('manage_'.$screen->id.'_columns',			[$this, 'filter_columns']);
		add_filter('manage_'.$taxonomy.'_custom_column',		[$this, 'filter_custom_column'], 10, 3);
		add_filter('manage_'.$screen->id.'_sortable_columns',	[$this, 'filter_sortable_columns']);

		$this->_args	= $this->parse_args($args);
	}

	public function __get($name){
		if(in_array($name, ['taxonomy', 'post_type'])){
			return get_current_screen()->$name;
		}else{
			return parent::__get($name);
		}
	}

	public function list_table(){
		if($this->hierarchical && $this->sortable){
			$sortable_items	= 'data-sortable_items="'.$this->get_sorteable_items().'"';
		}else{
			$sortable_items	= '';
		}

		echo '<form id="posts-filter" '.$sortable_items.' method="get">';

		echo wpjam_field(['key'=>'taxonomy',	'type'=>'hidden',	'value'=>$this->taxonomy]);
		echo wpjam_field(['key'=>'post_type',	'type'=>'hidden',	'value'=>$this->post_type]);

		$this->wp_list_table()->display(); 

		echo '</form>';
	}

	public function get_list_table(){
		return $this->append_extra_tablenav(parent::get_list_table());
	}

	public function filter_html($html){
		return parent::filter_html($this->append_extra_tablenav($html));
	}

	public function get_sorteable_items(){
		$parent	= $this->get_parent();
		$level	= $parent ? (wpjam_get_term_level($parent)+1) : 0;

		return 'tr.level-'.$level;
	}

	public function get_parent(){
		$parent	= wpjam_get_data_parameter('parent');

		if(is_null($parent)){
			if($this->levels == 1){
				return 0;
			}

			return null;
		}

		return (int)$parent;
	}

	public function get_edit_tags_link($args=[]){
		$args	= array_filter($args, 'is_exists');
		$args	= wp_parse_args($args, ['taxonomy'=>$this->taxonomy, 'post_type'=>$this->post_type]);

		return admin_url(add_query_arg($args, 'edit-tags.php'));
	}

	public function append_extra_tablenav($html){
		$extra	= '';

		if($this->hierarchical && $this->levels > 1){
			$parent	= $this->get_parent();

			if(is_null($parent)){
				$to		= 0;
				$text	= '只显示第一级';
			}elseif($parent > 0){
				$to		= 0;
				$text	= '返回第一级';
			}else{
				$to		= null;
				$text	= '显示所有';
			}

			$extra	= '<div class="alignleft actions"><a href="'.$this->get_edit_tags_link(['parent'=>$to]).'" class="button button-primary list-table-href">'.$text.'</a></div>';
		}

		if($extra = apply_filters('wpjam_terms_extra_tablenav', $extra, $this->taxonomy)){
			$html	= preg_replace('#(<div class="tablenav top">\s+?<div class="alignleft actions bulkactions">.*?</div>)#is', '$1 '.$extra, $html);
		}

		return $html;
	}

	public function single_row($raw_item){
		if($term = is_numeric($raw_item) ? get_term($raw_item) : $raw_item){
			$level	= wpjam_get_term_level($term);

			$this->wp_list_table()->single_row($term, $level);
		}
	}

	public function filter_row_actions($row_actions, $term){
		if(!$this->tax_object->supports('slug')){
			unset($row_actions['inline hide-if-no-js']);
		}

		$row_actions	= array_merge($row_actions, $this->get_row_actions($term->term_id));

		if(isset($row_actions['children'])){
			$parent	= $this->get_parent();

			if((empty($parent) || $parent != $term->term_id) && get_term_children($term->term_id, $term->taxonomy)){
				$row_actions['children']	= '<a href="'.$this->get_edit_tags_link(['parent'=>$term->term_id]).'">下一级</a>';
			}else{
				unset($row_actions['children']);
			}
		}

		foreach(['delete', 'view'] as $key){
			if($row_action = array_pull($row_actions, $key)){
				$row_actions[$key]	= $row_action;
			}
		}

		return array_merge($row_actions, ['term_id'=>'ID：'.$term->term_id]);
	}

	public function filter_columns($columns){
		$columns	= parent::filter_columns($columns);

		foreach(['slug', 'description'] as $key){
			if(!$this->tax_object->supports($key)){
				unset($columns[$key]);
			}
		}

		return $columns;
	}

	public function filter_per_page($per_page){
		$parent	= $this->get_parent();

		return is_null($parent) ? $per_page : 9999;
	}

	public function filter_pre_insert_term($term, $taxonomy){
		if($this->levels && $taxonomy == $this->taxonomy){
			if(!empty($_POST['parent']) && $_POST['parent'] != -1){
				if(wpjam_get_term_level($_POST['parent']) >= $this->levels - 1){
					return new WP_Error('error', '不能超过'.$this->levels.'级');
				}
			}
		}

		return $term;
	}

	public function sort_column_callback($term_id){
		$parent	= $this->get_parent();
		
		if(is_null($parent) || wpjam_get_data_parameter('orderby') || wpjam_get_data_parameter('s')){
			return wpjam_admin_tooltip('<span class="dashicons dashicons-editor-help"></span>', '如要进行排序，请先点击「只显示第一级」按钮。');
		}elseif(get_term($term_id)->parent == $parent){
			$sortable_row_actions	= '';

			foreach(['move', 'up', 'down'] as $action_key){
				$sortable_row_actions	.= '<span class="'.$action_key.'">'.wpjam_get_list_table_row_action($action_key, ['id'=>$term_id]).'</span>';
			}

			return '<div class="row-actions">'.$sortable_row_actions.'</div>';
		}else{
			return '';
		}
	}

	public function on_parse_term_query($term_query){
		if(!in_array('WP_Terms_List_Table', array_column(debug_backtrace(), 'class'))){
			return;
		}

		$term_query->query_vars['list_table_query']	= true;

		if($sortable_columns = $this->get_sortable_columns()){
			$orderby	= $term_query->query_vars['orderby'];

			if($orderby && isset($sortable_columns[$orderby])){
				if($object = WPJAM_List_Table_Column::get($orderby)){
					$orderby_type	= $object->sortable_column ?? 'meta_value';

					if(in_array($orderby_type, ['meta_value_num', 'meta_value'])){
						$term_query->query_vars['meta_key']	= $orderby;
						$term_query->query_vars['orderby']	= $orderby_type;
					}else{
						$term_query->query_vars['orderby']	= $orderby;
					}
				}
			}
		}

		if($this->hierarchical){
			$parent	= $this->get_parent();
			
			if($parent){
				$hierarchy	= _get_term_hierarchy($this->taxonomy);
				$term_ids	= $hierarchy[$parent] ?? [];
				$term_ids[]	= $parent;

				if($ancestors = get_ancestors($parent, $this->taxonomy)){
					$term_ids	= array_merge($term_ids, $ancestors);
				}

				$term_query->query_vars['include']	= $term_ids;
				// $term_query->query_vars['pad_counts']	= true;
			}elseif($parent === 0){
				$term_query->query_vars['parent']	= $parent;
			}
		}
	}
}

class WPJAM_Users_List_Table extends WPJAM_Builtin_List_Table{
	public function __construct(){
		if(wp_doing_ajax()){
			wpjam_add_admin_ajax('wpjam-list-table-action',	[$this, 'ajax_response']);
		}else{
			add_filter('wpjam_html',	[$this, 'filter_html']);
		}

		add_filter('user_row_actions',	[$this, 'filter_row_actions'], 1, 2);

		add_filter('manage_users_columns',			[$this, 'filter_columns']);
		add_filter('manage_users_custom_column',	[$this, 'filter_custom_column'], 10, 3);
		add_filter('manage_users_sortable_columns',	[$this, 'filter_sortable_columns']);

		$this->_args	= $this->parse_args([
			'title'			=> '用户',
			'singular'		=> 'user',
			'capability'	=> 'edit_user',
			'data_type'		=> 'user',
			'model'			=> 'WPJAM_User',
			'builtin_class'	=> 'WP_Users_List_Table'
		]);
	}

	public function single_row($raw_item){
		if($user = is_numeric($raw_item) ? get_userdata($raw_item) : $raw_item){
			echo $this->wp_list_table()->single_row($raw_item);
		}
	}

	public function filter_row_actions($row_actions, $user){
		foreach($this->get_row_actions($user->ID) as $key => $row_action){
			$action	= $this->get_object($key);

			if(is_null($action->roles) || array_intersect($user->roles, (array)$action->roles)){
				$row_actions[$key]	= $row_action;
			}
		}

		foreach(['delete', 'remove', 'view'] as $key){
			if($row_action = array_pull($row_actions, $key)){
				$row_actions[$key]	= $row_action;
			}
		}

		return array_merge($row_actions, ['id'=>'ID: '.$user->ID]);
	}
}