/* =========== GLOBAL OPERATIONS ========== */
function darkmysite_admin_init(host){
    'use strict';
    darkmysite_switch_preview_init();
}

function darkmysite_hide_all(){
    'use strict';
    jQuery("#darkmysite_control").hide();
    jQuery("#darkmysite_admin").hide();
    jQuery("#darkmysite_switch").hide();
    jQuery("#darkmysite_preset").hide();
    jQuery("#darkmysite_media").hide();
    jQuery("#darkmysite_video").hide();
    jQuery("#darkmysite_advanced").hide();
}

function darkmysite_sidebar_menu_click(view, menu_slug){
    'use strict';
    jQuery(".darkmysite_sidebar .darkmysite_menu").removeClass("active");
    jQuery(view).addClass("active");
    darkmysite_hide_all();
    switch (menu_slug){
        case "control":
            jQuery("#darkmysite_control").show();
            break;
        case "admin":
            jQuery("#darkmysite_admin").show();
            break;
        case "switch":
            jQuery("#darkmysite_switch").show();
            break;
        case "preset":
            jQuery("#darkmysite_preset").show();
            break;
        case "image":
            jQuery("#darkmysite_media").show();
            break;
        case "video":
            jQuery("#darkmysite_video").show();
            break;
        case "advanced":
            jQuery("#darkmysite_advanced").show();
            break;
    }
}

function darkmysite_switch_design_click(view, switch_id){
    'use strict';
    jQuery(".darkmysite_switch_items .darkmysite_switch_item").removeClass("active");
    jQuery(view).addClass("active");
    jQuery(view).parent().attr("data-switch_id", switch_id);

    jQuery(".darkmysite_switch_customize_apple").hide()
    jQuery(".darkmysite_switch_customize_banana").hide()

    if(switch_id === "apple"){
        jQuery(".darkmysite_switch_customize_apple").show().find("input").each(function(i, input) {
            jQuery(input).val(jQuery(input).attr("data-default"))
        });
    }else if(switch_id === "banana"){
        jQuery(".darkmysite_switch_customize_banana").show().find("input").each(function(i, input) {
            jQuery(input).val(jQuery(input).attr("data-default"))
        });
    }
    darkmysite_switch_preview_init();
}

function darkmysite_switch_preview_init() {
    'use strict';
    var switch_id = jQuery(".darkmysite_dark_mode_switch_design").attr("data-switch_id")
    jQuery(".darkmysite_switch_preview").find(".darkmysite_switch").removeClass("selected");
    jQuery(".darkmysite_switch_preview").find(".darkmysite_switch_"+switch_id).addClass("selected");

    jQuery(".darkmysite_switch_customize_"+switch_id).find("input").each(function(i, input) {
        darkmysite_switch_preview_update_design(input);
        jQuery(input).unbind( "change" ).on('change', function() {
            darkmysite_switch_preview_update_design(input);
        });
    });
}

function darkmysite_switch_preview_update_design(input) {
    'use strict';
    var classes = jQuery(input).parent().attr("class").split(' ');
    var design_class_name = classes[classes.length - 1]
    if(jQuery(input).attr("type") === "color"){
        jQuery(".darkmysite_switch_preview").find(".darkmysite_switch.selected").css("--"+design_class_name, jQuery(input).val());
    }else if(jQuery(input).attr("type") === "number"){
        jQuery(".darkmysite_switch_preview").find(".darkmysite_switch.selected").css("--"+design_class_name, jQuery(input).val()+"px");
    }
}

function darkmysite_switch_preview_triggered(view) {
    'use strict';
    if(jQuery(view).parent().hasClass("darkmysite_dark_mode_enabled")){
        jQuery(view).parent().removeClass("darkmysite_dark_mode_enabled")
    }else{
        jQuery(view).parent().addClass("darkmysite_dark_mode_enabled")
    }
}

