<?php
if(!class_exists('WP_List_Table')){
	include ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class WPJAM_List_Table extends WP_List_Table{
	use WPJAM_Call_Trait;

	public function __construct($args=[]){
		$this->_args	= $args	= wp_parse_args($args, [
			'title'			=> '',
			'plural'		=> '',
			'singular'		=> '',
			'data_type'		=> 'model',
			'capability'	=> 'manage_options',
			'per_page'		=> 50
		]);

		$primary_key	= $this->get_primary_key_by_model();

		if($primary_key){
			$args['primary_key']	= $primary_key;
		}

		$GLOBALS['wpjam_list_table']	= $this;

		parent::__construct($this->parse_args($args));
	}

	public function __get($name){
		if(in_array($name, $this->compat_fields, true)){
			return $this->$name;
		}elseif(isset($this->_args[$name])){
			return $this->_args[$name];
		}
	}

	public function __isset($name){
		return $this->$name !== null;
	}

	public function __set($name, $value){
		if(in_array($name, $this->compat_fields, true)){
			return $this->$name	= $value;
		}else{
			$this->_args	= $this->_args ?? [];

			return $this->_args[$name]	= $value; 
		}
	}

	public function __call($method, $args){
		if(in_array($method, $this->compat_methods, true)){
			return $this->$method(...$args);
		}elseif($method == 'get_query_id'){
			if($this->current_action()){
				return null;
			}

			return wpjam_get_parameter('id', ['sanitize_callback'=>'sanitize_text_field']);
		}elseif(str_ends_with($method, '_by_locale')){
			$method	= wpjam_remove_postfix($method, '_by_locale');

			return wpjam_call([$GLOBALS['wp_locale'], $method], ...$args);
		}elseif(str_ends_with($method, '_by_model')){
			$method	= wpjam_remove_postfix($method, '_by_model');

			if(method_exists($this->model, $method)){
				return wpjam_call([$this->model, $method], ...$args);
			}

			$fallback	= [
				'render_item'	=> 'item_callback',
				'get_subtitle'	=> 'subtitle',
				'get_views'		=> 'views',
				'query_items'	=> 'list',
			];

			if(isset($fallback[$method]) && method_exists($this->model, $fallback[$method])){
				return wpjam_call([$this->model, $fallback[$method]], ...$args);
			}

			if(in_array($method, [
				'render_item',
				'render_date'
			])){
				return $args[0];
			}elseif(in_array($method, [
				'get_subtitle',
				'get_views', 
				'get_fields',
				'extra_tablenav',
				'before_single_row',
				'after_single_row',
			])){
				return null;
			}else{
				if(method_exists($this->model, '__callStatic')){
					$result	= wpjam_call([$this->model, $method], ...$args);
				}else{
					$result	= new WP_Error('undefined_method', [$this->model.'->'.$method.'()']);
				}

				if(is_wp_error($result)){
					if(in_array($method, [
						'get_filterable_fields',
						'get_searchable_fields',
						'get_primary_key',
						'col_left',
					])){
						return null;
					}
				}

				return $result;
			}
		}
	}

	protected function parse_args($args){
		$this->screen	= $args['screen'] = get_current_screen();
		$this->_args	= $this->_args ?? [];
		$this->_args	= array_merge($this->_args, $args);

		$this->add_screen_item('ajax', true);
		$this->add_screen_item('form_id', 'list_table_form');
		$this->add_screen_item('query_id', $this->get_query_id());
		$this->add_screen_item('left_key', $this->left_key);

		if(is_array($this->per_page)){
			add_screen_option('per_page', $this->per_page);
		}

		if($this->style){
			wp_add_inline_style('list-tables', $this->style);
		}

		if($this->data_type){
			$data_type = $this->data_type;

			add_screen_option('data_type', $data_type);

			if(in_array($data_type, ['post_type', 'taxonomy']) && $this->$data_type && !$this->screen->$data_type){
				$this->screen->$data_type	= $this->$data_type;
			}
		}

		$args['row_actions']	= $args['bulk_actions']	= $args['overall_actions']	= $args['next_actions'] = [];

		foreach($this->get_objects('action') as $key => $object){
			$object->primary_key	= $this->primary_key;
			$object->model			= $this->model;
			$object->capability		= $object->capability ?? $this->capability;
			$object->page_title		= $object->page_title ?? ($object->title ? wp_strip_all_tags($object->title.$this->title) : '');
			$object->data_type		= $data_type;

			if($data_type && $data_type != 'model'){
				$object->$data_type	= $this->$data_type ?: '';
			}

			if($object->overall){
				if(!$object->response){
					$object->response	= 'list';
				}

				$args['overall_actions'][]	= $key;
			}else{
				if(is_null($object->response)){
					$object->response	= $key;
				}

				if($object->bulk && $object->current_user_can()){
					$args['bulk_actions'][$key]	= $object;
				}

				if($object->next && $object->response == 'form'){
					$args['next_actions'][$key]	= $object->next;
				}

				if($key == 'add'){
					if($this->layout == 'left'){
						$args['overall_actions'][]	= $key;
					}
				}else{
					if(is_null($object->row_action) || $object->row_action){
						$args['row_actions'][]	= $key;
					}
				}
			}
		}

		$args['row_actions']	= array_diff($args['row_actions'], array_values($args['next_actions']));

		$this->add_screen_item('bulk_actions', $args['bulk_actions']);

		$args['columns']	= $args['sortable_columns'] = [];
		$filterable_fields	= $this->get_filterable_fields_by_model();

		foreach($this->get_objects('column') as $object){
			$key	= $object->name;

			if(is_null($object->filterable)){
				$object->filterable	= $filterable_fields && in_array($key, $filterable_fields);
			}

			$args['columns'][$key]	= $object->column_title ?? $object->title;

			if($object->sortable_column){
				$args['sortable_columns'][$key] = [$key, true];
			}

			$object->add_style();
		}

		return $args;
	}

	protected function add_screen_item($key, $item){
		wpjam_add_screen_item('wpjam_list_table', $key, $item);
	}

	protected function register_action($name, $args, $defaults=[]){
		return wpjam_register_list_table_action($name, wp_parse_args($args, $defaults));
	}

	protected function register_column($name, $field){
		if(!empty($field['show_admin_column'])){
			$field	= wp_parse_args(wpjam_strip_data_type($field), ['order'=>10.5]);

			return wpjam_register_list_table_column($name, $field);
		}
	}

	protected function get_objects($type='action'){
		if($type == 'action'){
			if($this->sortable){
				$sortable		= is_array($this->sortable) ? $this->sortable : ['items'=>' >tr'];
				$action_args	= array_pull($sortable, 'action_args', []);

				$this->register_action('move',	$action_args, ['page_title'=>'拖动',		'direct'=>true,	'dashicon'=>'move']);
				$this->register_action('up',	$action_args, ['page_title'=>'向上移动',	'direct'=>true,	'dashicon'=>'arrow-up-alt']);
				$this->register_action('down',	$action_args, ['page_title'=>'向下移动',	'direct'=>true,	'dashicon'=>'arrow-down-alt']);

				$this->add_screen_item('sortable', $sortable);
			}

			$object	= wpjam_get_data_type_object($this->data_type);

			if($object){
				$object->register_list_table_action($this->_args);
			}
		}elseif($type == 'column'){
			$fields	= $this->get_fields_by_model() ?: [];

			foreach($fields as $key => $field){
				$this->register_column($key, $field);

				if(wpjam_get_fieldset_type($field) == 'single'){
					foreach($field['fields'] as $sub_key => $sub_field){
						$this->register_column($sub_key, $sub_field);
					}
				}
			}
		}

		return wpjam_call(['WPJAM_List_Table_'.ucfirst($type), 'get_registereds'], wpjam_slice_data_type($this->_args));
	}

	protected function get_object($name, $type='action'){
		return wpjam_call(['WPJAM_List_Table_'.ucfirst($type), 'get'], $name, wpjam_slice_data_type($this->_args));
	}

	protected function get_row_actions($id){
		$row_actions	= [];

		foreach($this->row_actions as $key){
			$row_actions[$key] = $this->get_row_action($key, ['id'=>$id]);
		}

		return array_filter($row_actions);
	}

	public function get_row_action($action, $args=[]){
		$object = $this->get_object($action);

		return $object ? $object->get_row_action($args, $this->layout) : '';
	}

	public function get_filter_link($filter, $title, $class=[]){
		$query_args	= $this->query_args ?: [];	

		foreach($query_args as $query_arg){
			if(!isset($filter[$query_arg])){
				$filter[$query_arg]	= wpjam_get_data_parameter($query_arg);
			}
		}

		$class		= (array)$class;
		$class[]	= 'list-table-filter';

		return wpjam_wrap_tag($title, 'a', [
			'title'	=> wp_strip_all_tags($title, true),
			'class'	=> $class,
			'data'	=> ['filter'=>$filter],
		]);
	}

	public function get_single_row($id){		
		return wpjam_ob_get_contents([$this, 'single_row'], $id);
	}

	public function single_row($raw_item){
		$raw_item	= $this->parse_item($raw_item);

		if(empty($raw_item)){
			return;
		}

		$this->before_single_row_by_model($raw_item);

		$attr	= [];

		$attr['class']	= !empty($item['class']) ? (array)$item['class'] : [];
		$attr['style']	= $item['style'] ?? '';

		if($this->primary_key){
			$id	= str_replace('.', '-', $raw_item[$this->primary_key]);

			$attr['id']		= $this->singular.'-'.$id;
			$attr['data']	= ['id'=>$id];

			if($this->multi_rows){
				$attr['class'][]	= 'tr-'.$id;
			}
		}

		$item		= $this->render_item($raw_item);
		$callback	= wpjam_parse_method($this, 'single_row_columns');

		echo wpjam_tag('tr', $attr, wpjam_ob_get_contents($callback, $item));

		$this->after_single_row_by_model($item, $raw_item);
	}

	protected function parse_item($item){
		if(!is_array($item)){
			$result	= $this->get_by_model($item);
			$item 	= is_wp_error($result) ? null : $result;
			$item	= $item ? (array)$item : $item;
		}

		return $item;
	}

	protected function render_item($raw_item){
		$item	= (array)$raw_item;

		if($this->primary_key){
			$id	= $item[$this->primary_key];

			$item['row_actions']	= $this->get_row_actions($id);

			if($this->primary_key == 'id'){
				$item['row_actions']['id']	= 'ID：'.$id;
			}
		}

		return $this->render_item_by_model($item);
	}

	protected function get_column_value($id, $name, $value=null){
		$object	= $this->get_object($name, 'column');

		if($object){
			if(is_null($value)){
				if(method_exists($this->model, 'value_callback')){
					$value	= wpjam_call([$this->model, 'value_callback'], $name, $id);
				}else{
					$value	= $object->default;		
				}
			}

			return $object->callback($id, $value);
		}

		return $value;
	}

	public function column_default($item, $name){
		$value	= $item[$name] ?? null;
		$id		= $this->primary_key ? $item[$this->primary_key] : null;

		return $id ? $this->get_column_value($id, $name, $value) : $value;
	}

	public function column_cb($item){
		if($this->primary_key){
			$id	= $item[$this->primary_key];

			if($this->capability == 'read' || current_user_can($this->capability, $id)){
				$column	= $this->get_primary_column_name();
				$name	= isset($item[$column]) ? strip_tags($item[$column]) : $id;
				$cb_id	= 'cb-select-'.$id;
				$label	= wpjam_tag('label', ['for'=>$cb_id, 'class'=>'screen-reader-text'], '选择'.$name);
				$input	= wpjam_tag('input', ['type'=>'checkbox', 'name'=>'ids[]', 'value'=>$id, 'id'=>$cb_id]);

				return $label.$input;
			}
		}

		return wpjam_tag('span', ['dashicons', 'dashicons-minus']);
	}

	public function render_column_items($id, $items, $args=[]){
		$item_type	= $args['item_type'] ?? 'image';
		$sortable	= $args['sortable'] ?? false;
		$max_items	= $args['max_items'] ?? 0;
		$per_row	= $args['per_row'] ?? 0;
		$width		= $args['width'] ?? 60;
		$height		= $args['height'] ?? 60;
		$style		= $args['style'] ?? '';
		$add_item	= $args['add_item'] ?? 'add_item';
		$edit_item	= $args['edit_item'] ?? 'edit_item';
		$move_item	= $args['move_item'] ?? 'move_item';
		$del_item	= $args['del_item'] ?? 'del_item';

		$i			= 0;

		$rendered	= '';
		
		if($item_type == 'image'){
			$key	= $args['image_key'] ?? 'image';
			
			foreach($items as $i => $item){
				$args	= ['id'=>$id,	'data'=>compact('i')];

				$class	= 'item';
				$image	= $item[$key];
				// $class	= $image ? $class : $class.' dashicons dashicons-plus-alt2';
				$image	= $image ? '<img src="'.wpjam_get_thumbnail($image, $width*2, $height*2).'" '.image_hwstring($width, $height).' />' : ' ';
				$image	= $this->get_row_action($move_item,	array_merge($args, ['class'=>'move-item', 'title'=>$image]));
				$image	.= $this->get_row_action($del_item,	array_merge($args, ['class'=>'del-item-icon dashicons dashicons-no-alt', 'title'=>' ']));

				$item_style	= 'width:'.$width.'px;';

				if(!empty($item['color'])){
					$item_style	.= ' color:'.$item['color'].';';
				}
				
				$title		= !empty($item['title']) ? '<span class="item-title" style="'.$item_style.'">'.$item['title'].'</span>' : '';

				$actions	= $this->get_row_action($move_item,	array_merge($args, [
					'class'	=>'move-item dashicons dashicons-move', 
					'title'	=>' ', 
					'wrap'	=>'<span class="%1$s">%2$s | </span>'
				])).$this->get_row_action($edit_item,	array_merge($args, [
					'class'	=>'', 
					'title'	=>'修改', 
					'wrap'	=>'<span class="%1$s">%2$s</span>'
				]));

				$actions	= $actions ? '<span class="row-actions" style="width:'.$width.'px;">'.$actions.'</span>':'';
				$rendered	.= '<div id="item-'.$i.'" data-i="'.$i.'" class="'.$class.'" style="width:'.$width.'px;">'.$image.$title.$actions.'</div>';
			}

			if(!$max_items || $i < $max_items-1){
				$rendered	.= $this->get_row_action($add_item, [
					'tag'	=> 'div',
					'id'	=> $id,
					'class'	=> 'add-item dashicons dashicons-plus-alt2',
					'style'	=> 'width:'.$width.'px; height:'.$height.'px; line-height:'.$height.'px;',
					'title'	=> ' '
				]);
			}
		}elseif($item_type == 'text'){
			$key	= $args['text_key'] ?? 'text';

			foreach($items as $i => $item){
				$args	= ['id'=>$id,	'data'=>compact('i')];
				$text	= $item[$key] ?: ' ';

				if(!empty($item['color'])){
					$text	= '<span style="color:'.$item['color'].'">'.$text.'</span>';
				}

				$text	= $this->get_row_action($move_item, array_merge($args, [
					'class'	=> 'move-item text',
					'title'	=> $text
				]));

				$actions	= $this->get_row_action($move_item, array_merge($args, [
					'class'	=> 'move-item dashicons dashicons-move',	
					'title'	=> ' ',
					'wrap'	=> '<span class="%1$s">%2$s | </span>'
				])).$this->get_row_action($edit_item, array_merge($args, [
					'class'	=> '',	
					'title'	=> '修改',
					'wrap'	=> '<span class="%1$s">%2$s | </span>'
				])).$this->get_row_action($del_item, array_merge($args, [
					'title'	=> '删除',
					'wrap'	=> '<span class="delete">%2$s</span>'
				]));

				$actions	= $actions ? '<span class="row-actions">'.$actions.'</span>':'';

				$rendered	.= '<div id="item-'.$i.'" data-i="'.$i.'" class="item">'.$text.$actions.'</div>';
			}

			if(!$max_items || $i < $max_items-1){
				$rendered	.= $this->get_row_action($add_item, ['id'=>$id,	'title'=>'新增']);
			}
		}

		if($per_row){
			$style	.= $style ? ' ' : '';
			$style	.= 'width:'.($per_row * ($width+30)).'px;';
		}

		$class		= ['items', $item_type.'-list'];
		$class[]	= $sortable ? ' sortable' : '';

		return wpjam_wrap_tag($rendered, 'div', ['class'=>$class, 'style'=>$style]);
	}

	public function get_list_table(){
		if(wp_doing_ajax()){
			$this->prepare_items();
		}

		return wpjam_ob_get_contents([$this, 'list_table']);
	}

	public function list_table(){
		$this->views();

		echo '<form action="#" id="list_table_form" method="POST">';

		if($this->is_searchable()){
			$this->search_box('搜索', 'wpjam');
			echo '<br class="clear" />';
		}

		$this->display(); 

		echo '</form>';
	}

	public function ajax_response(){
		$referer	= wpjam_get_referer();

		if(!$referer){
			return new WP_Error('error', '非法请求');
		}

		$referer_parts	= parse_url($referer);

		if($referer_parts['host'] == $_SERVER['HTTP_HOST']){
			$_SERVER['REQUEST_URI']	= $referer_parts['path'];
		}

		$action_type	= wpjam_get_ajax_action_type();

		if($action_type == 'query_item'){
			$id	= wpjam_get_post_parameter('id',	['default'=>'']);

			return ['type'=>'add',	'id'=>$id, 'data'=>$this->get_single_row($id)];
		}elseif($action_type == 'query_items'){
			foreach(wpjam_get_data_parameter() as $key=>$value){
				$_REQUEST[$key]	= $value;
			}

			return ['data'=>$this->get_list_table(), 'type'=>'list'];
		}

		$list_action	= wpjam_get_post_parameter('list_action');
		$object			= $this->get_object($list_action);

		if(!$object){
			return new WP_Error('invalid_action');
		}

		$id		= wpjam_get_post_parameter('id',	['default'=>'']);
		$ids	= wpjam_get_post_parameter('ids',	['sanitize_callback'=>'wp_parse_args', 'default'=>[]]);
		$bulk	= wpjam_get_post_parameter('bulk',	['sanitize_callback'=>'intval']);

		if($action_type != 'form'){
			if(!$object->verify_nonce($id, $bulk)){
				return new WP_Error('invalid_nonce');
			}

			if($bulk === 2){
				$bulk = 0;
			}
		}

		$id_or_ids	= $bulk ? $ids : $id;

		if(!$object->current_user_can($id_or_ids)){
			return new WP_Error('bad_authentication');
		}

		$data	= wpjam_get_data_parameter();

		$response	= [
			'list_action'	=> $list_action,
			'page_title'	=> $object->page_title,
			'type'			=> $object->response,
			'layout'		=> $this->layout,
			'id'			=> $id,
			'bulk'			=> $bulk,
			'ids'			=> $ids
		];

		$form_args	= [
			'action_type'	=> $action_type,
			'response_type'	=> $object->response,
			'id'			=> $id,
			'bulk'			=> $bulk,
			'ids'			=> $ids,
			'data'			=> $data,
		];

		if($action_type == 'form'){
			return array_merge($response, [
				'type'	=> 'form',
				'form'	=> $object->get_form($form_args),
				'width'	=> $object->width ?: 720
			]);
		}elseif($action_type == 'direct'){
			if($bulk){
				$result	= $object->callback($ids); 
			}else{
				if(in_array($list_action, ['move', 'up', 'down'])){
					$result	= $object->callback($id, $data);
				}else{
					$result	= $object->callback($id);

					if($list_action == 'duplicate'){
						$id = $result;
					}
				}
			}
		}elseif($action_type == 'submit'){
			$data	= $object->validate($id_or_ids, $data);

			if($object->response == 'form'){
				$form_args['data']	= $data;

				$result	= null;
			}else{
				$form_args['data']	= wpjam_get_post_parameter('defaults',	['sanitize_callback'=>'wp_parse_args', 'default'=>[]]);

				$submit_name	= wpjam_get_post_parameter('submit_name',	['default'=>$object->name]);
				$submit_text	= $object->get_submit_text($id, $submit_name);

				if(!$submit_text){
					return new WP_Error('invalid_submit_button');
				}

				$response['type']	= $submit_text['response'];

				$result	= $object->callback($id_or_ids, $data, $submit_name);
			}
		}

		$result_as_response	= is_array($result) && (
			isset($result['type']) || isset($result['bulk']) || isset($result['ids']) || isset($result['id']) || isset($result['items'])
		);

		if($result_as_response){
			$response	= array_merge($response, $result);

			$bulk	= $response['bulk'];
			$ids	= $response['ids'];
			$id		= $response['id'];
		}else{
			if(in_array($response['type'], ['add', 'duplicate']) || in_array($list_action, ['add', 'duplicate'])){
				if(is_array($result)){
					$dates	= $result['dates'] ?? $result;
					$date	= current($dates);
					$id		= is_array($date) ? ($date[$this->primary_key] ?? null) : null;

					if(is_null($id)){
						return new WP_Error('invalid_id');
					}
				}else{
					$id	= $result;
				}
			}
		}

		$data	= '';

		$form_required	= true;

		if($response['type'] == 'append'){
			return array_merge($response, ['data'=>$result, 'width'=>($object->width ?: 720)]);
		}elseif($response['type'] == 'redirect'){
			if(is_string($result)){
				$response['url']	= $result;
			}

			return $response;
		}elseif(in_array($response['type'], ['delete', 'move', 'up', 'down', 'form'])){
			if($this->layout == 'calendar'){
				$data	= $this->render_dates($result);
			}
		}elseif($response['type'] == 'items' && isset($response['items'])){
			foreach($response['items'] as $id => &$response_item){
				$response_item['id']	= $id;

				if($response_item['type'] == 'delete'){
					$form_required	= false;
				}elseif($response_item['type'] != 'append'){
					if(!is_blank($id)){
						$response_item['data']	= $this->get_single_row($id);
					}
				}
			}

			unset($response_item);
		}elseif($response['type'] == 'list'){
			if(in_array($list_action, ['add', 'duplicate'])){
				$response['id']	= $id;
			}

			$data	= $this->get_list_table();
		}else{
			if($bulk){
				$this->get_by_ids_by_model($ids);

				$data	= [];

				foreach($ids as $id){
					if(!is_blank($id)){
						$data[$id]	= $this->get_single_row($id);
					}
				}
			}else{
				if($this->layout == 'calendar'){
					$data	= $this->render_dates($result);
				}else{
					if(!$result_as_response && in_array($response['type'], ['add', 'duplicate'])){
						$response['id']	= $form_args['id'] = $id;
					}

					if(!is_blank($id)){
						$data	= $this->get_single_row($id);
					}
				}
			}
		}

		$response['data']	= $data;

		if($object->response != 'form'){
			if($result && is_array($result) && !empty($result['errmsg']) && $result['errmsg'] != 'ok'){ // 有些第三方接口返回 errmsg ： ok
				$response['errmsg'] = $result['errmsg'];
			}elseif($action_type == 'submit'){
				$response['errmsg'] = $submit_text['text'].'成功';
			}
		}

		if($action_type == 'submit'){
			if($response['type'] == 'delete'){
				$response['dismiss']	= true;
			}else{
				if($object->next){
					$response['next']		= $object->next;
					$response['page_title']	= $object->get_next_action()->page_title;

					if($response['type'] == 'form'){
						$response['errmsg']	= '';
					}
				}elseif($object->dismiss){
					$response['dismiss']	= true;
					$form_required			= false;
				}

				if($form_required){
					$response['form']	= $object->get_form($form_args);
				}
			}
		}

		return $response;
	}

	public function prepare_items(){
		$per_page	= $this->get_per_page();
		$offset		= ($this->get_pagenum()-1) * $per_page;

		if(method_exists($this->model, 'query_data')){
			$result	= $this->try('query_data_by_model', ['number'=>$per_page, 'offset'=>$offset]);
		}else{
			$result	= $this->try('query_items_by_model', $per_page, $offset);
		}

		$this->items	= $result['items'] ?? [];
		$total_items	= $result['total'] ?? count($this->items);

		if($total_items){
			$this->set_pagination_args(['total_items'=>$total_items,	'per_page'=>$per_page]);
		}
	}

	public function get_prev_action($name){
		foreach($this->next_actions as $prev => $next){
			if($next == $name){
				return $this->get_object($prev);
			}
		}
	}

	protected function get_bulk_actions(){
		return wp_list_pluck($this->bulk_actions, 'title');
	}

	public function get_subtitle(){
		$subtitle	= $this->get_subtitle_by_model();
		$search		= wpjam_get_data_parameter('s');

		if($search){
			$subtitle 	.= ' “'.esc_html($search).'”的搜索结果';
		}

		$subtitle	= $subtitle ? wpjam_tag('span', ['subtitle'], $subtitle) : '';

		if($this->layout != 'left'){
			$subtitle	= $this->get_row_action('add', ['class'=>'page-title-action', 'subtitle'=>true]).$subtitle;
		}

		return $subtitle;
	}

	protected function get_table_classes() {
		$classes = parent::get_table_classes();

		return $this->fixed ? $classes : array_diff($classes, ['fixed']);
	}

	public function get_singular(){
		return $this->singular;
	}

	protected function get_primary_column_name(){
		$name	= $this->primary_column;

		if($this->columns && (!$name || !isset($this->columns[$name]))){
			return array_key_first($this->columns);
		}

		return $name;
	}

	protected function handle_row_actions($item, $column_name, $primary){
		return ($primary === $column_name && !empty($item['row_actions'])) ? $this->row_actions($item['row_actions'], false) : '';
	}

	public function row_actions($actions, $always_visible=true){
		return parent::row_actions($actions, $always_visible);
	}

	public function get_per_page(){
		if($this->per_page && is_numeric($this->per_page)){
			return $this->per_page;
		}

		$option		= get_screen_option('per_page', 'option');
		$default	= get_screen_option('per_page', 'default') ?: 50;

		return $option ? $this->get_items_per_page($option, $default) : $default;
	}

	public function get_columns(){
		if($this->bulk_actions){
			return array_merge(['cb'=>'checkbox'], $this->columns);
		}

		return $this->columns;
	}

	public function get_sortable_columns(){
		return $this->sortable_columns;
	}

	public function get_views(){
		return $this->get_views_by_model() ?: [];
	}

	public function is_searchable(){
		return $this->search ?? $this->get_searchable_fields_by_model();
	}

	public function extra_tablenav($which='top'){
		$this->extra_tablenav_by_model($which);

		do_action(wpjam_get_filter_name($this->plural, 'extra_tablenav'), $which);

		if($which == 'top'){
			foreach($this->overall_actions as $action){
				$row_action	= $this->get_row_action($action, ['class'=>'button-primary button']);

				echo $row_action ? wpjam_wrap_tag($row_action, 'div', ['alignleft', 'actions', 'overallactions']) : '';
			}
		}
	}

	public function print_column_headers($with_id=true) {
		foreach(['orderby', 'order'] as $key){
			$value	= wpjam_get_data_parameter($key);

			if($value){
				$_GET[$key] = $value;
			}
		}

		parent::print_column_headers($with_id);
	}

	public function current_action(){
		return wpjam_get_request_parameter('list_action', ['default'=>parent::current_action()]);
	}
}

class WPJAM_Left_List_Table extends WPJAM_List_Table{
	public function col_left(){
		$result	= $this->col_left_by_model();

		if($result && is_array($result)){
			$args	= wp_parse_args($result, [
				'total_items'	=> 0,
				'total_pages'	=> 0,
				'per_page'		=> 10,
			]);

			$total_pages	= $args['total_pages'] ?: ($args['per_page'] ? ceil($args['total_items']/$args['per_page']) : 0);

			if($total_pages){
				$pages	= [];

				foreach(['prev', 'text', 'next', 'goto'] as $key){
					$pages[$key]	= $this->get_left_page_link($key, $total_pages);
				}

				$class	= ['tablenav-pages'];
				$class	= $total_pages < 2 ? array_merge($class, ['one-page']) : $class;

				echo wpjam_wrap_tag(join('', array_filter($pages)), 'span', 'left-pagination-links')->wrap('div', $class)->wrap('div', ['tablenav', 'bottom']);
			}
		}	
	}

	public function ajax_response(){
		if(wpjam_get_ajax_action_type() == 'left'){
			return ['data'=>$this->get_list_table(), 'left'=>$this->get_col_left(), 'type'=>'left'];
		}

		return parent::ajax_response();
	}

	protected function get_left_page_link($type, $total){
		$current	= (int)wpjam_get_data_parameter('left_paged') ?: 1;

		if($type == 'text'){
			return wpjam_wrap_tag($current, 'span', ['current-page'])
			->after('/')
			->after('span', ['total-pages'], number_format_i18n($total))
			->wrap('span', ['tablenav-paging-text']);
		}elseif($type == 'goto'){
			if($total < 2){
				return '';
			}

			return wpjam_tag('input', [
				'type'	=> 'text',
				'name'	=> 'paged',
				'value'	=> $current,
				'size'	=> strlen($total),
				'id'	=> 'left-current-page-selector',
				'class'	=> 'current-page',
				'aria-describedby'	=> 'table-paging',
			])->after('a', ['left-pagination', 'button', 'goto'], '&#10132;')
			->wrap('span', ['paging-input']);
		}elseif($type == 'prev'){
			$value	= 1;
			$paged	= max(1, $current - 1);
			$text	= '&lsaquo;';
			$reader	= __('Previous page');
		}else{
			$value	= $total;
			$paged	= min($value, $current + 1);
			$text	= '&rsaquo;';
			$reader	= __('Next page');
		}

		$attr	= ['aria-hidden'=>'true'];

		if($value == $current){
			$attr['class']	= ['tablenav-pages-navspan', 'button', 'disabled'];

			return wpjam_wrap_tag($text, 'span', $attr);
		}else{
			return wpjam_wrap_tag($text, 'span', $attr)
			->before('span', ['screen-reader-text'], $reader)
			->wrap('a', ['data'=>['left_paged'=>$paged], 'class'=>['left-pagination', 'button', $type.'-page']]);
		}
	}

	public function get_col_left(){
		return wpjam_ob_get_contents([$this, 'col_left']);
	}
}

class WPJAM_Calendar_List_Table extends WPJAM_List_Table{
	private $year;
	private $month;

	public function prepare_items(){
		$this->year		= (int)wpjam_get_data_parameter('year') ?: wpjam_date('Y');
		$this->month	= (int)wpjam_get_data_parameter('month') ?: wpjam_date('m');

		$this->month	= min($this->month, 12);
		$this->month	= max($this->month, 1);

		$this->year		= min($this->year, 2200);
		$this->year		= max($this->year, 1970);

		if(method_exists($this->model, 'query_data')){
			$this->items	= $this->try('query_data_by_model', ['year'=>$this->year, 'monthnum'=>$this->month], $this->layout);
		}else{
			$this->items	= $this->try('query_dates_by_model', $this->year, zeroise($this->month, 2));
		}
	}

	public function render_date($raw_item, $date){
		if(wp_is_numeric_array($raw_item)){
			foreach($raw_item as $key => &$_item){
				$_item	= $this->parse_item($_item);

				if(!$_item){
					unset($raw_item[$key]);
				}
			}
		}else{
			$raw_item	= $this->parse_item($raw_item);
		}

		$row_actions	= [];

		if(wpjam_is_assoc_array($raw_item)){
			$row_actions	= $this->get_row_actions($raw_item[$this->primary_key]);
		}else{
			$row_actions	= ['add'=>$this->get_row_action('add', ['data'=>['date'=>$date]])];
		}

		$links	= wpjam_tag('div', ['row-actions', 'alignright']);

		foreach($row_actions as $action => $link){
			$links->append('span', [$action], $link)->append(' ');
		}

		$item	= $this->render_date_by_model($raw_item, $date);
		$day	= explode('-', $date)[2];
		$class	= $date == wpjam_date('Y-m-d') ? ['day', 'today'] :  ['day'];

		return $links->before('span', [$class], $day)
		->wrap('div', ['date-meta'])
		->after('div', ['date-content'], $item);
	}

	public function render_dates($result){
		$dates	= $result['dates'] ?? $result;
		$data	= [];

		foreach($dates as $date => $item){
			$data[$date]	= $this->render_date($item, $date);
		}

		return $data;
	}

	public function display(){
		$this->display_tablenav('top');

		$year	= $this->year;
		$month	= zeroise($this->month, 2);
		$m_ts	= mktime(0, 0, 0, $this->month, 1, $this->year);	// 每月开始的时间戳
		$days	= date('t', $m_ts);
		$start	= (int)get_option('start_of_week');
		$pad	= calendar_week_mod(date('w', $m_ts) - $start);
		$tr		= wpjam_tag('tr');

		for($wd_count = 0; $wd_count <= 6; $wd_count++){
			$weekday	= ($wd_count + $start) % 7;
			$name		= $this->get_weekday_by_locale($weekday);

			$tr->append('th', [
				'scope'	=> 'col',
				'class'	=> in_array($weekday, [0, 6]) ? 'weekend' : 'weekday',
				'title'	=> $name
			], $this->get_weekday_abbrev_by_locale($name)); 
		}

		$thead	= wpjam_tag('thead')->append(wp_clone($tr));
		$tfoot	= wpjam_tag('tfoot')->append(wp_clone($tr));
		$tbody	= wpjam_tag('tbody', ['id'=>'the-list', 'data'=>['wp-lists'=>'list:'.$this->singular]]);
		$tr		= wpjam_tag('tr');

		if($pad){
			$tr->append('td', ['colspan'=>$pad, 'class'=>'pad']);
		}

		for($day=1; $day<=$days; ++$day){
			$date	= $year.'-'.$month.'-'.zeroise($day, 2);
			$item	= $this->items[$date] ?? [];
			$item	= $this->render_date($item, $date);

			$tr->append('td', [
				'id'	=> 'date_'.$date,
				'class'	=> in_array($pad+$start, [0, 6, 7]) ? 'weekend' : 'weekday'
			], $item);

			$pad++;

			if($pad%7 == 0){
				$tbody->append($tr);

				$pad	= 0;
				$tr	= wpjam_tag('tr');
			}
		}

		if($pad){
			$tr->append('td', ['colspan'=>(7-$pad), 'class'=>'pad']);

			$tbody->append($tr);
		}

		echo $tbody->before($tfoot)->before($thead)->wrap('table', ['cellpadding'=>10, 'cellspacing'=>0, 'class'=>'widefat fixed']);

		$this->display_tablenav('bottom');
	}

	public function extra_tablenav($which='top'){
		if($which == 'top'){
			echo wpjam_wrap_tag(sprintf(__('%1$s %2$d'), $this->get_month_by_locale($this->month), $this->year), 'h2');
		}

		parent::extra_tablenav($which);
	}

	public function pagination($which){
		$links	= $this->get_month_link('prev').$this->get_month_link().$this->get_month_link('next');

		echo wpjam_wrap_tag($links, 'span', ['pagination-links'])->wrap('div',	['tablenav-pages']);
	}

	public function get_month_link($type=''){
		if($type == 'prev'){
			$text	= '&lsaquo;';
			$class	= 'prev-month';

			if($this->month == 1){
				$year	= $this->year - 1;
				$month	= 12;
			}else{
				$year	= $this->year;
				$month	= $this->month - 1;
			}
		}elseif($type == 'next'){
			$text	= '&rsaquo;';
			$class	= 'next-month';

			if($this->month == 12){
				$year	= $this->year + 1;
				$month	= 1;
			}else{
				$year	= $this->year;
				$month	= $this->month + 1;
			}
		}else{
			$text	= '今日';
			$class	= 'current-month';
			$year	= wpjam_date('Y');
			$month	= wpjam_date('m');
		}

		if($type){
			$reader	= sprintf(__('%1$s %2$d'), $this->get_month_by_locale($month), $year);
			$text	= wpjam_wrap_tag($text, 'span', ['aria-hidden'=>'true'])->before('span', ['screen-reader-text'], $reader);
		}

		return $this->get_filter_link(['year'=>$year, 'month'=>$month], $text, $class.' button');
	}

	public function get_views(){
		return [];
	}

	public function get_bulk_actions(){
		return [];
	}

	public function is_searchable(){
		return false;
	}
}

class WPJAM_List_Table_Action extends WPJAM_Register{
	public function __call($method, $args){
		if($method == 'get_defaults' || $method == 'validate'){
			$id		= array_shift($args);
			$fields	= $this->get_fields($id, true);

			if(!$fields){
				return $args[1] ?? null;
			}

			return call_user_func_array([wpjam_fields($fields), $method], $args);
		}elseif($method == 'get_meta_type'){
			$value	= $this->meta_type;

			if($value === true || !$value){
				$object	= wpjam_get_data_type_object($this->data_type);
				$value	= $object ? $object->meta_type : '';

				if(!$value && $this->model && is_callable([$this->model, 'get_meta_type'])){
					$value	= call_user_func([$this->model, 'get_meta_type']);
				}
			}

			return $value;
		}elseif(str_ends_with($method, '_nonce')){
			$id 	= $args[0];
			$bulk	= $args[1] ?? false;
			$bulk	= $bulk ?: ($id ? false : true);
			$key	= $bulk ? 'bulk_'.$this->name : $this->name.'-'.$id;
			$action	= wpjam_get_nonce_action($key);
		
			if($method == 'verify_nonce'){
				$nonce	= wpjam_get_ajax_nonce();

				return wp_verify_nonce($nonce, $action);
			}else{
				return wp_create_nonce($action);
			}
		}else{
			$args	= wpjam_slice_data_type($this->args);

			if($method == 'get_next_action'){
				return self::get($this->next, $args);
			}elseif($method == 'get_prev_action'){
				return self::get($this->prev, $args) ?: wpjam_get_list_table_prev_action($this->name);
			}
		}
	}

	public function jsonSerialize(){
		return array_filter($this->generate_data_attr(['bulk'=>true]));
	}

	public function get_data($id, $include_prev=false, $by_callback=false){
		if($include_prev || $by_callback){
			$callback	= $this->data_callback;

			if($callback && is_callable($callback)){
				$data 	= wpjam_try($callback, $id, $this->name);

				if(!$include_prev){
					return $data;
				}
			}else{
				if($include_prev){
					throw new WPJAM_Exception('「'.$this->name.'」的 data_callback 无效', 'invalid_callback');
				}
			}
		}

		if($include_prev){
			$prev	= $this->get_prev_action();
			$prev	= $prev ? $prev->get_data($id, true) : [];

			return array_merge($prev, $data);
		}else{
			if(is_callable([$this->model, 'get'])){
				$data	= wpjam_try([$this->model, 'get'], $id);

				return $data ?: new WP_Error('invalid_id');
			}

			throw new WPJAM_Exception([$this->model.'->get()'], 'undefined_method');
		}
	}

	public function get_fields($id, $include_prev=false, $args=[]){
		if($this->direct){
			return [];
		}

		$fields	= $this->fields;

		if($fields && is_callable($fields)){
			$fields	= wpjam_try($fields, $id, $this->name);
		}

		$fields	= $fields ?: wpjam_try([$this->model, 'get_fields'], $this->name, $id);
		$fields	= is_array($fields) ? $fields : [];

		if($include_prev){
			$prev_action	= $this->get_prev_action();

			if($prev_action){
				$fields	= array_merge($fields, $prev_action->get_fields($id, true));
			}else{
				if($this->prev){
					throw new WPJAM_Exception('关联的上一步操作无效', 'error');
				}
			}
		}

		if(method_exists($this->model, 'filter_fields')){
			$fields	= wpjam_try([$this->model, 'filter_fields'], $fields, $id, $this->name);
		}else{
			if(!in_array($this->name, ['add', 'duplicate'])){
				$primary_key	= $this->primary_key;

				if($primary_key && isset($fields[$primary_key])){
					$fields[$primary_key]['type']	= 'view';
				}
			}
		}

		return $args ? wpjam_fields($fields, $args) :$fields;
	}

	public function get_form($args=[]){
		$object			= $this;
		$action_type	= $args['action_type'];
		$prev_action	= null;

		if($action_type == 'submit' && $this->next){
			if($this->response == 'form'){
				$prev_action	= $this;	
			}

			$object	= $this->get_next_action();
		}

		$bulk	= $args['bulk'];
		$id		= $bulk ? 0 : $args['id'];
		$id_arg	= $bulk ? $args['ids'] : $id;

		$fields_args	= ['id'=>$id, 'echo'=>false, 'data'=>$args['data']];

		if(!$bulk){
			if($id && ($action_type != 'submit' || $args['response_type'] != 'form')){
				$fields_args['data']	= array_merge($args['data'], $object->get_data($id, false, true));
			}

			$fields_args['meta_type']	= $object->get_meta_type();

			if($object->value_callback){
				$fields_args['value_callback']	= $object->value_callback;
			}elseif(method_exists($object->model, 'value_callback')){
				$fields_args['value_callback']	= [$object->model, 'value_callback'];
			}
		}

		$fields	= $object->get_fields($id_arg, false, $fields_args);
		$button	= '';

		$prev_action	= $prev_action ?: $object->get_prev_action();

		if($prev_action && !$bulk){
			$button	.= wpjam_tag('input', [
				'type'	=> 'button',
				'value'	=> '上一步',
				'class'	=> ['list-table-action', 'button','large'],
				'data'	=> $prev_action->generate_data_attr($args)
			]);

			if($action_type == 'form'){
				$args['data']	= array_merge($args['data'], $prev_action->get_data($id, true));
			}
		}

		if($object->next && $object->response == 'form'){
			$button	.= get_submit_button('下一步', 'primary', 'next', false);
		}else{
			foreach($object->get_submit_text($id) as $key => $item){
				$button	.= get_submit_button($item['text'], $item['class'], $key, false);
			}
		}

		$form	= wpjam_wrap_tag($fields, 'form', [
			'method'	=> 'post',
			'action'	=> '#',
			'id'		=> 'list_table_action_form', 
			'data'		=> $object->generate_data_attr(array_merge($args, ['type'=>'form']))
		]);

		if($button){
			$form->append('p', ['submit'], $button);
		}

		return $form;
	}

	public function get_row_action($args=[], $layout=''){
		if($layout == 'calendar' && !$this->calendar){
			return '';
		}

		$args	= wp_parse_args($args, ['id'=>0, 'data'=>[], 'class'=>'', 'style'=>'', 'title'=>'', 'wrap'=>'', 'fallback'=>false]);

		if(!$this->show_if($args['id'])){
			return '';
		}

		if(!$this->current_user_can($args['id'])){
			if($args['fallback'] === true ){
				return $args['title'];
			}elseif($args['fallback']){
				return $args['fallback'];
			}else{
				return '';
			}
		}

		$tag	= $args['tag'] ?? 'a';
		$attr	= ['title'=>$this->page_title, 'style'=>$args['style'], 'class'=>[$args['class']]];

		if($this->redirect){
			$tag	= 'a';

			$attr['href']		= str_replace('%id%', $args['id'], $this->redirect);
			$attr['class'][]	= 'list-table-redirect';
		}elseif($this->filter){
			$item	= (array)$this->get_data($args['id']);
			$data	= $this->data ?: [];
			$data	= array_merge($data, wp_array_slice_assoc($item, wp_parse_list($this->filter)));

			$attr['data']		= ['filter'=>wp_parse_args($args['data'], $data)];
			$attr['class'][]	= 'list-table-filter';
		}else{
			$attr['data']		= $this->generate_data_attr($args);
			$attr['class'][]	= in_array($this->response, ['move', 'move_item']) ? 'list-table-move-action' : 'list-table-action';
		}

		if(!empty($args['dashicon'])){
			$title	= wpjam_tag('span', ['dashicons dashicons-'.$args['dashicon']]);
		}elseif(!is_blank($args['title'])){
			$title	= $args['title'];
		}elseif($this->dashicon && empty($args['subtitle']) && ($layout == 'calendar' || !$this->title)){
			$title	= wpjam_tag('span', ['dashicons dashicons-'.$this->dashicon]);
		}else{
			$title	= $this->title ?: $this->page_title;
		}

		$row_action	= wpjam_wrap_tag($title, $tag, $attr); 

		return $args['wrap'] ? sprintf($args['wrap'], esc_attr($this->name), $row_action) : $row_action;
	}

	public function get_submit_text($id, $name=null){
		if($name){
			$submit_text	= $this->get_submit_text($id);

			return $submit_text[$name] ?? [];
		}

		$submit_text	= $this->submit_text;

		if(isset($submit_text)){
			if(!$submit_text){
				return [];
			}

			if(is_callable($submit_text)){
				$submit_text	= wpjam_try($submit_text, $id, $this->name);
			}
		}else{
			$submit_text = wp_strip_all_tags($this->title) ?: $this->page_title;
		}

		if(!is_array($submit_text)){
			$submit_text	= [$this->name=>$submit_text];
		}

		foreach($submit_text as &$item){
			$item	= is_array($item) ? $item : ['text'=>$item];
			$item	= wp_parse_args($item, ['response'=>$this->response, 'class'=>'primary']);
		}

		return $submit_text;
	}

	public function callback($id=0, $data=null, $submit_name=''){
		$bulk		= is_array($id); 
		$cb_key		= $bulk ? 'bulk_callback' : 'callback'; 
		$callback	= $this->$cb_key;

		if($submit_name){
			$submit_text	= $this->get_submit_text($id, $submit_name);

			if(!empty($submit_text[$cb_key])){
				$callback	= $submit_text[$cb_key];
			}
		}

		if($bulk){
			if(!$callback && method_exists($this->model, 'bulk_'.$this->name)){
				$callback	= [$this->model, 'bulk_'.$this->name];
			}

			if($callback){
				if(!is_callable($callback)){
					throw new WPJAM_Exception('', 'invalid_callback');
				}

				$result	= wpjam_try($callback, $id, $data, $this->name, $submit_name);

				if(is_null($result)){
					throw new WPJAM_Exception(['没有正确返回'], 'invalid_callback');
				}

				return $result;
			}else{
				$return	= wpjam_array();

				foreach($id as $_id){
					$result	= $this->callback($_id, $data, $submit_name);

					if(is_array($result)){
						$return->merge($result);
					}
				}

				return $return->get(null) ?: $result;
			}
		}else{
			if($callback){
				if(!is_callable($callback)){
					throw new WPJAM_Exception('', 'invalid_callback');
				}

				$args	= [$id, $data];

				if($this->overall){
					$args	= [$data];
				}elseif($this->response == 'add' && !is_null($data)){
					$reflection	= is_array($callback) ? new ReflectionMethod(...$callback) : new ReflectionFunction($callback);
					$parameters	= $reflection->getParameters();

					if(count($parameters) == 1 || $parameters[0]->name == 'data'){
						$args	= [$data];
					}
				}

				$args	= array_merge($args, [$this->name, $submit_name]);
				$result	= wpjam_try($callback, ...$args);

				if(is_null($result)){
					throw new WPJAM_Exception(['没有正确返回'], 'invalid_callback');
				}

				return $result;
			}else{
				if($this->name == 'add'){
					$method	= 'insert';
				}elseif($this->name == 'edit'){
					$method	= 'update';
				}elseif($this->name == 'duplicate'){
					if(!is_null($data)){
						$method	= 'insert';
					}
				}elseif(in_array($this->name, ['up', 'down'], true)){
					$method	= 'move';
				}else{
					$method	= $this->name;
				}

				$args		= [];
				$callback	= [$this->model, $method];

				if($this->overall || $method == 'insert' || $this->response == 'add'){
					if(is_callable([$this->model, $method])){
						$args	= [$data];
					}
				}else{
					if(method_exists($this->model, $method)){
						$callback 	= wpjam_parse_method($this->model, $method, $is_static, $id);
						$args		= $is_static ? [$id, $data] : [$data];
					}elseif(!$this->meta_type && method_exists($this->model, '__callStatic')){
						$args		= [$id, $data];
					}else{
						$meta_type	= $this->get_meta_type();

						if($meta_type){
							$args 		= [$meta_type, $id, $data, $this->get_defaults($id)];
							$callback	= 'wpjam_admin_update_metadata';
						}
					}
				}

				if(!$args){
					throw new WPJAM_Exception([$this->name, '回调函数'], 'undefined_method');
				}

				$result	= wpjam_try($callback, ...$args);

				return is_null($result) ? true : $result;
			}
		}
	}

	public function generate_data_attr($args=[]){
		$args	= wp_parse_args($args, ['type'=>'button', 'id'=>0, 'data'=>[], 'bulk'=>false, 'ids'=>[]]);
		$data	= $this->data ?: [];
		$attr	= [
			'action'	=> $this->name,
			'nonce'		=> $this->create_nonce($args['id'], $args['bulk']),
			'data'		=> wp_parse_args($args['data'], $data),
		];

		if($args['bulk']){
			$attr['bulk']	= $this->bulk;
			$attr['ids']	= $args['ids'];
			$attr['data']	= $attr['data'] ? http_build_query($attr['data']) : '';
			$attr['title']	= $this->title;
		}else{
			$attr['id']		= $args['id'];
		}

		if($args['type'] == 'button'){
			$attr['direct']		= $this->direct;
			$attr['confirm']	= $this->confirm;
		}else{
			$attr['next']		= $this->next;
		}	
		
		return $attr;
	}

	protected function show_if($id){
		try{
			$show_if	= $this->show_if;

			if($show_if){
				if(is_callable($show_if)){
					return wpjam_try($show_if, $id, $this->name);
				}elseif(is_array($show_if) && $id){
					return wpjam_show_if($this->get_data($id), $show_if);
				}
			}

			return true;
		}catch(Exception $e){
			return false;
		}
	}

	public function current_user_can($id=0){
		if($this->capability == 'read'){
			return true;
		}

		foreach((array)$id as $_id){
			if(!current_user_can($this->capability, $_id, $this->name)){
				return false;
			}
		}

		return true;
	}

	public static function autoload(){
		self::register_config(['data_type'=>true, 'orderby'=>'order']);
	}
}

class WPJAM_List_Table_Column extends WPJAM_Register{
	public function parse_args(){
		return wp_parse_args($this->args, ['type'=>'view', 'show_admin_column'=>true]);
	}

	public function callback($id, $value){
		$callback	= $this->column_callback ?: $this->callback;

		if($callback && is_callable($callback)){
			return wpjam_call($callback, $id, $this->name, $value);
		}

		if($this->options){
			$value	= (array)$value;
			
			foreach($value as &$item){
				$options	= wpjam_parse_options($this->options);
				$option		= $options[$item] ?? $item;
				$item		= $this->filterable ? wpjam_get_list_table_filter_link([$this->name=>$item], $option) : $option;
			}

			return implode(',', $value);
		}else{
			return $this->filterable ? wpjam_get_list_table_filter_link([$this->name=>$value], $value) : $value;
		}
	}

	public function add_style(){
		if($this->column_style){
			$column_style	= $this->column_style;

			if(!preg_match('/\{([^\}]*)\}/', $column_style)){
				$column_style	= '.manage-column.column-'.$this->name.'{ '.$column_style.' }';
			}

			wp_add_inline_style('list-tables', $column_style);
		}
	}

	protected static function get_config($key){
		if(in_array($key, ['data_type', 'orderby'])){
			return true;
		}
	}
}