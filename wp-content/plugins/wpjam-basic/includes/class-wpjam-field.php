<?php
class WPJAM_Field extends WPJAM_Args{
	public const DATA_ATTRS	= ['key', 'data_type', 'query_args', 'max_items', 'min_items', 'unique_items', 'item_type', 'group', 'direction'];

	protected function __construct($args){
		$this->args	= $args;

		$this->_title		= $this->title.'「'.$this->key.'」';
		$this->_editable	= $this->show_admin_column !== 'only' && !$this->disabled && !$this->readonly;
		$this->show_in_rest	= $this->show_in_rest ?? $this->_editable;

		if($this->data_type){
			if(in_array($this->data_type, ['qq-video', 'qq_video'])) {
				$this->data_type	= 'video';
			}

			$object	= wpjam_get_data_type_object($this->data_type);

			if($object){
				$this->query_args	= $object->parse_query_args($this) ?: new StdClass;
			}

			$this->set_object('data_type', $object);
		}

		$this->parse_name()->parse_args();
	}

	public function __get($key){
		if($key == 'field'){
			return $this->get_args();
		}elseif($key == '_top_name'){
			return $this->_names[0];
		}elseif($key == '_sub_name'){
			return array_value_last($this->_names);
		}

		$value	= $this->offsetGet($key);

		if(in_array($key, ['min', 'max', 'minlength', 'maxlength', 'max_items', 'min_items'])){
			return is_numeric($value) ? $value : null;
		}

		return $value;
	}

	public function __call($method, $args){
		if($method == 'has_sub_name'){
			return preg_match('/\[([^\]]*)\]/', $this->name);
		}elseif($method == 'prepend_name'){
			return $this->update_arg('name', $args[0].'['.implode('][', $this->_names).']');
		}elseif($method == 'parse_name'){
			$names	= $this->has_sub_name() ? wpjam_parse_name($this->name) : [$this->name];

			return $this->update_arg('_names', $names);
		}elseif($method == 'get_schema'){
			return $this->_schema;
		}elseif($method == 'get_default'){
			return $this->get_arg('show_in_rest.default', $this->value);
		}elseif($method == 'get_defaults'){
			return $this->pack($this->get_default());
		}elseif($method == 'get_show_if_keys'){
			$show_if	= $this->parse_show_if();

			return $show_if ? [$show_if->key] : [];
		}elseif($method == 'get_show_if_values'){
			// show_if 判断基于key，并且array类型的fieldset的key是 ${key}__{$sub_key}
			return [$this->key => $this->validate($args[0], false, true)];
		}elseif(strpos($method, '_by_')){
			list($method, $type)	= explode('_by_', $method);

			$object	= $this->get_object($type);

			if($object){
				if($type == 'data_type'){
					$args[]	= array_merge((array)$this->query_args, ['title'=>$this->_title]);
				}

				return wpjam_try([$object, $method], ...$args);
			}

			return $type == 'data_type' ? $args[0] : null;
		}
	}

	public function __toString(){
		return $this->render();
	}

	public function get_object($type){
		return $this->{'_'.$type.'_object'};
	}

	protected function set_object($type, $object){
		return $this->update_arg('_'.$type.'_object', $object);
	}

	protected function parse_args(){
		$schema	= ['type'=>'string'];

		if($this->type == 'email'){
			$schema['format']	= 'email';
		}elseif($this->type == 'color'){
			$schema['format']	= 'hex-color';
		}elseif($this->type == 'url'){
			$schema['format']	= 'uri';
		}elseif(in_array($this->type, ['number', 'range'])){
			if($this->step && ($this->step == 'any' || strpos($this->step, '.'))){
				$schema['type']	= 'number';
			}else{
				$schema['type']	= 'integer';
			}
		}elseif($this->type == 'timestamp'){
			$schema['type']	= 'integer';
		}elseif($this->type == 'checkbox'){
			$schema['type']	= 'boolean';
		}

		if($this->required){
			$schema['required']	= true;
		}

		$this->set_schema($schema);
	}

	protected function set_schema($schema){
		$map	= [];

		if($schema['type'] == 'string'){
			$map	= [
				'minlength'	=> 'minLength',
				'maxlength'	=> 'maxLength',
				'pattern'	=> 'pattern',
			];
		}elseif(in_array($schema['type'], ['number', 'integer'])){
			$map	= [
				'min'	=> 'minimum',
				'max'	=> 'maximum',
			];

			if($this->step && $this->step != 'any' && strpos($this->step, '.') === false){	// 浮点数不能求余数
				$schema['multipleOf']	= $this->step;
			}
		}elseif($schema['type'] == 'array'){
			$map	= [
				'max_items'		=> 'maxItems',
				'min_items'		=> 'minItems',
				'unique_items'	=> 'uniqueItems',
			];

			if($this->required){
				$schema['minItems']	= 1;
			}
		}

		foreach($map as $field_attr => $schema_attr){
			if(isset($this->$field_attr)){
				$schema[$schema_attr]	= $this->$field_attr;
			}
		}

		$_schema	= $this->get_arg('show_in_rest.schema');
		$_type		= $this->get_arg('show_in_rest.type');

		if(is_array($_schema)){
			$schema	= merge_deep($schema, $_schema);
		}

		if(!is_null($_type)){
			$schema['type']	= $_type;
		}

		return $this->update_arg('_schema', $this->parse_schema($schema));
	}

	protected function parse_schema($schema){
		$type	= $schema['type'] ?? '';

		if($type != 'object'){
			unset($schema['properties']);
		}elseif($type != 'array'){
			unset($schema['items']);
		}

		if(isset($schema['enum'])){
			if($type == 'integer'){
				$schema['enum']	= array_map('intval', $schema['enum']);
			}elseif($type == 'number'){
				$schema['enum']	= array_map('floatval', $schema['enum']);
			}else{
				$schema['enum']	= array_map('strval', $schema['enum']);
			}
		}elseif(isset($schema['properties'])){
			foreach($schema['properties'] as &$property){
				$property	= $this->parse_schema($property, false);
			}
		}elseif(isset($schema['items'])){
			$schema['items']	= $this->parse_schema($schema['items'], false);
		}

		return $schema;
	}

	protected function parse_show_if($show_if=null){
		$show_if	= $show_if ?? $this->show_if;
		$show_if	= wpjam_parse_show_if($show_if);

		if($show_if){
			foreach(['postfix', 'prefix'] as $fix){
				$show_if->$fix	= $show_if->$fix ?? $this->{'_'.$fix};

				if($show_if->$fix){
					$show_if->key	= wpjam_call('wpjam_add_'.$fix, $show_if->key, $show_if->$fix);
				}
			}
		}

		return $show_if;
	}

