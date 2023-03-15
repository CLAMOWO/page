<?php
class WPJAM_Menu_Page{
	public static function add($name, $args=[]){
		$object = wpjam_get_items_object('menu_page');
		$parent	= array_pull($args, 'parent');
		$key	= $parent ?: $name;
		$args	= $parent ? ['subs' => [$name => $args]] : wp_parse_args($args, ['subs'=>[]]);
		$item	= $object->get_item($key);

		if($item){
			$subs	= $args['subs'] + $item['subs'];
			$args	= array_merge($item, $args, ['subs'=>$subs]);
		}

		$object->set_item($key, $args);
	}

	public static function get_builtin_parents(){
		if(is_network_admin()){
			return [
				'settings'	=> 'settings.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'users'		=> 'users.php',
				'sites'		=> 'sites.php',
			];
		}elseif(is_user_admin()){
			return [
				'dashboard'	=> 'index.php',
				'users'		=> 'profile.php',
			];
		}else{
			$builtin_parents	= [
				'dashboard'	=> 'index.php',
				'management'=> 'tools.php',
				'options'	=> 'options-general.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'posts'		=> 'edit.php',
				'media'		=> 'upload.php',
				'links'		=> 'link-manager.php',
				'pages'		=> 'edit.php?post_type=page',
				'comments'	=> 'edit-comments.php',
				'users'		=> current_user_can('edit_users') ? 'users.php' : 'profile.php',
			];

			foreach(get_post_types(['_builtin'=>false, 'show_ui'=>true]) as $ptype) {
				$builtin_parents[$ptype.'s'] = 'edit.php?post_type='.$ptype;
			}

			return $builtin_parents;
		}
	}

	protected static function sort_callback($sub1, $sub2){
		$pos1	= $sub1['position'] ?? null;
		$pos2	= $sub2['position'] ?? null;

		if(isset($pos1) && isset($pos2)){
			if($pos1 != $pos2){
				return $pos1 <=> $pos2;
			}
		}elseif(isset($pos1)){
			return 1;
		}elseif(isset($pos2)){
			return -1;
		}

		$order1	= $sub1['order'] ?? 10;
		$order2	= $sub2['order'] ?? 10;

		return $order2 <=> $order1;
	}

	public static function render($is_rendering=true){
		$builtin_parents	= self::get_builtin_parents();
		$menu_filter		= wpjam_get_admin_menu_hook('filter');
		$menu_pages			= apply_filters($menu_filter, wpjam_get_items('menu_page'));

		foreach($menu_pages as $menu_slug => $menu_page){
			if(isset($builtin_parents[$menu_slug])){
				$parent_slug	= $builtin_parents[$menu_slug];
			}else{
				$parent_slug	= $menu_slug;
				$menu_page		= self::parse($menu_slug, $menu_page, '', $is_rendering);
			}

			if($menu_page && !empty($menu_page['subs'])){
				uasort($menu_page['subs'], [self::class, 'sort_callback']);

				if($parent_slug	== $menu_slug){
					$sub_page			= $menu_page['subs'][$menu_slug] ?? $menu_page;
					$menu_page['subs']	= array_merge([$menu_slug=>$sub_page], $menu_page['subs']);
				}

				foreach($menu_page['subs'] as $sub_slug => $sub_page){
					$sub_page	= self::parse($sub_slug, $sub_page, $parent_slug, $is_rendering);

					if(!$is_rendering && $GLOBALS['plugin_page'] == $sub_slug){
						break 2;
					}
				}
			}

			if(!$is_rendering && $GLOBALS['plugin_page'] == $menu_slug){
				break;
			}
		}
	}

