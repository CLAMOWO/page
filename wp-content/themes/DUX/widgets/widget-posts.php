<?php

class widget_ui_posts extends WP_Widget {

	function __construct(){
		parent::__construct( 'widget_ui_posts', 'DUX 聚合文章', array( 'classname' => 'widget_ui_posts' ) );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title   = apply_filters('widget_name', $instance['title']);
		$limit   = isset($instance['limit']) ? $instance['limit'] : 6;
		$days    = isset($instance['days']) ? $instance['days'] : '';
		$cat     = isset($instance['cat']) ? $instance['cat'] : '';
		$orderby = isset($instance['orderby']) ? $instance['orderby'] : 'comment_count';
		$img     = isset($instance['img']) ? $instance['img'] : '';
		$comn    = isset($instance['comn']) ? $instance['comn'] : '';

		$style='';
		if( !$img ) $style = ' class="nopic"';
		echo $before_widget;
		echo $before_title.$title.$after_title; 
		echo '<ul'.$style.'>';
			$args = array(
				'cat'              => $cat,
				'order'            => 'DESC',
				'showposts'        => $limit,
				'ignore_sticky_posts' => 1
			);

			if( $orderby !== 'views' ){
				$args['orderby'] = $orderby;
			}else{
				$args['orderby'] = 'meta_value_num';
				$args['meta_query'] = array(
		            array(
						'key'   => 'views',
						'order' => 'DESC'
		            )
		        );
			}

			global $hui_widget_post_filter_where_days;
			$hui_widget_post_filter_where_days = $days;
			if( $days ){
				add_filter('posts_where', 'hui_widget_post_filter_where');
			}
			query_posts($args);
			while (have_posts()) : the_post(); 
				$pic = _get_post_thumbnail();
				$noimg = strstr($pic, 'data-thumb="default"')?1:0;

				echo '<li'. ($noimg?' class="noimg"':'') .'>';
					echo '<a'. _post_target_blank() .' href="'. get_the_permalink() .'">';
						if( $img && !$noimg ){
							echo '<span class="thumbnail">'. $pic .'</span>';
						}
						echo '<span class="text">'. get_the_title() . get_the_subtitle() .'</span>';
						echo '<span class="muted">'. get_the_time('Y-m-d') .'</span>';
						if( !_hui('kill_comment_s') && $comn ){ 
							echo '<span class="muted">评论('. get_comments_number('0', '1', '%') .')</span>';
						}
					echo '</a>';
				echo '</li>';

		    endwhile;
		    wp_reset_query();
		echo '</ul>';
		echo $after_widget;
	}

	function form( $instance ) {
		$defaults = array( 
			'title'   => '热门文章', 
			'limit'   => 6, 
			'cat'     => '', 
			'orderby' => 'comment_count', 
			'img'     => '',
			'comn'    => '',
			'days'    => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<p>
			<label>
				标题：
				<input style="width:100%;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
			</label>
		</p>
		<p>
			<label>
				排序：
				<select style="width:100%;" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" style="width:100%;">
					<option value="comment_count" <?php selected('comment_count', $instance['orderby']); ?>>评论数</option>
					<option value="views" <?php selected('views', $instance['orderby']); ?>>浏览量</option>
					<option value="date" <?php selected('date', $instance['orderby']); ?>>发布时间</option>
					<option value="rand" <?php selected('rand', $instance['orderby']); ?>>随机</option>
				</select>
			</label>
		</p>
		<p>
			<label>
				分类限制：
				<a style="font-weight:bold;color:#f60;text-decoration:none;" href="javascript:;" title="格式：1,2 &nbsp;表限制ID为1,2分类的文章&#13;格式：-1,-2 &nbsp;表排除分类ID为1,2的文章&#13;也可直接写1或者-1；注意逗号须是英文的">？</a>
				<input style="width:100%;" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>" type="text" value="<?php echo $instance['cat']; ?>" size="24" />
			</label>
		</p>
		<p>
			<label>
				限制多少天内发布的文章：
				<input style="width:100%;" id="<?php echo $this->get_field_id('days'); ?>" name="<?php echo $this->get_field_name('days'); ?>" type="number" value="<?php echo $instance['days']; ?>" size="24" />
			</label>
		</p>
		<p>
			<label>
				显示数目：
				<input style="width:100%;" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo $instance['limit']; ?>" size="24" />
			</label>
		</p>
		<p>
			<label>
				<input style="vertical-align:-3px;margin-right:4px;" class="checkbox" type="checkbox" <?php checked( $instance['img'], 'on' ); ?> id="<?php echo $this->get_field_id('img'); ?>" name="<?php echo $this->get_field_name('img'); ?>">显示图片
			</label>
		</p>
		<p>
			<label>
				<input style="vertical-align:-3px;margin-right:4px;" class="checkbox" type="checkbox" <?php checked( $instance['comn'], 'on' ); ?> id="<?php echo $this->get_field_id('comn'); ?>" name="<?php echo $this->get_field_name('comn'); ?>">显示评论数
			</label>
		</p>
		
	<?php
	}
}

function hui_widget_post_filter_where($where = '') {
	global $hui_widget_post_filter_where_days;
	$where .= " AND post_date > '" . date('Y-m-d', strtotime('-'. $hui_widget_post_filter_where_days .' days')) . "'";
	return $where;
}