	public function show_if($values){
		$show_if	= $this->parse_show_if();

		return $show_if ? $show_if->compare($values) : true;
	}

	public function validate($value, $validate=true, $by_fields=false){
		if($by_fields){
			if(is_null($value)){
				$name	= $this->_top_name;
				$value	= [$name => wpjam_get_parameter($name, ['method'=>'POST'])];
			}

			$value	= $this->unpack($value);
		}

		try{
			$value	= $this->required($value, $validate);
			$value	= $this->validate_value($value, $validate);
			$value	= $this->required($value, $validate);

			if(is_array($value) || is_populated($value)){	// 空值只需用 required 验证
				$this->validate_from_schema($value);
			}

			return ($by_fields && $validate) ? $this->pack($value) : $value;
		}catch(WPJAM_Exception $e){
			if($validate){
				throw $e;
			}

			return null;
		}
	}

	protected function validate_from_schema($value){
		return wpjam_try('rest_validate_value_from_schema', $value, $this->_schema, $this->_title);
	}

	protected function required($value, $validate=false){
		if($validate && $this->required && is_blank($value)){
			throw new WPJAM_Exception([$this->_title], 'value_required');
		}

		return $value;
	}

	public function validate_value($value, $validate){
		$value	= $this->validate_value_by_data_type($value);

		if($this->type == 'timestamp'){
			$value	= $value ? wpjam_strtotime($value) : 0;
		}

		return $this->sanitize_value($value);
	}

	protected function sanitize_value($value, $type=null){
		$type	= $type ?? $this->get_arg('_schema.type');

		if(is_null($value) && !$this->required){
			$value	= false;
		}

		if($type == 'array'){
			$type	= $this->get_arg('_schema.items.type');
			$value	= $this->map($value, 'sanitize_value', $type);
		}elseif($type == 'integer'){
			if(is_numeric($value)){
				$value	= (int)$value;
			}
		}elseif($type == 'number'){
			if(is_numeric($value)){
				$value	= (float)$value;
			}
		}elseif($type == 'string'){
			if(is_scalar($value)){
				$value	= (string)$value;
			}
		}elseif($type == 'null'){
			if(is_blank($value)){
				$value	= null;
			}
		}elseif($type == 'boolean'){
			if(is_scalar($value)){
				$value	= rest_sanitize_boolean($value);
			}
		}

		return $value;
	}

	public function pack($value){
		foreach(array_reverse($this->_names) as $sub){
			$value	= [$sub => $value];
		}

		return $value;
	}

	public function unpack($data){
		return _wp_array_get($data, $this->_names);
	}

	protected function value_callback($args, $default=false){
		$value	= null;
		$name	= $this->_top_name;
		$cb_arg	= $args['id'] ?? $args;

		if($cb_arg !== false){
			if($this->value_callback && is_callable($this->value_callback)){
				$value	= wpjam_call($this->value_callback, $name, $cb_arg);
			}elseif(!empty($args['data']) && isset($args['data'][$name])){
				$value	= $args['data'][$name];
			}elseif(!empty($args['value_callback'])){
				if($name == 'meta_input' && !empty($args['meta_type']) && isset($this->_names[1])){
					$key	= $this->_names[1];
					$value	= wpjam_call($args['value_callback'], $key, $cb_arg);
					$value	= [$key=>$value];
				}else{
					$value	= wpjam_call($args['value_callback'], $name, $cb_arg);
				}
			}elseif(!empty($args['meta_type'])){
				$value	= wpjam_get_metadata($args['meta_type'], $cb_arg, $name);
			}
		}

		if(!is_null($value)){
			$value	= $this->unpack([$name=>$value]);
		}

		if($default && is_null($value)){
			$value	= $this->get_default();
		}

		$this->value	= $value;

		return $value;
	}

	public function prepare($args){
		$value	= $this->value_callback($args);
		$value	= rest_sanitize_value_from_schema($value, $this->_schema, $this->_title);
		$value	= $this->prepare_value($value);

		return $this->pack($value);
	}

	public function prepare_value($value){
		return $this->prepare_value_by_data_type($value, $this->parse_required);
	}

	public function render($args=[]){
		if(!empty($args['name'])){
			$this->prepend_name($args['name']);
		}

		$this->value_callback($args, true);
		$this->parse_class()->parse_description();

		return apply_filters('wpjam_field_html', (string)$this->render_component(), $this);
	}

	protected function render_component(){
		return $this->render_element();
	}

	protected function query_title($query_label, $query_class){
		$query_class[]	= 'query-title';
		$query_class[]	= 'query-title-'.$this->key;
		$query_class	= array_diff($query_class, ['field-key-'.$this->key]);

		if(!$query_label){
			$query_class[]	= 'hidden';
		}

		return wpjam_wrap_tag($query_label, 'span', 'query-text')->before(self::get_icon('dismiss'))->wrap('span', $query_class);
	}

	protected function render_element($args=[]){
		$this->archive()->update_args($args);

		$value	= $this->value;
		$type	= $this->type;

		if($type == 'checkbox'){
			if(!$this->options){
				$this->checked	= $value == 1;

				$value	= 1;
			}
		}elseif($type == 'color'){
			$type	= 'text';

			$this->add_class('color');
		}elseif($type == 'timestamp'){
			$type	= 'datetime-local';
			$value	= $value ? wpjam_date('Y-m-d\TH:i', $value) : '';
		}

		$query_label	= '';

		if($this->get_object('data_type')){
			$query_class	= $this->class;
			$query_label	= $this->query_label_by_data_type($value) ?: '';

			if($query_label){
				$this->add_class('hidden');
			}

			$this->add_class('autocomplete');

			$query_label	= $this->query_title($query_label, $query_class);
		}

		$attr		= array_merge($this->parse_attr(), ['type'=>$type, 'value'=>$value]);
		$element	= wpjam_tag('input', $attr)->after($query_label);

		if(($this->_label_attr || $this->description) && $type != 'hidden'){
			$label_attr	= $this->_label_attr ?: [];
			$label_attr	= array_merge($label_attr, ['id'=>'label_'.$this->id, 'for'=>$this->id]);

			if($this->type == 'color'){
				$element->wrap('label', $label_attr)->after($this->description);
			}else{
				$element->after($this->description)->wrap('label', $label_attr);
			}
		}

		$this->restore();

		return $element;
	}

