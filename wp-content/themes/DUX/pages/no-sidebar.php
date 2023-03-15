<?php 
/**
 * Template name: No sidebar page
 * Description:   A no sidebar page
 */

get_header();

?>
<div class="container container-no-sidebar">
	<div class="content">
		<?php while (have_posts()) : the_post(); ?>
		<header class="article-header">
			<h1 class="article-title"><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
		</header>
		<article class="article-content">
			<?php the_content(); ?>
		</article>
		<?php wp_link_pages('link_before=<span>&link_after=</span>&before=<div class="article-paging">&after=</div>&next_or_number=number'); ?>
		<?php endwhile;  ?>
		<?php comments_template('', true); ?>
	</div>
</div>

<?php

get_footer();