	public static function parse($menu_slug, $menu_page, $parent_slug='', $is_rendering=true){
		if(!$is_rendering && $GLOBALS['plugin_page'] != $menu_slug){
			return $menu_page;
		}

		if(is_numeric($menu_slug) || empty($menu_page['menu_title'])){
			return false;
		}

		if($parent_slug && strpos($parent_slug, '.php')){
			$admin_page	= $parent_slug;
			$network	= array_pull($menu_page, 'network', false);
		}else{
			$admin_page	= 'admin.php';
			$network	= array_pull($menu_page, 'network', true);
		}

		if(is_network_admin()){
			if(!$network){
				return false;
			}
		}else{
			if($network === 'only'){
				return false;
			}
		}

		$user 	= array_pull($menu_page, 'user', false);

		if(is_user_admin()){
			if(!$user){
				return false;
			}
		}else{
			if($user){
				return false;
			}
		}

		$menu_title	= $menu_page['menu_title'];
		$page_title	= $menu_page['page_title'] = $menu_page['page_title'] ?? $menu_title;
		$capability	= $menu_page['capability'] ?? 'manage_options';

		if(!empty($menu_page['map_meta_cap']) && is_callable($menu_page['map_meta_cap'])){
			wpjam_register_capability($capability, $menu_page['map_meta_cap']);
		}

		$menu_page['admin_url']	= $admin_url = add_query_arg(['page'=>$menu_slug], $admin_page);

		if(!empty($menu_page['query_args'])){
			$query_data		= wpjam_generate_query_data($menu_page['query_args']);
			$null_queries	= array_filter($query_data, 'is_null');

			if($null_queries){
				if($GLOBALS['plugin_page'] == $menu_slug){
					wp_die('「'.implode('」,「', array_keys($null_queries)).'」参数无法获取');
				}else{
					return $menu_page;
				}
			}

			$menu_page['query_data']	= $query_data;
			$menu_page['admin_url']		= $queried_url	= add_query_arg($query_data, $admin_url);

			if($is_rendering){
				wpjam_add_item('queried_menu', ['search'=>"<a href='".esc_url($admin_url)."'", 'replace'=>"<a href='".$queried_url."'"]);
			}
		}

		if($is_rendering){
			$args	= [$page_title, $menu_title, $capability, $menu_slug, [self::class, 'admin_page']];

			if($parent_slug){
				$callback	= 'add_submenu_page';

				array_unshift($args, $parent_slug);
			}else{
				$callback	= 'add_menu_page';
				$args[]		= $menu_page['icon'] ?? '';
			}

			$args[]	= $menu_page['position'] ?? null;

			$menu_page['page_hook']	= call_user_func_array($callback, $args);
		}

		if($GLOBALS['plugin_page'] == $menu_slug && ($parent_slug || ($parent_slug == '' && empty($menu_page['subs'])))){
			$GLOBALS['current_admin_url']	= is_network_admin() ? network_admin_url($menu_page['admin_url']) : admin_url($menu_page['admin_url']);

			wpjam_set_current_var('plugin_page', new WPJAM_Plugin_Page($menu_slug, $menu_page));
		}

		return $menu_page;
	}

	public static function filter_html($html){
		$queried	= wpjam_get_items('queried_menu');

		return str_replace(array_column($queried, 'search'), array_column($queried, 'replace'), $html);
	}

	public static function admin_page(){
		echo wpjam_tag('div', ['wrap'], wpjam_ob_get_contents([wpjam_get_current_var('plugin_page'), 'render']));
	}

	public static function load(){
		if(wpjam_get_items('queried_menu')){
			add_filter('wpjam_html', [self::class, 'filter_html']);
		}

		wpjam_get_current_var('plugin_page')->load();
	}
}

class WPJAM_Plugin_Page extends WPJAM_Register{
	protected function parse_args(){
		return array_merge($this->args, [
			'page_type'		=> 'page',
			'plugin_page'	=> $this->name,
			'load_arg'		=> '',
		]);
	}

	protected function include(){
		if(!$this->_included){
			$this->_included	= true;

			$key	= $this->page_type.'_file';
			$file	= $this->$key;

			if($file){
				foreach((array)$file as $_file){
					include $_file;
				}
			}
		}
	}