	public function add_class($class){
		$this->class	= $this->class ? wp_parse_list($this->class) : [];
		$this->class	= array_merge($this->class, wp_parse_list($class));

		return $this;
	}

	protected function parse_class(){
		if(is_null($this->class)){
			if($this->type == 'textarea'){
				$this->class	= ['large-text'];
			}elseif(in_array($this->type, ['text', 'password', 'url', 'image', 'file'], true)){
				$this->class	= ['regular-text'];
			}
		}

		return $this->add_class('field-key-'.$this->key);
	}

	protected function parse_description(){
		if($this->description){
			$tag	= $this->_description_tag;

			if(is_null($tag)){
				if($this->type == 'checkbox'){
					$tag	= '';
				}elseif(in_array($this->type, ['img', 'color', 'textarea', 'fieldset', 'uploader'])
					|| str_starts_with($this->type, 'mu-')
					|| array_intersect($this->class, ['large-text','regular-text'])
				){
					$tag	= 'p';
				}else{
					$tag	= 'span';
				}
			}

			$before	= $tag == 'p' ? '' : ($tag == 'span' ? '&ensp;' : '&thinsp;');

			$this->description	= wpjam_wrap_tag($this->description, $tag, 'description')->before($before);
		}

		$buttons	= $this->pull('buttons') ?: [];

		foreach($buttons as $btn_key => $button){
			$this->description	.= self::create($button, $btn_key);
		}

		return $this->description;
	}

	protected function parse_attr(){
		$this->data	= array_accessible($this->data) ? $this->data : [];

		foreach(self::DATA_ATTRS as $data_attr){
			if(is_populated($this->$data_attr)){
				$this->update_arg('data.'.$data_attr, $this->$data_attr);
			}
		}

		return array_except(array_except($this->get_args(), self::DATA_ATTRS), ['type', 'value', 'default', 'options', 'description', 'title', 'label', 'post_type', 'taxonomy', 'sep', 'fields', 'parse_required', 'show_if', 'show_in_rest', 'sortable_column', 'column_style', 'show_admin_column', 'wrap_class',]);
	}

	public function wrap($tag='div', $args=[], $wrap_by=''){
		$args	= wpjam_array($args);
		$class	= $args->pull('wrap_class');
		$class	= array_wrap($class);

		if($wrap_by){
			$class[]	= 'sub-field';

			if($this->type == 'fieldset'){
				$class[]	= 'has-sub-field';

				$this->group	= true;
			}
		}

		if($wrap_by == 'mu-fields'){
			$key	= $args->pull('key');
			$name	= $args->pull('name');
			$i		= $args->pull('i');
			$item	= $args->pull('item');

			$this->archive();

			if(!is_numeric($i) || is_null($item)){
				$this->delete_arg('required');
			}

			if($item && isset($item[$this->name])){
				$this->value	= $item[$this->name];
			}

			$this->_postfix	= $postfix = '__'.$i;
			$this->_prefix	= $prefix = $key.'__';
			$this->id		= $prefix.$this->id.$postfix;
			$this->key		= $prefix.$this->key.$postfix;
			$this->name		= $name.'['.$i.']'.'['.$this->name.']';

			$this->parse_args();
		}

		$html		= $this->render($args);
		$data		= [];
		$class[]	= $this->wrap_class;
		$show_if	= $this->parse_show_if();

		if($show_if){
			$data		= ['show_if'=>$show_if];
			$class[]	= 'show_if';
		}

		if($this->type != 'hidden'){
			$label	= $this->label ?? $this->title;
			$label	= $label ? wpjam_wrap_tag($label, 'label', [
				'for'	=> $this->id,
				'class'	=> $wrap_by ? 'sub-field-label' : ''
			]) : '';

			if($tag){
				$html	= wpjam_wrap_tag($html);

				if($wrap_by){
					$html	= $html->wrap('div', 'sub-field-detail');
				}

				if($tag == 'tr'){
					$label	= $label ? $label->wrap('th', ['scope'=>'row']) : '';
					$html	= $html->wrap('td', ['colspan'=>($label ? false : 2)]);
				}elseif($tag == 'p'){
					$label	.= $label ? wpjam_tag('br') : '';
				}

				$html->before($label)->wrap($tag, [
					'class'	=> $class,
					'data'	=> $data,
					'id'	=> $tag.'_'.esc_attr($this->id),
					'valign'=> $tag == 'tr' ? 'top' : false,
				]);
			}else{
				$html	= $label.$html;
			}
		}

		if($wrap_by == 'mu-fields'){
			$this->restore();
		}

		return $html;
	}

	public function callback($args=[]){
		return $this->render($args);
	}

	protected static function preprocess($args){
		$total	= array_pull($args, 'total');

		if($total && !isset($args['max_items'])){
			$args['max_items']	= $total;
		}

		$field	= ['type'=>'', 'id'=>'', 'name'=>'', 'options'=>[]];

		foreach($args as $attr => $value){
			if(is_numeric($attr)){
				$attr	= $value = strtolower(trim($value));

				if(!wpjam_is_bool_attr($attr)){
					continue;
				}
			}else{
				$attr	= strtolower(trim($attr));

				if(wpjam_is_bool_attr($attr)){
					if(!$value){
						continue;
					}

					$value	= $attr;
				}
			}

			$field[$attr]	= $value;
		}

		$field['options']	= $field['options'] ? wp_parse_args($field['options']) : [];
		$field['type']		= $field['type']	?: ($field['options'] ? 'select' : 'text');
		$field['id']		= $field['id']		?: $field['key'];
		$field['name']		= $field['name']	?: $field['key'];

		return $field;
	}

	public static function get_icon($name){
		$icon	= wpjam_tag();

		foreach(wp_parse_list($name) as $name){
			if($name == 'move'){
				$icon->after('span', ['dashicons', 'dashicons-menu']);
			}elseif($name == 'multiply'){
				$icon->after('span', ['dashicons', 'dashicons-no-alt']);
			}elseif($name == 'dismiss'){
				$icon->after('span', ['dashicons', 'dashicons-dismiss', 'init']);
			}elseif($name == 'del_btn'){
				$icon->after('a', ['button', 'del-item'], '删除');
			}elseif(in_array($name, ['del_item', 'del_img'])){
				$icon->after('a', ['dashicons', 'dashicons-no-alt', str_replace('_', '-', $name)]);
			}
		}

		return $icon;
	}

