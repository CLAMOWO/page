<?php

// require settings and options
define( 'OPTIONS_FRAMEWORK_DIRECTORY', get_template_directory_uri() . '/settings/' );
require_once get_stylesheet_directory() . '/settings/options-framework.php';
require_once get_stylesheet_directory() . '/options.php';

require_once get_stylesheet_directory() . '/settings/update.php';

add_action( 'optionsframework_custom_scripts', 'optionsframework_custom_scripts' );

function optionsframework_custom_scripts() { ?>
	<script type="text/javascript">
	jQuery(document).ready(function() {

		jQuery('#example_showhidden').click(function() {
	  		jQuery('#section-example_text_hidden').fadeToggle(400);
		});

		if (jQuery('#example_showhidden:checked').val() !== undefined) {
			jQuery('#section-example_text_hidden').show();
		}

	});
	</script>

	<?php
}



add_action('admin_enqueue_scripts', '_admin_load_scripts');
function _admin_load_scripts() {
    wp_enqueue_style('_admincss', get_stylesheet_directory_uri() . '/css/admin.css', array(), THEME_VERSION, 'all');
}




// editor style
add_editor_style( get_locale_stylesheet_uri() . '/css/editor-style.css' );

// 后台Ctrl+Enter提交评论回复
add_action('admin_footer', '_admin_comment_ctrlenter');
function _admin_comment_ctrlenter() {
	echo '<script type="text/javascript">
        jQuery(document).ready(function($){
            $("textarea").keypress(function(e){
                if(e.ctrlKey&&e.which==13||e.which==10){
                    $("#replybtn").click();
                }
            });
        });
    </script>';
};


function _add_editor_buttons($buttons) {
    $buttons[] = 'fontselect';
    $buttons[] = 'fontsizeselect';
    $buttons[] = 'cleanup';
    $buttons[] = 'styleselect';
    $buttons[] = 'del';
    $buttons[] = 'sub';
    $buttons[] = 'sup';
    $buttons[] = 'copy';
    $buttons[] = 'paste';
    $buttons[] = 'cut';
    $buttons[] = 'image';
    $buttons[] = 'anchor';
    $buttons[] = 'backcolor';
    $buttons[] = 'wp_page';
    $buttons[] = 'charmap';
    return $buttons;
}
add_filter("mce_buttons_2", "_add_editor_buttons");


/* 
 * delete google fonts
 * ====================================================
*/
// Remove Open Sans that WP adds from frontend
if (!function_exists('remove_wp_open_sans')) :
    function remove_wp_open_sans() {
        wp_deregister_style( 'open-sans' );
        wp_register_style( 'open-sans', false );
    }
    add_action('wp_enqueue_scripts', 'remove_wp_open_sans');
 
    // Uncomment below to remove from admin
    // add_action('admin_enqueue_scripts', 'remove_wp_open_sans');
endif;

function remove_open_sans() {    
    wp_deregister_style( 'open-sans' );    
    wp_register_style( 'open-sans', false );    
    wp_enqueue_style('open-sans','');    
}    
add_action( 'init', 'remove_open_sans' );



/* 
 * post meta from
 * ====================================================
*/
$postmeta_from = array(
    array(
        "name" => "fromname_value",
        "std" => "",
        "title" => __('来源名', 'haoui').'：'
    ),
    array(
        "name" => "fromurl_value",
        "std" => "",
        "title" => __('来源网址', 'haoui').'：'
    )
);

if( _hui('post_from_s') ){
    add_action('admin_menu', '_postmeta_from_create');
    add_action('save_post', '_postmeta_from_save');
}

function _postmeta_from() {
    global $post, $postmeta_from;
    foreach($postmeta_from as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<p>'.$meta_box['title'].'</p>';

        echo '<p><input type="text" style="width:98%" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';

    }
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function _postmeta_from_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_from_boxes', __('来源', 'haoui'), '_postmeta_from', 'post', 'side', 'high' );
    }
}

