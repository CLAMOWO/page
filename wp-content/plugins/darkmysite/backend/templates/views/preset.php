<div id="darkmysite_preset" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_preset.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>COLOR PRESET</h2>
                <p>Select and Customize Dark Mode Style</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>




    <div class="darkmysite_section_header">
        <h3>Color Preset</h3>
        <p>Choose between ready dark mode color preset</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_preset_items darkmysite_dark_mode_color_preset" data-preset_id="<?php echo esc_attr($settings["dark_mode_color_preset"]) ?>">
            <div class="darkmysite_preset_item" onclick="darkmysite_color_preset_click(this, `black`)">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "preset/black.png") ?>">
                <?php if($settings["dark_mode_color_preset"] == "black"){ ?><span class="darkmysite_preset_item_active"></span> <?php } ?>
            </div>
            <div class="darkmysite_preset_item" onclick="darkmysite_color_preset_click(this, `blue`)">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "preset/blue.png") ?>">
                <?php if($settings["dark_mode_color_preset"] == "blue"){ ?><span class="darkmysite_preset_item_active"></span> <?php } ?>
            </div>
            <div class="darkmysite_preset_item" onclick="darkmysite_color_preset_click(this, `green`)">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "preset/green.png") ?>">
                <?php if($settings["dark_mode_color_preset"] == "green"){ ?><span class="darkmysite_preset_item_active"></span> <?php } ?>
            </div>
            <div class="darkmysite_preset_item" onclick="darkmysite_color_preset_click(this, `orange`)">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "preset/orange.png") ?>">
                <?php if($settings["dark_mode_color_preset"] == "orange"){ ?><span class="darkmysite_preset_item_active"></span> <?php } ?>
            </div>
            <div class="darkmysite_preset_item" onclick="darkmysite_color_preset_click(this, `pink`)">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "preset/pink.png") ?>">
                <?php if($settings["dark_mode_color_preset"] == "pink"){ ?><span class="darkmysite_preset_item_active"></span> <?php } ?>
            </div>
        </div>
    </div>



    <div class="darkmysite_section_header">
        <h3>Preset Color Customization</h3>
        <p>Customize the selected dark mode preset color in your way</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_bg">
            <div class="darkmysite_input_select_setting_details">
                <h4>Body Background Color</h4>
                <p>Set the background color of your website's body when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_bg"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_secondary_bg">
            <div class="darkmysite_input_select_setting_details">
                <h4>Secondary Background Color</h4>
                <p>Set the background color of areas having different bg color when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_secondary_bg"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting darkmysite_dark_mode_text_color">
            <div class="darkmysite_input_select_setting_details">
                <h4>Text Color</h4>
                <p>Set the text color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_text_color"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_link_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Link Color</h4>
                <p>Set the link color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_link_color"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_link_hover_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Link Hover Color</h4>
                <p>Set the link color on mouse over of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_link_hover_color"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_input_bg" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Input Field Background Color</h4>
                <p>Set the input field background color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_input_bg"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_input_text_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Input Field Text Color</h4>
                <p>Set the input field text color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_input_text_color"]) ?>">
        </div>

        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_input_placeholder_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Input Field Placeholder Color</h4>
                <p>Set the input field placeholder color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_input_placeholder_color"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_border_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Border Color</h4>
                <p>Set the border color of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_border_color"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_btn_bg" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Button Background Color</h4>
                <p>Set the background color of buttons of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_btn_bg"]) ?>">
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_btn_text_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Button Text Color</h4>
                <p>Set the text color of buttons of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php echo esc_attr($settings["dark_mode_btn_text_color"]) ?>">
        </div>
        <!--<div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_input_select_setting pro_lock darkmysite_dark_mode_btn_text_color" onclick="darkmysite_show_pro_popup(``, ``)">
            <div class="darkmysite_input_select_setting_details">
                <h4>Button Text Color</h4>
                <p>Set the text color of buttons of your website when dark mode is enabled.</p>
            </div>
            <input type="color" value="<?php /*echo esc_attr($settings["dark_mode_btn_text_color"]) */?>">
        </div>-->
    </div>







</div>