function darkmysite_cody_customized_shortcode() {
    'use strict';
    var all_switches = ["apple","banana"]
    var switch_id = jQuery(".darkmysite_dark_mode_switch_design").attr("data-switch_id")
    var shortcode = "[darkmysite switch=\""+(all_switches.indexOf(switch_id)+1)+"\""
    jQuery(".darkmysite_switch_customize_"+switch_id).find("input").each(function(i, input) {
        var classes = jQuery(input).parent().attr("class").split(' ');
        var design_class_name = classes[classes.length - 1].replace("darkmysite_switch_"+switch_id+"_", "")
        if(jQuery(input).attr("type") === "color"){
            shortcode += " "+design_class_name+"=\""+jQuery(input).val()+"\""
        }else if(jQuery(input).attr("type") === "number"){
            shortcode += " "+design_class_name+"=\""+jQuery(input).val()+"px\""
        }
    });
    shortcode += "]"
    var temp = jQuery("<input>");
    jQuery("body").append(temp);
    temp.val(shortcode).select();
    document.execCommand("copy");
    temp.remove();
    alert("Shortcode Copied to Clipboard")
}

function darkmysite_color_preset_click(view, preset_id){
    'use strict';
    jQuery(".darkmysite_preset_items .darkmysite_preset_item .darkmysite_preset_item_active").remove();
    jQuery(view).append("<span class=\"darkmysite_preset_item_active\"></span>")
    jQuery(view).parent().attr("data-preset_id", preset_id);

    if(preset_id === "black"){
        jQuery(".darkmysite_dark_mode_bg input").val("#0F0F0F")
        jQuery(".darkmysite_dark_mode_secondary_bg input").val("#171717")
        jQuery(".darkmysite_dark_mode_text_color input").val("#BEBEBE")
        jQuery(".darkmysite_dark_mode_link_color input").val("#FFFFFF")
        jQuery(".darkmysite_dark_mode_link_hover_color input").val("#CCCCCC")
        jQuery(".darkmysite_dark_mode_input_bg input").val("#2D2D2D")
        jQuery(".darkmysite_dark_mode_input_text_color input").val("#BEBEBE")
        jQuery(".darkmysite_dark_mode_input_placeholder_color input").val("#989898")
        jQuery(".darkmysite_dark_mode_border_color input").val("#4A4A4A")
        jQuery(".darkmysite_dark_mode_btn_bg input").val("#2D2D2D")
        jQuery(".darkmysite_dark_mode_btn_text_color input").val("#BEBEBE")
    }else if(preset_id === "blue"){
        jQuery(".darkmysite_dark_mode_bg input").val("#142434")
        jQuery(".darkmysite_dark_mode_secondary_bg input").val("#182D43")
        jQuery(".darkmysite_dark_mode_text_color input").val("#B0CBE7")
        jQuery(".darkmysite_dark_mode_link_color input").val("#337EC9")
        jQuery(".darkmysite_dark_mode_link_hover_color input").val("#0075EB")
        jQuery(".darkmysite_dark_mode_input_bg input").val("#1B4B7B")
        jQuery(".darkmysite_dark_mode_input_text_color input").val("#B0CBE7")
        jQuery(".darkmysite_dark_mode_input_placeholder_color input").val("#2c73b7")
        jQuery(".darkmysite_dark_mode_border_color input").val("#4B6F93")
        jQuery(".darkmysite_dark_mode_btn_bg input").val("#1B4B7B")
        jQuery(".darkmysite_dark_mode_btn_text_color input").val("#B0CBE7")
    }else if(preset_id === "green"){
        jQuery(".darkmysite_dark_mode_bg input").val("#0D1D07")
        jQuery(".darkmysite_dark_mode_secondary_bg input").val("#112609")
        jQuery(".darkmysite_dark_mode_text_color input").val("#ABC2A2")
        jQuery(".darkmysite_dark_mode_link_color input").val("#509F34")
        jQuery(".darkmysite_dark_mode_link_hover_color input").val("#45CF14")
        jQuery(".darkmysite_dark_mode_input_bg input").val("#162d0d")
        jQuery(".darkmysite_dark_mode_input_text_color input").val("#ABC2A2")
        jQuery(".darkmysite_dark_mode_input_placeholder_color input").val("#3d7a28")
        jQuery(".darkmysite_dark_mode_border_color input").val("#2f5a1e")
        jQuery(".darkmysite_dark_mode_btn_bg input").val("#162d0d")
        jQuery(".darkmysite_dark_mode_btn_text_color input").val("#ABC2A2")
    }else if(preset_id === "orange"){
        jQuery(".darkmysite_dark_mode_bg input").val("#171004")
        jQuery(".darkmysite_dark_mode_secondary_bg input").val("#211706")
        jQuery(".darkmysite_dark_mode_text_color input").val("#D3BFA1")
        jQuery(".darkmysite_dark_mode_link_color input").val("#E09525")
        jQuery(".darkmysite_dark_mode_link_hover_color input").val("#FFB23E")
        jQuery(".darkmysite_dark_mode_input_bg input").val("#372911")
        jQuery(".darkmysite_dark_mode_input_text_color input").val("#D3BFA1")
        jQuery(".darkmysite_dark_mode_input_placeholder_color input").val("#b37b21")
        jQuery(".darkmysite_dark_mode_border_color input").val("#6D4911")
        jQuery(".darkmysite_dark_mode_btn_bg input").val("#372911")
        jQuery(".darkmysite_dark_mode_btn_text_color input").val("#D3BFA1")
    }else if(preset_id === "pink"){
        jQuery(".darkmysite_dark_mode_bg input").val("#19081B")
        jQuery(".darkmysite_dark_mode_secondary_bg input").val("#210A24")
        jQuery(".darkmysite_dark_mode_text_color input").val("#C09DC2")
        jQuery(".darkmysite_dark_mode_link_color input").val("#C832D4")
        jQuery(".darkmysite_dark_mode_link_hover_color input").val("#DE58E9")
        jQuery(".darkmysite_dark_mode_input_bg input").val("#440649")
        jQuery(".darkmysite_dark_mode_input_text_color input").val("#C09DC2")
        jQuery(".darkmysite_dark_mode_input_placeholder_color input").val("#8e6d92")
        jQuery(".darkmysite_dark_mode_border_color input").val("#630F68")
        jQuery(".darkmysite_dark_mode_btn_bg input").val("#440649")
        jQuery(".darkmysite_dark_mode_btn_text_color input").val("#C09DC2")
    }
}

