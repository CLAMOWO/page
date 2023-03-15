<?php

class widget_ui_ads extends WP_Widget {

	function __construct(){
		parent::__construct( 'widget_ui_ads', 'DUX 广告', array( 'classname' => 'widget_ui_orbui' ) );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_name', $instance['title']);
		$code = isset($instance['code']) ? $instance['code'] : '';
		$cat = (isset($instance['cat']) && trim($instance['cat'])) ? explode(' ', trim($instance['cat'])) : '';
		$nocat = isset($instance['nocat']) ? $instance['nocat'] : '';

		$show = false;
		if( !$cat ){
			$show = true;
		}elseif( !$nocat && (is_category($cat) || in_category($cat)) ){
			$show = true;
		}elseif( $nocat ){
			if( !is_category($cat) ){
				$show = true;
			}
			if( in_category($cat) ){
				$show = false;
			}else{
				$show = true;
			}
		}
		if( $show ){
			echo $before_widget . '<div class="item">'.$code.'</div>' . $after_widget;
		}
	}

	function form($instance) {
		$defaults = array( 
			'title' => '广告',
			'code' => '<a href="https://themebetter.com/theme/dux" target="_blank"><img src="https://themebetter.com/uploads/2016/05/tb_dux.jpg"></a>',
			'cat' => '',
			'nocat' => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<p>
			<label>
				广告名称：
				<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" class="widefat" />
			</label>
		</p>
		<p>
			<label>
				广告代码：
				<textarea id="<?php echo $this->get_field_id('code'); ?>" name="<?php echo $this->get_field_name('code'); ?>" class="widefat" rows="12" style="font-family:Courier New;"><?php echo $instance['code']; ?></textarea>
			</label>
		</p>
		<p>
			<label>
				限制以下分类目录及其下面的文章页显示：
				<a style="font-weight:bold;color:#f60;text-decoration:none;" href="javascript:;" title="格式：1 2 &nbsp;表示限制ID为1和2的分类目录，注意多个ID之间用空格隔开">？</a>
				<input style="width:100%;" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>" type="text" value="<?php echo $instance['cat']; ?>" size="24" />
			</label>
			<label>
				<input style="vertical-align:-3px;margin-right:4px;" class="checkbox" type="checkbox" <?php checked( $instance['nocat'], 'on' ); ?> id="<?php echo $this->get_field_id('nocat'); ?>" name="<?php echo $this->get_field_name('nocat'); ?>">反选以上分类目录
			</label>
		</p>
<?php
	}
}