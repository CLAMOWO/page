<?php
    /**
     * 进行主动推送
     * wppao.com update by 缘殊 QQ208125126
     * 2020-5-24
     * 1代表提交过了
     * -1代表提交失败
     * 2代表天级收录
     */
     function wppsmp_baidu_zdsubmit($post_ID){
            $baidu_zd_token = wppsmp_get_setting('zd_token');
            
            if($baidu_zd_token){

                $baidu_zd_domain = get_option('home');
                /**
                 * 不要推送推送过的
                 * 知道么？
                 */
                if (get_post_meta($post_ID, 'baidu_submit', true) == 1) return;

                $url = get_permalink($post_ID);
                $api = 'http://data.zz.baidu.com/urls?site=' . $baidu_zd_domain . '&token=' . $baidu_zd_token;
                $request = new WP_Http;
                $result = $request->request($api, array('method' => 'POST', 'body' => $url, 'headers' => 'Content-Type: text/plain'));
                $result = json_decode($result['body'], true);

                /**
                 * 推送成功要记录
                 * 不要像个hape一直推送 渣渣
                 */
                if (array_key_exists('success', $result)) {
                    add_post_meta($post_ID, 'baidu_submit', 1, true);
                }else{
                    add_post_meta($post_ID, 'baidu_submit', -1, true);
                }
            }
    }

    if (wppsmp_get_setting('zd_submit')) {
        add_action('publish_post', 'wppsmp_baidu_zdsubmit', 0);
    }

    /**
     * 后期？
     * 后期加个页面显示是不是推送过好了。
     * 等有时间再说。
     */

    add_filter('manage_posts_columns', 'wppsmp_add_posts_baidu_submit_columns');
    function wppsmp_add_posts_baidu_submit_columns($columns) {
        $columns['baidu'] = '百度推送';
        return $columns;
    }

    add_action('manage_posts_custom_column', 'wppsmp_manage_posts_columns', 10, 2);
    function wppsmp_manage_posts_columns($column_name, $id) {
        global $wpdb;
        switch ($column_name) {
            case 'baidu':
                $baidu_status =  get_post_meta($id, 'baidu_submit', true);
                if($baidu_status == 1 ){
                    $baidu = '主动推送成功';
                }elseif($baidu_status == 2){
                    $baidu = '天级收录成功';
                }elseif($baidu_status == -1){
                    $baidu = '推送失败';
                }else{
                    $baidu = '没有推送';
                }
                echo $baidu;
                break;
            default:
                break;
        }
    }
