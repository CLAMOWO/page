<?php 
	get_header(); 
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>
<section class="container">
	<div class="content-wrap">
	<div class="content">
		<?php 
			if( $paged<=1 && _hui('focusslide_s') ){ 
				_moloader('mo_slider', false);
				mo_slider('focusslide');
			} 
		?>
		<?php  
			if( $paged<=1 && ((!wp_is_mobile() && _hui('topics_at_home', 1)) || (wp_is_mobile() && _hui('topics_at_home_m', 1))) ){
				_moloader('mo_topics');
			}
		?>
		<?php 
			$pagedtext = ''; 
			if( $paged > 1 ){
				$pagedtext = ' <small>第'.$paged.'页</small>';
			}
		?>
		<?php  
			if( $paged<=1 && _hui('minicat_home_s') ){
				_moloader('mo_minicat');
			}
		?>
		<?php _the_ads($name='ads_index_01', $class='orbui-index orbui-index-01') ?>
		<?php if( _hui('index_list_title') ){ ?>
		<div class="title">
			<h3>
				<?php echo _hui('index_list_title') ?>
				<?php echo $pagedtext ?>
			</h3>
			<?php 
				if( _hui('index_list_title_r') ){
					echo '<div class="more">'._hui('index_list_title_r').'</div>';
				} 
			?>
		</div>
		<?php } ?>
		<?php 
			global $sticky_ids;
			$pagedefaultnums = get_option( 'posts_per_page', 10 );
			$pagenums        = $pagedefaultnums;
			$offset_nums     = 0;
			$sticky_nums     = 0;
			$sticky_ids      = get_option('sticky_posts');

			if( _hui('home_sticky_s') && $sticky_ids && _hui('home_sticky_n') && in_array(_hui('home_sticky_n'), array('1','2','3','4','5')) ){

				rsort( $sticky_ids );

	            $sticky_nums = count($sticky_ids);

	            if( $sticky_nums > _hui('home_sticky_n') ){
	                $sticky_nums = _hui('home_sticky_n');

	                $sticky_ids = array();
	                $args = array(
						'post__in'            => $sticky_ids,
						'posts_per_page'      => $sticky_nums,
						'ignore_sticky_posts' => 0
					);
					query_posts($args);
					while ( have_posts() ) : the_post(); 
						$sticky_ids[] = get_the_ID();
					endwhile; 
					wp_reset_query();

					$sticky_ids = array_slice($sticky_ids, 0, $sticky_nums);
	            }

				if( $paged <= 1 ){
					$args = array(
						'post__in'            => $sticky_ids,
						'posts_per_page'      => $sticky_nums,
						'ignore_sticky_posts' => 1
					);
					query_posts($args);
					get_template_part( 'excerpt' );
					wp_reset_query();

					$pagenums = $pagenums-$sticky_nums;
				}else{
					$offset_nums = $sticky_nums;
				}
			}


			$args = array(
				'post__not_in'        => array(),
				'posts_per_page'      => $pagenums,
				'paged'               => $paged,
				'ignore_sticky_posts' => 1
	        );

	        if( $offset_nums ){
	            $args['offset'] = $pagenums*($paged-1) - $offset_nums;
	        }

	        if( _hui('notinhome_post') ){
				$args['post__not_in'] = explode("\n", _hui('notinhome_post'));
			}

	        if( _hui('home_sticky_s') && $sticky_ids ){
	            $args['post__not_in'] = array_unique(array_merge($sticky_ids,$args['post__not_in']));
	        }

	        if( _hui('notinhome') ){
	            $pool = array();
	            foreach (_hui('notinhome') as $key => $value) {
	                if( $value ) $pool[] = $key;
	            }
	            if( $pool ) $args['cat'] = '-'.implode($pool, ',-');
	        }

			query_posts($args);
			get_template_part( 'excerpt' ); 
			if( _hui('home_sticky_s') && $sticky_ids ){
				$wp_query->max_num_pages = ceil( ($wp_query->found_posts+$sticky_nums) / $pagedefaultnums );
			}
			_moloader('mo_paging');
			wp_reset_query();
		?>
		<?php _the_ads($name='ads_index_02', $class='orbui-index orbui-index-02') ?>
	</div>
	</div>
	<?php get_sidebar(); ?>
</section>
<?php get_footer();