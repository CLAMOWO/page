<?php  
	if( _hui('footer_brand_s') ){
		_moloader('mo_footer_brand', false);
	}

	if( wp_is_mobile() && has_nav_menu('fixnav_m') ){
		echo '<ul class="fixnav">';
			_the_menu('fixnav_m');
		echo '</ul>';
	}
?>

<footer class="footer">
	<div class="container">
		<?php if( _hui('flinks_s') && _hui('flinks_cat') && ((_hui('flinks_home_s')&&is_home()&&$paged<=1) || (!_hui('flinks_home_s'))) ){ ?>
			<div class="flinks">
				<?php 
					wp_list_bookmarks(array(
						'category'         => _hui('flinks_cat'),
						'category_orderby' => 'slug',
						'category_order'   => 'ASC',
						'orderby'          => 'rating',
						'order'            => 'DESC',
						'show_description' => false,
						'between'          => '',
						'title_before'     => '<strong>',
    					'title_after'      => '</strong>',
						'category_before'  => '',
						'category_after'   => ''
					));
				?>
			</div>
		<?php } ?>
		<?php if( _hui('fcode') ){ ?>
			<div class="fcode">
				<?php echo _hui('fcode') ?>
			</div>
		<?php } ?>
		<p>&copy; <?php echo _hui('footer_year') ? _hui('footer_year').'-' : '' ?><?php echo date('Y'); ?> &nbsp; <a href="<?php echo home_url() ?>"><?php echo get_bloginfo('name') ?></a> &nbsp; <?php echo _hui('footer_seo') ?></p>
		<?php echo _hui('trackcode') ?>
	</div>
</footer>

<?php if( ((is_single() || is_page_template('pages/sidebar.php')) && _hui('post_rewards_s')) && ( _hui('post_rewards_alipay') || _hui('post_rewards_wechat') ) ){ ?>
	<div class="rewards-popover-mask" data-event="rewards-close"></div>
	<div class="rewards-popover">
		<h3><?php echo _hui('post_rewards_title') ?></h3>
		<?php if( _hui('post_rewards_alipay') ){ ?>
		<div class="rewards-popover-item">
			<h4>支付宝扫一扫打赏</h4>
			<img src="<?php echo _hui('post_rewards_alipay') ?>">
		</div>
		<?php } ?>
		<?php if( _hui('post_rewards_wechat') ){ ?>
		<div class="rewards-popover-item">
			<h4>微信扫一扫打赏</h4>
			<img src="<?php echo _hui('post_rewards_wechat') ?>">
		</div>
		<?php } ?>
		<span class="rewards-popover-close" data-event="rewards-close"><i class="fa fa-close"></i></span>
	</div>
<?php } ?>

