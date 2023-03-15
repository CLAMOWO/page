<div id="darkmysite_control">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_control.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>CONTROL SETTINGS</h2>
                <p>Settings to Enable or Disable Dark Mode</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>


    <?php if($settings["show_support_msg_block"] == "1"){ ?>
        <div class="darkmysite_support_msg_block">
            <span class="darkmysite_support_msg_block_icon darkmysite_ignore"></span>
            <div class="darkmysite_support_msg_block_details">
                <h4>Things Not Working Properly?</h4>
                <p>Every website template is designed differently. Example, if a section is not made dark, surely there is some background image that's not dark. This type of issues may make you feel like "Gosh! Useless Plugin". But we have all the functionality made to make every website show perfect dark mode. <a target="_blank" href="<?php echo esc_url(DARKMYSITE_SERVER."/contact-us") ?>">Contact Us</a>  anytime, surely you will get a solution.</p>
            </div>
            <button class="darkmysite_support_msg_block_close_icon darkmysite_ignore" onclick="darkmysite_close_support_msg_block()"></button>
        </div>
    <?php } ?>


    <div class="darkmysite_section_header">
        <h3>Basic Control</h3>
        <p>Settings to Control Dark Mode on your Website</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_setting darkmysite_enable_dark_mode_switch">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_dark_mode_switch"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable Frontend Dark Mode Switch</h4>
                <p>Check to show the Dark Mode Floating Switch in your Website's frontend.</p>
            </div>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_setting darkmysite_enable_default_dark_mode">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_default_dark_mode"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable Default Dark Mode</h4>
                <p>Check to automatically turn your website dark by default.</p>
            </div>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_setting darkmysite_enable_os_aware">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_os_aware"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable OS Aware Dark Mode</h4>
                <p>Check to enable or disable dark mode automatically according to users’ device preference.</p>
            </div>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_setting darkmysite_enable_keyboard_shortcut">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_keyboard_shortcut"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable Keyboard Shortcut</h4>
                <p>Check to turn ON or OFF dark mode by pressing Ctrl+Alt+D on keyboard.</p>
            </div>
        </div>
    </div>










    <div class="darkmysite_section_header">
        <h3>Advanced Control</h3>
        <p>Settings to Control Dark Mode with extra preference</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_setting darkmysite_enable_time_based_dark">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_time_based_dark"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable Time Based Auto Dark Mode</h4>
                <p>Automatically turn dark mode ON based on users’ localtime.</p>
                <div class="darkmysite_duplicable_time_range_items">
                    <div class="darkmysite_duplicable_time_range_item">
                        <p>From</p>
                        <input type="time" value="<?php echo esc_attr($settings["time_based_dark_start"]) ?>">
                        <p>To</p>
                        <input type="time" value="<?php echo esc_attr($settings["time_based_dark_stop"]) ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_setting darkmysite_hide_on_desktop">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["hide_on_desktop"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Hide Dark Mode Switch on Desktop</h4>
                <p>Check to hide the Dark Mode Floating Switch if users’ are using desktop or laptop.</p>
            </div>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_input_select_setting darkmysite_hide_on_mobile">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["hide_on_mobile"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Hide Dark Mode Switch on Mobile</h4>
                <p>Check to hide the Dark Mode Floating Switch if users’ are using mobile.</p>
            </div>
            <select style="<?php echo esc_attr($settings["hide_on_mobile"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["hide_on_mobile_by"] == "user_agent" ? "selected" : "") ?> value="user_agent">Hide by User Agent</option>
                <option <?php echo esc_attr($settings["hide_on_mobile_by"] == "screen_size" ? "selected" : "") ?> value="screen_size">Hide by Screen Size</option>
                <option <?php echo esc_attr($settings["hide_on_mobile_by"] == "both" ? "selected" : "") ?> value="both">Hide by Both</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_switch_in_menu_setting darkmysite_enable_switch_in_menu">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_switch_in_menu_checkbox_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_switch_in_menu"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_switch_in_menu_setting_details">
                <h4>Show Switch in Menu</h4>
                <p>Show the dark mode toggle switch in specific menu.</p>
                <select style="<?php echo esc_attr($settings["enable_switch_in_menu"] == "1" ? "" : "display: none;") ?>">
                    <?php foreach ($this->utils->getWpNavMenus() as $single_item) { ?>
                        <option value="<?php echo esc_attr($single_item["id"]); ?>" <?php echo esc_attr($settings["switch_in_menu_location"] == $single_item["id"] ? "selected" : "") ?>> <?php echo esc_attr($single_item["text"]); ?> </option>
                    <?php } ?>
                </select>
                <textarea style="<?php echo esc_attr($settings["enable_switch_in_menu"] == "1" ? "" : "display: none;") ?>" placeholder="Switch Shortcode" rows="3"><?php echo esc_attr($settings["switch_in_menu_shortcode"]) ?></textarea>
                <span class="darkmysite_menu_shortcode_helper" style="<?php echo esc_attr($settings["enable_switch_in_menu"] == "1" ? "" : "display: none;") ?>">You can generate customized switch shortcode from <strong>SWITCH STYLE</strong> > <STRONG>Advanced Customization</STRONG>.</span>
            </div>
        </div>
    </div>

</div>