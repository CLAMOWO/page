<?php

$result = array();

/* Check if user has admin capabilities */
if(current_user_can('manage_options')){

    if(isset($_REQUEST['enable_dark_mode_switch'])){

        /* Control */
        if(isset($_REQUEST['show_support_msg_block'])){
            $this->base_admin->settings->updateSettings("show_support_msg_block", sanitize_text_field($_REQUEST['show_support_msg_block']));
        }
        if(isset($_REQUEST['enable_dark_mode_switch'])){
            $this->base_admin->settings->updateSettings("enable_dark_mode_switch", sanitize_text_field($_REQUEST['enable_dark_mode_switch']));
        }
        if(isset($_REQUEST['enable_default_dark_mode'])){
            $this->base_admin->settings->updateSettings("enable_default_dark_mode", sanitize_text_field($_REQUEST['enable_default_dark_mode']));
        }
        if(isset($_REQUEST['enable_os_aware'])){
            $this->base_admin->settings->updateSettings("enable_os_aware", sanitize_text_field($_REQUEST['enable_os_aware']));
        }
        if(isset($_REQUEST['enable_keyboard_shortcut'])){
            $this->base_admin->settings->updateSettings("enable_keyboard_shortcut", sanitize_text_field($_REQUEST['enable_keyboard_shortcut']));
        }
        if(isset($_REQUEST['enable_time_based_dark'])){
            $this->base_admin->settings->updateSettings("enable_time_based_dark", sanitize_text_field($_REQUEST['enable_time_based_dark']));
        }
        if(isset($_REQUEST['time_based_dark_start'])){
            $this->base_admin->settings->updateSettings("time_based_dark_start", sanitize_text_field($_REQUEST['time_based_dark_start']));
        }
        if(isset($_REQUEST['time_based_dark_stop'])){
            $this->base_admin->settings->updateSettings("time_based_dark_stop", sanitize_text_field($_REQUEST['time_based_dark_stop']));
        }
        if(isset($_REQUEST['hide_on_desktop'])){
            $this->base_admin->settings->updateSettings("hide_on_desktop", sanitize_text_field($_REQUEST['hide_on_desktop']));
        }
        if(isset($_REQUEST['hide_on_mobile'])){
            $this->base_admin->settings->updateSettings("hide_on_mobile", sanitize_text_field($_REQUEST['hide_on_mobile']));
        }
        if(isset($_REQUEST['hide_on_mobile_by'])){
            $this->base_admin->settings->updateSettings("hide_on_mobile_by", sanitize_text_field($_REQUEST['hide_on_mobile_by']));
        }
        if(isset($_REQUEST['enable_switch_in_menu'])){
            $this->base_admin->settings->updateSettings("enable_switch_in_menu", sanitize_text_field($_REQUEST['enable_switch_in_menu']));
        }
        if(isset($_REQUEST['switch_in_menu_location'])){
            $this->base_admin->settings->updateSettings("switch_in_menu_location", sanitize_text_field($_REQUEST['switch_in_menu_location']));
        }
        if(isset($_REQUEST['switch_in_menu_shortcode'])){
            $this->base_admin->settings->updateSettings("switch_in_menu_shortcode", wp_filter_post_kses($_REQUEST['switch_in_menu_shortcode']));
        }

        /* Admin */
        if(isset($_REQUEST['enable_admin_dark_mode'])){
            $this->base_admin->settings->updateSettings("enable_admin_dark_mode", sanitize_text_field($_REQUEST['enable_admin_dark_mode']));
        }

        /* Switch */
        if(isset($_REQUEST['dark_mode_switch_design'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_design", sanitize_text_field($_REQUEST['dark_mode_switch_design']));
        }
        if(isset($_REQUEST['dark_mode_switch_position'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_position", sanitize_text_field($_REQUEST['dark_mode_switch_position']));
        }
        if(isset($_REQUEST['dark_mode_switch_margin_top'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_margin_top", sanitize_text_field($_REQUEST['dark_mode_switch_margin_top']));
        }
        if(isset($_REQUEST['dark_mode_switch_margin_bottom'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_margin_bottom", sanitize_text_field($_REQUEST['dark_mode_switch_margin_bottom']));
        }
        if(isset($_REQUEST['dark_mode_switch_margin_left'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_margin_left", sanitize_text_field($_REQUEST['dark_mode_switch_margin_left']));
        }
        if(isset($_REQUEST['dark_mode_switch_margin_right'])){
            $this->base_admin->settings->updateSettings("dark_mode_switch_margin_right", sanitize_text_field($_REQUEST['dark_mode_switch_margin_right']));
        }
        if(isset($_REQUEST['enable_absolute_position'])){
            $this->base_admin->settings->updateSettings("enable_absolute_position", sanitize_text_field($_REQUEST['enable_absolute_position']));
        }

        /* Switch Apple*/
        if(isset($_REQUEST['switch_apple_width_height'])){
            $this->base_admin->settings->updateSettings("switch_apple_width_height", sanitize_text_field($_REQUEST['switch_apple_width_height']));
        }
        if(isset($_REQUEST['switch_apple_border_radius'])){
            $this->base_admin->settings->updateSettings("switch_apple_border_radius", sanitize_text_field($_REQUEST['switch_apple_border_radius']));
        }
        if(isset($_REQUEST['switch_apple_icon_width'])){
            $this->base_admin->settings->updateSettings("switch_apple_icon_width", sanitize_text_field($_REQUEST['switch_apple_icon_width']));
        }
        if(isset($_REQUEST['switch_apple_light_mode_bg'])){
            $this->base_admin->settings->updateSettings("switch_apple_light_mode_bg", sanitize_text_field($_REQUEST['switch_apple_light_mode_bg']));
        }
        if(isset($_REQUEST['switch_apple_dark_mode_bg'])){
            $this->base_admin->settings->updateSettings("switch_apple_dark_mode_bg", sanitize_text_field($_REQUEST['switch_apple_dark_mode_bg']));
        }
        if(isset($_REQUEST['switch_apple_light_mode_icon_color'])){
            $this->base_admin->settings->updateSettings("switch_apple_light_mode_icon_color", sanitize_text_field($_REQUEST['switch_apple_light_mode_icon_color']));
        }
        if(isset($_REQUEST['switch_apple_dark_mode_icon_color'])){
            $this->base_admin->settings->updateSettings("switch_apple_dark_mode_icon_color", sanitize_text_field($_REQUEST['switch_apple_dark_mode_icon_color']));
        }
        /* Switch Banana*/
        if(isset($_REQUEST['switch_banana_width_height'])){
            $this->base_admin->settings->updateSettings("switch_banana_width_height", sanitize_text_field($_REQUEST['switch_banana_width_height']));
        }
        if(isset($_REQUEST['switch_banana_border_radius'])){
            $this->base_admin->settings->updateSettings("switch_banana_border_radius", sanitize_text_field($_REQUEST['switch_banana_border_radius']));
        }
        if(isset($_REQUEST['switch_banana_icon_width'])){
            $this->base_admin->settings->updateSettings("switch_banana_icon_width", sanitize_text_field($_REQUEST['switch_banana_icon_width']));
        }
        if(isset($_REQUEST['switch_banana_light_mode_bg'])){
            $this->base_admin->settings->updateSettings("switch_banana_light_mode_bg", sanitize_text_field($_REQUEST['switch_banana_light_mode_bg']));
        }
        if(isset($_REQUEST['switch_banana_dark_mode_bg'])){
            $this->base_admin->settings->updateSettings("switch_banana_dark_mode_bg", sanitize_text_field($_REQUEST['switch_banana_dark_mode_bg']));
        }
        if(isset($_REQUEST['switch_banana_light_mode_icon_color'])){
            $this->base_admin->settings->updateSettings("switch_banana_light_mode_icon_color", sanitize_text_field($_REQUEST['switch_banana_light_mode_icon_color']));
        }
        if(isset($_REQUEST['switch_banana_dark_mode_icon_color'])){
            $this->base_admin->settings->updateSettings("switch_banana_dark_mode_icon_color", sanitize_text_field($_REQUEST['switch_banana_dark_mode_icon_color']));
        }





        /* Preset */
        if(isset($_REQUEST['dark_mode_color_preset'])){
            $this->base_admin->settings->updateSettings("dark_mode_color_preset", sanitize_text_field($_REQUEST['dark_mode_color_preset']));
        }
        if(isset($_REQUEST['dark_mode_bg'])){
            $this->base_admin->settings->updateSettings("dark_mode_bg", sanitize_text_field($_REQUEST['dark_mode_bg']));
        }
        if(isset($_REQUEST['dark_mode_secondary_bg'])){
            $this->base_admin->settings->updateSettings("dark_mode_secondary_bg", sanitize_text_field($_REQUEST['dark_mode_secondary_bg']));
        }
        if(isset($_REQUEST['dark_mode_text_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_text_color", sanitize_text_field($_REQUEST['dark_mode_text_color']));
        }
        if(isset($_REQUEST['dark_mode_link_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_link_color", sanitize_text_field($_REQUEST['dark_mode_link_color']));
        }
        if(isset($_REQUEST['dark_mode_link_hover_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_link_hover_color", sanitize_text_field($_REQUEST['dark_mode_link_hover_color']));
        }
        if(isset($_REQUEST['dark_mode_input_bg'])){
            $this->base_admin->settings->updateSettings("dark_mode_input_bg", sanitize_text_field($_REQUEST['dark_mode_input_bg']));
        }
        if(isset($_REQUEST['dark_mode_input_text_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_input_text_color", sanitize_text_field($_REQUEST['dark_mode_input_text_color']));
        }
        if(isset($_REQUEST['dark_mode_input_placeholder_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_input_placeholder_color", sanitize_text_field($_REQUEST['dark_mode_input_placeholder_color']));
        }
        if(isset($_REQUEST['dark_mode_border_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_border_color", sanitize_text_field($_REQUEST['dark_mode_border_color']));
        }
        if(isset($_REQUEST['dark_mode_btn_bg'])){
            $this->base_admin->settings->updateSettings("dark_mode_btn_bg", sanitize_text_field($_REQUEST['dark_mode_btn_bg']));
        }
        if(isset($_REQUEST['dark_mode_btn_text_color'])){
            $this->base_admin->settings->updateSettings("dark_mode_btn_text_color", sanitize_text_field($_REQUEST['dark_mode_btn_text_color']));
        }


        /* Media */
        if(isset($_REQUEST['enable_low_image_brightness'])){
            $this->base_admin->settings->updateSettings("enable_low_image_brightness", sanitize_text_field($_REQUEST['enable_low_image_brightness']));
        }
        if(isset($_REQUEST['image_brightness_to'])){
            $this->base_admin->settings->updateSettings("image_brightness_to", sanitize_text_field($_REQUEST['image_brightness_to']));
        }
        if(isset($_REQUEST['enable_image_grayscale'])){
            $this->base_admin->settings->updateSettings("enable_image_grayscale", sanitize_text_field($_REQUEST['enable_image_grayscale']));
        }
        if(isset($_REQUEST['image_grayscale_to'])){
            $this->base_admin->settings->updateSettings("image_grayscale_to", sanitize_text_field($_REQUEST['image_grayscale_to']));
        }
        if(isset($_REQUEST['enable_bg_image_darken'])){
            $this->base_admin->settings->updateSettings("enable_bg_image_darken", sanitize_text_field($_REQUEST['enable_bg_image_darken']));
        }
        if(isset($_REQUEST['bg_image_darken_to'])){
            $this->base_admin->settings->updateSettings("bg_image_darken_to", sanitize_text_field($_REQUEST['bg_image_darken_to']));
        }
        if(isset($_REQUEST['enable_invert_inline_svg'])){
            $this->base_admin->settings->updateSettings("enable_invert_inline_svg", sanitize_text_field($_REQUEST['enable_invert_inline_svg']));
        }


        /* Video */
        if(isset($_REQUEST['enable_low_video_brightness'])){
            $this->base_admin->settings->updateSettings("enable_low_video_brightness", sanitize_text_field($_REQUEST['enable_low_video_brightness']));
        }
        if(isset($_REQUEST['video_brightness_to'])){
            $this->base_admin->settings->updateSettings("video_brightness_to", sanitize_text_field($_REQUEST['video_brightness_to']));
        }
        if(isset($_REQUEST['enable_video_grayscale'])){
            $this->base_admin->settings->updateSettings("enable_video_grayscale", sanitize_text_field($_REQUEST['enable_video_grayscale']));
        }
        if(isset($_REQUEST['video_grayscale_to'])){
            $this->base_admin->settings->updateSettings("video_grayscale_to", sanitize_text_field($_REQUEST['video_grayscale_to']));
        }

        /* Restriction */


        $result = array("status" => "true");

    }else{
        $result = array("status" => 'false');
    }
}else{
    $result = array("status" => 'false');
}

echo json_encode($result,  JSON_UNESCAPED_UNICODE);