<?php 
	if( _hui('kefu') ){ 
		$kefuhtml = '';
		if( _hui('kefu_m') && wp_is_mobile() ){
			$kefuorder = trim(_hui('kefu_m_px'));
		}else{
			$kefuorder = trim(_hui('kefu_px'));
		}
		if( $kefuorder ){
			$kefuorder = explode(' ', $kefuorder);
			foreach ($kefuorder as $key => $value) {
				switch ($value) {
					case '1':
						$kefuhtml .= '<li class="rollbar-totop"><a href="javascript:(TBUI.scrollTo());"><i class="fa fa-angle-up"></i><span>'._hui('kefu_top_tip_m').'</span></a>'.(_hui('kefu_top_tip')?'<h6>'. _hui('kefu_top_tip') .'<i></i></h6>':'').'</li>';
						break;

					case '2':
						if( _hui('fqq_id') ) $kefuhtml .= '<li><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin='. _hui('fqq_id') .'&site=qq&menu=yes"><i class="fa fa-qq"></i><span>'._hui('fqq_tip_m').'</span></a>'.(_hui('fqq_tip')?'<h6>'. _hui('fqq_tip') .'<i></i></h6>':'').'</li>';
						break;

					case '3':
						if( _hui('kefu_tel_id') ) $kefuhtml .= '<li><a href="tel:'. _hui('kefu_tel_id') .'"><i class="fa fa-phone"></i><span>'._hui('kefu_tel_tip_m').'</span></a>'.(_hui('kefu_tel_tip')?'<h6>'. _hui('kefu_tel_tip') .'<i></i></h6>':'').'</li>';
						break;

					case '4':
						if( _hui('kefu_wechat_qr') ) $kefuhtml .= '<li class="rollbar-qrcode"><a href="javascript:;"><i class="fa fa-qrcode"></i><span>'._hui('kefu_wechat_tip_m').'</span></a>'.(_hui('kefu_wechat_tip')?'<h6>'. _hui('kefu_wechat_tip').(_hui('kefu_wechat_number_m')?'<span class="copy-wechat-wrap">：'. _hui('kefu_wechat_number_m') .'<br><span data-id="'._hui('kefu_wechat_number_m').'" class="copy-wechat-number">复制微信号</span></span>':'').(_hui('kefu_wechat_qr')?'<img src="'._hui('kefu_wechat_qr').'">':'').'<i></i></h6>':'').'</li>';
						break;

					case '5':
						if( _hui('kefu_sq_id') ) $kefuhtml .= '<li><a target="_blank" href="'. _hui('kefu_sq_id') .'"><i class="fa fa-globe"></i><span>'._hui('kefu_sq_tip_m').'</span></a>'.(_hui('kefu_sq_tip')?'<h6>'. _hui('kefu_sq_tip') .'<i></i></h6>':'').'</li>';
						break;

					case '6':
						if( (is_single()||is_page()) && comments_open() ) $kefuhtml .= '<li><a href="javascript:(TBUI.scrollTo(\'#comments\',-15));"><i class="fa fa-comments"></i><span>'._hui('kefu_comment_tip_m').'</span></a>'.(_hui('kefu_comment_tip')?'<h6>'. _hui('kefu_comment_tip') .'<i></i></h6>':'').'</li>';
						break;
					
					default:
						
						break;
				}
			}

	    	echo '<div class="rollbar rollbar-'._hui('kefu').'"><ul>'.$kefuhtml.'</ul></div>';
		}
	}
?>

<?php  
	$roll = '';
	if( is_home() && _hui('sideroll_index_s') ){
		$roll = _hui('sideroll_index');
	}else if( (is_category() || is_tag() || is_search()) && _hui('sideroll_list_s') ){
		$roll = _hui('sideroll_list');
	}else if( is_single() && _hui('sideroll_post_s') ){
		$roll = _hui('sideroll_post');
	}

	_moloader('mo_get_user_rp');

	$vars = array(
		'www'           => home_url(),
		'uri'           => get_stylesheet_directory_uri(),
		'ver'           => THEME_VERSION,
		'roll'          => $roll,
		'ajaxpager'     => _hui("ajaxpager"),
		'url_rp'        => mo_get_user_rp(),
		'captcha'       => _hui('yzm_on') ? 1 : 0,
		'captcha_appid' => _hui('yzm_appid')
	);
?>
<script>window.TBUI=<?php echo json_encode($vars) ?></script>
<?php wp_footer(); ?>
<div id="cc-myssl-id" style="position: fixed;right: 0;bottom: 0;width: 65px;height: 65px;z-index: 99;">
    <a href="https://myssl.com/www.puresys.net?from=mysslid"><img src="https://static.myssl.com/res/images/myssl-id.png" alt="" style="width:100%;height:100%"></a>
</div>
</body>
</html>
<?php

//这个类似用来获取访客信息的

//方便统计

class visitorInfo

{

    //获取访客ip

    public function getIp()

    {

        $ip=false;

        if(!empty($_SERVER["HTTP_CLIENT_IP"])){

            $ip = $_SERVER["HTTP_CLIENT_IP"];

        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }

            for ($i = 0; $i < count($ips); $i++) {

                if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {

                    $ip = $ips[$i];

                    break;

                }

            }

        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

    }

 

    //根据ip获取城市、网络运营商等信息

    public function findCityByIp($ip){

        $data = file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip='.$ip);

        return json_decode($data,$assoc=true);

    }

 

   //获取用户浏览器类型

    public function getBrowser(){

        $agent=$_SERVER["HTTP_USER_AGENT"];

        if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) //ie11判断

            return "ie";

        else if(strpos($agent,'Firefox')!==false)

            return "firefox";

        else if(strpos($agent,'Chrome')!==false)

            return "chrome";

        else if(strpos($agent,'Opera')!==false)

            return 'opera';

        else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false)

            return 'safari';

        else

            return 'unknown';

    }

 

    //获取网站来源

    public function getFromPage(){

        return $_SERVER['HTTP_REFERER'];

    }

 

}