	public static function print_media_templates(){
		$tmpls	= [
			'mu-action'	=> self::get_icon('del_btn,move'),
			'mu-row'	=> self::get_icon('del_item,move'),
			'img'		=> '<img style="{{ data.img_style }}" src="{{ data.img_url }}{{ data.thumb_args }}" alt="" />',
			'mu-img'	=> '<img src="{{ data.img_url }}{{ data.thumb_args }}" /><input type="hidden" name="{{ data.name }}" value="{{ data.img_value }}" />',
			'mu-file'	=> '<input type="url" name="{{ data.name }}" class="regular-text" value="{{ data.img_url }}" />',
			'del-icon'	=> self::get_icon('del_item')
		];

		foreach($tmpls as $id => $tmpl){
			echo self::generate_tmpl($id, $tmpl);
		}

		echo wpjam_tag('div', ['id'=>'tb_modal']);
	}

	public static function generate_tmpl($id, $tmpl){
		return wpjam_wrap_tag($tmpl, 'script', ['type'=>'text/html', 'id'=>'tmpl-wpjam-'.$id]);
	}

	public static function create($args, $key=''){
		if(is_object($args)){
			return $args;
		}

		if(empty($args['key']) && $key){
			$args['key']	= $key;
		}

		if(is_numeric($args['key'])){
			trigger_error('Field 的 key「'.$args['key'].'」'.'不能为纯数字');
			return;
		}elseif(!$args['key']){
			trigger_error('Field 的 key 不能为空');
			return;
		}

		$field	= self::preprocess($args);
		$type	= $field['type'];

		if(($type == 'checkbox' && $field['options']) || in_array($type, ['select', 'radio'])){
			return new WPJAM_Options_Field($field);
		}elseif($type == 'fieldset'){
			return new WPJAM_Fieldset($field);
		}elseif($type == 'mu-fields'){
			return new WPJAM_MU_Fields_Field($field);
		}elseif($type == 'mu-text'){
			return new WPJAM_MU_Text_Field($field);
		}elseif($type == 'uploader'){
			return new WPJAM_Uploader_Field($field);
		}elseif(in_array($type, ['mu-img', 'mu-image', 'mu-file'], true)){
			return new WPJAM_MU_Image_Field($field);
		}elseif(in_array($type, ['img', 'image', 'file'], true)){
			return new WPJAM_Image_Field($field);
		}elseif(in_array($type, ['textarea', 'editor'])){
			return new WPJAM_Textarea_Field($field);
		}elseif(in_array($type, ['view', 'br', 'hr'], true)){
			return new WPJAM_View_Field($field);
		}else{
			return new WPJAM_Field($field);
		}
	}
}

class WPJAM_Options_Field extends WPJAM_Field{
	protected function parse_args(){
		$this->_values	= array_keys(wpjam_parse_options($this->options));
		$schema			= ['type'=>'string'];
		$custom_input	= $this->pull('custom_input');

		if($custom_input){
			$this->_description_tag	= 'p';

			$this->_custom	= '__custom';
			$custom_field	= is_array($custom_input) ? $custom_input : [];
			$custom_field	= self::create(wp_parse_args($custom_field, [
				'title'			=> is_string($custom_input) ? $custom_input : '其他',
				'placeholder'	=> '请输入其他选项',
				'id'			=> $this->id.$this->_custom.'_input',
				'key'			=> $this->key.$this->_custom.'_input',
				'type'			=> 'text',
				'class'			=> '',
				'required'		=> true,
				'data-wrap_id'	=> $this->type != 'select' ? $this->id.'_options' : '',
				'show_if'		=> ['key'=>$this->key, 'value'=>$this->_custom],
			]));

			$custom_field->_title	= $this->_title.'-「'.$custom_field->title.'」';

			$this->set_object('custom', $custom_field);
		}else{
			$schema['enum']	= $this->_values;
		}

		if($this->type != 'select'){
			$this->update_arg('data-wrap_id', $this->id.'_options');

			if($this->type == 'checkbox'){
				$schema	= ['type'=>'array', 'items'=>$schema];
			}
		}

		$this->set_schema($schema);
	}

	protected function parse_custom_value($value, $action='render'){
		$field	= $this->get_object('custom');

		if(!$field){
			return $value;
		}

		$custom	= null;

		if($this->type == 'checkbox'){
			$value	= $value ?: [];

			$value	= array_diff($value, [$this->_custom]);
			$diff	= array_diff($value, $this->_values);

			if($action == 'validate' && count($diff) > 1){
				throw new WPJAM_Exception($field->_title.'只能传递一个其他选项值', 'too_many_custom_value');
			}

			if($diff){
				$custom	= current($diff);
			}
		}else{
			if($value && !in_array($value, $this->_values)){
				$custom	= $value;
			}
		}

		if($action == 'render'){
			$field->value	= $custom;
		}elseif($action == 'validate'){
			if(isset($custom)){
				$arg	= $this->type == 'checkbox' ? '_schema.items.type' : '_schema.type';
				$type	= $this->get_arg($arg);

				$field->update_arg('_schema.type', $type);
				$field->validate($custom, true);
			}
		}

		return $value;
	}

	public function prepare_value($value){
		if($this->type == 'checkbox'){
			return $this->parse_custom_value($value, 'prepare');
		}

		return $value;
	}

	public function validate_value($value, $validate){
		$value	= $this->parse_custom_value($value, 'validate');

		return parent::validate_value($value, $validate);
	}

	protected function parse_option_title($opt_title, &$attr){
		$attr	= ['class'=>[], 'data'=>[]];

		if(is_array($opt_title)){
			$opt_arr	= $opt_title;
			$opt_title	= array_pull($opt_arr, 'title');

			foreach($opt_arr as $k => $v){
				if($k == 'show_if'){
					$show_if	= $this->parse_show_if($v);

					if($show_if){
						$attr['class'][]	= 'show_if';
						$attr['data']		+= ['show_if'=>$show_if];
					}
				}elseif($k == 'class'){
					$attr['class']		= array_merge($attr['class'], explode(' ', $v));
				}elseif(in_array($k, ['disabled', 'required'])){
					if($v){
						$attr[$k]	= $k;
					}
				}elseif($k == 'options'){
					$attr[$k]	= $v;
				}elseif(!is_array($v)){
					$attr['data'][$k]	= $v;
				}
			}
		}

		return $opt_title;
	}