	public function load(){
		wpjam_plugin_page_load($this->plugin_page, $this->load_arg);

		// 一般 load_callback 优先于 load_file 执行
		// 如果 load_callback 不存在，尝试优先加载 load_file
		if($this->load_callback){
			$load_callback	= $this->load_callback;

			if(!is_callable($load_callback)){
				$this->include();
			}

			if(is_callable($load_callback)){
				call_user_func($load_callback, $this->name);
			}
		}

		$this->include();

		if($this->chart){
			WPJAM_Chart::init($this->chart);
		}

		if($this->editor){
			add_action('admin_footer', 'wp_enqueue_editor');
		}

		try{
			if($this->function == 'tab'){
				wpjam_try(['WPJAM_Tab_Page', 'load_current']);
			}else{
				$page_model	= 'WPJAM_Admin_Page';
				$page_name	= null;

				if(!$this->function){
					$this->function	= wpjam_get_filter_name($this->plugin_page, 'page');
				}elseif(is_string($this->function)){
					$function	= $this->function == 'list' ? 'list_table' : $this->function;

					if(in_array($function, ['option', 'list_table', 'form', 'dashboard'])){
						$page_model	= 'WPJAM_'.ucwords($function, '_').'_Page';
						$page_name	= $this->{$function.'_name'} ?: $this->plugin_page;
					}
				}

				$object	= wpjam_try([$page_model, 'create'], $page_name, $this);

				if(wp_doing_ajax()){
					return $object->load();
				}

				add_action('load-'.$this->page_hook, [$object, 'load']);

				$this->page_object	= $object;

				if($page_name){
					$this->page_title	= $object->title ?: $this->page_title;
					$this->subtitle		= $object->get_subtitle() ?: $this->subtitle;
					$this->summary		= $this->summary ?: $object->get_summary(); 
					$this->query_data	= $this->query_data ?: [];
					$this->query_data	+= $object->query_args ? wpjam_generate_query_data($object->query_args) : [];
				}
			}
		}catch(WPJAM_Exception $e){
			wpjam_admin_add_error($e->get_wp_error());
		}
	}

	public function render(){
		$page_title	= $this->page_title ?? $this->title;

		if($this->tab_page){
			echo wpjam_tag('h2', [], $page_title.$this->subtitle);
		}else{
			echo wpjam_tag('h1', ['wp-heading-inline'], $page_title)->after($this->subtitle)->after('hr', ['wp-header-end']);
		}

		$summary	= $this->summary;

		if(is_callable($summary)){
			$summary	= call_user_func($summary, $this->plugin_page, $this->load_arg);
		}elseif(is_array($summary)){
			$summ_arr	= $summary;
			$summary	= $summ_arr[0];

			if(!empty($summ_arr[1])){
				$summary	.= '，详细介绍请点击：'.wpjam_tag('a', ['href'=>$summ_arr[1], 'target'=>'_blank'], $this->menu_title);
			}
		}elseif(is_file($summary)){
			$summary	= wpjam_get_file_summary($summary);
		}

		$summary	.= wpjam_get_page_summary($this->page_type);

		echo $summary ? wpjam_wrap_tag($summary, 'p') : '';

		if($this->function == 'tab'){
			$callback	= wpjam_get_filter_name($this->plugin_page, 'page');

			if(is_callable($callback)){
				wpjam_call($callback);	// 所有 Tab 页面都执行的函数
			}

			WPJAM_Tab_Page::render_current();
		}else{
			$this->page_object->render();
		}
	}
}

class WPJAM_Tab_Page extends WPJAM_Plugin_Page{
	protected function parse_args(){
		return array_merge($this->args, [
			'page_type'	=> 'tab',
			'tab_page'	=> true,
			'load_arg'	=> $this->name,
		]);
	}

