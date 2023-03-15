<?php

    function wppsmp_setting_helper(){
        if(isset($_POST['generated_new_sitemap']) && stripslashes_deep($_POST['generated_new_sitemap'])){
            wppsmp_new_sitemap();
        }

        $result = '<div class="wrap">
                <h1>WP泡网站地图插件调试:</h1>
                <h2>说明</h2>
                <p>插件支持自动更新地图，开启文章发布自动生成即可，请勿重复生成。</p>
                <h2>网站地图地址</h2>
                <p>SitemapXML:  '.get_site_url().'/sitemap.xml</p>
                <p>SitemapHtml: '.get_site_url().'/sitemap.html</p>
                <h2>手动生成地图</h2>
                <form action="" method="post" name="generated_new_sitemap" id="generated_new_sitemap" class="validate">
                     <p class="submit"><input type="submit" name="generated_new_sitemap" id="generated_new_sitemap" class="button button-primary" value="生成地图"></p>
                </form>
                </div>
                ';

        echo $result;
    }
