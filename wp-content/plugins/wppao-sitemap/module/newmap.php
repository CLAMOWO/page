<?php

function wppsmp_new_sitemap() {
    global $wpdb, $posts;

    if(!empty(wppsmp_get_setting('include_pages'))) {

        if (wppsmp_get_setting('include_new_posts')) {
            $new_posts_limit = '500';
        } else {
            $new_posts_limit = '1000';
        }

        $xml_contents = '';
        $xml_contents_page = '';
        $xml_contents_post = '';
        $xml_contents_tag = '';
        $xml_contents_cat = '';

        $sql_mini = "select ID,post_modified,post_date,post_type FROM $wpdb->posts WHERE post_password = '' AND (post_type='post' or post_type='page') AND post_status = 'publish' ORDER BY post_modified DESC LIMIT 0,$new_posts_limit";
        $recentposts_mini = $wpdb->get_results($sql_mini);

        if ($recentposts_mini) {

            foreach ($recentposts_mini as $post) {
                if ($post->post_type == 'page') {
                    if (!in_array('pages', wppsmp_get_setting('include_pages'))) {
                        continue;
                    }
                    $loc = get_page_link($post->ID);
                    $loc = wppsmp_escape_xml($loc);
                    if (!$loc) {
                        continue;
                    }
                    if ($post->post_modified == '0000-00-00 00:00:00') {
                        $post_date = $post->post_date;
                    } else {
                        $post_date = $post->post_modified;
                    }
                    $lastmod = date("Y-m-d\TH:i:s+00:00", wppsmp_get_mysql_timestamp($post_date));
                    $changefreq = 'weekly';
                    $priority = '0.3';
                    $xml_contents_page .= "<url>";
                    $xml_contents_page .= "<loc>$loc</loc>";
                    $xml_contents_page .= "<lastmod>$lastmod</lastmod>";
                    $xml_contents_page .= "<changefreq>$changefreq</changefreq>";
                    $xml_contents_page .= "<priority>$priority</priority>";
                    $xml_contents_page .= "</url>";
                } else {
                    if (!in_array('posts', wppsmp_get_setting('include_pages'))) {
                        continue;
                    }
                    $loc = get_permalink($post->ID);
                    $loc = wppsmp_escape_xml($loc);
                    if (!$loc) {
                        continue;
                    }
                    if ($post->post_modified == '0000-00-00 00:00:00') {
                        $post_date = $post->post_date;
                    } else {
                        $post_date = $post->post_modified;
                    }
                    $lastmod = date("Y-m-d\TH:i:s+00:00", wppsmp_get_mysql_timestamp($post_date));
                    $changefreq = 'monthly';
                    $priority = '0.6';
                    $xml_contents_post .= "<url>";
                    $xml_contents_post .= "<loc>$loc</loc>";
                    $xml_contents_post .= "<lastmod>$lastmod</lastmod>";
                    $xml_contents_post .= "<changefreq>$changefreq</changefreq>";
                    $xml_contents_post .= "<priority>$priority</priority>";
                    $xml_contents_post .= "</url>";
                }
            }

            if (in_array('categorys', wppsmp_get_setting('include_pages'))) {
                $category_ids = get_terms('category', 'orderby=count&hide_empty=0');
                if ($category_ids) {
                    foreach ($category_ids as $cat_id) {
                        $loc = get_category_link($cat_id);
                        $loc = wppsmp_escape_xml($loc);
                        if (!$loc) {
                            continue;
                        }
                        $lastmod = date("Y-m-d\TH:i:s+00:00", current_time('timestamp', '1'));
                        $changefreq = 'Weekly';
                        $priority = '0.3';
                        $xml_contents_cat .= "<url>";
                        $xml_contents_cat .= "<loc>$loc</loc>";
                        $xml_contents_cat .= "<lastmod>$lastmod</lastmod>";
                        $xml_contents_cat .= "<changefreq>$changefreq</changefreq>";
                        $xml_contents_cat .= "<priority>$priority</priority>";
                        $xml_contents_cat .= "</url>";
                    }
                }
            }

            /**
             *  tags
             *
             */
            if (in_array('tags', wppsmp_get_setting('include_pages'))) {
                $all_the_tags = get_tags();
                if ($all_the_tags) {
                    foreach ($all_the_tags as $this_tag) {
                        $tag_id = $this_tag->term_id;
                        $loc = get_tag_link($tag_id);
                        $loc = wppsmp_escape_xml($loc);
                        if (!$loc) {
                            continue;
                        }
                        $lastmod = date("Y-m-d\TH:i:s+00:00", current_time('timestamp', '1'));
                        $changefreq = 'Weekly';
                        $priority = '0.3';
                        $xml_contents_tag .= "<url>";
                        $xml_contents_tag .= "<loc>$loc</loc>";
                        $xml_contents_tag .= "<lastmod>$lastmod</lastmod>";
                        $xml_contents_tag .= "<changefreq>$changefreq</changefreq>";
                        $xml_contents_tag .= "<priority>$priority</priority>";
                        $xml_contents_tag .= "</url>";
                    }
                }
            }

            /**
             *  ends
             *
             */
            $xml_contents = $xml_contents_post . $xml_contents_page . $xml_contents_cat . $xml_contents_tag;


        }

        /**
         * NEW XML
         * */
        if (wppsmp_get_setting("xml_sitemap")) {
            wppsmp_update_xml($xml_contents);
        }

        /**
         * NEW HTML
         *
         * */
        if (wppsmp_get_setting("html_sitemap")) {
            wppsmp_update_html();
        }

    }

}