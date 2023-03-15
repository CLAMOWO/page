<?php 
/**
 * Template name: Blank
 * Description:   A blank content page
 */

get_header();

?>
<div class="container">
	<?php while (have_posts()) : the_post(); ?>
	<article class="article-content">
		<?php the_content(); ?>
	</article>
	<?php wp_link_pages('link_before=<span>&link_after=</span>&before=<div class="article-paging">&after=</div>&next_or_number=number'); ?>
	<?php endwhile;  ?>
	<?php comments_template('', true); ?>
</div>

<?php

get_footer();