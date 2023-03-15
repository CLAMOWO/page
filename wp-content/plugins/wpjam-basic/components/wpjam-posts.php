<?php
/*
Name: 文章设置
URI: https://mp.weixin.qq.com/s/XS3xk-wODdjX3ZKndzzfEg
Description: 文章设置把文章编辑的一些常用操作，提到文章列表页面，方便设置和操作
Version: 2.0
*/
class WPJAM_Basic_Posts extends WPJAM_Option_Model{
	public static function get_sections(){
		$post_list_fields	= [
			'post_list_ajax'			=> ['value'=>1,	'description'=>'支持全面的 <strong>AJAX操作</strong>'],
			'post_list_set_thumbnail'	=> ['value'=>1,	'description'=>'显示和设置<strong>文章缩略图</strong>'],
			'post_list_sort_selector'	=> ['value'=>1,	'description'=>'显示<strong>排序下拉选择框</strong>'],
			'post_list_author_filter'	=> ['value'=>1,	'description'=>'支持<strong>通过作者进行过滤</strong>'],
			'upload_external_images'	=> ['value'=>0,	'description'=>'支持<strong>上传外部图片</strong>'],
		];

		$excerpt_show_if	= ['key'=>'excerpt_optimization', 'value'=>1];
		$excerpt_options	= [0=>'WordPress 默认方式截取', 1=>'按照中文最优方式截取', 2=>'直接不显示摘要'];
		$excerpt_fields		= [
			'excerpt_optimization'	=> ['title'=>'未设文章摘要：',	'options'=>$excerpt_options],
			'excerpt_length'		=> ['title'=>'文章摘要长度：',	'type'=>'number',	'show_if'=>$excerpt_show_if,	'value'=>200],
			'excerpt_cn_view2'		=> ['title'=>'中文截取算法：',	'type'=>'view',		'show_if'=>$excerpt_show_if,	'short'=>'QB6zUXA_QI1lseAfNV29Lg',	'value'=>'<strong>中文算2个字节，英文算1个字节</strong>']
		];

		return [
			'posts'		=>['title'=>'文章设置',	'fields'=>WPJAM_Basic::parse_fields([
				'post_list_fieldset'	=> ['title'=>'后台列表',	'type'=>'fieldset',	'fields'=>$post_list_fields],
				'excerpt_fieldset'		=> ['title'=>'文章摘要',	'type'=>'fieldset',	'fields'=>$excerpt_fields],
				'remove_post_tag'		=> ['title'=>'移除标签',	'value'=>0,	'description'=>'移除默认文章类型的标签功能支持'],
				'404_optimization'		=> ['title'=>'404 跳转',	'value'=>0,	'description'=>'增强404页面跳转到文章页面能力']
			])]
		];
	}

	public static function get_menu_page(){
		return [
			'parent'		=> 'wpjam-basic',
			'menu_slug'		=> 'wpjam-posts',
			'menu_title'	=> '文章设置',
			'summary'		=> __FILE__,
			'position'		=> 4,
			'function'		=> 'tab',
			'tabs'			=> ['posts'=>[
				'title'			=> '文章设置',
				'function'		=> 'option',
				'option_name'	=> 'wpjam-basic',
				'site_default'	=> true,
				'order'			=> 20,
				'summary'		=> '文章设置优化，增强后台文章列表页和详情页功能。',
			]]
		];
	}

