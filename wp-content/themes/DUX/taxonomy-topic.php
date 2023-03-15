<?php 
get_header(); 

global $wp_query;
$termdata = $wp_query->get_queried_object();

$pagedtext = '';
if( $paged && $paged > 1 ){
	$pagedtext = ' <small>第'.$paged.'页</small>';
}
?>
<section class="container">
	<div class="content-wrap">
		<div class="content">
			<div class="topic-hd">
				<img src="<?php echo _get_tax_meta($termdata->term_id, 'image') ?>" alt="<?php echo $termdata->name ?>">
				<div class="-info">
					<dfn><i class="fa fa-book"></i><?php echo $termdata->count ?></dfn>
					<h1><?php echo $termdata->name ?><?php echo $pagedtext ?></h1>
					<div class="-desc"><?php echo $termdata->description ?></div>
				</div>
			</div>
			<?php 
				get_template_part( 'excerpt' ); 
				_moloader('mo_paging');
				wp_reset_query();
			?>
		</div>
	</div>
	<?php get_sidebar(); ?>
</section>

<?php get_footer(); ?>