	protected function render_component(){
		if($this->type == 'checkbox'){
			$this->name	.= '[]';

			if(!is_array($this->value) && !is_blank($this->value)){
				$this->value	= [$this->value];
			}
		}else{
			$this->value	= $this->value ?? current($this->_values);
		}

		$items	= $this->render_options();
		$custom	= $this->render_custom_field($items);

		if($this->type == 'select'){
			$component	= wpjam_wrap_tag(implode('', $items), 'select', $this->parse_attr())->after($custom);
		}else{
			$items[]	= $custom;
			$sep		= $this->sep ?? '&emsp;';
			$component	= wpjam_wrap_tag(implode($sep, $items), 'div', ['id'=>$this->id.'_options']);
		}

		return $component->after($this->description);
	}

	protected function render_options($options=null){
		$options	= $options ?? $this->options;
		$value		= $this->value;
		$items		= [];

		foreach($options as $opt_value => $opt_title){
			$opt_title	= $this->parse_option_title($opt_title, $attr);

			if($this->type == 'checkbox'){
				$checked	= is_array($value) && in_array($opt_value, $value);
			}else{
				$checked	= $value ? ($opt_value == $value) : !$opt_value;
			}

			if($this->type == 'select'){
				$sub_opts	= array_pull($attr, 'options');

				if(isset($sub_opts)){
					if($sub_opts){
						$sub_items	= $this->render_options($sub_opts);
						$items[]	= wpjam_wrap_tag(implode('', $sub_items), 'optgroup', array_merge($attr, ['label'=>$opt_title]));
					}
				}else{
					$items[]	= wpjam_wrap_tag($opt_title, 'option', array_merge($attr, ['value'=>$opt_value, 'selected'=>$checked]));
				}
			}else{
				$data		= array_pull($attr, 'data');
				$class		= array_pull($attr, 'class');
				$class[]	= $checked ? 'checked' : '';
				$items[]	= $this->render_element(array_merge($attr, [
					'required'		=> false,
					'id'			=> $this->id.'_'.$opt_value,
					'value'			=> $opt_value,
					'checked'		=> $checked,
					'description'	=> '&thinsp;'.$opt_title,
					'_label_attr'	=> ['data'=>$data, 'class'=>$class]
				]));
			}
		}

		return $items;
	}

	protected function render_custom_field(&$items){
		$field	= $this->get_object('custom');

		if(!$field){
			return '';
		}

		$value 		= $this->parse_custom_value($this->value, 'render');
		$checked	= !is_null($field->value);
		$opt_title	= $this->parse_option_title($field->pull('title'), $attr);
		$custom		= $field->update_arg('name', $this->name)->wrap('label');

		if($this->type == 'select'){
			$custom		= '&emsp;'.$custom;
			$items[]	= wpjam_wrap_tag($opt_title, 'option', array_merge($attr, ['value'=>$this->_custom, 'selected'=>$checked]));
		}else{
			$items[]	= $this->render_element([
				'required'		=> false,
				'id'			=> $this->id.$this->_custom,
				'value'			=> $this->_custom,
				'checked'		=> $checked,
				'description'	=> '&thinsp;'.$opt_title,
				'_label_attr'	=> $attr
			]);
		}

		return $custom;
	}
}

class WPJAM_FieldSet extends WPJAM_Field{
	public function parse_args(){
		$this->set_object('fields', WPJAM_Fields::create($this->fields, $this))->parse_fields();
		$this->set_schema($this->get_schema_by_fields());
	}

	public function parse_fields($parse_by=null){
		$parse_by	= $parse_by ?: $this;
		$prefix		= $parse_by->key.'__';

		foreach($this->get_objects_by_fields() as $object){
			if($this->fieldset_type == 'array'){
				$object->update_args([
					'_prefix'	=> $prefix.($object->_prefix ?: ''),
					'key'		=> $prefix.$object->key,
					'id'		=> $prefix.$object->id,
				])->prepend_name($parse_by->name)->parse_name();

				if($object->type == 'fieldset'){
					$object->parse_fields($parse_by);
				}
			}else{
				if(!isset($object->show_in_rest)){
					$object->show_in_rest	= $this->show_in_rest;
				}
			}
		}
	}

	public function validate_value($value, $validate){
		if($this->get_object('data_type')){
			return parent::validate_value($value, $validate);
		}

		return $this->validate_value_by_fields($value, $validate);
	}

	public function render($args=[]){
		$fields	= $this->render_by_fields($args);
		$data	= [];

		if($this->fieldset_type == 'array'){
			$data['key']	= $this->key;

			if($this->get_object('data_type')){
				$data['value']	= $this->render_value_by_data_type($this->value_callback($args, true)) ?: new StdClass;
			}
		}

		$class	= $this->group ? 'field-group ' : '';
		$class	.= 'fieldset';

		$this->add_class($class)->parse_description();

		$tag	= wpjam_wrap_tag($fields)->after($this->description);

		if($this->summary){
			$tag->before(wpjam_wrap_tag($this->summary, 'strong')->wrap('summary'))->wrap('details');
		}

		return $tag->wrap('div', ['data'=>$data, 'class'=>$this->class])->render();
	}
}

class WPJAM_Image_Field extends WPJAM_Field{
	protected function parse_args(){
		$schema	= ['type'=>'string', 'format'=>'uri'];

		if($this->type == 'img'){
			if($this->item_type != 'url'){
				$schema	= ['type'=>'integer'];
			}
		}

		$this->set_schema($schema);
	}

	public function prepare_value($value){
		return wpjam_get_thumbnail($value, $this->size);
	}