	public static function find_by_name($post_name, $post_type='', $post_status='publish'){
		$args	= $args_with_type = $args_for_meta = [];

		if($post_status && $post_status != 'any'){
			$args['post_status']	= $post_status;
		}


		if($post_type && $post_type != 'any'){
			$args_with_type	= array_merge($args, ['post_type'=>$post_type]);
		}

		$post_types		= get_post_types(['public'=>true, 'exclude_from_search'=>false]);
		$post_types		= array_diff($post_types, ['attachment']);
		$args_for_meta	= array_merge($args, ['post_type'=>array_values($post_types)]);

		$meta	= wpjam_get_by_meta('post', '_wp_old_slug', $post_name);
		$posts	= $meta ? WPJAM_Post::get_by_ids(array_column($meta, 'post_id')) : [];

		if($args_with_type){
			foreach($posts as $post){
				if(wpjam_match($post, $args_with_type)){
					return $post;
				}
			}
		}

		foreach($posts as $post){
			if(wpjam_match($post, $args_for_meta)){
				return $post;
			}
		}

		$wpdb		= $GLOBALS['wpdb'];
		$post_types	= get_post_types(['public'=>true, 'hierarchical'=>false, 'exclude_from_search'=>false]);
		$post_types	= array_diff($post_types, ['attachment']);

		$where		= "post_type in ('" . implode( "', '", array_map('esc_sql', $post_types)) . "')";
		$where		.= ' AND '.$wpdb->prepare("post_name LIKE %s", $wpdb->esc_like($post_name).'%');

		$post_ids	= $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE $where");
		$posts		= $post_ids ? WPJAM_Post::get_by_ids($post_ids) : [];

		if($args_with_type){
			foreach($posts as $post){
				if(wpjam_match($post, $args_with_type)){
					return $post;
				}
			}
		}

		foreach($posts as $post){
			if($args){
				if(wpjam_match($post, $args)){
					return $post;
				}
			}else{
				return $post;
			}
		}
	}

	public static function set_list_table_option(){
		$screen	= get_current_screen();

		if($screen->base == 'edit' && defined('WC_PLUGIN_FILE') && str_starts_with($screen->post_type, 'shop_')){
			$ajax	= false;
		}else{
			$scripts	= '';

			if(self::get_setting('post_list_ajax', 1)){
				$ajax		= true;
				$scripts	= "
				jQuery(function($){
					$(window).load(function(){
						if($('#the-list').length){
							$.wpjam_delegate_events('#the-list', '.editinline');
						}

						if($('#doaction').length){
							$.wpjam_delegate_events('#doaction');
						}
					});
				})
				";
			}else{
				$ajax	= false;
			}

			$scripts	.= "
			jQuery(function($){
				let observer = new MutationObserver(function(mutations){
					if($('#the-list .inline-editor').length > 0){
						let tr_id	= $('#the-list .inline-editor').attr('id');

						if(tr_id == 'bulk-edit'){
							$('#the-list').trigger('bulk_edit');
						}else{
							let id	= tr_id.split('-')[1];

							if(id > 0){
								$('#the-list').trigger('quick_edit', id);
							}
						}
					}
				});

				observer.observe(document.querySelector('body'), {childList: true, subtree: true});
			});
			";
			wp_add_inline_script('jquery', $scripts);
		}

		$screen->add_option('wpjam_list_table', ['ajax'=>$ajax, 'form_id'=>'posts-filter']);
	}

	public static function upload_external_images($post_id){
		$content	= get_post($post_id)->post_content;
		$bulk		= (int)wpjam_get_parameter('bulk', ['method'=>'POST']);

		if(preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches)){
			$img_urls	= array_unique($matches[1]);
			$replace	= wpjam_fetch_external_images($img_urls, $post_id);

			if($replace){
				$content	= str_replace($img_urls, $replace, $content);

				return wp_update_post(['post_content'=>$content, 'ID'=>$post_id], true);
			}else{
				return $bulk == 2 ? true : new WP_Error('error', '文章中无外部图片');
			}
		}