	public static function load_current(){
		$object	= wpjam_get_current_var('plugin_page');
		$tabs	= $object->tabs ?: [];
		$tabs	= apply_filters(wpjam_get_filter_name($object->name, 'tabs'), $tabs);

		foreach($tabs as $tab_name => $tab_args){
			self::register($tab_name, $tab_args);
		}

		if(wp_doing_ajax()){
			$current_tab	= wpjam_get_parameter('current_tab', ['method'=>'POST', 'sanitize_callback'=>'sanitize_key']);
		}else{
			$current_tab	= wpjam_get_parameter('tab', ['sanitize_callback'=>'sanitize_key']);
		}

		$tabs	= [];

		foreach(self::get_registereds() as $name => $tab){
			if(!$tab->plugin_page){
				$tab->plugin_page	= $object->name;
			}else{
				if($tab->plugin_page != $object->name){
					continue;
				}
			}

			if($tab->capability){
				if($tab->map_meta_cap && is_callable($tab->map_meta_cap)){
					wpjam_register_capability($tab->capability, $tab->map_meta_cap);
				}

				if(!current_user_can($tab->capability)){
					continue;
				}
			}

			if(!$current_tab){
				$current_tab	= $name;
			}

			if($tab->query_args){
				$query_data	= wpjam_generate_query_data($tab->query_args);

				if($null_queries = array_filter($query_data, 'is_null')){
					if($current_tab == $name){
						wp_die('「'.implode('」,「', array_keys($null_queries)).'」参数无法获取');
					}else{
						continue;
					}
				}else{
					if($current_tab == $name){
						$GLOBALS['current_admin_url']	= add_query_arg($query_data, $GLOBALS['current_admin_url']);
					}
				}

				$tab->query_data	= $query_data;
			}

			$tabs[$name]	= $tab;
		}

		if(!$tabs){
			return new WP_Error('error', 'Tabs 未设置');
		}

		$GLOBALS['current_tab']			= $current_tab;
		$GLOBALS['current_admin_url']	= $GLOBALS['current_admin_url'].'&tab='.$current_tab;

		$object->tabs	= $tabs;
		$tab_object		= $tabs[$current_tab] ?? null;

		if(!$tab_object){
			return new WP_Error('error', '无效的 Tab');
		}elseif(!$tab_object->function){
			return new WP_Error('error', 'Tab 未设置 function');
		}elseif(!$tab_object->function == 'tab'){
			return new WP_Error('error', 'Tab 不能嵌套 Tab');
		}

		$tab_object->page_hook	= $object->page_hook;

		wpjam_set_current_var('current_tab', $tab_object);

		$tab_object->load();
	}

	public static function render_current(){
		$plugin_page	= wpjam_get_current_var('plugin_page');
		$current_tab	= wpjam_get_current_var('current_tab');

		if(count($plugin_page->tabs) > 1){
			$tag	= wpjam_tag();

			foreach($plugin_page->tabs as $tab_name => $tab_object){
				$tab_title	= $tab_object->tab_title ?: $tab_object->title;
				$tab_url	= $plugin_page->admin_url.'&tab='.$tab_name;

				if($tab_object->query_data){
					$tab_url	= add_query_arg($tab_object->query_data, $tab_url);
				}

				$class	= ['nav-tab'];

				if($current_tab && $current_tab->name == $tab_name){
					$class[]	= 'nav-tab-active';
				}

				$tag->after('a', ['class'=>$class, 'href'=>$tab_url], $tab_title);
			}

			echo $tag->wrap('nav', ['nav-tab-wrapper', 'wp-clearfix']);
		}

		if($current_tab){
			$current_tab->render();
		}
	}

	protected static function get_config($key){
		if($key == 'orderby'){
			return true;
		}elseif($key == 'model'){
			return false;
		}
	}
}

class WPJAM_Admin_Page extends WPJAM_Args{
	public function __call($method, $args){
		if($this->object && method_exists($this->object, $method)){
			return call_user_func_array([$this->object, $method], $args);
		}elseif(in_array($method, ['get_subtitle', 'get_summary'])){
			$key	= wpjam_remove_prefix($method, 'get_');

			return $this->$key;
		}
	}

	public function __get($key){
		if(empty($this->args['object']) || in_array($key, ['object', 'tab_page'])){
			return parent::__get($key);
		}else{
			return $this->object->$key;
		}
	}

	public function load(){
	}

	public function render(){
		if($this->chart){
			WPJAM_Chart::form();
		}

		if(is_callable($this->function)){
			call_user_func($this->function);
		}
	}

	public static function create($name, $menu){
		if(!is_callable($menu->function)){
			return new WP_Error('invalid_menu_page', ['函数', $menu->function]);
		}

		return new self($menu->to_array());
	}
}

class WPJAM_Form_Page extends WPJAM_Admin_Page{
	public function render(){
		try{
			echo $this->get_form();
		}catch(WPJAM_Exception $e){
			wp_die($e->get_wp_error());
		}
	}

