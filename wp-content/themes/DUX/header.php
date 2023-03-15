<!DOCTYPE HTML>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<link rel="dns-prefetch" href="//apps.bdimg.com">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-title" content="<?php echo get_bloginfo('name') ?>">
<meta http-equiv="Cache-Control" content="no-siteapp">
<title><?php echo _title(); ?></title>
<?php wp_head(); ?>
<link rel="shortcut icon" href="<?php echo home_url() . '/favicon.ico' ?>">
<!--[if lt IE 9]><script src="<?php echo get_stylesheet_directory_uri() ?>/js/libs/html5.min.js"></script><![endif]-->
</head>
<body <?php body_class(_bodyclass()); ?>>
<header class="header">
	<div class="container">
		<?php _the_logo(); ?>
		<?php  
			$_brand = _hui('brand');
			if( $_brand ){
				$_brand = explode("\n", $_brand);
				echo '<div class="brand">' . $_brand[0] . '<br>' . $_brand[1] . '</div>';
			}
		?>
		<ul class="site-nav site-navbar">
			<?php _the_menu( (wp_is_mobile()&&has_nav_menu('nav_m'))?'nav_m':'nav' ) ?>
			<?php if( !is_search() && ((_hui('pc_search')&&!wp_is_mobile()) || (_hui('m_search')&&wp_is_mobile())) ){ ?>
				<li class="navto-search"><a href="javascript:;" class="search-show active"><i class="fa fa-search"></i></a></li>
			<?php } ?>
		</ul>
		<?php if( !_hui('topbar_off') ){ ?>
		<div class="topbar">
			<ul class="site-nav topmenu">
				<?php _the_menu('topmenu') ?>
				<?php if( _hui('guanzhu_b') ){ ?>
				<li class="menusns menu-item-has-children">
					<a href="javascript:;"><?php echo _hui('sns_txt') ?></a>
					<ul class="sub-menu">
						<?php if(_hui('wechat')){ echo '<li><a class="sns-wechat" href="javascript:;" title="'._hui('wechat').'" data-src="'._hui('wechat_qr').'">'._hui('wechat').'</a></li>'; } ?>
						<?php for ($i=1; $i < 10; $i++) { 
							if( _hui('sns_tit_'.$i) && _hui('sns_link_'.$i) ){ 
								echo '<li><a target="_blank" rel="external nofollow" href="'._hui('sns_link_'.$i).'">'. _hui('sns_tit_'.$i) .'</a></li>'; 
							}
						} ?>
					</ul>
				</li>
				<?php } ?>
			</ul>
			<?php if( is_user_logged_in() ): global $current_user; ?>
				<?php _moloader('mo_get_user_page', false) ?>
				Hi, <?php echo $current_user->display_name ?>
				<?php if( _hui('user_page_s') ){ ?>
					&nbsp; &nbsp; <a rel="nofollow" href="<?php echo mo_get_user_page() ?>">进入会员中心</a>
				<?php } ?>
				<?php if( is_super_admin() ){ ?>
					&nbsp; &nbsp; <a rel="nofollow" target="_blank" href="<?php echo site_url('/wp-admin/') ?>">后台管理</a>
					&nbsp; &nbsp; <a rel="nofollow" target="_blank" href="<?php echo site_url('/wp-admin/post-new.php') ?>">写文章</a>
					<?php if( is_single() || is_page() ){ ?>
						&nbsp; &nbsp; <?php edit_post_link('[编辑]') ?>
					<?php } ?>
				<?php } ?>
			<?php elseif( _hui('user_page_s') ): ?>
				<?php _moloader('mo_get_user_rp', false) ?>
				<a rel="nofollow" href="javascript:;" class="signin-loader">Hi, 请登录</a>
				&nbsp; &nbsp; <a rel="nofollow" href="javascript:;" class="signup-loader">我要注册</a>
				&nbsp; &nbsp; <a rel="nofollow" href="<?php echo mo_get_user_rp() ?>">找回密码</a>
			<?php endif; ?>
		</div>
		<?php } ?>
		<?php if( _hui('m_navbar') ){ ?>
			<i class="fa fa-bars m-icon-nav"></i>
		<?php } ?>
		<?php if( _hui('user_page_s') ){ ?>
			<?php if( !is_user_logged_in() ){ ?>
				<a rel="nofollow" href="javascript:;" class="signin-loader m-icon-user"><i class="fa fa-user"></i></a>
			<?php }else{ ?>
				<?php _moloader('mo_get_user_page', false) ?>
				<a rel="nofollow" href="<?php echo mo_get_user_page() ?>" class="m-icon-user"><i class="fa fa-user"></i></a>
			<?php } ?>
		<?php } ?>
	</div>
</header>
<div class="site-search">
	<div class="container">
		<?php  
			echo '<form method="get" class="site-search-form" action="'.esc_url( home_url( '/' ) ).'" ><input class="search-input" name="s" type="text" placeholder="输入关键字" value="'.htmlspecialchars($s).'" required="required"><button class="search-btn" type="submit"><i class="fa fa-search"></i></button></form>';
		?>
	</div>
</div>
<?php if( is_single() && _hui('breadcrumbs_single_s') ){ ?>
	<div class="breadcrumbs">
		<div class="container"><?php echo hui_breadcrumbs() ?></div>
	</div>
<?php } ?>
<?php 
	$ads_site_01_on = true;
	if( is_category() ){
		_moloader('mo_is_minicat', false);
		if( mo_is_minicat() ){
			$ads_site_01_on = false;
		}
	}else if( is_page_template( 'pages/navs.php' ) || is_page_template( 'pages/user.php' ) || is_page_template( 'pages/resetpassword.php' ) ){
		$ads_site_01_on = false;
	}
	$ads_site_01_on && _the_ads($name='ads_site_01', $class='orbui-site orbui-site-01');
?>