function darkmysite_switch_position_change(view){
    'use strict';
    var position = jQuery(view).val();
    if(position === "top_right"){
        jQuery(".darkmysite_dark_mode_switch_margin_top").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_right").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_bottom").hide().prev().hide()
        jQuery(".darkmysite_dark_mode_switch_margin_left").hide().prev().hide()
    }else if(position === "top_left"){
        jQuery(".darkmysite_dark_mode_switch_margin_top").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_left").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_bottom").hide().prev().hide()
        jQuery(".darkmysite_dark_mode_switch_margin_right").hide().prev().hide()
    }else if(position === "bottom_right"){
        jQuery(".darkmysite_dark_mode_switch_margin_bottom").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_right").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_top").hide().prev().hide()
        jQuery(".darkmysite_dark_mode_switch_margin_left").hide().prev().hide()
    }else if(position === "bottom_left"){
        jQuery(".darkmysite_dark_mode_switch_margin_bottom").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_left").show().prev().show()
        jQuery(".darkmysite_dark_mode_switch_margin_top").hide().prev().hide()
        jQuery(".darkmysite_dark_mode_switch_margin_right").hide().prev().hide()
    }
    jQuery(".darkmysite_dark_mode_switch_margin_top input").val("40")
    jQuery(".darkmysite_dark_mode_switch_margin_right input").val("40")
    jQuery(".darkmysite_dark_mode_switch_margin_bottom input").val("40")
    jQuery(".darkmysite_dark_mode_switch_margin_left input").val("40")
}


function darkmysite_checkbox_input_select_change(view) {
    'use strict';
    if(jQuery(view).parent().find("input[type='checkbox']:checked").length > 0){
        jQuery(view).parent().parent().find("select").show()
        jQuery(view).parent().parent().find("input").show()
    }else{
        jQuery(view).parent().parent().find("select").hide()
        jQuery(view).parent().parent().find("input").hide()
    }
}

