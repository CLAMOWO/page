<?php
class WPPAO_PGS_SETTING{

    function __construct( $args ){
        global $wppao_pgs_plugins;
        if(!isset($wppao_pgs_plugins)) $wppao_pgs_plugins = array();
        $this->info = $args;
        $this->key = isset($this->info['key']) ? $this->info['key'] : '';
        $this->version = isset($this->info['ver']) ? $this->info['ver'] : '';
        $this->basename = isset($this->info['basename']) ? $this->info['basename'] : '';
        $this->plugin_slug = isset($this->info['slug']) ? $this->info['slug'] : '';
        $this->updateName = 'wppao_pgs_update_' . $this->info['plugin_id'];
        $this->content =  isset($this->info['content']) ? $this->info['content'] : '';
        $this->automaticCheckDone = false;
        $this->option_arr = isset($this->info['option']) ? $this->info['option'] : '';
        $this->submenu_arr = isset($this->info['submenu']) ? $this->info['submenu'] : '';

        $_wppao_pgs_plugins[$this->info['plugin_id']] = $this->plugin_slug;

        //add_action( 'delete_site_transient_update_plugins', array($this, 'updated') );
        add_action( 'admin_menu', array($this, 'init'));
    }

    function init(){
        $title = isset($this->info['title']) ? $this->info['title'] : '';
        $icon = isset($this->info['icon']) ? $this->info['icon'] : '';
        $position = isset($this->info['position']) ? $this->info['position'] : '';

        add_menu_page( $title, $title, 'edit_theme_options', $this->plugin_slug, array( &$this, 'options'), $icon);

        if($this->submenu_arr){
            foreach ($this->submenu_arr as $submenu_item){
                add_submenu_page($this->plugin_slug, $submenu_item['title'], $submenu_item['title'], 'manage_options', $this->plugin_slug.$submenu_item['slug'],$submenu_item['func']);
            }
        }
    }

    //读设置
    function get($setting_name,$default=''){
        $option = $this->$key;
        //每个插件单独设置
        if(isset($option[$setting_name])){
            return str_replace("\r\n", "\n", $option[$setting_name]);
        }else{
            return $default;
        }
    }

    //写配置
    function write($setting_name,$default=''){
        $option = $this->$key;
        //每个插件单独设置
        if(isset($option[$setting_name])){
            echo str_replace("\r\n", "\n", $option[$setting_name]);
        }else{
            echo $default;
        }
    }