		return $bulk == 2 ? true : new WP_Error('error', '文章中无图片');
	}

	public static function builtin_page_load($screen){
		if($screen->base  == 'post'){
			self::post_page_load($screen);
		}elseif(in_array($screen->base, ['edit-tags', 'term'])){
			self::term_page_load($screen);
		}else{
			self::edit_page_load($screen);
		}
	}

	public static function edit_page_load($screen){
		$ptype			= $screen->post_type;
		$builtin_page	= WPJAM_Post_Builtin_Page::create($screen);

		if(!$builtin_page){
			return;
		}

		self::set_list_table_option();

		add_action('restrict_manage_posts',	[$builtin_page, 'taxonomy_dropdown'], 1);
		add_action('restrict_manage_posts',	[$builtin_page, 'author_dropdown'], 1);
		add_action('restrict_manage_posts',	[$builtin_page, 'orderby_dropdown'], 999);
		add_filter('request',				[$builtin_page, 'filter_request']);

		$style	= ['.fixed .column-date{width:8%;}'];

		if($ptype != 'attachment'){
			add_filter('post_column_taxonomy_links',	[$builtin_page, 'filter_taxonomy_links'], 10, 3);
			add_filter('wpjam_single_row', 				[$builtin_page, 'filter_post_single_row'], 10, 2);

			if($builtin_page->object->in_taxonomy('category')){
				add_filter('disable_categories_dropdown', '__return_true');
			}

			$fields	= [];

			$fields['post_title']	= ['title'=>'标题',	'type'=>'text',	'required'];

			if($builtin_page->object->supports('excerpt')){
				$fields['post_excerpt']	= ['title'=>'摘要',	'type'=>'textarea',	'class'=>'',	'rows'=>3];
			}

			if($builtin_page->object->supports('thumbnail') && self::get_setting('post_list_set_thumbnail', 1)){
				$fields['_thumbnail_id']	= ['title'=>'头图', 'type'=>'img', 'size'=>'600x0'];
			}

			$fields	= array_merge($fields, $builtin_page->object->get_fields());

			if(!WPJAM_List_Table_Action::get('set')){
				wpjam_register_list_table_action('set', [
					'title'			=> '设置',
					'page_title'	=> '设置'.$builtin_page->object->label,
					'fields'		=> $fields,
					'row_action'	=> false
				]);
			}

			if(self::get_setting('upload_external_images')){
				wpjam_register_list_table_action('upload_external_images', [
					'title'			=> '上传外部图片',
					'page_title'	=> '上传外部图片',
					'direct'		=> true,
					'confirm'		=> true,
					'bulk'			=> 2,
					'order'			=> 9,
					'callback'		=> [self::class, 'upload_external_images']
				]);
			}

			$style[]	= '#bulk-titles, ul.cat-checklist{height:auto; max-height: 14em;}';

			if($ptype == 'page'){
				wpjam_register_posts_column('template', '模板', 'get_page_template_slug');

				$style[]	= '.fixed .column-template{width:15%;}';
			}elseif($ptype == 'product'){
				if(self::get_setting('post_list_set_thumbnail', 1) && defined('WC_PLUGIN_FILE')){
					wpjam_unregister_posts_column('thumb');
				}
			}
		}

		$width_columns	= [];

		if($builtin_page->object->supports('author')){
			$width_columns[]	= '.fixed .column-author';
		}

		foreach($builtin_page->object->get_taxonomies() as $tax_object){
			if($tax_object->show_admin_column){
				$width_columns[]	= '.fixed .column-'.$tax_object->column_name;
			}
		}

		$count = count($width_columns);

		if($count){
			$widths		= ['14%',	'12%',	'10%',	'8%',	'7%'];
			$style[]	= implode(',', $width_columns).'{width:'.($widths[$count-1] ?? '6%').'}';
		}

		wp_add_inline_style('list-tables', "\n".implode("\n", $style)."\n");
	}

	public static function post_page_load($screen){
		$builtin_page	= WPJAM_Post_Builtin_Page::create($screen);

		if(!$builtin_page){
			return;
		}

		add_filter('post_updated_messages',		[$builtin_page, 'filter_post_updated_messages']);
		add_filter('admin_post_thumbnail_html',	[$builtin_page, 'filter_admin_thumbnail_html'], 10, 2);
		add_filter('redirect_post_location',	[$builtin_page, 'filter_redirect_location']);

		add_filter('post_edit_category_parent_dropdown_args',	[$builtin_page, 'filter_edit_category_parent_dropdown_args']);

		$style	= [];

		foreach($builtin_page->object->get_taxonomies() as $tax_object){
			if($tax_object->levels == 1){
				$style[]	= '#new'.$tax_object->name.'_parent{display:none;}';
			}
		}

		if(self::get_setting('disable_trackbacks')){
			$style[]	= 'label[for="ping_status"]{display:none !important;}';
		}

		if($style){
			wp_add_inline_style('list-tables', "\n".implode("\n", $style));
		}
		
		if(self::get_setting('disable_autoembed')){
			if($screen->is_block_editor){
				$scripts	= "
				jQuery(function($){
					wp.domReady(function (){
						wp.blocks.unregisterBlockType('core/embed');
					});
				});
				";

				wp_add_inline_script('jquery', $scripts);
			}
		}
	}

	public static function term_page_load($screen){
		$builtin_page	= WPJAM_Term_Builtin_Page::create($screen);

		if(!$builtin_page){
			return;
		}

		add_filter('term_updated_messages',			[$builtin_page, 'filter_term_updated_messages']);
		add_filter('taxonomy_parent_dropdown_args',	[$builtin_page, 'filter_parent_dropdown_args'], 10, 3);

		if($screen->base == 'edit-tags'){
			add_filter('wpjam_single_row',	[$builtin_page, 'filter_term_single_row'], 10, 2);

			self::set_list_table_option();

			$fields	= $builtin_page->object->get_fields();

			if($fields){
				wpjam_register_list_table_action('set_thumbnail', [
					'title'			=> '设置',
					'page_title'	=> '设置缩略图',
					'fields'		=> $fields,
					'row_action'	=> false
				]);
			}

			$style		= [
				'.fixed th.column-slug{ width:16%; }',
				'.fixed th.column-description{width:22%;}',
				'.form-field.term-parent-wrap p{display: none;}',
				'.form-field span.description{color:#666;}'
			];
		}else{
			$style		= [];
		}

		foreach(['slug', 'description', 'parent'] as $key){
			if(!$builtin_page->object->supports($key)){
				$style[]	= '.form-field.term-'.$key.'-wrap{display: none;}'."\n";
			}
		}

		wp_add_inline_style('list-tables', "\n".implode("\n", $style));
	}

	public static function filter_get_the_excerpt($text='', $post=null){
		$optimization	= self::get_setting('excerpt_optimization');

		if(empty($text) && $optimization){
			remove_filter('get_the_excerpt', 'wp_trim_excerpt');

			if($optimization != 2){
				remove_filter('the_excerpt', 'wp_filter_content_tags');
				remove_filter('the_excerpt', 'shortcode_unautop');

				$length	= self::get_setting('excerpt_length') ?: 200;
				$text	= wpjam_get_post_excerpt($post, $length);
			}
		}

		return $text;
	}

	public static function filter_old_slug_redirect_post_id($post_id){
		// 解决文章类型改变之后跳转错误的问题
		// WP 原始解决函数 'wp_old_slug_redirect' 和 'redirect_canonical'
		if(empty($post_id) && self::get_setting('404_optimization')){
			$post	= self::find_by_name(get_query_var('name'), get_query_var('post_type'));

			return $post ? $post->ID : $post_id;
		}

		return $post_id;
	}

	public static function init(){
		if(self::get_setting('remove_post_tag')){
			unregister_taxonomy_for_object_type('post_tag', 'post');
		}

		if(is_admin()){
			wpjam_register_builtin_page_load([
				'base'		=> ['edit', 'upload', 'post', 'edit-tags', 'term'],
				'callback'	=> [self::class, 'builtin_page_load'],
			]);
		}
	}

	public static function add_hooks(){
		add_filter('get_the_excerpt',			[self::class, 'filter_get_the_excerpt'], 9, 2);
		add_filter('old_slug_redirect_post_id',	[self::class, 'filter_old_slug_redirect_post_id']);
	}
}

