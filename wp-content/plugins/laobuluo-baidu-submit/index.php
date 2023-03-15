<?php
/**
Plugin Name: 老部落百度快速提交插件
Plugin URI: https://www.lezaiyun.com/baidu-submit.html
Description: 老部落百度提交插件，包括百度普通提交和百度快速提交，提高百度爬取网站和加快百度收录网站。
Version: 2.2
Author: 老蒋和他的小伙伴
Author URI: https://www.lezaiyun.com/
Requires PHP: 7.0
 */
namespace laobuluo\seo;
if (!defined('ABSPATH')) die();

if (!class_exists('LAOBULUO_BAIDU_SUBMITTER')) {
    class LAOBULUO_BAIDU_SUBMITTER {
        private $option_name       = 'laobuluo_bs_options';       // 插件参数保存名称
        private $menu_title        = [                            // 设置菜单的菜单名
                'setting_page'     => '百度提交插件',
                'manually_submit'  => '批量提交',
                'check_baidu_page' => '检查收录',
            ];
        private $page_title        = [                            // 设置菜单的页面title
                'setting_page'     => '百度提交设置',
                'manually_submit'  => '批量提交',
                'check_baidu_page' => '检查收录',
            ];
        private $capability        = 'administrator';             // 设置页面管理所需权限
        private $version           = '2.1';                       // 插件数据版本， 每次修改应与上方的Version值相同
        private $meta_box_info     = [
                'id'               => 'laobuluo_baidu_submitter_meta_box_id',
                'title'            => '百度推送',
                'context'          => 'side',
                'priority'         => 'default',
                'nonce'            => [
                    'action'       => 'laobuluo_baidu_submitter_nonce_action',
                    'name'         => 'laobuluo_baidu_submitter_nonce',
                ],
            ];
        private $meta_key          = 'is_laobuluo_baidu_submit';
        private $in_baidu_meta_key = 'is_laobuluo_in_baidu';

        private $base_folder;
        private $menu_slug;
        private $options;
        private $baidu;
        private $site;

        public function __construct() {
            $this->includes();
            $this->base_folder = plugin_basename(dirname(__FILE__));
            $this->menu_slug       = [
                'setting_page'     => $this->base_folder . '/setting',
                'manually_submit'  => $this->base_folder . '/manually_submit',
                'check_baidu_page' => $this->base_folder . '/check_baidu',
            ];

            $this->site    = site_url();
            $this->options = get_option($this->option_name);
            $this->baidu   = new BaiduSubmitter($this->options, $this->site);

            add_action( 'laobuluo_bs_event', array($this, 'bs_cron_event') );

            # 插件 activation 函数当一个插件在 WordPress 中”activated(启用)”时被触发。
            register_activation_hook(__FILE__, array($this, 'init_options'));
            register_deactivation_hook(__FILE__, array($this, 'restore_options'));  # 禁用时触发钩子

            # 添加快速收录于普通收录勾选meta_box
            add_action( 'add_meta_boxes', array($this, 'add_baidu_submitter_meta_box') );
            add_action( 'save_post', array($this, 'save_baidu_submitter_post_data') );

            # TODO: 待做配额超标时的延迟处理（optional）剩余的部分做计划任务延时提交，消耗将来的次数。
            # Done: 待做日志保存信息或者图标展示(暂时先不保存日志)

            # 添加插件设置菜单
            add_action('admin_menu', array($this, 'admin_menu_setting'));
            add_filter('plugin_action_links', array($this, 'setting_plugin_action_links'), 10, 2);

            add_action('admin_notices', array($this, 'bs_admin_notices') );  # Notice用来后台全Screen显示

            add_filter('post_row_actions',array($this, 'post_row_actions'),99,2);
            # Filters the array of row action links on the <Posts list table>.

            # TODO: 列表下拉框和批量处理按钮优化
            # 在 WordPress 后台文章列表添加排序选项下拉筛选框，支持多种方式排序
            # add_action('restrict_manage_posts', array($this, 'restrict_manage_posts'), 10, 2);

        }

        public function post_row_actions($actions, $post){
            if ( $this->options['check_switch'] && current_user_can($this->capability) && $post->post_status=='publish' ) {
                if( get_post_meta( $post->ID, $this->in_baidu_meta_key, true) ){
                    $url = 'href="https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd=' . urlencode(get_the_title($post)) . '"';
                    $content = '<span>百度已收录</span>';
                }else{
                    $url = '';
                    $content = '<span style="color:red;">暂未收录</span>';
                }
                $actions['check_in_baidu'] = '<a class="check_in_baidu" rel="external nofollow" target="_blank" ' . $url . ' >'.$content.'</a>';
            }
            return $actions;
        }

        public function bs_cron_event(){
            if ( $this->options['check_switch'] ) {
                $args = [
                    # 详见：https://developer.wordpress.org/reference/classes/wp_query/parse_query/
                    # 'meta_compare_key' => $this->in_baidu_meta_key,
                    'numberposts'      => 10,  # 每次执行限制数量
                    'post_status'      => 'publish',
                    'meta_query'       => [
                        [
                            'key'      => $this->in_baidu_meta_key,
                            'compare'  => 'NOT EXISTS'
                        ],
                    ],
                ];
                $p_list = get_posts($args);
                if ( $p_list ) {
                    $http_args = [
                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Firefox/78.0',
                        'timeout' => 3,
                        'redirection' => 3,
                        'sslverify' => FALSE,
                    ];
                    # $this->options['check_cookie'] = '8C7E9E488822A7180C01CE3CD543D6CA:FG=1';  # TODO: 测试完删除
                    if ( $this->options['check_cookie'] != '' ) {
                        $http_args['cookies'] = [ 'BAIDUID' => $this->options['check_cookie'] ];
                    }

                    $errors = '';  # 还是覆盖只保留一条错误记录吧，后续有需要再调整
                    foreach ( $p_list as $p ) {
                        # TODO: 若直接循环有问题，则可采取2个方案。 1. 设置sleep或者分配single任务，2. 使用wp_ajax请求
                        $url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&tn=monline_4_dg&wd=' . urlencode( get_permalink($p) );
                        $response = wp_safe_remote_get( $url, $http_args );
                        if ( is_wp_error( $response ) ) {
                            $errors = show_message($response);
                        } else {
                            if ( strpos($response['body'], '<title>百度安全验证</title>') ) {
                                $errors = '需要百度安全验证！';
                            } else if ( !strpos($response['body'], '没有找到该URL。您可以直接访问') && !strpos($response['body'], '很抱歉，没有找到与') ) {
                                update_post_meta($p->ID, $this->in_baidu_meta_key, current_time('timestamp'));  # 存个时间戳，以后好做统计
                            }
                        }
                    }
                    if (!empty($errors)) {
                        $cache = new LaobuluoCache('check');
                        $cache->set('check_error', $errors);
                    }
                }
            }
        }

        # 添加meta box模块
        public function add_baidu_submitter_meta_box() {
            if (!$this->options['switch']) return;
            $types = array();
            if ($this->options['is_post']) {
                $types[] = 'post';
            }
            if ($this->options['is_page']) {
                $types[] = 'page';
            }

            foreach ( $types as $type ) {
                add_meta_box(
                    $this->meta_box_info['id'],                       // Meta Box在前台页面源码中的id
                    $this->meta_box_info['title'],                    // 显示的标题
                    array($this, 'render_baidu_submitter_meta_box'),  // 回调方法，用于输出Meta Box的HTML代码
                    $type,                                            // 在哪个post type页面添加
                    $this->meta_box_info['context'],                  // 在哪显示该Meta Box
                    $this->meta_box_info['priority']                  // 优先级
                );
            }
        }

        # 显示meta box的html代码
        function render_baidu_submitter_meta_box( $post ) {
            if (!$this->options['switch']) return;
            if (!$this->options['is_post'] && !$this->options['is_page']) return;

            // 添加 nonce 项用于save post时的安全检查
            wp_nonce_field( $this->meta_box_info['nonce']['action'], $this->meta_box_info['nonce']['name'] );

            $is_daily_html = '';
            $is_normal_html = '';

            // 检测是否存在已推送标签，若已推送，则更改展示。
            $meta_value = get_post_meta( $post->ID, $this->meta_key, true );  # (optional) 如果设置为 true，返回单个值。

            $cache = new LaobuluoCache('remain');
            $_remain = $cache->get('remain');
            $_remain_daily = $cache->get('remain_daily');
            $remain = ($_remain and $_remain[1] > current_time('timestamp')) ? $_remain[0] : False;
            $remain_daily = ($_remain_daily and $_remain_daily[1] > current_time('timestamp')) ? $_remain_daily[0] : False;

            if ('normal' == $meta_value) {
                $html = '已提交普通收录';
            } elseif ('daily' == $meta_value) {
                $html = '已提交快速收录';
            } else {
                if($this->options['is_daily']) {
                    $is_daily_html = '<input type="checkbox" name="daily_submit" ';
                    if ( $remain_daily === False ) {
                        $is_daily_html .= ' />快速收录  (剩余配额：10条)';
                    } else if ( $remain_daily > 0 ) {
                        $is_daily_html .= ' />快速收录  (剩余配额：' . $remain_daily . '条)';
                    } else {
                        $is_daily_html = '<span>快速收录配额已用完，请明天再试!</span>';
                    }
                }
                if($this->options['is_normal']) {
                    $is_normal_html = '<input type="checkbox" name="normal_submit" checked="TRUE" ';
                    if ( $remain === False ) {
                        $is_normal_html .= ' />普通收录  (剩余配额：99999条)';
                    } else if ( $remain>0 ) {
                        $is_normal_html .= ' />普通收录  (剩余配额：' . $remain . '条)';
                    } else {
                        $is_normal_html = '<span>普通收录配额已用完，请明天再试!</span>';
                    }

                }
                $html = $is_daily_html . '<br />' . $is_normal_html;
                if ( strlen($meta_value) > 0 ) {
                    $html .= '<br /><p>' . $meta_value . '</p>';
                }
            }

            echo $html;
        }

        # 处理
        public function save_baidu_submitter_post_data( $post_id ) {
            if (!$this->options['switch']) return False;
            // 如果是系统自动保存，则不操作
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

            $post = get_post($post_id);
            if ($post->post_status != 'publish') return $post_id;

            $types = array();
            if ($this->options['is_post']) $types[] = 'post';
            if ($this->options['is_page']) $types[] = 'page';
            if (!in_array($post->post_type, $types, True)) return $post_id;

            // 检查nonce是否设置
            if (!isset($_POST[$this->meta_box_info['nonce']['name']]))  return $post_id;
            $nonce = $_POST[$this->meta_box_info['nonce']['name']];
            // 验证nonce是否正确
            if (!wp_verify_nonce( $nonce, $this->meta_box_info['nonce']['action'])) return $post_id;

            // 检查用户权限
            if ($_POST['post_type'] == 'post') {
                if (!current_user_can('edit_post', $post_id )) {
                    return $post_id;
                }
            }

            if ( isset($_POST['normal_submit']) ) $meta_value = 'normal';
            if ( isset($_POST['daily_submit']) ) $meta_value = 'daily';
            # 生成页面时，提交推送成功的将不会生成meta_value, 省去get_post_meta验证，若有问题需严谨地取值验证。
            if ( isset($meta_value) ) {
                $post_link = get_permalink($post_id);
                if ($post_link) {
                    $urls_array = array($post_link);
                    $resp = $this->baidu->request($meta_value, $urls_array);
                    if ( !is_wp_error( $resp ) ) {
                        if ($resp->error === Null) {
                            // 更新数据，第四个参数pre_value，用于指定之前的值替换，暂时先不添加
                            update_post_meta( $post_id, $this->meta_key, $meta_value );

                            $data = $meta_value == 'daily' ? $resp->remain_daily : $resp->remain;
                            $cache = new LaobuluoCache('remain');
                            $cache->set($meta_value, $data);
                        }  else {
                            update_post_meta( $post_id, $this->meta_key, $resp->message );
                        }
                    } else {
                        update_post_meta( $post_id, $this->meta_key, show_message($resp) );
                    }
                }
            }

        }

        // 初始化选项
        public function init_options() {
            $options = array(
                'version'          => $this->version,  # 用于以后当有数据结构升级时初始化数据
                'switch'           => False,
                'is_post'          => False,
                'is_page'          => False,
                'token'            => '',
                'is_daily'         => False,
                'is_normal'        => False,
                'event_recurrance' => 'hourly',
                'check_switch'     => False,  # 收录开关
                'check_cookie'     => '',
            );

            if(!$this->options){
                if (add_option($this->option_name, $options, '', 'yes')) {
                    $this->options = get_option($this->option_name);
                }
            }
            if (!wp_next_scheduled('laobuluo_bs_event')) {
                wp_schedule_event(time(), $this->options['event_recurrance'], 'laobuluo_bs_event');  // 激活时才有效
            }
        }

        private function includes() {
            require_once('api.php');
        }

        public function bs_admin_notices() {
            $cache = new LaobuluoCache('check');
            $check_error_cache = $cache->get('check_error')[0];
            if ( $check_error_cache !== False && trim($check_error_cache) != '' ) {
                # 'error', 'success', 'warning', 'info'.
                printf( '<div class="notice notice-warning"><p>%1$s</p></div>', esc_attr( $check_error_cache ) );
                # 显示通知完删除
                $cache->delete( 'check_error' );
            }
        }

        // 在插件列表页添加设置按钮
        public function setting_plugin_action_links($links, $file) {
            if ($file == plugin_basename(dirname(__FILE__) . '/index.php')) {
                $links[] = '<a href="admin.php?page=' . $this->menu_slug['manually_submit'] . '">' . $this->menu_title['manually_submit'] . '</a>';
                $links[] = '<a href="admin.php?page=' . $this->menu_slug['setting_page'] . '">' . $this->menu_title['setting_page'] . '</a>';
            }
            return $links;
        }

        // 在导航栏“设置”中添加条目
        public function admin_menu_setting() {
            add_menu_page(__($this->page_title['setting_page']), __($this->menu_title['setting_page']), $this->capability,
                $this->menu_slug['setting_page'], array($this, 'setting_page'), false, 100);
            add_submenu_page($this->menu_slug['setting_page'],$this->page_title['manually_submit'], $this->menu_title['manually_submit'],
                $this->capability, $this->menu_slug['manually_submit'], array($this, 'manually_submit_page'));
        }

        /**
         *  插件设置页面
         */
        public function check_baidu_page() {
            if (!current_user_can( $this->capability )) wp_die('Insufficient privileges!');
            require_once('check_baidu.php');
        }

        public function manually_submit_page() {
            if (!current_user_can( $this->capability )) wp_die('Insufficient privileges!');

            $cache = new LaobuluoCache('remain');
            $_remain = $cache->get('remain');
            $_remain_daily = $cache->get('remain_daily');
            $remain = ($_remain and $_remain[1] > current_time('timestamp')) ? $_remain[0] : 99999;
            $remain_daily = ($_remain_daily and $_remain_daily[1] > current_time('timestamp')) ? $_remain_daily[0] : 10;

            # 也许放这里不合适，待验证。
            if ($remain <= 0)  add_settings_error('laobuluo', 'laobuluo_option_notice', '普通收录配额不足！');
            if ($remain_daily <= 0) add_settings_error('laobuluo', 'laobuluo_option_notice', '快速收录配额不足！');

            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce']) && !empty($_POST)) {
                if (isset($_POST['is_normal'])) $meta_value = 'normal';
                if (isset($_POST['is_daily'])) $meta_value = 'daily';
                $links_str = isset($_POST['manual_links']) ? sanitize_text_field(trim(stripslashes($_POST['manual_links']))) : '';
                if ( trim($links_str) == '' ) {
                    add_settings_error('laobuluo', 'laobuluo_option_notice', '请输入待推送链接！');
                }

                if (isset($meta_value)) {

                    $limit = $meta_value == 'daily' ? $remain_daily : $remain;
                    $urls_array = explode(' ', $links_str);
                    if ( count($urls_array) > $limit ) {
                        add_settings_error('laobuluo', 'laobuluo_option_notice', '可用配额超标'. count($urls_array)-$limit . '条！');
                    } else {
                        $resp = $this->baidu->request($meta_value, $urls_array);

                        if ( !is_wp_error( $resp ) ) {
                            if ($resp->error === null) {
                                # TODO: 根据返回值，未处理的有not_same_site（不是本站url） not_valid（不合法的url）的报错提醒问题。
                                foreach($urls_array as $url) {
                                    $post_id = url_to_postid($url);
                                    if ( $post_id > 0 ) {  # 0 为查询失败
                                        update_post_meta( $post_id, $this->meta_key, $meta_value );
                                    }
                                }
                                $data = $meta_value == 'daily' ? $resp->remain_daily : $resp->remain;
                                $cache->set($meta_value, $data);

                                add_settings_error('laobuluo', 'laobuluo_option_notice', '本次成功提交' . $resp->success . '条链接。', 'success');
                                $remain = $cache->get('remain')[0];
                                $remain_daily = $cache->get('remain_daily')[0];
                            }
                        } else {
                            add_settings_error('laobuluo', 'laobuluo_option_notice', show_message($resp));
                        }
                    }
                } else {
                    add_settings_error('laobuluo', 'laobuluo_option_notice', '请选择推送类型！');
                }
            }

            require_once('manually_submit.php');
            settings_errors('laobuluo');
        }

        public function setting_page() {
            if (!current_user_can( $this->capability )) wp_die('Insufficient privileges!');

            $this->options = get_option($this->option_name);
            if ($this->options && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce']) && !empty($_POST)) {
                $this->options['switch'] = isset($_POST['switch']);
                $this->options['is_post'] = isset($_POST['is_post']);
                $this->options['is_page'] = isset($_POST['is_page']);
                $this->options['is_daily'] = isset($_POST['is_daily']);
                $this->options['is_normal'] = isset($_POST['is_normal']);
                $this->options['token'] = isset($_POST['token']) ? sanitize_text_field(trim(stripslashes($_POST['token']))) : '';
                $this->options['check_switch'] = isset($_POST['check_switch']);
                $this->options['check_cookie'] = isset($_POST['check_cookie']) ? sanitize_text_field(trim(stripslashes($_POST['check_cookie']))) : '';

                update_option($this->option_name, $this->options);

                $this->baidu = new BaiduSubmitter($this->options, $this->site);
                add_settings_error('laobuluo', 'laobuluo_option_notice', '设置保存成功！', 'success');
            }
            require_once('setting_page.php');
            settings_errors('laobuluo');
        }
    }

    global $LAOBULUO_BAIDU_SUBMITTER;
    $LAOBULUO_BAIDU_SUBMITTER = new LAOBULUO_BAIDU_SUBMITTER();
}
