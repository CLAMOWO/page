<?php if( _hui('layout') == '1' ) return; ?>
<div class="sidebar">
<?php 
	_moloader('mo_notice', false);
	
	if (function_exists('dynamic_sidebar')){
		dynamic_sidebar('gheader'); 

		if (is_home()){
			dynamic_sidebar('home'); 
		}
		elseif ( function_exists('is_woocommerce') && function_exists('is_product') && is_product() ){
			dynamic_sidebar('wooproduct'); 
		}
		elseif (is_category()){
			dynamic_sidebar('cat'); 
		}
		elseif ( taxonomy_exists( 'topic' ) ){
			dynamic_sidebar('topic'); 
		}
		else if (is_tag() ){
			dynamic_sidebar('tag'); 
		}
		else if (is_search()){
			dynamic_sidebar('search'); 
		}
		else if (is_single()){
			dynamic_sidebar('single'); 
		}

		dynamic_sidebar('gfooter');
	} 
?>
</div>