class WPJAM_Post_Builtin_Page{
	private $object;

	private function __construct($object){
		$this->object	= $object;
	}

	public function __get($key){
		if($key == 'object'){
			return $this->object;
		}elseif($key == 'post_type'){
			return $this->object->name;
		}else{
			return null;
		}
	}

	public function is_wc_shop(){
		return defined('WC_PLUGIN_FILE') && str_starts_with($this->post_type, 'shop_');
	}

	public function taxonomy_dropdown($ptype){
		foreach($this->object->get_taxonomies() as $taxonomy => $tax_object){
			$filterable	= $tax_object->filterable ?? ($taxonomy == 'category' ? true : false);

			if($filterable && $tax_object->show_admin_column){
				$tax_object->dropdown();
			}
		}
	}

	public function author_dropdown($ptype){
		if(wpjam_basic_get_setting('post_list_author_filter', 1) && $this->object->supports('author')){
			wp_dropdown_users([
				'name'						=> 'author',
				'capability'				=> 'edit_posts',
				'orderby'					=> 'post_count',
				'order'						=> 'DESC',
				'hide_if_only_one_author'	=> true,
				'show_option_all'			=> $ptype == 'attachment' ? '所有上传者' : '所有作者',
				'selected'					=> (int)wpjam_get_data_parameter('author')
			]);
		}
	}

