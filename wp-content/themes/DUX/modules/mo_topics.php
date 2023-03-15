<div class="mo-topics">
	<ul>
		<?php 
			$lists = get_terms(array(
				'taxonomy'   => 'topic',
				'hide_empty' => true,
				'number'     => 3,
			));

			foreach ($lists as $key => $item) {
				$link = get_term_link($item->term_id);
				echo '<li>';
	                echo '<a class="-pic"'._post_target_blank().' href="'. $link .'">';
						echo '<img src="'. _get_tax_meta($item->term_id, 'image') .'" alt="'. $item->name .'">';
						echo '<div class="-info">';
		                	echo '<dfn><i class="fa fa-book"></i>'. $item->count .'</dfn>';
		                	echo '<h2>'. $item->name .'</h2>';
	                	echo '</div>';
	                echo '</a>';
	            echo '</li>';

			}
	    ?>
    </ul>
</div>