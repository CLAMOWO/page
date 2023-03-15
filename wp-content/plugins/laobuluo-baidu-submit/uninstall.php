<?php
if(!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')){
	// 如果 uninstall 不是从 WordPress 调用，则退出
	exit();
}

// 从 options 表删除选项
delete_option('laobuluo_bs_options');

// 取消定时任务
wp_clear_scheduled_hook('laobuluo_bs_event');
