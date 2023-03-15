<div id="darkmysite_switch" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_switch.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>SWITCH STYLE</h2>
                <p>Select and Customize Floating Switch</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>




    <div class="darkmysite_section_header">
        <h3>Switch Design</h3>
        <p>Choose Default Dark Mode Floating Switch</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_switch_items darkmysite_dark_mode_switch_design darkmysite_ignore" data-switch_id="<?php echo esc_attr($settings["dark_mode_switch_design"]) ?>">
            <div class="darkmysite_switch_item <?php echo esc_attr($settings["dark_mode_switch_design"] == "apple" ? "active" : "") ?>" onclick="darkmysite_switch_design_click(this, `apple`)">
                <img width="55px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_apple.png") ?>">
            </div>
            <div class="darkmysite_switch_item <?php echo esc_attr($settings["dark_mode_switch_design"] == "banana" ? "active" : "") ?>" onclick="darkmysite_switch_design_click(this, `banana`)">
                <img width="55px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_banana.png") ?>">
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="55px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_cherry.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="55px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_durian.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="85px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_elderberry.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="85px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_fazli.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="85px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_guava.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="85px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_honeydew.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
            <div class="darkmysite_switch_item" onclick="darkmysite_show_pro_popup(``, ``)">
                <img width="85px" src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "switch/switch_incaberry.png") ?>">
                <div class="darkmysite_switch_locked">
                    <span class="darkmysite_switch_locked_icon"></span>
                </div>
            </div>
        </div>
    </div>






    <div class="darkmysite_section_header">
        <h3>Position Customization</h3>
        <p>Customize the Floating Switch Position for perfect placement</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_switch_position">
            <div class="darkmysite_input_select_setting_details">
                <h4>Switch Position</h4>
                <p>Choose the screen position where the Floating Switch should be displayed.</p>
            </div>
            <select onchange="darkmysite_switch_position_change(this)">
                <option <?php echo esc_attr($settings["dark_mode_switch_position"] == "top_right" ? "selected" : "") ?> value="top_right">Top Right</option>
                <option <?php echo esc_attr($settings["dark_mode_switch_position"] == "top_left" ? "selected" : "") ?> value="top_left">Top Left</option>
                <option <?php echo esc_attr($settings["dark_mode_switch_position"] == "bottom_right" ? "selected" : "") ?> value="bottom_right">Bottom Right</option>
                <option <?php echo esc_attr($settings["dark_mode_switch_position"] == "bottom_left" ? "selected" : "") ?> value="bottom_left">Bottom Left</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "top") !== false ? "" : "display: none;") ?>"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_switch_margin_top" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "top") !== false ? "" : "display: none;") ?>">
            <div class="darkmysite_input_select_setting_details">
                <h4>Margin from Top</h4>
                <p>Customize default margin from the top of the Floating Switch.</p>
            </div>
            <input type="number" value="<?php echo esc_attr($settings["dark_mode_switch_margin_top"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "bottom") !== false ? "" : "display: none;") ?>"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_switch_margin_bottom" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "bottom") !== false ? "" : "display: none;") ?>">
            <div class="darkmysite_input_select_setting_details">
                <h4>Margin from Bottom</h4>
                <p>Customize default margin from the bottom of the Floating Switch.</p>
            </div>
            <input type="number" value="<?php echo esc_attr($settings["dark_mode_switch_margin_bottom"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "left") !== false ? "" : "display: none;") ?>"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_switch_margin_left" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "left") !== false ? "" : "display: none;") ?>">
            <div class="darkmysite_input_select_setting_details">
                <h4>Margin from Left</h4>
                <p>Customize default margin from the left of the Floating Switch.</p>
            </div>
            <input type="number" value="<?php echo esc_attr($settings["dark_mode_switch_margin_left"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "right") !== false ? "" : "display: none;") ?>"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_switch_margin_right" style="<?php echo esc_attr(strpos($settings["dark_mode_switch_position"], "right") !== false ? "" : "display: none;") ?>">
            <div class="darkmysite_input_select_setting_details">
                <h4>Margin from Right</h4>
                <p>Customize default margin from the right of the Floating Switch.</p>
            </div>
            <input type="number" value="<?php echo esc_attr($settings["dark_mode_switch_margin_right"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Different Switch Position in Mobile</h4>
                <p>Should the Floating Switch be displayed on different position in mobile?</p>
            </div>
            <select>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting darkmysite_enable_absolute_position">
            <div class="darkmysite_input_select_setting_details">
                <h4>Absolute Switch Position</h4>
                <p>Enable to make the floating switch scroll from it's position with page scrolling.</p>
            </div>
            <select>
                <option <?php echo esc_attr($settings["enable_absolute_position"] == "1" ? "selected" : "") ?> value="1">Enable</option>
                <option <?php echo esc_attr($settings["enable_absolute_position"] == "0" ? "selected" : "") ?> value="0">Disable</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Draggable Position Change</h4>
                <p>Allow/Disallow users to change the floating switch position by dragging to where they want.</p>
            </div>
            <select>
                <option value="0">Disable</option>
                <option value="1">Enable</option>
            </select>
        </div>
    </div>












    <div class="darkmysite_section_header">
        <h3>Advanced Customization</h3>
        <p>Customize the Selected Switch Design in Your Way</p>
    </div>
    <div class="darkmysite_settings_with_switch_preview">

        <div class="darkmysite_section_block">

            <?php include DARKMYSITE_PATH . "backend/templates/views/switch_customize/apple.php"; ?>
            <?php include DARKMYSITE_PATH . "backend/templates/views/switch_customize/banana.php"; ?>

        </div>

        <div class="darkmysite_switch_preview_container">
            <div class="darkmysite_switch_preview">
                <div class="darkmysite_switch darkmysite_switch_apple <?php echo esc_attr($settings["dark_mode_switch_design"] == "apple" ? "selected" : "") ?>" onclick="darkmysite_switch_preview_triggered(this)">
                    <span class="darkmysite_switch_icon"></span>
                </div>
                <div class="darkmysite_switch darkmysite_switch_banana <?php echo esc_attr($settings["dark_mode_switch_design"] == "banana" ? "selected" : "") ?>" onclick="darkmysite_switch_preview_triggered(this)">
                    <span class="darkmysite_switch_icon"></span>
                </div>
            </div>
            <button class="darkmysite_copy_customized_shortcode" onclick="darkmysite_cody_customized_shortcode()">Copy Customized Shortcode</button>
        </div>


    </div>






</div>