	protected function render_component(){
		if(!current_user_can('upload_files')){
			return '';
		}

		$data	= [
			'uploader_id'	=> 'uploader_'.$this->id,
			'item_type'		=> $this->type == 'image' ? 'image' : $this->item_type
		];

		if($this->type == 'img'){
			$data	= array_merge($data, ['img_style'=>'']);
			$size	= $this->pull('size');

			if($size){
				$size	= wpjam_parse_size($size);

				list($width, $height)	= wp_constrain_dimensions($size['width'], $size['height'], 600, 600);

				$data['img_style']	.= $width > 2 ? 'width:'.($width/2).'px;' : '';
				$data['img_style']	.= $height > 2 ? ' height:'.($height/2).'px;' : '';
			}

			$data['thumb_args']	= wpjam_get_thumbnail_args(($size ?: 400));

			$class		= ['wpjam-img'];
			$component	= wpjam_tag();

			if(!empty($this->value)){
				$img_url	= wpjam_get_thumbnail($this->value, $size);

				if($img_url){
					$class[]	= 'has-img';
					$component	= wpjam_tag('img', ['src'=>$img_url, 'style'=>$data['img_style']]);;
				}
			}

			if(!$this->readonly && !$this->disabled){
				wpjam_tag('span', ['wp-media-buttons-icon'])->after('添加图片')
				->wrap('button', 'button add_media')
				->wrap('div', 'wp-media-buttons')
				->before(self::get_icon('del_img'))->insert_after($component);
			}else{
				$class[]	= 'readonly';
			}

			$component->wrap('div', ['class'=>$class, 'data'=>$data]);

			if(!$this->readonly && !$this->disabled){
				$component->after($this->render_element(['type'=>'hidden']));
			}
		}else{
			$btn_name	= $this->type == 'image' ? '图片' : '文件';
			$component	= wpjam_wrap_tag('选择'.$btn_name, 'a', ['class'=>'button', 'data'=>$data])
			->before($this->render_element(['type'=>'url', 'description'=>'']))
			->wrap('div', 'wpjam-file');
		}

		return $component->after($this->description);
	}
}

class WPJAM_View_Field extends WPJAM_Field{
	protected function parse_args(){
		return $this->update_args([
			'_editable'		=> false,
			'show_in_rest'	=> false
		]);
	}

	public function value_callback($args, $default=false){
		if(!is_null($this->value)){
			return $this->value;
		}

		return parent::value_callback($args, $default);
	}

	protected function render_component(){
		if($this->type == 'hr'){
			return wpjam_tag('hr');
		}

		$options	= $this->options;

		if($options){
			$values	= $this->value ? [$this->value] : ['', 0];

			foreach($values as $v){
				if(isset($options[$v])){
					return $options[$v];
				}
			}
		}

		return $this->value;
	}
}

class WPJAM_Textarea_Field extends WPJAM_Field{
	protected function render_component(){
		if($this->type == 'textarea'){
			return $this->render_element();
		}

		$this->rows	= $this->rows ?: 12;
		$this->id	= 'editor_'.$this->id;
		$settings	= $this->pull('settings') ?: [];

		if(wp_doing_ajax()){
			$this->add_class(['editor', 'large-text'])->update_arg('data-settings', wp_parse_args($settings, [
				'tinymce'		=>[
					'wpautop'	=> true,
					'plugins'	=> 'charmap colorpicker compat3x directionality hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
					'toolbar1'	=> 'bold italic underline strikethrough | bullist numlist | blockquote hr | alignleft aligncenter alignright alignjustify | link unlink | wp_adv',
					'toolbar2'	=> 'formatselect forecolor backcolor | pastetext removeformat charmap | outdent indent | undo redo | wp_help'
				],
				'quicktags'		=> true,
				'mediaButtons'	=> true
			]));

			return $this->render_element();
		}else{
			$editor	= wpjam_ob_get_contents('wp_editor', $this->value, $this->id, ['textarea_name'=>$this->name, 'textarea_rows'=>$this->rows]);

			return wpjam_wrap_tag($editor, 'div', ['style'=>$this->style])->after($this->description);
		}
	}

	protected function render_element($args=[]){
		$value	= is_array($this->value) ? implode("\n", $this->value) : $this->value;
		$attr	= wp_parse_args($this->parse_attr(), ['rows'=>6, 'cols'=>50]);

		return wpjam_wrap_tag(esc_textarea($value), 'textarea', $attr)->after($this->description);
	}
}

class WPJAM_Uploader_Field extends WPJAM_Field{
	protected function render_component(){
		$mime_types	= $this->pull('mime_types') ?: ['title'=>'图片', 'extensions'=>'jpeg,jpg,gif,png'];
		$mime_types	= wp_is_numeric_array($mime_types) ? $mime_types : [$mime_types];
		$button_id	= 'plupload_button__'.$this->key;
		$container	= 'plupload_upload__'.$this->key;
		$plupload	= [
			'browse_button'		=> $button_id,
			'container'			=> $container,
			'file_data_name'	=> $this->key,
			'filters'			=> [
				'mime_types'	=> $mime_types,
				'max_file_size'	=> (wp_max_upload_size()?:0).'b'
			],
			'multipart_params'	=> [
				'_wpnonce'	=> wp_create_nonce('upload-'.$this->key),
				'action'	=> 'wpjam-upload',
				'file_name'	=> $this->key,
			]
		];

		$parts			= $this->value ? explode('/', $this->value) : [];
		$query_title 	= $parts ? array_pop($parts) : '';
		$component		= $this->query_title($query_title, $this->class)
		->before($this->render_element(['type'=>'hidden']))
		->before('input', ['type'=>'button', 'id'=>$button_id, 'class'=>'button', 'value'=>__('Select Files')]);

		$class	= ['hide-if-no-js', 'plupload'];
		$data	= ['key'=>$this->key, 'plupload'=>&$plupload];

		if($this->pull('drap_drop') && !wp_is_mobile()){
			$class[]	= 'drag-drop';
			$dd_id		= 'plupload_drag_drop__'.$this->key;
			$plupload	= array_merge($plupload, ['drop_element'=>$dd_id]);

			$component->wrap('p', 'drag-drop-buttons')
			->before('p', _x('or', 'Uploader: Drop files here - or - Select Files'))
			->before('p', ['drag-drop-info'], __('Drop files to upload'))
			->wrap('div', 'drag-drop-inside')
			->wrap('div', ['id'=>$dd_id, 'class'=>'plupload-drag-drop']);
		}

		return $component->after(wpjam_tag('div', ['percent'])->after('div', ['bar'])->wrap('div', ['progress', 'hidden']))
		->wrap('div', ['id'=>$container, 'class'=>$class, 'data'=>$data])
		->after($this->description);
	}
}

class WPJAM_MU_Field extends WPJAM_Field{
	public function validate_value($value, $validate){
		if($value){
			$value	= is_array($value) ? filter_deep($value, 'is_populated') : wpjam_json_decode($value);
		}

		if(empty($value) || is_wp_error($value)){
			return ($validate && $this->required) ? null : [];
		}

		return array_values($value);
	}

