<?php 
/**
 * Template name: Sidebar page
 * Description:   A page has sidebar
 */

get_header();

?>
<section class="container">
	<div class="content-wrap">
	<div class="content">
		<?php while (have_posts()) : the_post(); ?>
		<header class="article-header">
			<h1 class="article-title"><a href="<?php the_permalink() ?>"><?php the_title(); ?><?php echo get_the_subtitle() ?></a></h1>
		</header>
		<article class="article-content">
			<?php the_content(); ?>
		</article>
		<?php wp_link_pages('link_before=<span>&link_after=</span>&before=<div class="article-paging">&after=</div>&next_or_number=number'); ?>
		<?php 
		if( _hui('post_like_s') || _hui('post_rewards_s') ){ ?>
            <div class="post-actions">
            	<?php if( _hui('post_like_s') ){ ?><?php echo hui_get_post_like($class='post-like action action-like'); ?><?php } ?>
            	<?php if( _hui('post_rewards_s') ){ ?><a href="javascript:;" class="action action-rewards" data-event="rewards"><i class="fa fa-jpy"></i> <?php echo _hui('post_rewards_text', '打赏') ?></a><?php } ?>
            </div>
        <?php } ?>
		<?php endwhile; ?>

		<?php if( !wp_is_mobile() || (!_hui('m_post_share_s') && wp_is_mobile()) ){ ?>
			<div class="action-share"><?php _moloader('mo_share'); ?></div>
		<?php } ?>

		<div class="article-tags"><?php the_tags('标签：','',''); ?></div>
		
		<?php comments_template('', true); ?>
	</div>
	</div>
	<?php get_sidebar(); ?>
</section>

<?php

get_footer();