	public function orderby_dropdown($ptype){
		if(wpjam_basic_get_setting('post_list_sort_selector', 1) && !$this->is_wc_shop()){
			$options		= [''=>'排序','ID'=>'ID'];
			$wp_list_table	= _get_list_table('WP_Posts_List_Table', ['screen'=>get_current_screen()->id]);

			list($columns, $hidden, $sortable_columns)	= $wp_list_table->get_column_info();

			foreach($sortable_columns as $sortable_column => $data){
				if(isset($columns[$sortable_column])){
					$options[$data[0]]	= $columns[$sortable_column];
				}
			}

			if($ptype != 'attachment'){
				$options['modified']	= '修改时间';
			}elseif($ptype == 'product'){
				if(wpjam_basic_get_setting('post_list_set_thumbnail', 1) && defined('WC_PLUGIN_FILE')){
					wpjam_unregister_posts_column('thumb');
				}
			}

			$orderby	= wpjam_get_data_parameter('orderby', ['sanitize_callback'=>'sanitize_key']);
			$order		= wpjam_get_data_parameter('order', ['sanitize_callback'=>'sanitize_key', 'default'=>'DESC']);

			echo wpjam_field(['key'=>'orderby',	'type'=>'select',	'value'=>$orderby,	'options'=>$options]);
			echo wpjam_field(['key'=>'order',	'type'=>'select',	'value'	=>$order,	'options'=>['desc'=>'降序','asc'=>'升序']]);
		}
	}

	public function filter_request($query_vars){
		$tax_query	= [];

		foreach($this->object->get_taxonomies() as $taxonomy => $tax_object){
			if(!$tax_object->show_ui){
				continue;
			}

			$tax	= $taxonomy == 'post_tag' ? 'tag' : $taxonomy;

			if($tax != 'category'){
				$tax_id	= wpjam_get_data_parameter($tax.'_id');

				if($tax_id){
					$query_vars[$tax.'_id']	= $tax_id;
				}
			}

			$tax_arg		= ['taxonomy'=>$taxonomy,	'field'=>'term_id'];

			$tax__and		= wpjam_get_data_parameter($tax.'__and',	['sanitize_callback'=>'wp_parse_id_list']);
			$tax__in		= wpjam_get_data_parameter($tax.'__in',		['sanitize_callback'=>'wp_parse_id_list']);
			$tax__not_in	= wpjam_get_data_parameter($tax.'__not_in',	['sanitize_callback'=>'wp_parse_id_list']);

			if($tax__and){
				if(count($tax__and) == 1){
					$tax__in	= is_null($tax__in) ? [] : $tax__in;
					$tax__in[]	= reset($tax__and);
				}else{
					$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__and,	'operator'=>'AND']);	// 'include_children'	=> false,
				}
			}

