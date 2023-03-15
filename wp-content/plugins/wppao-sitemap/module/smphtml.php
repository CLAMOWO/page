<?php

/**
 *  Update Sitemap html
 *
 */

function wppsmp_update_html(){
    global $wpdb;

    /**
     *  Post if checked
     *  index 500 not 1000
     * */
    $html_contents = '';
    $html_category_contents = '';
    $html_page_contents = '';
    $html_tag_contents = '';

    if(!empty(wppsmp_get_setting('include_pages'))) {
        if (in_array('posts', wppsmp_get_setting('include_pages'))) {
            if (wppsmp_get_setting('include_new_posts')) {
                $sql_html = "select ID FROM $wpdb->posts WHERE post_password = '' AND post_type='post' AND post_status = 'publish' ORDER BY post_modified DESC LIMIT 0,500";
            } else {
                $sql_html = "select ID FROM $wpdb->posts WHERE post_password = '' AND post_type='post' AND post_status = 'publish' ORDER BY post_modified DESC LIMIT 0,1000";
            }

            $recentposts_html = $wpdb->get_results($sql_html);
            if ($recentposts_html) {
                foreach ($recentposts_html as $post) {
                    $html_contents .= '<li><a href="' . get_permalink($post->ID) . '" title="' . get_the_title($post->ID) . '" target="_blank">' . get_the_title($post->ID) . '</a></li>';
                }
            }
        }
        if (in_array('categorys', wppsmp_get_setting('include_pages'))) {
            $html_category_contents = wp_list_categories('echo=0');
        }
        if (in_array('pages', wppsmp_get_setting('include_pages'))) {
            $html_page_contents = wp_list_pages('echo=0');
        }
        if (in_array('tags', wppsmp_get_setting('include_pages'))) {
            $html_tag_contents = wp_tag_cloud('echo=0&number=245');
            $html_tag_contents = '<br /><h3>标签云</h3>' . $html_tag_contents;
        }
    }else{
        $html_contents = "Noting Update";
    }

    $blog_title = __('SiteMap','wppao_sitemap');
    $blog_name = get_bloginfo('name');
    $blog_keywords = $blog_title.','.$blog_name;
    $wppsmp_generator = 'Wppao Sitemap';
    $wppsmp_author = 'Yuanshu (QQ 208125126 https://github.com/mryuanshu)';
    $wppsmp_copyright = 'https://wppao.com';
    $blog_home = get_bloginfo('url');
    $sitemap_url = get_bloginfo('url').'/sitemap.html';
    $recentpost = "最新文章";
    $footnote = "首页";
    $updated_time = date('Y-M-d H:i:s');
    $updated_time = str_replace('-- ::','',$updated_time);

    if($html_contents) {
        $path_html  = WPPAO_SITEMAP_DIR.'views/sitemap.html';
        $html = file_get_contents("$path_html");

        $html = str_replace("{{网站标题}}",$blog_title,$html);
        $html = str_replace("{{网站名称}}",$blog_name,$html);
        $html = str_replace("{{网站地址}}",$blog_home,$html);
        $html = str_replace("{{网站关键词}}",$blog_keywords,$html);
        $html = str_replace("{{地图生成插件}}",$wppsmp_generator,$html);
        $html = str_replace("{{地图作者}}",$wppsmp_author,$html);
        $html = str_replace("{{地图官网}}",$wppsmp_copyright,$html);
        $html = str_replace("{{网站地图位置}}",$sitemap_url,$html);
        $html = str_replace("{{底标}}",$footnote,$html);
        $html = str_replace("{{最新文章}}",$recentpost,$html);
        $html = str_replace("{{更新时间}}",$updated_time,$html);
        $html = str_replace("{{文章内容}}",$html_contents,$html);
        $html = str_replace("{{目录内容}}",$html_category_contents,$html);
        $html = str_replace("{{页面内容}}",$html_page_contents,$html);
        $html = str_replace("{{标签内容}}",$html_tag_contents,$html);
        $wppsmp_get_sitepath = wppsmp_get_sitepath();
        $filename_html = $wppsmp_get_sitepath.'sitemap.html';
        if( wppsmp_get_filestatus($wppsmp_get_sitepath) || wppsmp_get_filestatus($filename_html) ){
            file_put_contents("$filename_html","$html");
            @chmod($filename_html, 0777);
        }
        wppsmp_show_messages("生成HTML地图成功");
    }
}