	public static function create($name, $menu){
		$object	= WPJAM_Page_Action::get($name);

		if(!$object){
			if(!$menu->callback){
				return new WP_Error('invalid_menu_page', ['Page Action', $name]);
			}

			$object	= WPJAM_Page_Action::register($name, $menu->to_array());
		}

		return new self(array_merge($menu->to_array(), ['object'=>$object]));
	}
}

class WPJAM_Option_Page extends WPJAM_Admin_Page{
	public function load(){
		if(wp_doing_ajax()){
			wpjam_add_admin_ajax('wpjam-option-action',	[$this, 'ajax_response']);
		}else{
			add_action('admin_action_update', [$this, 'register_settings']);

			if(isset($_POST['response_type'])) {
				$message	= $_POST['response_type'] == 'reset' ? '设置已重置。' : '设置已保存。';

				wpjam_admin_add_error($message);
			}

			$this->register_settings();
		}
	}

	// 部分代码拷贝自 do_settings_sections 和 do_settings_fields 函数
	public function render(){
		$sections	= $this->get_sections();
		$count		= count($sections);

		if(!$this->tab_page && $count > 1){
			echo '<div class="tabs">';

			$tag	= wpjam_tag();

			foreach($sections as $section_id => $section){
				$attr		= [['class'=>'nav-tab', 'href'=>'#tab_'.$section_id], ['id'=>'tab_title_'.$section_id, 'class'=>[]]];
				$show_if	= isset($section['show_if']) ? wpjam_parse_show_if($section['show_if']) : null;

				if($show_if){
					$attr[1]['data']	= ['show_if'=>$show_if];
					$attr[1]['class'][]	= 'show_if';
				}

				$tag->after(wpjam_tag('a', $attr[0], $section['title'])->wrap('li', $attr[1]));
			}

			echo $tag->wrap('ul')->wrap('h2', ['nav-tab-wrapper', 'wp-clearfix']);
		}

		echo '<form action="options.php" method="POST" id="wpjam_option">';

		settings_errors();

		settings_fields($this->option_group);

		foreach($sections as $section_id => $section){
			echo '<div id="tab_'.$section_id.'"'.'>';

			if($count > 1 && !empty($section['title'])){
				$h_tag	= $this->tab_page ? 'h3' : 'h2';

				echo wpjam_tag($h_tag, [], $section['title']);
			}

			if(!empty($section['callback'])) {
				call_user_func($section['callback'], $section);
			}

			if(!empty($section['summary'])) {
				echo wpautop($section['summary']);
			}

			wpjam_fields($section['fields'], [
				'name'				=> $this->option_type == 'array' ? $this->name : '',
				'value_callback'	=> [$this, 'value_callback'],
			]);

			echo '</div>';
		}

		if($count > 1){
			echo '</div>';
		}

		echo '<p class="submit">';

		echo get_submit_button('', 'primary', 'option_submit', false, ['data-action'=>'save']);

		if($this->reset){
			echo '&emsp;'.get_submit_button('重置选项', 'secondary', 'option_reset', false, ['data-action'=>'reset']);
		}

		echo '</p>';

		echo '</form>';
	}

	public static function create($name, $menu){
		$object	= WPJAM_Option_Setting::get($name);

		if(!$object){
			if($menu->model && method_exists($menu->model, 'register_option')){	// 舍弃 ing
				$object	= call_user_func([$menu->model, 'register_option'], $menu->delete_arg('model')->to_array());
			}else{
				if($menu->sections || $menu->fields){
					$args	= $menu->to_array();
				}else{
					$args	= apply_filters(wpjam_get_filter_name($name, 'setting'), []); // 舍弃 ing

					if(!$args){
						return new WP_Error('invalid_menu_page', ['Option', $name]);
					}
				}

				$object	= WPJAM_Option_Setting::create($name, $args);
			}
		}

		return new self(array_merge($menu->to_array(), ['object'=>$object]));
	}
}

class WPJAM_List_Table_Page extends WPJAM_Admin_Page{
	public function load(){
		if(wp_doing_ajax()){
			wpjam_add_admin_ajax('wpjam-list-table-action',	[$this, 'ajax_response']);
		}else{
			$result = wpjam_call([$this, 'prepare_items']);

			if(is_wp_error($result)){
				wpjam_admin_add_error($result);
			}
		}
	}