			if($tax__in){
				$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__in]);
			}

			if($tax__not_in){
				$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__not_in,	'operator'=>'NOT IN']);
			}
		}

		if($tax_query){
			$tax_query['relation']		= wpjam_get_data_parameter('tax_query_relation',	['default'=>'and']);
			$query_vars['tax_query']	= $tax_query;
		}

		return $query_vars;
	}

	public function filter_taxonomy_links($term_links, $taxonomy, $terms){
		if($taxonomy == 'post_format'){
			foreach($term_links as &$term_link){
				$term_link	= str_replace('post-format-', '', $term_link);
			}
		}else{
			$tax_object	= wpjam_get_taxonomy_object($taxonomy);

			if($tax_object){
				foreach($terms as $i => $term){
					$term_links[$i]	= $tax_object->link_replace($term_links[$i], $term);
				}
			}
		}

		return $term_links;
	}

	public function filter_post_single_row($single_row, $post_id){
		if(wpjam_basic_get_setting('post_list_set_thumbnail', 1) && ($this->object->supports('thumbnail') || $this->object->supports('images'))){
			$thumbnail	= get_the_post_thumbnail($post_id, [50,50]) ?: '<span class="no-thumbnail">暂无图片</span>';
			$thumbnail	= wpjam_get_list_table_row_action('set', ['id'=>$post_id, 'class'=>'wpjam-thumbnail-wrap', 'title'=>$thumbnail, 'fallback'=>true]);
			$single_row	= str_replace('<a class="row-title" ', $thumbnail.'<a class="row-title" ', $single_row);
		}

		if(wpjam_basic_get_setting('post_list_ajax', 1)){
			$set_action	= wpjam_get_list_table_row_action('set', ['id'=>$post_id, 'class'=>'row-action', 'title'=>'<span class="dashicons dashicons-edit"></span>']);
			$single_row = preg_replace('/(<strong>.*?<a class=\"row-title\".*?<\/a>.*?)(<\/strong>)/is', '$1 '.$set_action.'$2', $single_row);

			$quick_edit	= '<a title="快速编辑" href="javascript:;" class="editinline row-action"><span class="dashicons dashicons-edit"></span></a>';

			if($this->object->supports('author')){
				$single_row = preg_replace('/(<td class=\'author column-author\' .*?>.*?)(<\/td>)/is', '$1 '.$quick_edit.'$2', $single_row);
			}

			foreach($this->object->get_taxonomies() as $tax_object){
				if($tax_object->show_in_quick_edit){
					$single_row	= preg_replace('/(<td class=\''.$tax_object->column_name.' column-'.$tax_object->column_name.'\' .*?>.*?)(<\/td>)/is', '$1 '.$quick_edit.'$3', $single_row);
				}
			}
		}

		return $single_row;
	}

	public function filter_post_updated_messages($messages){
		$key	= $this->object->hierarchical ? 'page' : 'post';

		if(isset($messages[$key])){
			$search		= $key == 'post' ? '文章':'页面';
			$replace	= $this->object->labels->name;

			foreach($messages[$key] as &$message){
				$message	= str_replace($search, $replace, $message);
			}
		}

		return $messages;
	}

	public function filter_admin_thumbnail_html($content, $post_id){
		if($post_id){
			$size		= $this->object->thumbnail_size;
			$content	.= $size ? wpautop('尺寸：'.$size) : '';
		}

		return $content;
	}

	public function filter_redirect_location($location){
		if(parse_url($location, PHP_URL_FRAGMENT)){
			return $location;
		}

		if($fragment = parse_url(wp_get_referer(), PHP_URL_FRAGMENT)){
			return $location.'#'.$fragment;
		}

		return $location;
	}

	public function filter_edit_category_parent_dropdown_args($args){
		$object	= wpjam_get_taxonomy_object($args['taxonomy']);
		$levels	= $object ? (int)$object->levels : 0;

		if($levels == 1){
			$args['parent']	= -1;
		}elseif($levels > 1){
			$args['depth']	= $levels - 1;
		}

		return $args;
	}

	public function filter_content_save_pre($content){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $content;
		}

		if(!preg_match_all('/<img.*?src=\\\\[\'"](.*?)\\\\[\'"].*?>/i', $content, $matches)){
			return $content;
		}

		$img_urls	= array_unique($matches[1]);
		
		if($replace	= wpjam_fetch_external_images($img_urls)){
			if(is_multisite()){
				setcookie('wp-saving-post', $_POST['post_ID'].'-saved', time()+DAY_IN_SECONDS, ADMIN_COOKIE_PATH, false, is_ssl());
			}

			$content	= str_replace($img_urls, $replace, $content);
		}

		return $content;
	}

	public static function create($screen){
		$object	= wpjam_get_post_type_object($screen->post_type);

		return $object ? new self($object) : null;
	}
}

