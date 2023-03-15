<?php
add_action('widgets_init', 'unregister_d_widget');

function unregister_d_widget(){
    unregister_widget('WP_Widget_Search');
    unregister_widget('WP_Widget_Recent_Comments');
}

$widgets = array(
	'sticky',
	'statistics',
	'ads',
	'textads',
	'comments',
	'posts',
	'readers',
	'tags'
);

foreach ($widgets as $widget) {
	include 'widget-'.$widget.'.php';
}


add_action( 'widgets_init', 'widget_ui_loader' );
function widget_ui_loader() {
	global $widgets;
	foreach ($widgets as $widget) {
		register_widget( 'widget_ui_'.$widget );
	}
}






add_action('in_widget_form', 'hui_add_widget_option', 10, 3);
add_filter('widget_update_callback', 'hui_update_widget_option', 10, 3);
add_filter('dynamic_sidebar_params', 'hui_dynamic_sidebar_params', 10, 3 );

function hui_add_widget_option($widget, $return, $instance){
	$opt = isset($instance['onshowphone']) ? $instance['onshowphone'] : 0;
    echo '<p><input type="checkbox" id="' . $widget->get_field_id('onshowphone') . '" name="' . $widget->get_field_name('onshowphone') . '" value="1" ' . checked($opt, 1, false) . '/>';
    echo '<label for="' . $widget->get_field_id('onshowphone') . '">在手机端显示</label></p>';
}

function hui_update_widget_option($instance, $new_instance, $old_instance){
    if (isset($new_instance['onshowphone']) && $new_instance['onshowphone']) {
        $instance['onshowphone'] = 1;
    } else {
        $instance['onshowphone'] = false;
    }
    return $instance;
}

function hui_dynamic_sidebar_params( $params ) {
    global $wp_registered_widgets;
    $widget_id  = $params[0]['widget_id'];
    $widget_obj = $wp_registered_widgets[$widget_id];
    $widget_opt = get_option($widget_obj['callback'][0]->option_name);
    $widget_num = $widget_obj['params'][0]['number'];
 
    if ( isset($widget_opt[$widget_num]['onshowphone']) && !empty($widget_opt[$widget_num]['onshowphone']) )
        $params[0]['before_widget'] = preg_replace( '/class="/', "class=\"widget-on-phone ", $params[0]['before_widget'], 1 );

    return $params;
}