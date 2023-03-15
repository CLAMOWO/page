<?php
/*
Name: 缩略图设置
URI: https://mp.weixin.qq.com/s/93TRBqSdiTzissW-c0bLRQ
Description: 缩略图设置可以无需预定义就可以进行动态裁图，并且还支持文章和分类缩略图
Version: 2.0
*/
class WPJAM_Thumbnail extends WPJAM_Option_Model{
	public static function get_fields(){
		$tax_options	= [];

		foreach(get_object_taxonomies('post', 'objects') as $tax => $object){
			if($object->show_ui && $object->public){
				$tax_options[$tax]	= [
					'title'		=> $object->label,
					'show_if'	=> ['key'=>'term_thumbnail_taxonomies',	'value'=>$tax,	'prefix'=>'',	'postfix'=>'']
				];
			}
		}

		$order_options	= [
			''			=> '请选择来源',
			'first'		=> '第一张图',
			'post_meta'	=> '自定义字段',
			'term'		=>[
				'title'		=> '分类缩略图',
				'show_if'	=> ['key'=>'term_thumbnail_type', 'compare'=>'IN', 'value'=>['img','image'], 'prefix'=>'',	'postfix'=>'']
			]
		];

		$term_show_if	= ['key'=>'term_thumbnail_type', 'compare'=>'!=', 'value'=>''];
		$term_fields	= [
			'type'			=> ['type'=>'select',	'options'=>[''=>'关闭分类缩略图', 'img'=>'本地媒体模式', 'image'=>'输入图片链接模式']],
			'taxonomies'	=> ['type'=>'checkbox',	'show_if'=>$term_show_if,	'options'=>wp_list_pluck($tax_options, 'title'),	'title'=>'支持的分类模式：'],
			'width'			=> ['type'=>'number',	'show_if'=>$term_show_if,	'group'=>'term',	'class'=>'small-text',	'title'=>'缩略图尺寸：'],
			'plus'			=> ['type'=>'view',		'show_if'=>$term_show_if,	'group'=>'term',	'title'=>wpjam_field_get_icon('multiply')],
			'height'		=> ['type'=>'number',	'show_if'=>$term_show_if,	'group'=>'term',	'class'=>'small-text']
		];

		$post_fields	= [
			'view'		=> ['type'=>'view',			'value'=>'首先使用文章特色图片，如未设置，将按照下面的顺序获取：'],
			'orders'	=> ['type'=>'mu-fields',	'group'=>true,	'max_items'=>5,	'fields'=>[
				'type'		=> ['type'=>'select',	'options'=>$order_options],
				'taxonomy'	=> ['type'=>'select',	'show_if'=>['key'=>'type', 'value'=>'term'],		'options'=>[''=>'请选择分类模式']+$tax_options],
				'post_meta'	=> ['type'=>'text',		'show_if'=>['key'=>'type', 'value'=>'post_meta'],	'class'=>'all-options',	'placeholder'=>'请输入自定义字段的 meta_key'],
			]]
		];

		return [
			'auto'		=> ['title'=>'缩略图设置',	'type'=>'radio',	'sep'=>'<br />',	'options'=>[
				0	=>'修改主题代码，手动使用 <a href="https://blog.wpjam.com/m/wpjam-basic-thumbnail-functions/" target="_blank">WPJAM 的缩略图函数</a>。',
				1	=>'无需修改主题，自动应用 WPJAM 的缩略图设置。'
			]],
			'pdf'		=> ['title'=>'PDF预览图',	'type'=>'checkbox',	'name'=>'disable_pdf_preview',	'description'=>'屏蔽生成 PDF 预览图功能。'],
			'default'	=> ['title'=>'默认缩略图',	'type'=>'mu-img',	'item_type'=>'url'],
			'term_set'	=> ['title'=>'分类缩略图',	'type'=>'fieldset',	'fields'=>$term_fields,	'prefix'=>'term_thumbnail'],
			'post_set'	=> ['title'=>'文章缩略图',	'type'=>'fieldset',	'fields'=>$post_fields,	'prefix'=>'post_thumbnail']
		];
	}

	public static function get_menu_page(){
		return [
			'parent'		=> 'wpjam-basic',
			'menu_title'	=> '缩略图设置',
			'function'		=> 'option',
			'position'		=> 3,
			'summary'		=> __FILE__,
		];
	}

	public static function get_default(){
		$default	= self::get_setting('default', []);

		if($default && is_array($default)){
			$default	= $default[array_rand($default)];
		}else{
			$default	= '';
		}

		return apply_filters('wpjam_default_thumbnail_url', $default);
	}

