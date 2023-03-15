<?php

    function wppsmp_update_xml($xml_contents){
		
        $wppsmp_siteurl = home_url();
        $wppsmp_updatetime = current_time('timestamp', '1');
        $wppsmp_lasttime = date("Y-m-d\TH:i:s+00:00",$wppsmp_updatetime);

        $xml_begin = '<?xml version="1.0" encoding="UTF-8"?>'.wppsmp_xml_annotate().'<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml_home = "<url><loc>$wppsmp_siteurl</loc><lastmod>$wppsmp_lasttime</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>";
        $xml_end = '</urlset>';

        if($xml_contents){
            $sitemap_xml = $xml_begin.$xml_home.$xml_contents.$xml_end;
            $wppsmp_get_sitepath = wppsmp_get_sitepath();
            $filename = $wppsmp_get_sitepath.'sitemap.xml';
			
            if( wppsmp_get_filestatus($wppsmp_get_sitepath) || wppsmp_get_filestatus($filename) ){
                file_put_contents("$filename","$sitemap_xml");
                @chmod($filename, 0777);
                wppsmp_show_messages("生成XML地图成功");
            }else{
                wppsmp_show_messages("生成XML地图失败，目录不可写入");
            }
        }else{
            wppsmp_show_messages("生成XML地图失败，没有内容");
        }
    }