function _postmeta_from_save( $post_id ) {
    global $postmeta_from;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename']) ? $_POST['post_newmetaboxes_noncename'] : '', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_from as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}







/* 
 * post meta keywords
 * ====================================================
*/
$postmeta_keywords_description = array(
    
    array(
        "name" => "title",
        "std" => "",
        "title" => __('标题', 'haoui').'：'
    ),
    array(
        "name" => "keywords",
        "std" => "",
        "title" => __('关键字', 'haoui').'：'
    ),
    array(
        "name" => "description",
        "std" => "",
        "title" => __('描述（建议字数在30-160之间）', 'haoui').'：'
        )
);

if( !_hui('seo_off') && _hui('post_keywords_description_s') ){
    add_action('admin_menu', '_postmeta_keywords_description_create');
    add_action('save_post', '_postmeta_keywords_description_save');
}

function _postmeta_keywords_description() {
    global $post, $postmeta_keywords_description;
    foreach($postmeta_keywords_description as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<p>'.$meta_box['title'].'</p>';
        if( $meta_box['name'] == 'description' ){
            echo '<p><textarea style="width:98%" name="'.$meta_box['name'].'" rows="4">'.$meta_box_value.'</textarea></p>';
        }else{
            echo '<p><input type="text" style="width:98%" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';
        }
    }
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function _postmeta_keywords_description_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_keywords_description_boxes', __('SEO设置', 'haoui'), '_postmeta_keywords_description', 'post', 'side', 'high' );
        add_meta_box( 'postmeta_keywords_description_boxes', __('SEO设置', 'haoui'), '_postmeta_keywords_description', 'page', 'side', 'high' );
    }
}

function _postmeta_keywords_description_save( $post_id ) {
    global $postmeta_keywords_description;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename'])?$_POST['post_newmetaboxes_noncename']:'', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_keywords_description as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}








$postmeta_subtitle = array(
    array(
        "name" => "subtitle",
        "std" => ""
    )
);

add_action('admin_menu', 'hui_postmeta_subtitle_create');
add_action('save_post', 'hui_postmeta_subtitle_save');


function hui_postmeta_subtitle() {
    global $post, $postmeta_subtitle;
    foreach($postmeta_subtitle as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<p>'.(isset($meta_box['title']) ? $meta_box['title'] : '').'</p>';
        echo '<p><input type="text" style="width:98%" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';
    }
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function hui_postmeta_subtitle_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_subtitle_boxes', __('副标题', 'haoui'), 'hui_postmeta_subtitle', 'post', 'side', 'high' );
    }
}

function hui_postmeta_subtitle_save( $post_id ) {
    global $postmeta_subtitle;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename'])?$_POST['post_newmetaboxes_noncename']:'', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_subtitle as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}











$postmeta_thumblink = array(
    array(
        "name" => "thumblink",
        "std" => ""
    )
);

if( _hui('thumblink_s') ){
    add_action('admin_menu', 'hui_postmeta_thumblink_create');
    add_action('save_post', 'hui_postmeta_thumblink_save');
}


function hui_postmeta_thumblink() {
    global $post, $postmeta_thumblink;
    foreach($postmeta_thumblink as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<p>'.(isset($meta_box['title']) ? $meta_box['title'] : '').'</p>';
        echo '<p><input type="text" style="width:98%" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';
    }
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function hui_postmeta_thumblink_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_thumblink_boxes', __('外链缩略图地址', 'haoui'), 'hui_postmeta_thumblink', 'post', 'side', 'high' );
    }
}

function hui_postmeta_thumblink_save( $post_id ) {
    global $postmeta_thumblink;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename'])?$_POST['post_newmetaboxes_noncename']:'', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_thumblink as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}













$postmeta_original = array(
    array(
        "name" => "is_original",
        "std" => ""
    )
);

if( _hui('original_s') ){
    add_action('admin_menu', 'hui_postmeta_original_create');
    add_action('save_post', 'hui_postmeta_original_save');
}


function hui_postmeta_original() {
    global $post, $postmeta_original;
    foreach($postmeta_original as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<p>'.(isset($meta_box['title']) ? $meta_box['title'] : '').'</p>';
        echo '<p><input type="text" style="width:98%" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';
    }
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function hui_postmeta_original_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_original_boxes', __('外链缩略图地址', 'haoui'), 'hui_postmeta_original', 'post', 'side', 'high' );
    }
}

