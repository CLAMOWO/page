<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/layui.css' />
<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/laobuluo.css'/>
<script src='<?php echo plugin_dir_url( __FILE__ );?>layui/layui.js'></script>
<div class="wrap">
    <h1 class="wp-heading-inline"></h1>
</div>

<div class="container-laobuluo-main">
    <div class="laobuluo-wbs-header" style="margin-bottom: 15px;">
        <div class="laobuluo-wbs-logo"><a><img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/logo.png"></a><span class="wbs-span">老部落百度快速收录插件</span><span class="wbs-free">Free V2.1</span></div>
        <div class="laobuluo-wbs-btn">
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/?utm_source=baidu-setting&utm_media=link&utm_campaign=header" target="_blank"><i class="layui-icon layui-icon-home"></i> 插件主页</a>
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/baidu-submit.html?utm_source=baidu-setting&utm_media=link&utm_campaign=header" target="_blank"><i class="layui-icon layui-icon-release"></i> 插件教程</a>
        </div>
    </div>
</div>
<!-- 内容 -->
<div class="container-laobuluo-main">
    <div class="layui-container container-m">
        <div class="layui-row layui-col-space15">
            <!-- 左边 -->
            <div class="layui-col-md9">
                <div class="laobuluo-panel">
                    <div class="laobuluo-controw">
                        <fieldset class="layui-elem-field layui-field-title site-title">
                            <legend><a name="get">批量提交</a></legend>
                        </fieldset>
                        <form class="layui-form wpcosform" action="<?php echo wp_nonce_url('./admin.php?page=' . $this->base_folder . '/manually_submit'); ?>" name="manually_bds_form" method="post">
                            <div class="layui-form-item">
                                <label class="layui-form-label">提交网址</label>
                                <div class="layui-input-block">
                                    <textarea class="layui-textarea" name="manual_links" rows="10" cols="50" id="bds_manual_links" class="large-text code"></textarea>
                                    <div class="layui-form-mid layui-word-aux">一行一条</div>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">推送类型</label>
                                <div class="layui-input-block">
                                    <?php
                                    if (isset($remain_daily) && $remain_daily > 0) {
                                        echo '<input type="checkbox" name="is_daily" lay-skin="primary" title="快速收录 （剩余配额 ' . $remain_daily . ' 条）"/>';
                                    } else {
                                        echo '<div class="layui-form-mid layui-word-aux">快速收录配额不足，请明天再试！</div>';
                                    }
                                    ?>
                                </div>
                                <div class="layui-input-block">
                                    <?php
                                    if (isset($remain) && $remain > 0) {
                                        echo '<input type="checkbox" name="is_normal" lay-skin="primary" title="普通收录 （剩余配额 ' . $remain . ' 条）"/>';
                                    } else {
                                        echo '<div class="layui-form-mid layui-word-aux">普通收录配额不足，请明天再试！</div>';
                                    }
                                    ?>

                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                   <input type="submit" name="submit" value="推送链接" class="layui-btn" /> 
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- 左边 -->
            <!-- 右边  -->
            <div class="layui-col-md3">
                <div id="nav">
                    <div class="laobuluo-panel">
                        <div class="laobuluo-panel-title">商家推荐 <span class="layui-badge layui-bg-orange">优选云服务器商家推荐</span></div>
                        <div class="laobuluo-shangjia">
                            <a href="https://www.laobuluo.com/4927.html?utm_source=baidu-setting&utm_media=link&utm_campaign=rightsads" target="_blank">
                                <img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/ucloud.jpg"></a>
                        </div>
                    </div>
                    <div class="laobuluo-panel">
                        <div class="laobuluo-panel-title">关注公众号</div>
                        <div class="laobuluo-code">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/qrcode.png">
                            <p>微信扫码关注 <span class="layui-badge layui-bg-blue">老蒋玩运营</span> 公众号</p>
                            <p><span class="layui-badge">优先</span> 获取插件更新 和 更多 <span class="layui-badge layui-bg-green">免费插件</span> </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 右边 end -->
        </div>
    </div>
</div>
<!-- 内容 -->
<!-- footer -->
<div class="container-laobuluo-main">
    <div class="layui-container container-m">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="laobuluo-footer-code">
                    <span class="codeshow"></span>
                </div>
                <div class="laobuluo-links">
                    <a href="https://www.laobuluo.com/?utm_source=baidu-setting&utm_media=link&utm_campaign=footer"  target="_blank">老部落</a>
                    <a href="https://www.lezaiyun.com/?utm_source=baidu-setting&utm_media=link&utm_campaign=footer" target="_blank">插件官方</a>
                    <a href="https://www.lezaiyun.com/baidu-submit.html?utm_source=baidu-setting&utm_media=link&utm_campaign=footer" target="_blank">使用说明</a>
                    <a href="https://www.lezaiyun.com/about/?utm_source=baidu-setting&utm_media=link&utm_campaign=footer" target="_blank">关于我们</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- footer -->
<script>
    layui.use(['form', 'element', 'jquery'], function() {
        var $ = layui.jquery;
        var form = layui.form;

        function menuFixed(id) {
            var obj = document.getElementById(id);
            var _getHeight = obj.offsetTop;
            var _Width = obj.offsetWidth
            window.onscroll = function() {
                changePos(id, _getHeight, _Width);
            }
        }

        function changePos(id, height, width) {
            var obj = document.getElementById(id);
            obj.style.width = width + 'px';
            var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            var _top = scrollTop - height;
            if (_top < 150) {
                var o = _top;
                obj.style.position = 'relative';
                o = o > 0 ? o : 0;
                obj.style.top = o + 'px';

            } else {
                obj.style.position = 'fixed';
                obj.style.top = 50 + 'px';

            }
        }
        $(window).resize(function(){
			  if( $(window).width() > 1024 ){
				   menuFixed('nav');
			  } 
		  })

    })
</script>