	protected function render_component(){
		$value	= $this->value ?: [];

		if(!is_blank($value)){
			if(is_array($value)){
				$value	= filter_deep($value, 'is_populated');
				$value	= array_values($value);
			}else{
				$value	= (array)$value;
			}
		}

		$this->_last	= count($value);

		if($this->max_items && count($value) >= $this->max_items){
			$this->_last	-=1;
		}else{
			$value[]		= null;
		}

		$items		= $this->render_items($value);
		$class		= [$this->type];
		$class[]	= ($this->readonly || $this->disabled) ? ' readonly' : '';
		$class[]	= $this->direction == 'row' ? 'field-group' : '';
		$data		= ['max_items'=>$this->max_items, 'direction'=>$this->direction];
		$attr		= ['id'=>$this->id, 'class'=>$class, 'data'=>$data];

		return wpjam_wrap_tag(implode("\n", $items), 'div', $attr)->after($this->description);
	}

	protected function render_items($value){
		return [];
	}

	protected function render_item($item, $i=0){
		if($item){
			if(!$this->readonly && !$this->disabled){
				$item	.= $this->render_button($i);
			}

			$class	= 'mu-item'.($this->group ? ' field-group' : '');
			$item	= wpjam_wrap_tag($item, 'div', $class);
		}

		return $item;
	}

	protected function new_item_button($data=[], $class='button'){
		$data['item_class']	='mu-item'.($this->group ? ' field-group' : '');
		$data['item']		= $this->_item;

		$text	= $this->_button_text ?? '添加'.($this->title ?: '选项');

		return wpjam_wrap_tag($text, 'a', ['class'=>'new-item '.$class, 'data'=>$data]);
	}

	protected function render_button($i=0){
		if($this->_last === $i){
			return $this->new_item_button();
		}

		$icon	= $this->direction == 'row' ? 'del_item' : 'del_btn';

		return self::get_icon($icon.',move');
	}
}

class WPJAM_MU_Text_Field extends WPJAM_MU_Field{
	protected function parse_args(){
		$item_field	= array_except($this->get_args(), ['required', 'description']);	// 提交时才验证

		$this->item_type	= $this->item_type ?: 'text';

		$this->set_object('item', self::create(array_merge($item_field, ['type'=>$this->item_type])));

		if($this->show_in_rest){
			$this->update_arg('show_in_rest', true)->set_schema(['type'=>'array', 'items'=>$this->get_schema_by_item()]);
		}
	}

	public function prepare_value($value){
		return $this->map($value, 'prepare_value_by_item');
	}

	public function validate_value($value, $validate){
		$value	= parent::validate_value($value, $validate);

		return $this->map($value, 'validate_value_by_item', $validate);
	}

	protected function render_items($value){
		$field	= $this->get_object('item');
		$items	= [];

		foreach($value as $i => $item){
			$field->parse_class()->update_args(['id'=>'', 'name'=>$this->name.'[]', 'value'=>$item]);

			if($this->_last == $i){
				$this->_item	= $field->value;	// 新增的默认值
			}

			$items[]	= $this->render_item($field->render_component(), $i);
		}

		return $items;
	}
}

class WPJAM_MU_Fields_Field extends WPJAM_MU_Field{
	protected function parse_args(){
		$this->set_object('fields', WPJAM_Fields::create($this->fields, $this));
		$this->set_schema(['type'=>'array', 'items'=>$this->get_schema_by_fields()]);
	}

	public function prepare_value($value){
		return $this->map($value, 'prepare_value_by_fields');
	}

	public function validate_value($value, $validate){
		$value	= parent::validate_value($value, $validate);

		return $this->map($value, 'validate_value_by_fields', $validate);
	}

	protected function validate_from_schema($value){
		$this->map($value, 'validate_by_fields');	// 报错信息可能不知道是哪个

		return parent::validate_from_schema($value);
	}

	protected function render_items($value){
		$args	= ['name'=>$this->name, 'key'=>$this->key];
		$items	= [];

		foreach($value as $i => $item){
			$item		= $this->render_by_fields(array_merge($args, ['i'=>$i, 'item'=>$item]));
			$items[]	= $this->render_item($item, $i);
		}

		$item		= $this->render_by_fields(array_merge($args, ['i'=>'{{ data.i }}']));
		$items[]	= self::generate_tmpl(md5($this->name), $item.$this->render_button('{{ data.i }}'));

		return $items;
	}

	protected function render_button($i=0){
		if(!is_numeric($i) || $this->_last === $i){
			return $this->new_item_button(['i'=>$i, 'tmpl_id'=>md5($this->name)]);
		}

		return self::get_icon('del_btn,move');
	}
}

class WPJAM_MU_Image_Field extends WPJAM_MU_Field{
	protected function parse_args(){
		$schema_items	= ['type'=>'string', 'format'=>'uri'];

		if($this->type == 'mu-img'){
			if($this->item_type != 'url'){
				$schema_items	= ['type'=>'integer'];
			}
		}else{
			$this->class	= $this->class ?? ['regular-text'];
		}

		$this->set_schema(['type'=>'array',	'items'=>$schema_items]);
	}

	public function prepare_value($value){
		if($value && is_array($value)){
			$value	= wpjam_map($value, 'wpjam_get_thumbnail', $this->size);
			$value	= array_filter($value);
		}

		return $value;
	}

	protected function render_items($value){
		if(!current_user_can('upload_files')){
			return '';
		}

		$items	= [];
		$args	= ['id'=>'', 'name'=>$this->name.'[]', 'description'=>''];

		foreach($value as $i => $img){
			if($this->type == 'mu-img'){
				$img_url	= $img ? wpjam_get_thumbnail($img) : '';

				if(!$img_url){
					continue;
				}

				$thumb	= wpjam_get_thumbnail($img, 200, 200);
				$item	= $this->render_element(array_merge($args, ['type'=>'hidden', 'value'=>$img]));
				$item	= wpjam_tag('img', ['src'=>$thumb])->wrap('a', ['href'=>$img_url, 'class'=>'wpjam-modal']).$item;
			}else{
				$item	= $this->render_element(array_merge($args, ['type'=>'url', 'value'=>$img]));
			}

			$items[]	= $this->render_item($item, $i);
		}

		if($this->type == 'mu-img' && !$this->readonly && !$this->disabled){
			$items[]	= $this->render_button(-1);
		}

		return $items;
	}

	protected function render_button($i=0){
		$data	= [
			'name'			=> $this->name.'[]',
			'item_type'		=> $this->type == 'mu-image' ? 'image' : $this->item_type,
			'uploader_id'	=> 'uploader_'.$this->id
		];

		if($this->type == 'mu-img'){
			if($i == -1){
				$data['thumb_args']	= wpjam_get_thumbnail_args([200,200]);
				$this->_button_text	= '';

				return $this->new_item_button($data, 'dashicons dashicons-plus-alt2');
			}

			return self::get_icon('del_item');
		}else{
			if($this->_last === $i){
				$data['title']	= $data['item_type'] == 'image' ? '选择图片' : '选择文件';

				$this->_button_text	= $data['title'].'[多选]';

				return $this->new_item_button($data);
			}

			return self::get_icon('del_btn,move');
		}
	}
}

