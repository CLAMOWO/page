<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'DarkMySiteUtils' ) ) {
    class DarkMySiteUtils
    {

        public $base_admin;
        public function __construct($base_admin)
        {
            $this->base_admin = $base_admin;
        }


        public function isMobile() {
            if(function_exists("wp_is_mobile")){
                return wp_is_mobile();
            }else{
                return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
            }
        }
        public function is_hidden_by_user_agent($hide_on_desktop, $hide_on_mobile, $hide_on_mobile_by) {
            if($this->isMobile()){
                if($hide_on_mobile == "1"){
                    if($hide_on_mobile_by == "user_agent" || $hide_on_mobile_by == "both"){
                        return True;
                    }
                }
            }else{
                if($hide_on_desktop == "1"){
                    return True;
                }
            }
            return False;
        }


        public function generateSwitchStyles($settings){
            $styles = array();
            switch ($settings["dark_mode_switch_design"]){
                case "apple":
                    $styles = array(
                        "--darkmysite_switch_apple_width_height" => $settings["switch_apple_width_height"]."px",
                        "--darkmysite_switch_apple_border_radius" => $settings["switch_apple_border_radius"]."px",
                        "--darkmysite_switch_apple_icon_width" => $settings["switch_apple_icon_width"]."px",
                        "--darkmysite_switch_apple_light_mode_bg" => $settings["switch_apple_light_mode_bg"],
                        "--darkmysite_switch_apple_dark_mode_bg" => $settings["switch_apple_dark_mode_bg"],
                        "--darkmysite_switch_apple_light_mode_icon_color" => $settings["switch_apple_light_mode_icon_color"],
                        "--darkmysite_switch_apple_dark_mode_icon_color" => $settings["switch_apple_dark_mode_icon_color"],
                    );
                    break;
                case "banana":
                    $styles = array(
                        "--darkmysite_switch_banana_width_height" => $settings["switch_banana_width_height"]."px",
                        "--darkmysite_switch_banana_border_radius" => $settings["switch_banana_border_radius"]."px",
                        "--darkmysite_switch_banana_icon_width" => $settings["switch_banana_icon_width"]."px",
                        "--darkmysite_switch_banana_light_mode_bg" => $settings["switch_banana_light_mode_bg"],
                        "--darkmysite_switch_banana_dark_mode_bg" => $settings["switch_banana_dark_mode_bg"],
                        "--darkmysite_switch_banana_light_mode_icon_color" => $settings["switch_banana_light_mode_icon_color"],
                        "--darkmysite_switch_banana_dark_mode_icon_color" => $settings["switch_banana_dark_mode_icon_color"],
                    );
                    break;
            }
            return $styles;
        }


        public function generateSwitchStylesForShortcode($atts){
            $styles = array();
            $switch_name = "apple";
            if($atts['switch'] == "1"){
                $switch_name = "apple";
            }else if($atts['switch'] == "2"){
                $switch_name = "banana";
            }
            foreach ($atts as $key => $value){
                if($key == "switch"){ continue; }
                $styles["--darkmysite_switch_".$switch_name."_".$key] = $value;
            }
            return $styles;
        }

        public function generateDisallowedElementsStr($external_support_class_obj){
            $disallowed_elements = "";
            // Get Disallowed Elements from External Plugins
            $disallowed_from_external = $external_support_class_obj->getDisallowedElementsByAvailablePlugins();
            if(sizeof($disallowed_from_external) > 0){
                foreach( $disallowed_from_external as $single_element ) {
                    $disallowed_elements .= ($disallowed_elements != "" ? ", " : "").trim($single_element);
                }
            }
            return $disallowed_elements;
        }


        public function minify_string($buffer) {
            $search = array(
                '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
                '/[^\S ]+\</s',     // strip whitespaces before tags, except space
                '/(\s)+/s',         // shorten multiple whitespace sequences
                '/<!--(.|\s)*?-->/' // Remove HTML comments
            );
            $replace = array(
                '>',
                '<',
                '\\1',
                ''
            );
            $buffer = preg_replace($search, $replace, $buffer);
            return $buffer;
        }

        public function getWpNavMenus() {
            $results = array();
            $results[] = array("id" => "0", "text" => "Choose Menu");
            $menus = wp_get_nav_menus();
            foreach ($menus as $menu) {
                $results[] = array("id" => $menu->term_id, "text" => $menu->name);
            }
            return $results;
        }

    }
}