	public function render(){
		$layout		= $this->layout;
		$list_table	= $this->get_list_table();

		if($layout == 'left'){
			$list_table	= wpjam_tag('div', ['col-wrap', 'list-table'], $list_table)->wrap('div', ['id'=>'col-right']);
			$col_left	= wpjam_tag('div', ['col-wrap', 'left'], $this->get_col_left())->wrap('div', ['id'=>'col-left']);

			echo $list_table->before($col_left)->wrap('div', ['id'=>'col-container', 'class'=>'wp-clearfix']);
		}else{
			$layout_class	= $layout ? ' layout-'.$layout : '';

			echo wpjam_tag('div', ['list-table', $layout_class], $list_table);
		}
	}

	public static function create($name, $menu){
		$args	= wpjam_get_item('list_table', $name);

		if(!$args){
			if($menu->model){
				$args	= $menu->to_array();
			}else{
				$args	= apply_filters(wpjam_get_filter_name($name, 'list_table'), []);

				if(!$args){
					return new WP_Error('invalid_menu_page', ['List Table', $name]);
				}
			}
		}

		if(empty($args['model']) || !class_exists($args['model'])){
			return new WP_Error('invalid_menu_page', ['List Table 的 Model', $args['model']]);
		}

		foreach(['admin_head', 'admin_footer'] as $admin_hook){
			if(method_exists($args['model'], $admin_hook)){
				add_action($admin_hook,	[$args['model'], $admin_hook]);
			}
		}

		$args	= wp_parse_args($args, ['primary_key'=>'id', 'name'=>$name, 'singular'=>$name, 'plural'=>$name.'s', 'layout'=>'']);

		if($args['layout'] == 'left' || $args['layout'] == '2'){
			$args['layout']	= 'left';

			$object	= new WPJAM_Left_List_Table($args);
		}elseif($args['layout'] == 'calendar'){
			$object	= new WPJAM_Calendar_List_Table($args);
		}else{
			$object	= new WPJAM_List_Table($args);
		}

		return new self(array_merge($menu->to_array(), ['object'=>$object]));
	}
}

class WPJAM_Dashboard_Page extends WPJAM_Admin_Page{
	public function load(){
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		// wp_dashboard_setup();

		wp_enqueue_script('dashboard');

		if(wp_is_mobile()){
			wp_enqueue_script('jquery-touch-punch');
		}

		$widgets	= $this->widgets ?: [];
		$widgets	= array_merge($widgets, wpjam_get_items('dashboard_widget'));

		foreach($widgets as $widget_id => $widget){
			if(!isset($widget['dashboard']) || $widget['dashboard'] == $this->name){
				$title		= $widget['title'];
				$callback	= $widget['callback'] ?? wpjam_get_filter_name($widget_id, 'dashboard_widget_callback');
				$context	= $widget['context'] ?? 'normal';	// 位置，normal 左侧, side 右侧
				$priority	= $widget['priority'] ?? 'core';
				$args		= $widget['args'] ?? [];

				// 传递 screen_id 才能在中文的父菜单下，保证一致性。
				add_meta_box($widget_id, $title, $callback, get_current_screen()->id, $context, $priority, $args);
			}
		}
	}

	public function render(){
		$tag	= wpjam_tag('div', ['id'=>'dashboard-widgets-wrap'], wpjam_ob_get_contents('wp_dashboard'));

		if($this->welcome_panel && is_callable($this->welcome_panel)){
			$welcome_panel	= wpjam_ob_get_contents($this->welcome_panel, $this->name);

			$tag->before('div', ['id'=>'welcome-panel', 'class'=>'welcome-panel wpjam-welcome-panel'], $welcome_panel);
		}

		echo $tag;

	}

	public static function create($name, $menu){
		$args	= wpjam_get_item('dashboard', $name);

		if($args === null){
			if(!$menu->widgets){
				return new WP_Error('invalid_menu_page', ['Dashboard', $name]);
			}

			$args	= $menu->to_array();
		}

		return new self(array_merge($args, ['name'=>$name]));
	}
}