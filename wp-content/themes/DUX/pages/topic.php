<?php 
/**
 * Template name: Topics
 * Description:   A Topics page
 */

get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$purl = get_page_link();

if( strstr($purl, '?') ){
	$purl .= '?paged=';
}else{
	$purl .= '/page/';
}

$pagetopics = _hui('topics_pagenumber', 12);
$listnumber = _hui('topics_list_number', 3);
?>
<div class="container">
	<div class="topic-items">
		<h1><?php echo trim(wp_title('', false)) ?></h1>
		<ul>
			<?php 
				$lists = get_terms(array(
					'taxonomy'   => 'topic',
					'hide_empty' => true,
					'number' => $pagetopics,
					'offset' => $pagetopics*($paged-1),
				));

				foreach ($lists as $key => $item) {
					$link = get_term_link($item->term_id);

					echo '<li>';
		                echo '<a class="-pic'.( (!$listnumber && !_hui('topics_list_more', 1)) ? ' -pic-only' : '' ).'"'._post_target_blank().' href="'. $link .'">';
							echo '<img src="'. _get_tax_meta($item->term_id, 'image') .'" alt="'. $item->name .'">';
							echo '<div class="-info">';
			                	if( _hui('topics_list_count', 1) ) echo '<dfn><i class="fa fa-book"></i>'. $item->count .'</dfn>';
			                	echo '<h2>'. $item->name .'</h2>';
			                	if( _hui('topics_list_desc', 1) && $item->description ) echo '<div class="-desc">'. $item->description .'</div>';
		                	echo '</div>';
		                echo '</a>';
		                if( $listnumber || _hui('topics_list_more', 1) ){
			                echo '<div class="-list">';
				                if( $listnumber ){
					                echo '<ol>';
					                	$args = array(
							        		'tax_query'      => array(
							        			array(
							                        'taxonomy' => 'topic',
							                        'field'    => 'id',
							                        'terms'    => array($item->term_id),
							                    )
							        		),
							        		'order'          => 'DESC',
							        		'posts_per_page' => $listnumber,
							        		'post_type'      => 'post',
							            );
							            query_posts( $args );
							            while ( have_posts() ) : the_post();
				                            echo '<li>';
				                                echo '<a'. _post_target_blank() .' class="-inner" href="'.get_permalink().'">';
				                                	echo _get_post_thumbnail();
				                                    echo '<span>'. get_the_title() .'</span>';
				                                echo '</a>';
				                            echo '</li>';
				                        endwhile; 

				                        wp_reset_query();

					                echo '</ol>';
					            }
			                	if( _hui('topics_list_more', 1) ){
			                		echo '<a class="-more"'._post_target_blank().' href="'. $link .'">'.__('进入专题', 'TBL').'<i>&gt;</i></a>';
			                	}
			                echo '</div>';
		                }
		            echo '</li>';

				}
		    ?>
	    </ul>
    </div>
    <?php _topic_paging() ?>
</div>

<?php get_footer(); 

function _topic_paging() {
    global $pagetopics;
    global $paged;
    $max_page = ceil(wp_count_terms('topic', array('hide_empty' => true))/$pagetopics);
    $p = 3;

    if ( $max_page == 1 ) return; 
    echo '<div class="pagination pagination-multi"><ul>';
    if ( empty( $paged ) ) $paged = 1;
    if ( $paged > $p + 1 ) _topic_paging_link( 1 );
    if ( $paged > $p + 2 ) echo "<li><span>···</span></li>";
    for( $i = $paged - $p; $i <= $paged + $p; $i++ ) { 
        if ( $i > 0 && $i <= $max_page ) $i == $paged ? print "<li class=\"active\"><span>{$i}</span></li>" : _topic_paging_link( $i );
    }
    if ( $paged < $max_page - $p - 1 ) echo "<li><span>···</span></li>";
    if ( $paged < $max_page - $p ) _topic_paging_link( $max_page );
    // echo '<li><span>共 '.$max_page.' 页</span></li>';
    echo '</ul></div>';
}

function _topic_paging_link( $i ) {
	global $purl;
    echo '<li><a title="第'.$i.'页" href="'. $purl.$i .'">'.$i.'</a></li>';
}