function hui_postmeta_original_save( $post_id ) {
    global $postmeta_original;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename'])?$_POST['post_newmetaboxes_noncename']:'', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_original as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}








/*$postmeta_xzh = array(
    array(
        "title" => "原创文章",
        "name" => "is_original",
        "std" => ""
    )
);

if( _hui('xzh_on') ){
    add_action('admin_menu', 'hui_postmeta_xzh_create');
    add_action('save_post', 'hui_postmeta_xzh_save');
}

function hui_postmeta_xzh() {
    global $post, $postmeta_xzh;
    foreach($postmeta_xzh as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo '<p><label><input '.($meta_box_value?'checked':'').' type="checkbox" value="1" name="'.$meta_box['name'].'"> '.(isset($meta_box['title']) ? $meta_box['title'] : '').'</label></p>';
    }
    $tui = get_post_meta($post->ID, 'xzh_tui_back', true);
    if( $tui ) echo '<p>实时推送结果：'.$tui.'</p>';
   
    echo '<input type="hidden" name="post_newmetaboxes_noncename" id="post_newmetaboxes_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function hui_postmeta_xzh_create() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_xzh_boxes', __('百度熊掌号设置', 'haoui'), 'hui_postmeta_xzh', 'post', 'side', 'high' );
    }
}

function hui_postmeta_xzh_save( $post_id ) {
    global $postmeta_xzh;
   
    if ( !wp_verify_nonce( isset($_POST['post_newmetaboxes_noncename'])?$_POST['post_newmetaboxes_noncename']:'', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($postmeta_xzh as $meta_box) {
        $data = isset($_POST[$meta_box['name']]) ? $_POST[$meta_box['name']] : '';
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}*/






$tb_product = array(
    array(
        "name" => "price"
        , "std" => ""
        , "title" => '价格：'
    ),
    array(
        "name" => "link"
        , "std" => ""
        , "title" => '直达链接：'
    )
);
add_action('admin_menu', 'tb_product_create');
add_action('save_post', 'tb_product_save');
function tb_product_init() {
    global $post, $tb_product;
    foreach($tb_product as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
        if($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        if( isset($meta_box['title']) ) echo'<p style="margin-bottom:4px;">'.$meta_box['title'].'</p>';
        echo '<p style="margin-top:0;"><input type="text" style="width:100%;" value="'.$meta_box_value.'" name="'.$meta_box['name'].'"></p>';
    }
    echo '<input type="hidden" name="tb_product_noncename" id="tb_product_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}

function tb_product_create() {
    if ( function_exists('add_meta_box') ) {
        add_meta_box( 'postmeta_product_boxes', __('产品设置', 'haoui'), 'tb_product_init', 'post', 'side', 'high' );
    }
}

function tb_product_save( $post_id ) {
    global $tb_product;
   
    if ( !wp_verify_nonce( isset($_POST['tb_product_noncename']) ? $_POST['tb_product_noncename'] : '', plugin_basename(__FILE__) ))
        return;
   
    if ( !current_user_can( 'edit_posts', $post_id ))
        return;
                   
    foreach($tb_product as $meta_box) {
        $data = $_POST[$meta_box['name']];
        if(get_post_meta($post_id, $meta_box['name']) == "")
            add_post_meta($post_id, $meta_box['name'], $data, true);
        elseif($data != get_post_meta($post_id, $meta_box['name'], true))
            update_post_meta($post_id, $meta_box['name'], $data);
        elseif($data == "")
            delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
    }
}










// baidu tui
////////////////////////////////////////////////////////////////////////////////////////////////////

if( _hui('bdtui_on') ){
    add_action('publish_post', 'tb_post_to_baidu_tui');
    add_action('publish_future_post', 'tb_post_to_baidu_tui');
}
function tb_post_to_baidu_tui($postid) {
    $plink = get_permalink($postid);
    if( $plink ){

        if( _hui('bdtui_kuai_api') && isset($_POST['baidutui_kuai_on']) && $_POST['baidutui_kuai_on'] && !get_post_meta($postid, 'baidutui_kuai', true) ){
            $ch = curl_init();
            $options =  array(
                CURLOPT_URL            => _hui('bdtui_kuai_api'),
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => $plink,
                CURLOPT_HTTPHEADER     => array('Content-Type: text/plain')
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            update_post_meta($postid, 'baidutui_kuai', $result);
        }

        if( _hui('bdtui_api') && !get_post_meta($postid, 'baidutui', true) ){
            $ch = curl_init();
            $options =  array(
                CURLOPT_URL            => _hui('bdtui_api'),
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => $plink,
                CURLOPT_HTTPHEADER     => array('Content-Type: text/plain')
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            update_post_meta($postid, 'baidutui', $result);
        }

    }
}




if( _hui('bdtui_on') ) add_action( 'add_meta_boxes', 'tbcm_meta_boxs2' );
function tbcm_meta_boxs2() {
    add_meta_box( 'tb_baidu_tui', '百度收录', 'tb_baidu_tui_init', 'post', 'side', 'low' );
}

function tb_baidu_tui_init() {
    global $post;
    $tui = get_post_meta($post->ID, 'baidutui', true);
    $kuai = get_post_meta($post->ID, 'baidutui_kuai', true);
    echo '<br>';
    echo '<label><input type="checkbox" name="baidutui_kuai_on" id="">快速收录</label>';
    echo '<br>';
    echo '<br>';

    if( $kuai ){
        $kuaiObj = json_decode( $kuai );
        echo '<p><strong>快速收录：'.(isset($kuaiObj->success_daily)&&$kuaiObj->success_daily>0?'<span style="color:#46B450">推送成功</span>':'<span style="color:#FF5E52">推送失败</span>').'</strong></p>';
        echo '<p>推送结果：<code style="word-break:break-all">'.($kuai?$kuai:'').'</code></p>';
        echo '<br>';
    }

    if( $tui ){
        $tuiObj = json_decode( $tui );
        echo '<p><strong>普通收录：'.(isset($tuiObj->success)&&$tuiObj->success>0?'<span style="color:#46B450">推送成功</span>':'<span style="color:#FF5E52">推送失败</span>').'</strong></p>';
        echo '<p>推送结果：<code style="word-break:break-all">'.($tui?$tui:'').'</code></p>';
    }else{
        echo '<p><strong>普通收录：</strong>将在发布或更新文章时推送</p>';
    }

    echo '<input type="hidden" name="tb_baidu_tui_noncename" id="tb_baidu_tui_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}










if( !_hui('seo_off') && _hui('post_keywords_description_s') ){
    add_filter('manage_post_posts_columns', 'tb_add_posts_columns');
    add_action('manage_posts_custom_column', 'tb_render_posts_columns', 10, 2);

    add_action('quick_edit_custom_box',  'tb_quick_edit_custom_box', 10, 2);
    add_action('save_post', 'tb_quick_edit_save');
    add_action('admin_footer', 'tb_quick_edit_script');
}

function tb_add_posts_columns($columns) {
    $columns['tbseo'] = 'SEO';
    return $columns;
}

function tb_render_posts_columns($column_name, $id) {
    if ($column_name != 'tbseo'){
        return;
    }
    $title = get_post_meta( $id, 'title', true);
    $keywords = get_post_meta( $id, 'keywords', true);
    $description = get_post_meta( $id, 'description', true);
    if( $title ){
        echo '<span class="tbseo-title">'.$title.'</span>';
    }else{
        echo '标题 自动';
    }
    echo "<br>";
    if( $keywords ){
        echo '<span class="tbseo-keywords">'.$keywords.'</span>';
    }else{
        echo '关键字 自动';
    }
    echo "<br>";
    if( $description ){
        echo '<span class="tbseo-description">'.$description.'</span>';
    }else{
        echo '描述 自动';
    }
}

function tb_quick_edit_custom_box($column_name, $post_type) {
    if ($column_name != 'tbseo'){
        return;
    }
    ?>

    <fieldset class="inline-edit-col-right">
        <br/>
        <br/>
        <div class="inline-edit-col">
            <span class="title">SEO 标题</span>
            <span class="input-text-wrap"><input class="tbseo-title" type="text" name="tbseo[title]" value=""></span>
            <span class="title">SEO 关键字</span>
            <span class="input-text-wrap"><input class="tbseo-keywords" type="text" name="tbseo[keywords]" value=""></span>
            <span class="title">SEO 描述</span>
            <span class="input-text-wrap"><textarea class="tbseo-description" style="display:block;width:100%;" rows="3" name="tbseo[description]" autocomplete="off"></textarea></span>
        </div>
    </fieldset>
    <?php
}

function tb_quick_edit_save($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
        return $post_id;
    }

    if ( !current_user_can( 'edit_post', $post_id ) || 'post' !== $_POST['post_type'] ){
        return $post_id;
    }

    $post = get_post($post_id);
    if (isset($_POST['tbseo']) && ($post->post_type != 'revision')) {
        foreach ($_POST['tbseo'] as $key => $value) {
            if ($value){
                update_post_meta( $post_id, $key, $value);
            }else{
                delete_post_meta( $post_id, $key);
            }
        }
        
    }
    return $post_id;
}

function tb_quick_edit_script() {
    $current_screen = get_current_screen(); 
    if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')){
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(function($){
            var wp_inline_edit_function = inlineEditPost.edit;
            inlineEditPost.edit = function( post_id ) {
                wp_inline_edit_function.apply( this, arguments );
                var id = 0;
                if ( typeof( post_id ) == 'object' ) {
                    id = parseInt( this.getId( post_id ) )
                }
                if ( id > 0 ) {
                    var edit_row = $( '#edit-' + id ), post_row = $( '#post-' + id )
                    $('.tbseo-title', edit_row).val( $('.tbseo-title', post_row).text() )
                    $('.tbseo-keywords', edit_row).val( $('.tbseo-keywords', post_row).text() )
                    $('.tbseo-description', edit_row).val( $('.tbseo-description', post_row).text() )
                }
            }
        });
    </script>
    <?php
}
