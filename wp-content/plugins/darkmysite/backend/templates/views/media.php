<div id="darkmysite_media" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_image.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>IMAGE STYLE</h2>
                <p>Customize Image Appearance in Dark Mode</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>




    <div class="darkmysite_section_header">
        <h3>Appearance Control</h3>
        <p>Settings to change image appearance on dark mode</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_input_select_setting darkmysite_enable_low_image_brightness">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_low_image_brightness"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Low Brightness</h4>
                <p>Check and select the brightness level of images on dark mode.</p>
            </div>
            <select style="<?php echo esc_attr($settings["enable_low_image_brightness"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["image_brightness_to"] == "0" ? "selected" : "") ?> value="0">0% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "10" ? "selected" : "") ?> value="10">10% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "20" ? "selected" : "") ?> value="20">20% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "30" ? "selected" : "") ?> value="30">30% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "40" ? "selected" : "") ?> value="40">40% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "50" ? "selected" : "") ?> value="50">50% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "60" ? "selected" : "") ?> value="60">60% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "70" ? "selected" : "") ?> value="70">70% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "80" ? "selected" : "") ?> value="80">80% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "90" ? "selected" : "") ?> value="90">90% Brightness</option>
                <option <?php echo esc_attr($settings["image_brightness_to"] == "100" ? "selected" : "") ?> value="100">100% Brightness</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_input_select_setting darkmysite_enable_image_grayscale">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_image_grayscale"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Grayscale Image</h4>
                <p>Check and select the grayscale level of images on dark mode.</p>
            </div>
            <select style="<?php echo esc_attr($settings["enable_image_grayscale"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "0" ? "selected" : "") ?> value="0">0% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "10" ? "selected" : "") ?> value="10">10% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "20" ? "selected" : "") ?> value="20">20% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "30" ? "selected" : "") ?> value="30">30% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "40" ? "selected" : "") ?> value="40">40% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "50" ? "selected" : "") ?> value="50">50% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "60" ? "selected" : "") ?> value="60">60% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "70" ? "selected" : "") ?> value="70">70% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "80" ? "selected" : "") ?> value="80">80% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "90" ? "selected" : "") ?> value="90">90% Grayscale</option>
                <option <?php echo esc_attr($settings["image_grayscale_to"] == "100" ? "selected" : "") ?> value="100">100% Grayscale</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_input_select_setting darkmysite_enable_bg_image_darken">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_bg_image_darken"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Darken Background Image</h4>
                <p>Check and select the level of darkness of background images on dark mode.</p>
            </div>
            <select style="<?php echo esc_attr($settings["enable_bg_image_darken"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "0" ? "selected" : "") ?> value="0">0% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "10" ? "selected" : "") ?> value="10">10% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "20" ? "selected" : "") ?> value="20">20% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "30" ? "selected" : "") ?> value="30">30% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "40" ? "selected" : "") ?> value="40">40% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "50" ? "selected" : "") ?> value="50">50% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "60" ? "selected" : "") ?> value="60">60% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "70" ? "selected" : "") ?> value="70">70% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "80" ? "selected" : "") ?> value="80">80% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "90" ? "selected" : "") ?> value="90">90% Darken</option>
                <option <?php echo esc_attr($settings["bg_image_darken_to"] == "100" ? "selected" : "") ?> value="100">100% Darken</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_setting darkmysite_enable_invert_inline_svg">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_invert_inline_svg"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Invert Inline SVG</h4>
                <p>Check to automatically invert all inline SVG images in dark mode.</p>
            </div>
        </div>
    </div>


    <div class="darkmysite_section_header">
        <h3>Image Inversion</h3>
        <p>Settings to invert the colors of images on dark mode</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" disabled><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Invert Images</h4>
                <p>Check to invert specific images on dark mode.</p>
            </div>
        </div>
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <textarea placeholder="Image URLs on IMG tag and CSS backgrounds are supported.&#10;Example:&#10;https://example.com/wp-content/uploads/2022/12/1.jpg&#10;https://example.com/2.svg" rows="6"></textarea>
            <span>Image "src", "srcset" or CSS backgroundImage URLs are supported to target for inversion.</span>
        </div>
    </div>


    <div class="darkmysite_section_header">
        <h3>Image Replacement</h3>
        <p>Replace specified images when dark mode is active</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_image_replace_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">

            <div class="darkmysite_image_replace_setting_item">
                <div class="darkmysite_image_replace_setting_item_part_0">
                    <h4>Normal Mode Image</h4>
                    <input type="text" value="" placeholder="Image URL">
                </div>
                <div class="darkmysite_image_replace_setting_item_part_1">
                    <button class="choose_image darkmysite_ignore"></button>
                </div>
                <div class="darkmysite_image_replace_setting_item_part_2">
                    <h4>Dark Mode Image</h4>
                    <input type="text" value="" placeholder="Image URL">
                </div>
                <div class="darkmysite_image_replace_setting_item_part_3">
                    <button class="choose_image darkmysite_ignore"></button>
                </div>
                <div class="darkmysite_image_replace_setting_item_part_4">
                    <button class="add_item darkmysite_ignore"></button>
                </div>
            </div>

        </div>
    </div>


</div>