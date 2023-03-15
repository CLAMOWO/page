<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'DarkMySiteSettings' ) ) {
    class DarkMySiteSettings
    {

        public $base_admin;

        function __construct($base_admin)
        {

            $this->base_admin = $base_admin;

            $defaultOption = array();
            if (!get_option("darkmysite_settings")) {
                update_option('darkmysite_settings', $defaultOption);
            }

        }



        public function updateSettings($key, $value = "<<darkmysite_empty_value>>")
        {
            $exits = false;
            $exitingValue = Null;
            $dataSettings = get_option("darkmysite_settings");
            $dataNewSettings = array();
            foreach ($dataSettings as $singleSettings) {
                if (isset($singleSettings['key'])) {
                    if ($singleSettings['key'] == $key) {
                        $exits = true;
                        $exitingValue = $singleSettings['value'];
                        $singleSettings['value'] = ($value != "<<darkmysite_empty_value>>") ? $value : $singleSettings['value'];
                    }
                }
                if ($value != "<<darkmysite_empty_value>>") {
                    $dataNewSettings[] = $singleSettings;
                }
            }
            if ($exits && $value != "<<darkmysite_empty_value>>") {
                update_option('darkmysite_settings', $dataNewSettings);
            } else if (!$exits && $value != "<<darkmysite_empty_value>>") {
                $dataNewSettings[] = array("key" => $key, "value" => $value);
                update_option('darkmysite_settings', $dataNewSettings);
            } else if ($exits && $value == "<<darkmysite_empty_value>>") {
                return stripslashes($exitingValue);
            }else{
                return Null;
            }
        }



        public function get_all_darkmysite_settings(){
            $settings = array();

            /* Control */

            $settings["show_support_msg_block"] = $this->updateSettings("show_support_msg_block");
            $settings["show_support_msg_block"] = ($settings["show_support_msg_block"] == Null) ? "1" : $settings["show_support_msg_block"];

            $settings["enable_dark_mode_switch"] = $this->updateSettings("enable_dark_mode_switch");
            $settings["enable_dark_mode_switch"] = ($settings["enable_dark_mode_switch"] == Null) ? "1" : $settings["enable_dark_mode_switch"];

            $settings["enable_default_dark_mode"] = $this->updateSettings("enable_default_dark_mode");
            $settings["enable_default_dark_mode"] = ($settings["enable_default_dark_mode"] == Null) ? "0" : $settings["enable_default_dark_mode"];

            $settings["enable_os_aware"] = $this->updateSettings("enable_os_aware");
            $settings["enable_os_aware"] = ($settings["enable_os_aware"] == Null) ? "1" : $settings["enable_os_aware"];

            $settings["enable_keyboard_shortcut"] = $this->updateSettings("enable_keyboard_shortcut");
            $settings["enable_keyboard_shortcut"] = ($settings["enable_keyboard_shortcut"] == Null) ? "1" : $settings["enable_keyboard_shortcut"];

            $settings["enable_time_based_dark"] = $this->updateSettings("enable_time_based_dark");
            $settings["enable_time_based_dark"] = ($settings["enable_time_based_dark"] == Null) ? "0" : $settings["enable_time_based_dark"];

            $settings["time_based_dark_start"] = $this->updateSettings("time_based_dark_start");
            $settings["time_based_dark_start"] = ($settings["time_based_dark_start"] == Null) ? "19:00" : $settings["time_based_dark_start"];

            $settings["time_based_dark_stop"] = $this->updateSettings("time_based_dark_stop");
            $settings["time_based_dark_stop"] = ($settings["time_based_dark_stop"] == Null) ? "07:00" : $settings["time_based_dark_stop"];

            $settings["hide_on_desktop"] = $this->updateSettings("hide_on_desktop");
            $settings["hide_on_desktop"] = ($settings["hide_on_desktop"] == Null) ? "0" : $settings["hide_on_desktop"];

            $settings["hide_on_mobile"] = $this->updateSettings("hide_on_mobile");
            $settings["hide_on_mobile"] = ($settings["hide_on_mobile"] == Null) ? "0" : $settings["hide_on_mobile"];

            $settings["hide_on_mobile_by"] = $this->updateSettings("hide_on_mobile_by");
            $settings["hide_on_mobile_by"] = ($settings["hide_on_mobile_by"] == Null) ? "user_agent" : $settings["hide_on_mobile_by"];

            $settings["enable_switch_in_menu"] = $this->updateSettings("enable_switch_in_menu");
            $settings["enable_switch_in_menu"] = ($settings["enable_switch_in_menu"] == Null) ? "0" : $settings["enable_switch_in_menu"];

            $settings["switch_in_menu_location"] = $this->updateSettings("switch_in_menu_location");
            $settings["switch_in_menu_location"] = ($settings["switch_in_menu_location"] == Null) ? "0" : $settings["switch_in_menu_location"];

            $settings["switch_in_menu_shortcode"] = $this->updateSettings("switch_in_menu_shortcode");
            $settings["switch_in_menu_shortcode"] = ($settings["switch_in_menu_shortcode"] == Null) ? "[darkmysite switch=\"1\"]" : $settings["switch_in_menu_shortcode"];


            /* Admin */

            $settings["enable_admin_dark_mode"] = $this->updateSettings("enable_admin_dark_mode");
            $settings["enable_admin_dark_mode"] = ($settings["enable_admin_dark_mode"] == Null) ? "1" : $settings["enable_admin_dark_mode"];


            /* Switch */

            $settings["dark_mode_switch_design"] = $this->updateSettings("dark_mode_switch_design");
            $settings["dark_mode_switch_design"] = ($settings["dark_mode_switch_design"] == Null) ? "apple" : $settings["dark_mode_switch_design"];

            $settings["dark_mode_switch_position"] = $this->updateSettings("dark_mode_switch_position");
            $settings["dark_mode_switch_position"] = ($settings["dark_mode_switch_position"] == Null) ? "bottom_right" : $settings["dark_mode_switch_position"];

            $settings["dark_mode_switch_margin_top"] = $this->updateSettings("dark_mode_switch_margin_top");
            $settings["dark_mode_switch_margin_top"] = ($settings["dark_mode_switch_margin_top"] == Null) ? "40" : $settings["dark_mode_switch_margin_top"];

            $settings["dark_mode_switch_margin_bottom"] = $this->updateSettings("dark_mode_switch_margin_bottom");
            $settings["dark_mode_switch_margin_bottom"] = ($settings["dark_mode_switch_margin_bottom"] == Null) ? "40" : $settings["dark_mode_switch_margin_bottom"];

            $settings["dark_mode_switch_margin_left"] = $this->updateSettings("dark_mode_switch_margin_left");
            $settings["dark_mode_switch_margin_left"] = ($settings["dark_mode_switch_margin_left"] == Null) ? "40" : $settings["dark_mode_switch_margin_left"];

            $settings["dark_mode_switch_margin_right"] = $this->updateSettings("dark_mode_switch_margin_right");
            $settings["dark_mode_switch_margin_right"] = ($settings["dark_mode_switch_margin_right"] == Null) ? "40" : $settings["dark_mode_switch_margin_right"];

            $settings["enable_absolute_position"] = $this->updateSettings("enable_absolute_position");
            $settings["enable_absolute_position"] = ($settings["enable_absolute_position"] == Null) ? "0" : $settings["enable_absolute_position"];


            //========== Switch Apple ===============
            $settings["switch_apple_width_height"] = $this->updateSettings("switch_apple_width_height");
            $settings["switch_apple_width_height"] = ($settings["switch_apple_width_height"] == Null) ? "60" : $settings["switch_apple_width_height"];

            $settings["switch_apple_border_radius"] = $this->updateSettings("switch_apple_border_radius");
            $settings["switch_apple_border_radius"] = ($settings["switch_apple_border_radius"] == Null) ? "7" : $settings["switch_apple_border_radius"];

            $settings["switch_apple_icon_width"] = $this->updateSettings("switch_apple_icon_width");
            $settings["switch_apple_icon_width"] = ($settings["switch_apple_icon_width"] == Null) ? "30" : $settings["switch_apple_icon_width"];

            $settings["switch_apple_light_mode_bg"] = $this->updateSettings("switch_apple_light_mode_bg");
            $settings["switch_apple_light_mode_bg"] = ($settings["switch_apple_light_mode_bg"] == Null) ? "#121116" : $settings["switch_apple_light_mode_bg"];

            $settings["switch_apple_dark_mode_bg"] = $this->updateSettings("switch_apple_dark_mode_bg");
            $settings["switch_apple_dark_mode_bg"] = ($settings["switch_apple_dark_mode_bg"] == Null) ? "#ffffff" : $settings["switch_apple_dark_mode_bg"];

            $settings["switch_apple_light_mode_icon_color"] = $this->updateSettings("switch_apple_light_mode_icon_color");
            $settings["switch_apple_light_mode_icon_color"] = ($settings["switch_apple_light_mode_icon_color"] == Null) ? "#ffffff" : $settings["switch_apple_light_mode_icon_color"];

            $settings["switch_apple_dark_mode_icon_color"] = $this->updateSettings("switch_apple_dark_mode_icon_color");
            $settings["switch_apple_dark_mode_icon_color"] = ($settings["switch_apple_dark_mode_icon_color"] == Null) ? "#121116" : $settings["switch_apple_dark_mode_icon_color"];

            // ======== Switch Banana ===========
            $settings["switch_banana_width_height"] = $this->updateSettings("switch_banana_width_height");
            $settings["switch_banana_width_height"] = ($settings["switch_banana_width_height"] == Null) ? "60" : $settings["switch_banana_width_height"];

            $settings["switch_banana_border_radius"] = $this->updateSettings("switch_banana_border_radius");
            $settings["switch_banana_border_radius"] = ($settings["switch_banana_border_radius"] == Null) ? "7" : $settings["switch_banana_border_radius"];

            $settings["switch_banana_icon_width"] = $this->updateSettings("switch_banana_icon_width");
            $settings["switch_banana_icon_width"] = ($settings["switch_banana_icon_width"] == Null) ? "38" : $settings["switch_banana_icon_width"];

            $settings["switch_banana_light_mode_bg"] = $this->updateSettings("switch_banana_light_mode_bg");
            $settings["switch_banana_light_mode_bg"] = ($settings["switch_banana_light_mode_bg"] == Null) ? "#121116" : $settings["switch_banana_light_mode_bg"];

            $settings["switch_banana_dark_mode_bg"] = $this->updateSettings("switch_banana_dark_mode_bg");
            $settings["switch_banana_dark_mode_bg"] = ($settings["switch_banana_dark_mode_bg"] == Null) ? "#ffffff" : $settings["switch_banana_dark_mode_bg"];

            $settings["switch_banana_light_mode_icon_color"] = $this->updateSettings("switch_banana_light_mode_icon_color");
            $settings["switch_banana_light_mode_icon_color"] = ($settings["switch_banana_light_mode_icon_color"] == Null) ? "#ffffff" : $settings["switch_banana_light_mode_icon_color"];

            $settings["switch_banana_dark_mode_icon_color"] = $this->updateSettings("switch_banana_dark_mode_icon_color");
            $settings["switch_banana_dark_mode_icon_color"] = ($settings["switch_banana_dark_mode_icon_color"] == Null) ? "#121116" : $settings["switch_banana_dark_mode_icon_color"];




            /* Preset */

            $settings["dark_mode_color_preset"] = $this->updateSettings("dark_mode_color_preset");
            $settings["dark_mode_color_preset"] = ($settings["dark_mode_color_preset"] == Null) ? "black" : $settings["dark_mode_color_preset"];

            $settings["dark_mode_bg"] = $this->updateSettings("dark_mode_bg");
            $settings["dark_mode_bg"] = ($settings["dark_mode_bg"] == Null) ? "#0F0F0F" : $settings["dark_mode_bg"];

            $settings["dark_mode_secondary_bg"] = $this->updateSettings("dark_mode_secondary_bg");
            /* ======== Remove below when uploading version after 20 April 2023 ========= */
            if($settings["dark_mode_secondary_bg"] == Null){
                if($settings["dark_mode_color_preset"] == "black"){
                    $settings["dark_mode_secondary_bg"] = "#171717";
                }else if($settings["dark_mode_color_preset"] == "blue"){
                    $settings["dark_mode_secondary_bg"] = "#182D43";
                }else if($settings["dark_mode_color_preset"] == "green"){
                    $settings["dark_mode_secondary_bg"] = "#112609";
                }else if($settings["dark_mode_color_preset"] == "orange"){
                    $settings["dark_mode_secondary_bg"] = "#211706";
                }else if($settings["dark_mode_color_preset"] == "pink"){
                    $settings["dark_mode_secondary_bg"] = "#210A24";
                }
            }
            /* ======== Remove above when uploading version after 20 April 2023 ========= */
            $settings["dark_mode_secondary_bg"] = ($settings["dark_mode_secondary_bg"] == Null) ? "#171717" : $settings["dark_mode_secondary_bg"];

            $settings["dark_mode_text_color"] = $this->updateSettings("dark_mode_text_color");
            $settings["dark_mode_text_color"] = ($settings["dark_mode_text_color"] == Null) ? "#BEBEBE" : $settings["dark_mode_text_color"];

            $settings["dark_mode_link_color"] = $this->updateSettings("dark_mode_link_color");
            $settings["dark_mode_link_color"] = ($settings["dark_mode_link_color"] == Null) ? "#FFFFFF" : $settings["dark_mode_link_color"];

            $settings["dark_mode_link_hover_color"] = $this->updateSettings("dark_mode_link_hover_color");
            $settings["dark_mode_link_hover_color"] = ($settings["dark_mode_link_hover_color"] == Null) ? "#CCCCCC" : $settings["dark_mode_link_hover_color"];

            $settings["dark_mode_input_bg"] = $this->updateSettings("dark_mode_input_bg");
            $settings["dark_mode_input_bg"] = ($settings["dark_mode_input_bg"] == Null) ? "#2D2D2D" : $settings["dark_mode_input_bg"];

            $settings["dark_mode_input_text_color"] = $this->updateSettings("dark_mode_input_text_color");
            $settings["dark_mode_input_text_color"] = ($settings["dark_mode_input_text_color"] == Null) ? "#BEBEBE" : $settings["dark_mode_input_text_color"];

            $settings["dark_mode_input_placeholder_color"] = $this->updateSettings("dark_mode_input_placeholder_color");
            $settings["dark_mode_input_placeholder_color"] = ($settings["dark_mode_input_placeholder_color"] == Null) ? "#989898" : $settings["dark_mode_input_placeholder_color"];

            $settings["dark_mode_border_color"] = $this->updateSettings("dark_mode_border_color");
            $settings["dark_mode_border_color"] = ($settings["dark_mode_border_color"] == Null) ? "#4A4A4A" : $settings["dark_mode_border_color"];

            $settings["dark_mode_btn_bg"] = $this->updateSettings("dark_mode_btn_bg");
            $settings["dark_mode_btn_bg"] = ($settings["dark_mode_btn_bg"] == Null) ? "#2D2D2D" : $settings["dark_mode_btn_bg"];

            $settings["dark_mode_btn_text_color"] = $this->updateSettings("dark_mode_btn_text_color");
            $settings["dark_mode_btn_text_color"] = ($settings["dark_mode_btn_text_color"] == Null) ? "#BEBEBE" : $settings["dark_mode_btn_text_color"];


            /* Media */

            $settings["enable_low_image_brightness"] = $this->updateSettings("enable_low_image_brightness");
            $settings["enable_low_image_brightness"] = ($settings["enable_low_image_brightness"] == Null) ? "1" : $settings["enable_low_image_brightness"];

            $settings["image_brightness_to"] = $this->updateSettings("image_brightness_to");
            $settings["image_brightness_to"] = ($settings["image_brightness_to"] == Null) ? "80" : $settings["image_brightness_to"];

            $settings["enable_image_grayscale"] = $this->updateSettings("enable_image_grayscale");
            $settings["enable_image_grayscale"] = ($settings["enable_image_grayscale"] == Null) ? "0" : $settings["enable_image_grayscale"];

            $settings["image_grayscale_to"] = $this->updateSettings("image_grayscale_to");
            $settings["image_grayscale_to"] = ($settings["image_grayscale_to"] == Null) ? "80" : $settings["image_grayscale_to"];

            $settings["enable_bg_image_darken"] = $this->updateSettings("enable_bg_image_darken");
            $settings["enable_bg_image_darken"] = ($settings["enable_bg_image_darken"] == Null) ? "1" : $settings["enable_bg_image_darken"];

            $settings["bg_image_darken_to"] = $this->updateSettings("bg_image_darken_to");
            $settings["bg_image_darken_to"] = ($settings["bg_image_darken_to"] == Null) ? "60" : $settings["bg_image_darken_to"];

            $settings["enable_invert_inline_svg"] = $this->updateSettings("enable_invert_inline_svg");
            $settings["enable_invert_inline_svg"] = ($settings["enable_invert_inline_svg"] == Null) ? "0" : $settings["enable_invert_inline_svg"];


            /* Video */

            $settings["enable_low_video_brightness"] = $this->updateSettings("enable_low_video_brightness");
            $settings["enable_low_video_brightness"] = ($settings["enable_low_video_brightness"] == Null) ? "1" : $settings["enable_low_video_brightness"];

            $settings["video_brightness_to"] = $this->updateSettings("video_brightness_to");
            $settings["video_brightness_to"] = ($settings["video_brightness_to"] == Null) ? "80" : $settings["video_brightness_to"];

            $settings["enable_video_grayscale"] = $this->updateSettings("enable_video_grayscale");
            $settings["enable_video_grayscale"] = ($settings["enable_video_grayscale"] == Null) ? "0" : $settings["enable_video_grayscale"];

            $settings["video_grayscale_to"] = $this->updateSettings("video_grayscale_to");
            $settings["video_grayscale_to"] = ($settings["video_grayscale_to"] == Null) ? "80" : $settings["video_grayscale_to"];


            /* Restriction */


            return $settings;
        }


    }

}