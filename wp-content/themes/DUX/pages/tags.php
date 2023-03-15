<?php 
/**
 * Template name: Tags
 * Description:   A tags page
 */

get_header();

$pagetags = _hui('tagspagenumber', 40);

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$purl = get_page_link();

if( strstr($purl, '?') ){
	$purl .= '?paged=';
}else{
	$purl .= '/page/';
}

$tagsoffset = $pagetags*($paged-1);

?>

<div class="container container-tags">
	<h1><?php the_title(); ?></h1>
	<div class="tagslist">
		<ul>
			<?php 
				$tagslist = get_tags('orderby=count&order=DESC&number='.$pagetags.'&offset='.$tagsoffset);
				foreach($tagslist as $tag) {
					echo '<li><a class="name" href="'.get_tag_link($tag).'">'. $tag->name .'</a><small>&times;'. $tag->count .'</small>'; 

					$posts = get_posts( "tag_id=". $tag->term_id ."&numberposts=1" );
					foreach( $posts as $post ) {
						setup_postdata( $post );
						echo '<p><a class="tit" href="'.get_permalink().'">'.get_the_title().'</a></p>';
					}

					echo '</li>';
				} 
		
			?>
		</ul>
	</div>
	<?php _tags_paging() ?>

</div>

<?php

get_footer();

function _tags_paging() {
    global $pagetags;
    global $paged;
    $max_page = ceil(wp_count_terms('post_tag', array('hide_empty' => true))/$pagetags);
    $p = 3;

    if ( $max_page == 1 ) return; 
    echo '<div class="pagination pagination-multi"><ul>';
    if ( empty( $paged ) ) $paged = 1;
    if ( $paged > $p + 1 ) _tags_paging_link( 1 );
    if ( $paged > $p + 2 ) echo "<li><span>···</span></li>";
    for( $i = $paged - $p; $i <= $paged + $p; $i++ ) { 
        if ( $i > 0 && $i <= $max_page ) $i == $paged ? print "<li class=\"active\"><span>{$i}</span></li>" : _tags_paging_link( $i );
    }
    if ( $paged < $max_page - $p - 1 ) echo "<li><span>···</span></li>";
    if ( $paged < $max_page - $p ) _tags_paging_link( $max_page );
    // echo '<li><span>共 '.$max_page.' 页</span></li>';
    echo '</ul></div>';
}

function _tags_paging_link( $i ) {
	global $purl;
    echo '<li><a title="第'.$i.'页" href="'. $purl.$i .'">'.$i.'</a></li>';
}