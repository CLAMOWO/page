<?php

class widget_ui_comments extends WP_Widget {

	function __construct(){
		parent::__construct( 'widget_ui_comments', 'DUX 最新评论', array( 'classname' => 'widget_ui_comments' ) );
	}

	function widget( $args, $instance ) {
		if ( _hui('kill_comment_s') ) return;
		extract( $args );

		$title   = apply_filters('widget_name', $instance['title']);
		$limit   = isset($instance['limit']) ? $instance['limit'] : 8;
		$outer   = isset($instance['outer']) ? $instance['outer'] : '1';
		$outpost = isset($instance['outpost']) ? $instance['outpost'] : '';

		echo $before_widget;
		echo $before_title.$title.$after_title; 
		echo '<ul>';
		echo mod_newcomments( $limit,$outpost,$outer );
		echo '</ul>';
		echo $after_widget;
	}

	function form($instance) {
		$defaults = array( 
			'title' => '最新评论', 
			'limit' => 8, 
			'outer' => '1',
			'outpost' => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

?>
		<p>
			<label>
				标题：
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
			</label>
		</p>
		<p>
			<label>
				显示数目：
				<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo $instance['limit']; ?>" />
			</label>
		</p>
		<p>
			<label>
				排除用户ID（多个ID之前使用空格隔开）：
				<input class="widefat" id="<?php echo $this->get_field_id('outer'); ?>" name="<?php echo $this->get_field_name('outer'); ?>" type="text" value="<?php echo $instance['outer']; ?>" />
			</label>
		</p>
		<p>
			<label>
				排除文章ID（多个ID之前使用空格隔开）：
				<input class="widefat" id="<?php echo $this->get_field_id('outpost'); ?>" name="<?php echo $this->get_field_name('outpost'); ?>" type="text" value="<?php echo $instance['outpost']; ?>" />
			</label>
		</p>

<?php
	}
}

function mod_newcomments( $limit,$outpost,$outer ){
	global $wpdb;

	$comments = get_comments(array(
		'author__not_in' => $outer ? explode(' ', trim($outer)) : '',
		'post__not_in'   => $outpost ? explode(' ', trim($outpost)) : '',
		'number'         => $limit,
		'status'         => 'approve',
		'type'           => 'comment',
	));
	foreach ($comments as $key => $comment) {
		echo '<li><a href="'.get_comment_link($comment->comment_ID).'" title="'.$comment->post_title.'上的评论">'._get_the_avatar($comment->user_id, $comment->comment_author_email).' <strong>'.$comment->comment_author.'</strong> '._get_time_ago( $comment->comment_date ).'说：<br>'.str_replace(' src=', ' data-original=', convert_smilies(strip_tags($comment->comment_content))).'</a></li>';
	}
};