class WPJAM_Fields{
	use WPJAM_Call_Trait;

	private $objects	= [];
	private $create_by	= null;

	private function __construct($objects, $create_by=null){
		$this->objects		= $objects;
		$this->create_by	= $create_by;
	}

	public function __toString(){
		return $this->render(['echo'=>false]);
	}

	public function	__invoke($args=[]){
		return $this->render(array_merge($args, ['echo'=>false]));
	}

	public function	__call($method, $args){
		if($method == 'get_objects'){
			return $this->objects;
		}elseif($method == 'get_show_if_values'){
			if(!$this->get_show_if_keys() && !$this->create_by){
				return [];
			}
		}elseif(in_array($method, ['prepare_value', 'validate_value'])){
			$item	= $args[0];
		}elseif($method == 'validate'){
			$values		= $args[0] ?? null;
			$if_values	= $args[1] ?? $this->get_show_if_values($values);
			$show		= $args[2] ?? true;
		}elseif($method == 'wrap'){
			$grouped	= [];
			$pre_group	= '';
		}

		$data	= wpjam_array();

		foreach($this->objects as $object){
			if(in_array($method, ['prepare_value', 'validate_value'])){
				$name	= $object->_sub_name;

				if(isset($item[$name])){
					$args[0]		= $item[$name];
					$data[$name]	= wpjam_try([$object, $method], ...$args);
				}
			}elseif($method == 'get_schema'){
				if($object->show_in_rest){
					$name			= $object->_sub_name;
					$data[$name]	= $object->get_schema();
				}
			}elseif($method == 'wrap'){
				if($object->show_admin_column === 'only'){
					continue;
				}

				if($this->create_by){
					$group	= $object->type == 'fieldset' ? '' : $object->group;

					if($group != $pre_group){
						$grouped	= implode('', $grouped);
						$data[]		= $pre_group ? wpjam_wrap_tag($grouped, 'div', 'field-group') : $grouped;
						$grouped	= [];
						$pre_group	= $group;
					}

					$grouped[]	= $object->wrap($args[0], $args[1], $this->create_by->type);
				}else{
					$data[]		= $object->wrap($args[0], $args[1]);
				}
			}elseif($method == 'validate'){
				if(!$object->_editable){
					continue;
				}
				
				$field_show	= $show ? $object->show_if($if_values) : false;

				if($object->type == 'fieldset' && $object->fieldset_type != 'array'){
					$value	= $object->validate_by_fields($values, $if_values, $field_show);
				}else{
					if(!$field_show){
						$value	= $if_values[$object->key] = null;	// 第一次获取的值都是经过 json schema validate 的，可能存在 show_if 的字段在后面
						$value	= $object->pack($value);
					}else{
						$value	= $object->validate($values, true, true);
					}
				}

				$data->merge($value);
			}else{
				if(in_array($method, ['get_defaults', 'get_show_if_values'])){
					if(!$object->_editable){
						continue;
					}
				}elseif($method == 'prepare'){
					if(!$object->show_in_rest){
						continue;
					}
				}

				if($object->type == 'fieldset'){
					$value	= wpjam_try([$object, $method.'_by_fields'], ...$args);

					if($method == 'get_show_if_keys'){
						$value	= array_merge($value, $object->get_show_if_keys());
					}
				}else{
					$value	= wpjam_try([$object, $method], ...$args);
				}

				$data->merge($value);
			}
		}

		$data	= $data->get(null);

		if($method == 'get_show_if_keys'){
			return array_unique($data);
		}elseif($method == 'get_schema'){
			return ['type'=>'object', 'properties'=>$data];
		}elseif($method == 'wrap'){
			if($this->create_by && $grouped){
				$grouped	= implode('', $grouped);
				$data[]		= $pre_group ? wpjam_wrap_tag($grouped, 'div', 'field-group') : $grouped;
			}

			return implode("\n", $data);
		}

		return $data;
	}

	public function render($args=[]){
		$args	= wpjam_array($args);

		if(!$this->create_by){
			$echo	= $args->pull('echo', true);
			$type	= $args->pull('fields_type', 'table');
			$tag	= $args['wrap_tag'] ?? null;

			if(is_null($tag)){
				$tag	= $type == 'table' ? 'tr' : ($type == 'list' ? 'li' : $type);
			}
		}else{
			$echo	= false;
			$type	= '';
			$tag	= 'div';
		}

		$html	= $this->wrap($tag, $args);

		if($type == 'table'){
			$html	= wpjam_wrap_tag($html, 'tbody')->wrap('table', ['cellspacing'=>0, 'class'=>'form-table']);
		}elseif($type == 'list'){
			$html	= wpjam_wrap_tag($html, 'ul');
		}

		if($echo){
			echo $html;
		}else{
			return (string)$html;
		}
	}

	public function callback($args=[]){
		return $this->render($args);
	}

	public static function create($fields, $create_by=''){
		if(is_object($fields)){
			return $fields;
		}elseif(is_array($fields)){
			$objects		= [];
			$is_property	= false;
			$prefix			= '';

			if($create_by){
				if($create_by->type == 'fieldset'){
					if($create_by->fieldset_type == 'array'){
						$is_property	= true;
					}else{
						if($create_by->prefix){
							$prefix	= $create_by->prefix;
							$prefix	= ($prefix === true ? $create_by->key : $prefix).'_';
						}
					}
				}elseif($create_by->type == 'mu-fields'){
					$is_property	= true;
				}
			}

			foreach($fields as $key => $field){
				$key	= $prefix.$key;
				$object	= WPJAM_Field::create($field, $key);

				if($object){
					if($is_property){
						if($object->has_sub_name()){
							trigger_error($create_by->_title.'子字段不允许[]模式:'.$object->name);

							continue;
						}

						if(($object->type == 'fieldset' && !$object->get_object('data_type')) || $object->type == 'mu-fields'){
							trigger_error($create_by->_title.'子字段不允许'.$object->type.':'.$object->name);

							continue;
						}
					}

					$objects[$key]	= $object;
				}
			}

			return new WPJAM_Fields($objects, $create_by);
		}
	}
}