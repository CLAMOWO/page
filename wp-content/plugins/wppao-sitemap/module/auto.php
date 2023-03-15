<?php

if(wppsmp_get_setting('update_new_posts')){
    add_action('publish_post', 'wppsmp_new_sitemap');
}
