<?php
class WPPAO_PGS_MODULE{
    public static function get_all_pages(){
        $pages = get_pages(array('post_type' => 'page','post_status' => 'publish'));
        $res = array();
        if($pages){
            foreach ($pages as $page) {
                $p = array(
                    'ID' => $page->ID,
                    'title' => $page->post_title
                );
                $res[] = $p;
            }
        }
        return $res;
    }

    public static function editor_settings($args = array()){
        return array(
            'textarea_name' => $args['textarea_name'],
            'textarea_rows' => $args['textarea_rows'],
            'tinymce'       => array(
                'height'        => 150,
                'toolbar1' => 'formatselect,fontsizeselect,bold,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,fullscreen,wp_help',
                'toolbar2' => '',
                'toolbar3' => '',
            )
        );
    }

    public static function category( $tax = 'category' ){
        $categories = get_terms( array(
            'taxonomy' => $tax,
            'hide_empty' => false,
        ) );

        $cats = array();

        foreach ($categories as $cat){
            $cats[$cat->term_id] = $cat->name;
        }

        return $cats;
    }
}