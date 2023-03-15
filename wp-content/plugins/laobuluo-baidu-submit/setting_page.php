<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/layui.css' />
<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/laobuluo.css'/>
<script src='<?php echo plugin_dir_url( __FILE__ );?>layui/layui.js'></script>
<div class="wrap">
    <h1 class="wp-heading-inline"></h1>
</div>
<div class="container-laobuluo-main">
    <div class="laobuluo-wbs-header" style="margin-bottom: 15px;">
        <div class="laobuluo-wbs-logo"><a><img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/logo.png"></a><span class="wbs-span">老部落百度快速收录插件</span><span class="wbs-free">Free V2.2</span></div>
        <div class="laobuluo-wbs-btn">
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/?utm_source=lbs-setting&utm_media=link&utm_campaign=header" target="_blank"><i class="layui-icon layui-icon-home"></i> 插件主页</a>
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/baidu-submit.html?utm_source=lbs-setting&utm_media=link&utm_campaign=header" target="_blank"><i class="layui-icon layui-icon-release"></i> 插件教程</a>
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
                            <legend><a name="get">设置选项</a></legend>
                        </fieldset>
                        <form class="layui-form wpcosform" action="<?php echo wp_nonce_url('./admin.php?page=' . $this->base_folder . '/setting'); ?>" name="laobuluo_baidu_submit_form" method="post">
                            <div class="layui-form-item">
                                <label class="layui-form-label">开启插件</label>
                                <div class="layui-input-block">
                                    <input <?php if ($this->options['switch']) echo 'checked="TRUE"'; ?> type="checkbox" name="switch" title="开启">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">文章类型</label>
                                <div class="layui-input-inline" style="width:90px;">
                                    <input lay-skin="primary" <?php if ($this->options['is_post']) echo 'checked="TRUE"'; ?> type="checkbox" name="is_post" name="switch" title="文章">
                                </div>
                                <div class="layui-input-inline">
                                    <input lay-skin="primary" <?php if ($this->options['is_page']) echo 'checked="TRUE"'; ?> type="checkbox" name="is_page" title="页面">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                 <label class="layui-form-label">Token</label>
                                 <div class="layui-input-inline">
                                     <input class="layui-input" type="text" name="token" value="<?php echo esc_attr($this->options['token']); ?>" size="30" placeholder="输入Token"/>
                                 </div>
                                 <p class="layui-form-mid layui-word-aux">百度站长平台查看我们账户的Token值</p>
                            </div>
                            <div class="layui-form-item">
                                 <label class="layui-form-label">开启类型</label>
                                 <div class="layui-input-inline">
                                      <input lay-skin="primary" <?php if($this->options['is_daily']) echo 'checked="TRUE"'; ?> type="checkbox" name="is_daily" title="开启快速收录"/> 
                                 </div>
                                 <div class="layui-input-inline">
                                      <input lay-skin="primary" <?php if($this->options['is_normal']) echo 'checked="TRUE"'; ?> type="checkbox" name="is_normal" title="开启自动普通收录"/>
                                 </div>
                            </div>
                            <div class="layui-form-item">
                                 <label class="layui-form-label"></label>
                                 <p class="layui-form-mid layui-word-aux">只有选择开启后，才会在编辑文章的时候看到可选勾选给当篇文章选择何种收录推送</p>
                            </div>
                                                   
                                                     
                            <div class="layui-form-item">
                                 <label class="layui-form-label"></label>
                                 <div class="layui-input-block">
                                     <input type="submit" name="submit" value="保存设置" class="layui-btn" />
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
                        <div class="laobuluo-panel-title">商家推荐 <span class="layui-badge layui-bg-orange">每月便宜云服务器商</span></div>
                        <div class="laobuluo-shangjia">
                            <a href="https://www.laobuluo.com/4927.html?utm_source=lbs-setting&utm_media=link&utm_campaign=rightsads" target="_blank">
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
                     <a href="https://www.laobuluo.com/?utm_source=lbs-setting&utm_media=link&utm_campaign=footer"  target="_blank">老部落</a>
                    <a href="https://www.lezaiyun.com/?utm_source=lbs-setting&utm_media=link&utm_campaign=footer" target="_blank">插件官方</a>
                    <a href="https://www.lezaiyun.com/baidu-submit.html?utm_source=lbs-setting&utm_media=link&utm_campaign=footer" target="_blank">使用说明</a>
                    <a href="https://www.lezaiyun.com/about/?utm_source=lbs-setting&utm_media=link&utm_campaign=footer" target="_blank">关于我们</a>
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