class WPJAM_Term_Builtin_Page{
	private $object;

	private function __construct($object){
		$this->object	= $object;
	}

	public function __get($key){
		if($key == 'object'){
			return $this->object;
		}elseif($key == 'taxonomy'){
			return $this->object->name;
		}else{
			return null;
		}
	}

	public function filter_term_updated_messages($messages){
		if(!in_array($this->taxonomy, ['post_tag', 'category'])){
			$label	= $this->object->labels->name;
			
			foreach($messages['_item'] as $key => $message){
				$messages[$this->taxonomy][$key]	= str_replace(['项目', 'Item'], [$label, ucfirst($label)], $message);
			}
		}

		return $messages;
	}

	public function filter_parent_dropdown_args($args, $taxonomy, $action_type){
		$object	= wpjam_get_taxonomy_object($args['taxonomy']);
		$levels	= $object ? (int)$object->levels : 0;

		if($levels > 1){
			$args['depth']	= $levels - 1;

			if($action_type == 'edit'){
				$term_id	= $args['exclude_tree'];
				$term_level	= wpjam_get_term_level($term_id);

				if($children = get_term_children($term_id, $taxonomy)){
					$child_level	= 0;

					foreach($children as $child){
						$new_child_level	= wpjam_get_term_level($child);

						if($child_level	< $new_child_level){
							$child_level	= $new_child_level;
						}
					}
				}else{
					$child_level	= $term_level;
				}

				$redueced	= $child_level - $term_level;

				if($redueced < $args['depth']){
					$args['depth']	-= $redueced;
				}else{
					$args['parent']	= -1;
				}
			}
		}

		return $args;
	}

	public function filter_term_single_row($single_row, $term_id){
		if(WPJAM_List_Table_Action::get('set_thumbnail')){
			$thumb_url	= wpjam_get_term_thumbnail_url($term_id, [100, 100]);
			$thumbnail	= $thumb_url ? '<img class="wp-term-image" src="'.$thumb_url.'"'.image_hwstring(50,50).' />' : '<span class="no-thumbnail">暂无图片</span>';
			$thumbnail	= wpjam_get_list_table_row_action('set_thumbnail', ['id'=>$term_id, 'class'=>'wpjam-thumbnail-wrap', 'title'=>$thumbnail, 'fallback'=>true]);
			$single_row	= str_replace('<a class="row-title" ', $thumbnail.'<a class="row-title" ', $single_row);
		}
		
		return $this->object->link_replace($single_row, $term_id);
	}

	public static function create($screen){
		$object	= wpjam_get_taxonomy_object($screen->taxonomy);

		return $object ? new self($object) : null;
	}
}

wpjam_register_option('wpjam-basic', [
	'plugin_page'	=> 'wpjam-posts',
	'current_tab'	=> 'posts',
	'site_default'	=> true,
	'model'			=> 'WPJAM_Basic_Posts',
]);