    function options(){
        require_once WPPAO_PGS_PATH . 'module.php';
        
        do_action( 'wppao_pgs_plugin_panel_init' );
        $this->options = get_option($this->key);
        $this->form_action();
        $this->settings = $this->get_settings();
        //这里
        $pages = WPPAO_PGS_MODULE::get_all_pages();

         // Load CSS
        wp_enqueue_style( "wppao-pgs-css", WPPAO_PGS_URI . "css/wppao-pgs.css", false, WPPAO_PGS_VER, "all");
        wp_enqueue_style( 'wp-color-picker' );

        // Load JS
        wp_enqueue_script("wppao-pgs-js",  WPPAO_PGS_URI  . "js/wppao-pgs.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), WPPAO_PGS_VER, true);
        wp_enqueue_media();

        /**
         * Custom JS
         */
        wp_enqueue_script("wppao-pgs-custom-js",  WPPAO_PGS_URI  . "js/wppao-custom.js", "pgs-custom" , WPPAO_PGS_VER, true);

        ?>
        <div class="wrap wppao-pgs-wrap">
            <div class="wppao-pgs-head">
                <div class="wppao-pgs-ver">WP泡插件版本 V<?php echo isset($this->version)?$this->version:'1.0.0';?></div>
                <h1>插件设置<small><?php echo isset($this->info['name'])?$this->info['name']:'';?></small></h1>
            </div>
            <div class="wppao-pgs-contact">
                <div class="wppao-pgs-author">
                    <p><a target="_blank" href="http://mail.qq.com/cgi-bin/qm_share?t=qm_mailme&email=vNHT2d7d3sX8ytXMks3Nkt-T0Q" style="text-decoration:none;"><img src="<?php echo WPPAO_PGS_URI.'images/qqmail.png'?>"/></a> | <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=208125126&site=qq&menu=yes"><img border="0" src="<?php echo WPPAO_PGS_URI.'images/qq.gif'?>" alt="点击这里给我发消息" title="点击这里给我发消息"/></a> | <a target="_blank" href="//shang.qq.com/wpa/qunwpa?idkey=0b5390a1b87fe1df19be33d3920fbfe84729db5a4ed849904872bed0cb3db753"><img border="0" src="<?php echo WPPAO_PGS_URI.'images/qun.png'?>" alt="Wordpress主题插件分享" title="Wordpress主题插件分享"></a></p>
                    <p>作者： <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=208125126&site=qq&menu=yes">缘殊</a> </p>
                    <p>联系QQ： <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=208125126&site=qq&menu=yes">208125126</a>  【欢迎联系进行WP、PHP、C#等等各种定制开发】</p>
                    <p>点击红色字体关注微信公众号WP泡---> <span id="tanwxgzh">WP泡</span>  </p>
                    <p>QQ交流群：<a target="_blank" href="//shang.qq.com/wpa/qunwpa?idkey=0b5390a1b87fe1df19be33d3920fbfe84729db5a4ed849904872bed0cb3db753">226495220</a> </p>
                    <p>官网：<a target="_blank" href="//wppao.com">WP泡</a> https://wppao.com</p>
                </div>
                <div class="wppao-pgs-gzh">
                    <p>扫码关注公众号进行反馈：</p>
                    <p><img src="<?php echo WPPAO_PGS_URI.'/images/gzh.jpg'; ?>" /></p>
                </div>
            </div>
            <?php echo $this->build_form();?>
        </div>
    <?php }

    private function build_form(){ ?>
            <form action="" method="post" id="wppao-pgs-form" class="wppao-pgs-form">
                <?php wp_nonce_field( $this->key . '_options', $this->key . '_nonce', true );?>
                    <?php if(isset($this->settings->option)) { $i=0;foreach ($this->settings->option as $item) {
                        $this->option_item($item, $i);
                        $i++;
                    }} ?>
                <div class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="保存更改">
                </div>
            </form>
    <?php
    }
    
    private function option_item($option, $i){
        $type = $option->type;
        $title = isset($option->title)?$option->title:'';
        $desc = isset($option->desc)?$option->desc:'';
        $name = isset($option->name)?$option->name:'';
        $group = isset($option->group)?$option->group:'';
        $id = isset($option->id)?$option->id:$name;
        $rows = isset($option->rows)?$option->rows:3;
        $value = isset($option->std)?$option->std:'';
        $value = isset($this->options[$name]) ? $this->options[$name] : $value;
        $notice = $desc?'<small class="input-notice">'.$desc.'</small>':'';
        $tax = isset($option->tax)?$option->tax:'category';

        switch ($type) {
            case 'title':
                $first = $i==0?' section-hd-first':'';
                echo '<div class="section-hd'.$first.'"><h3 class="section-title">'.$title.' <small>'.$desc.'</small></h3></div>';
                break;

            case 'text':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><input type="text" class="form-control" id="wppao_pgs_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            case 'radio':
                $html = '';
                foreach ($option->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<label class="radio-inline"><input type="radio" name="'.$name.'" checked value="'.$opk.'">'.$opv.'</label>':'<label class="radio-inline"><input type="radio" name="'.$name.'" value="'.$opk.'">'.$opv.'</label>';
                }
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input">'.$html . $notice.'</div></div>';
                break;

            case 'checkbox':
                $html = '';
                foreach ($option->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $checked = '';
                    if(is_array($value)){
                        foreach($value as $v){
                            if($opk==$v) $checked = ' checked';
                        }
                    }else{
                        if($opk==$value) $checked = ' checked';
                    }
                    $html .= '<label class="checkbox-inline"><input type="checkbox" name="'.$name.'[]"'.$checked.' value="'.$opk.'">'.$opv.'</label>';
                }
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input">'.$html . $notice.'</div></div>';
                break;

            case 'checkbox_sort':
                $html = '';
                $option->options = (array) $option->options;
                $value = $value ? $value : array();
                foreach ($value as $item) {
                    $html.='<label class="checkbox-inline"><input name="'.$name.'[]" checked type="checkbox" value="'.$item.'"> '.$option->options[$item].'</label>';
                }
                foreach ($option->options as $key => $val) {
                    $key = $key==='_empty_'?'':$key;
                    $key = $key===0?'0':$key;
                    if(!in_array($key, $value)){
                        $html.='<label class="checkbox-inline"><input name="'.$name.'[]" type="checkbox" value="'.$key.'"> '.$val.'</label>';
                    }
                }
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><div class="cat-checkbox-list j-cat-sort" data-name="'.$name.'">'.$html.'</div><div>'.$notice.'</div></div></div>';
                break;

            case 'info':
                echo '<div class="form-group '.$group.' clearfix"><label class="form-label">'.$title.'</label><div class="form-input" style="padding-top:7px;">'.$value . $notice.'</div></div>';
                break;

            case 'select':
                $html = '';
                foreach ($option->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<option selected value="'.$opk.'">'.$opv.'</option>':'<option value="'.$opk.'">'.$opv.'</option>';
                }
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><select class="form-control" id="wppao_pgs_'.$id.'" name="'.$name.'">'.$html.'</select>'.$notice.'</div></div>';
                break;

            case 'wppimg_fonts':
                if(class_exists('WPPAO_Watermark')) {
                    $html = '';
                    $default_fonts = WPPAO_Watermark::default_fonts();
                    foreach ($default_fonts as $key => $default_font) {
                        if ($value == $default_font) {
                            $selectd = 'selected';
                        } else {
                            $selectd = '';
                        }
                        $html .= '<option value="' . $default_font . '" ' . $selectd . '>' . $key . '</option>';
                    }
                    $custom_fonts = WPPAO_Watermark::custom_fonts();
                    if ($custom_fonts) {
                        foreach ($custom_fonts as $key => $custom_font) {
                            if ($value == $default_font) {
                                $selectd = 'selected';
                            } else {
                                $selectd = '';
                            }
                            $html .= '<option value="' . $custom_font . '" ' . $selectd . '>' . $key . '</option>';
                        }
                    }
                    echo '<div class="form-group ' . $group . ' clearfix"><label for="wppao_pgs_' . $id . '" class="form-label">' . $title . '</label><div class="form-input"><select class="form-control" id="wppao_pgs_' . $id . '" name="' . $name . '">' . $html . '</select>' . $notice . '</div></div>';
                    $selectd = '';
                }
                break;

            case 'wppimg_position':
                if(class_exists('WPPAO_Watermark')) {
                    $html = '<div class="wppimg-postion">';
                    $nopostion = false;
                    for ($i = 1; $i < 10; $i++) {
                        if ($i == 9 && $nopostion == false) {
                            $checked = 'checked';
                        } else {
                            if ($value == $i) {
                                $checked = 'checked';
                                $nopostion = true;
                            } else {
                                $checked = '';
                            }
                        }

                        $html .= '<div class="wppimg-postion-' . $i . '"><input type="radio" name="' . $name . '" value="' . $i . '" ' . $checked . ' >' . $i . '</div>';
                    }
                    echo '<div class="form-group ' . $group . ' clearfix"><label for="wppao_pgs_' . $id . '" class="form-label">' . $title . '</label><div class="form-input">' . $html . '</div>' . $notice . '</div></div>';
                    $checked = '';
                }
                break;

            case 'textarea':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><textarea class="form-control" rows="'.$rows.'" id="wppao_pgs_'.$id.'" name="'.$name.'">'.esc_html($value).'</textarea>'.$notice.'</div></div>';
                break;

            case 'editor':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="col-sm-10">';
                wp_editor( wpautop( $value ), 'wppao_pgs_'.$id, WPPAO_PGS_MODULE::editor_settings(array('textarea_name'=>$name, 'textarea_rows'=>$rows)) );
                echo $notice.'</div></div>';
                break;

            case 'upload':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><input type="text" class="form-control" id="wppao_pgs_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'"><button id="wppao_pgs_'.$id.'_upload" type="button" class="button upload-btn"><i class="dashicons-before dashicons-admin-media"></i> 上传</button>'.$notice.'</div></div>';
                break;

            case 'color':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><input class="color-picker" type="text"  name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            case 'page':
                $html = '<option value="">--请选择--</option>';
                $pages = WPPAO_PGS_MODULE::get_all_pages();
                foreach ($pages as $page) {
                    $html.=$page['ID']==$value?'<option selected value="'.$page['ID'].'">'.$page['title'].'</option>':'<option value="'.$page['ID'].'">'.$page['title'].'</option>';
                }
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input"><select class="form-control" id="wppao_pgs_'.$id.'" name="'.$name.'">'.$html.'</select>'.$notice.'</div></div>';
                break;


            case 'cat_single':
                $option->options = WPPAO_PGS_MODULE::category($tax);
                $option->type = 'select';
                $this->option_item($option, $i);
                break;

            case 'cat_multi':
                $option->options = WPPAO_PGS_MODULE::category($tax);
                $option->type = 'checkbox';
                $this->option_item($option, $i);
                break;

            case 'cat_multi_sort':
                $option->options = WPPAO_PGS_MODULE::category($tax);
                $option->type = 'checkbox_sort';
                $this->option_item($option, $i);
                break;

            case 'toggle':
                echo '<div class="form-group '.$group.' clearfix"><label for="wppao_pgs_'.$id.'" class="form-label">'.$title.'</label><div class="form-input toggle-wrap">';
                if($value=='1'){
                    echo '<div class="toggle active"></div>';
                }else{
                    echo '<div class="toggle"></div>';
                }
                echo '<input type="hidden" id="wppao_pgs_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            default:
                break;
        }
    }

    function form_action(){
        $nonce = isset($_POST[$this->key . '_nonce']) ? $_POST[$this->key . '_nonce'] : '';

        // Check nonce
        if ( ! $nonce || ! wp_verify_nonce( $nonce, $this->key . '_options' ) ){
            return;
        }

        $data = $_POST;
        $this->options = array();

        /**
         * PostData Clean
         * */
        foreach ($data as $item=>$value){
            $this->options[$item] = stripslashes_deep($value);
        }

        $this->settings = $this->get_settings();
        if(isset($this->settings->option)) { foreach ($this->settings->option as $item) {
            if(isset($item->name) && $item->name!='' && isset($data[$item->name])) {
                $this->options[$item->name] = $data[$item->name];
            }
        }}

        update_option($this->key, $this->options);

        echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"><p><strong>设置已保存。</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button></div>';
    }

    private function get_settings(){
        $ops = $this->option_arr;
        //print_r(json_encode($ops,true));
        return json_decode(json_encode($ops));
    }
}