	public static function filter_post_thumbnail_url($thumbnail_url, $post){
		$object	= wpjam_get_post_object($post);

		if(!$object->in_taxonomy('category')){
			return $thumbnail_url;
		}

		foreach(self::get_setting('post_thumbnail_orders', []) as $order){
			if($order['type'] == 'first'){
				$value	= $object->get_first_image_url();
			}elseif($order['type'] == 'post_meta'){
				$value	= $order['post_meta'] ? $object->{$order['post_meta']} : '';
			}elseif($order['type'] == 'term'){
				$value	= '';

				if($order['taxonomy'] && $object->in_taxonomy($order['taxonomy'])){
					foreach($object->get_terms($order['taxonomy']) as $term){
						$value	= wpjam_get_term_thumbnail_url($term);

						if($value){
							return $value;
						}
					}
				}
			}

			if($value){
				return $value;
			}
		}

		return $thumbnail_url ?: self::get_default();
	}

	public static function filter_has_post_thumbnail($has_thumbnail, $post){
		if(!$has_thumbnail && self::get_setting('auto')){
			return (bool)wpjam_get_post_thumbnail_url($post);
		}

		return $has_thumbnail;
	}

	public static function filter_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr){
		$object	= wpjam_get_post_object($post_id);

		if(!$object->supports('thumbnail') || empty($html)){
			$thumbnail_url	= '';
			$thumbnail_size	= wpjam_parse_size($size, 2);

			if(self::get_setting('auto')){
				$thumbnail_url	= $object->get_thumbnail_url($thumbnail_size);
			}elseif($object->supports('images')){
				$thumbnail_url	= $object->images ? current($object->images) : '';
				$thumbnail_url	= $thumbnail_url ? wpjam_get_thumbnail($thumbnail_url, $thumbnail_size) : ''; 
			}

			if(!$thumbnail_url){
				return $html;
			}

			if(function_exists('wp_get_loading_attr_default')){
				$loading	= wp_get_loading_attr_default('the_post_thumbnail');
			}elseif(wp_lazy_loading_enabled('img', 'wp_get_attachment_image')){
				$loading	= 'lazy';
			}else{
				$loading	= '';
			}

			$class	= is_array($size) ? join('x', $size) : $size;

			$attr	= wp_parse_args($attr, [
				'src'		=> $thumbnail_url,
				'class'		=> "attachment-$class size-$class wp-post-image",
				'loading'	=> $loading
			]);

			$size	= wpjam_parse_size($size);
			$html	= rtrim('<img '.image_hwstring($size['width'], $size['height']));

			foreach($attr as $name => $value){
				if($name == 'loading' && !$value){
					continue;
				}

				$html	.= " $name=".'"'.esc_attr($value).'"';
			}

			$html	.= ' />';
		}

		return $html;
	}

	public static function filter_fallback_intermediate_image_sizes($fallback_sizes){
		if(self::get_setting('disable_pdf_preview')){
			return [];
		}

		return $fallback_sizes;
	}

	public static function init(){
		$taxonomies	= self::get_setting('term_thumbnail_taxonomies', []);

		if($taxonomies){
			$settings	= [];

			if(self::get_setting('term_thumbnail_type') == 'img'){
				$width	= self::get_setting('term_thumbnail_width', 200);
				$height	= self::get_setting('term_thumbnail_height', 200);

				if($width || $height){
					$settings['thumbnail_size']	= $width.'x'.$height;
				}

				$settings['thumbnail_type']	= 'img';
			}else{
				$settings['thumbnail_type']	= 'image';
			}

			foreach($taxonomies as $taxonomy){
				$tax_object	= wpjam_get_taxonomy_object($taxonomy);

				if($tax_object && $tax_object->is_object_in('post')){
					$tax_object->add_support('thumbnail');
					$tax_object->update_args($settings);
				}
			}
		}
	}

	public static function add_hooks(){
		add_filter('wpjam_post_thumbnail_url',	[self::class, 'filter_post_thumbnail_url'], 1, 2);
		add_filter('has_post_thumbnail',		[self::class, 'filter_has_post_thumbnail'], 10, 2);
		add_filter('post_thumbnail_html',		[self::class, 'filter_post_thumbnail_html'], 10, 5);

		add_filter('fallback_intermediate_image_sizes',	[self::class, 'filter_fallback_intermediate_image_sizes']);
	}
}

function wpjam_get_default_thumbnail_url($size='full', $crop=1){
	$default	= WPJAM_Thumbnail::get_default();

	return $default ? wpjam_get_thumbnail($default, $size, $crop) : '';
}

wpjam_register_option('wpjam-thumbnail', [
	'site_default'	=> true,
	'model'			=> 'WPJAM_Thumbnail',
]);