function darkmysite_switch_in_menu_checkbox_change(view) {
    'use strict';
    if(jQuery(view).parent().find("input[type='checkbox']:checked").length > 0){
        jQuery(view).parent().parent().find("select").show()
        jQuery(view).parent().parent().find("textarea").show()
        jQuery(view).parent().parent().find("span.darkmysite_menu_shortcode_helper").show()
    }else{
        jQuery(view).parent().parent().find("select").hide()
        jQuery(view).parent().parent().find("textarea").hide()
        jQuery(view).parent().parent().find("span.darkmysite_menu_shortcode_helper").hide()
    }
}


function darkmysite_copy_shortcode(view){
    'use strict';
    var temp = jQuery("<input>");
    jQuery("body").append(temp);
    temp.val(jQuery(view).text()).select();
    document.execCommand("copy");
    temp.remove();
    alert("Shortcode Copied to Clipboard")
}


function darkmysite_save() {
    'use strict';
    jQuery('.darkmysite_body_header_save_btn').text('SAVING..').prop('disabled', true);

    var post_data = {
        'action': 'darkmysite_update_settings',

        /* Control */
        'show_support_msg_block': jQuery(".darkmysite_support_msg_block").length > 0 ? "1" : "0",
        'enable_dark_mode_switch': jQuery(".darkmysite_enable_dark_mode_switch input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'enable_default_dark_mode': jQuery(".darkmysite_enable_default_dark_mode input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'enable_os_aware': jQuery(".darkmysite_enable_os_aware input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'enable_keyboard_shortcut': jQuery(".darkmysite_enable_keyboard_shortcut input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'enable_time_based_dark': jQuery(".darkmysite_enable_time_based_dark input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'time_based_dark_start': jQuery(".darkmysite_enable_time_based_dark").find("input[type='time']").eq(0).val(),
        'time_based_dark_stop': jQuery(".darkmysite_enable_time_based_dark").find("input[type='time']").eq(1).val(),
        'hide_on_desktop': jQuery(".darkmysite_hide_on_desktop input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'hide_on_mobile': jQuery(".darkmysite_hide_on_mobile input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'hide_on_mobile_by': jQuery(".darkmysite_hide_on_mobile select").val(),
        'enable_switch_in_menu': jQuery(".darkmysite_enable_switch_in_menu input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'switch_in_menu_location': jQuery(".darkmysite_enable_switch_in_menu select").val(),
        'switch_in_menu_shortcode': jQuery(".darkmysite_enable_switch_in_menu textarea").val(),

        /* Admin */
        'enable_admin_dark_mode': jQuery(".darkmysite_enable_admin_dark_mode input[type='checkbox']:checked").length > 0 ? "1" : "0",

        /* Switch */
        'dark_mode_switch_design': jQuery(".darkmysite_dark_mode_switch_design").attr("data-switch_id"),
        'dark_mode_switch_position': jQuery(".darkmysite_dark_mode_switch_position select").val(),
        'dark_mode_switch_margin_top': jQuery(".darkmysite_dark_mode_switch_margin_top input").val(),
        'dark_mode_switch_margin_bottom': jQuery(".darkmysite_dark_mode_switch_margin_bottom input").val(),
        'dark_mode_switch_margin_left': jQuery(".darkmysite_dark_mode_switch_margin_left input").val(),
        'dark_mode_switch_margin_right': jQuery(".darkmysite_dark_mode_switch_margin_right input").val(),
        'enable_absolute_position': jQuery(".darkmysite_enable_absolute_position select").val(),

        /* Switch Apple */
        'switch_apple_width_height': jQuery(".darkmysite_switch_apple_width_height input").val(),
        'switch_apple_border_radius': jQuery(".darkmysite_switch_apple_border_radius input").val(),
        'switch_apple_icon_width': jQuery(".darkmysite_switch_apple_icon_width input").val(),
        'switch_apple_light_mode_bg': jQuery(".darkmysite_switch_apple_light_mode_bg input").val(),
        'switch_apple_dark_mode_bg': jQuery(".darkmysite_switch_apple_dark_mode_bg input").val(),
        'switch_apple_light_mode_icon_color': jQuery(".darkmysite_switch_apple_light_mode_icon_color input").val(),
        'switch_apple_dark_mode_icon_color': jQuery(".darkmysite_switch_apple_dark_mode_icon_color input").val(),
        /* Switch Banana */
        'switch_banana_width_height': jQuery(".darkmysite_switch_banana_width_height input").val(),
        'switch_banana_border_radius': jQuery(".darkmysite_switch_banana_border_radius input").val(),
        'switch_banana_icon_width': jQuery(".darkmysite_switch_banana_icon_width input").val(),
        'switch_banana_light_mode_bg': jQuery(".darkmysite_switch_banana_light_mode_bg input").val(),
        'switch_banana_dark_mode_bg': jQuery(".darkmysite_switch_banana_dark_mode_bg input").val(),
        'switch_banana_light_mode_icon_color': jQuery(".darkmysite_switch_banana_light_mode_icon_color input").val(),
        'switch_banana_dark_mode_icon_color': jQuery(".darkmysite_switch_banana_dark_mode_icon_color input").val(),


        /* Preset */
        'dark_mode_color_preset': jQuery(".darkmysite_dark_mode_color_preset").attr("data-preset_id"),
        'dark_mode_bg': jQuery(".darkmysite_dark_mode_bg input").val(),
        'dark_mode_secondary_bg': jQuery(".darkmysite_dark_mode_secondary_bg input").val(),
        'dark_mode_text_color': jQuery(".darkmysite_dark_mode_text_color input").val(),
        'dark_mode_link_color': jQuery(".darkmysite_dark_mode_link_color input").val(),
        'dark_mode_link_hover_color': jQuery(".darkmysite_dark_mode_link_hover_color input").val(),
        'dark_mode_input_bg': jQuery(".darkmysite_dark_mode_input_bg input").val(),
        'dark_mode_input_text_color': jQuery(".darkmysite_dark_mode_input_text_color input").val(),
        'dark_mode_input_placeholder_color': jQuery(".darkmysite_dark_mode_input_placeholder_color input").val(),
        'dark_mode_border_color': jQuery(".darkmysite_dark_mode_border_color input").val(),
        'dark_mode_btn_bg': jQuery(".darkmysite_dark_mode_btn_bg input").val(),
        'dark_mode_btn_text_color': jQuery(".darkmysite_dark_mode_btn_text_color input").val(),

        /* Media */
        'enable_low_image_brightness': jQuery(".darkmysite_enable_low_image_brightness input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'image_brightness_to': jQuery(".darkmysite_enable_low_image_brightness select").val(),
        'enable_image_grayscale': jQuery(".darkmysite_enable_image_grayscale input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'image_grayscale_to': jQuery(".darkmysite_enable_image_grayscale select").val(),
        'enable_bg_image_darken': jQuery(".darkmysite_enable_bg_image_darken input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'bg_image_darken_to': jQuery(".darkmysite_enable_bg_image_darken select").val(),
        'enable_invert_inline_svg': jQuery(".darkmysite_enable_invert_inline_svg input[type='checkbox']:checked").length > 0 ? "1" : "0",

        /* Video */
        'enable_low_video_brightness': jQuery(".darkmysite_enable_low_video_brightness input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'video_brightness_to': jQuery(".darkmysite_enable_low_video_brightness select").val(),
        'enable_video_grayscale': jQuery(".darkmysite_enable_video_grayscale input[type='checkbox']:checked").length > 0 ? "1" : "0",
        'video_grayscale_to': jQuery(".darkmysite_enable_video_grayscale select").val(),

        /* Restriction */

    };

    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: post_data,
        success: function (data) {
            var obj = JSON.parse(data);
            if (obj.status === "true") {
                jQuery('.darkmysite_body_header_save_btn').text('SAVE CHANGES').prop('disabled', false);
            }
        }
    });
}




function darkmysite_show_pro_popup(headline, details) {
    'use strict';
    if(headline === ""){
        headline = "Go Premium";
    }
    if(details === ""){
        details = "This feature is only available in the Pro Version";
    }
    jQuery(".darkmysite_pro_popup_container").css("display", "flex");
    jQuery(".darkmysite_pro_popup_container h3").text(headline);
    jQuery(".darkmysite_pro_popup_container p").text(details);
}
function darkmysite_close_pro_popup() {
    'use strict';
    jQuery(".darkmysite_pro_popup_container").css("display", "none");
}

function darkmysite_close_support_msg_block() {
    'use strict';
    jQuery(".darkmysite_support_msg_block").remove();
}