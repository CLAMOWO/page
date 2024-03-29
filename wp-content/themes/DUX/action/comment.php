<?php
if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
	header('Allow: POST');
	header('HTTP/1.1 405 Method Not Allowed');
	header('Content-Type: text/plain');
	exit;
}

require( dirname(__FILE__).'/../../../../wp-load.php' );
nocache_headers();
$comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
$post = get_post($comment_post_ID);
if ( empty($post->comment_status) ) {
	do_action('comment_id_not_found', $comment_post_ID);
	err(__('Invalid comment status.')); // 將 exit 改為錯誤提示
}

$status = get_post_status($post);
$status_obj = get_post_status_object($status);

do_action('pre_comment_on_post', $comment_post_ID);

$comment_author       = ( isset($_POST['author']) )  ? htmlspecialchars(trim($_POST['author']), ENT_QUOTES) : null;
$comment_author_email = ( isset($_POST['email']) )   ? htmlspecialchars(trim($_POST['email']), ENT_QUOTES) : null;
$comment_author_url   = ( isset($_POST['url']) )     ? htmlspecialchars(trim($_POST['url']), ENT_QUOTES) : null;
$comment_content      = ( isset($_POST['comment']) ) ? htmlspecialchars(trim($_POST['comment']), ENT_QUOTES) : null;
$edit_id              = ( isset($_POST['edit_id']) ) ? $_POST['edit_id'] : null; // 提取 edit_id


// If the user is logged in
$user = wp_get_current_user();
if ( $user->ID ) {
	if ( empty( $user->display_name ) )
		$user->display_name=$user->user_login;
	$comment_author       = esc_sql($user->display_name);
	$comment_author_email = esc_sql($user->user_email);
	$comment_author_url   = esc_sql($user->user_url);
	if ( current_user_can('unfiltered_html') ) {
		if ( isset($_POST['_wp_unfiltered_html_comment']) && wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
			kses_remove_filters(); // start with a clean slate
			kses_init_filters(); // set up the filters
		}
	}
} else {
	if ( get_option('comment_registration') || 'private' == $status )
		err('Hi，你必须登录才能发表评论！'); // 將 wp_die 改為錯誤提示
}


$comment_type = '';
if ( get_option('require_name_email') && !$user->ID ) {
	if ( 6 > strlen($comment_author_email) || '' == $comment_author )
		err( '请填写昵称和邮箱！' ); // 將 wp_die 改為錯誤提示
	elseif ( !is_email($comment_author_email))
		err( '请填写有效的邮箱地址！' ); // 將 wp_die 改為錯誤提示
}
if ( '' == $comment_content )
	err( '请填写点评论！' ); // 將 wp_die 改為錯誤提示
// 增加: 錯誤提示功能
function err($ErrMsg) {
    header('HTTP/1.1 405 Method Not Allowed');
    echo $ErrMsg;
    exit;
}


global $wpdb;
// IF HAS
$dupe = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = '$comment_post_ID' AND ( comment_author = '$comment_author' ";
if ( $comment_author_email ) $dupe .= "OR comment_author_email = '$comment_author_email' ";
$dupe .= ") AND comment_content = '$comment_content' LIMIT 1";
if ( $wpdb->get_var($dupe) ) {
    err( '请不要重复评论' );
}



$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;
$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');
// 增加: 檢查評論是否正被編輯, 更新或新建評論
if ( $edit_id ){
$comment_id = $commentdata['comment_ID'] = $edit_id;
wp_update_comment( $commentdata );
} else {
$comment_id = wp_new_comment( $commentdata );
}
$comment = get_comment($comment_id);
if ( !$user->ID ) {
	$comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
	setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
	setcookie('comment_author_email_' . COOKIEHASH, $comment->comment_author_email, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
	setcookie('comment_author_url_' . COOKIEHASH, esc_url($comment->comment_author_url), time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
}

$comment_depth = 1;   //为评论的 class 属性准备的
$tmp_c = $comment;
while($tmp_c->comment_parent != 0){
$comment_depth++;
$tmp_c = get_comment($tmp_c->comment_parent);
}
//以下是評論式樣, 不含 "回覆". 要用你模板的式樣 copy 覆蓋.

echo '<li '; comment_class(); echo ' id="comment-'.get_comment_ID().'">';
// echo '<span class="comt-f">#</span>';
//头像
echo '<div class="comt-avatar">';
/*global $loguser;
if( $loguser ){
	echo '<img src="'.$loguser->avatar.'">';
}else{*/
	echo _get_the_avatar($user_id=$comment->user_id, $user_email=$comment->comment_author_email, $src=true);
// }
echo '</div>'; 
//内容
echo '<div class="comt-main" id="div-comment-'.get_comment_ID().'">';
	// echo str_replace(' src=', ' data-src=', convert_smilies(get_comment_text()));
	comment_text();
	echo '<div class="comt-meta"><span class="comt-author">'.get_comment_author_link().'</span>';
    echo '<time>'._get_time_ago($comment->comment_date).'</time>'; 
	echo '</div>';
    if ($comment->comment_approved == '0'){
      echo '<span class="comt-approved">待审核</span>';
    }
echo '</div>';
?>
