<div id="darkmysite_video" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_video.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>VIDEO STYLE</h2>
                <p>Customize Video Appearance in Dark Mode</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>




    <div class="darkmysite_section_header">
        <h3>Appearance Control</h3>
        <p>Settings to change video appearance on dark mode</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_input_select_setting darkmysite_enable_low_video_brightness">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_low_video_brightness"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Low Brightness</h4>
                <p>Check and select the brightness level of videos on dark mode.</p>
            </div>
            <select style="<?php echo esc_attr($settings["enable_low_video_brightness"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["video_brightness_to"] == "0" ? "selected" : "") ?> value="0">0% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "10" ? "selected" : "") ?> value="10">10% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "20" ? "selected" : "") ?> value="20">20% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "30" ? "selected" : "") ?> value="30">30% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "40" ? "selected" : "") ?> value="40">40% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "50" ? "selected" : "") ?> value="50">50% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "60" ? "selected" : "") ?> value="60">60% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "70" ? "selected" : "") ?> value="70">70% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "80" ? "selected" : "") ?> value="80">80% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "90" ? "selected" : "") ?> value="90">90% Brightness</option>
                <option <?php echo esc_attr($settings["video_brightness_to"] == "100" ? "selected" : "") ?> value="100">100% Brightness</option>
            </select>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_checkbox_input_select_setting darkmysite_enable_video_grayscale">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input onchange="darkmysite_checkbox_input_select_change(this)" type="checkbox" <?php echo esc_attr($settings["enable_video_grayscale"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_input_select_setting_details">
                <h4>Grayscale Video</h4>
                <p>Check and select the grayscale level of videos on dark mode.</p>
            </div>
            <select style="<?php echo esc_attr($settings["enable_video_grayscale"] == "1" ? "" : "display: none;") ?>">
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "0" ? "selected" : "") ?> value="0">0% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "10" ? "selected" : "") ?> value="10">10% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "20" ? "selected" : "") ?> value="20">20% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "30" ? "selected" : "") ?> value="30">30% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "40" ? "selected" : "") ?> value="40">40% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "50" ? "selected" : "") ?> value="50">50% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "60" ? "selected" : "") ?> value="60">60% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "70" ? "selected" : "") ?> value="70">70% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "80" ? "selected" : "") ?> value="80">80% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "90" ? "selected" : "") ?> value="90">90% Grayscale</option>
                <option <?php echo esc_attr($settings["video_grayscale_to"] == "100" ? "selected" : "") ?> value="100">100% Grayscale</option>
            </select>
        </div>
    </div>



    <div class="darkmysite_section_header">
        <h3>Video Replacement</h3>
        <p>Replace specified videos (self-hosted, youtube, vimeo or dailymotion) when dark mode is active</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_video_replace_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">

            <div class="darkmysite_video_replace_setting_item">
                <div class="darkmysite_video_replace_setting_item_part_0">
                    <h4>Normal Mode Video</h4>
                    <input type="text" value="" placeholder="Video URL">
                </div>
                <div class="darkmysite_video_replace_setting_item_part_1">
                    <button class="choose_video darkmysite_ignore"></button>
                </div>
                <div class="darkmysite_video_replace_setting_item_part_2">
                    <h4>Dark Mode Video</h4>
                    <input type="text" value="" placeholder="Video URL">
                </div>
                <div class="darkmysite_video_replace_setting_item_part_3">
                    <button class="choose_video darkmysite_ignore"></button>
                </div>
                <div class="darkmysite_video_replace_setting_item_part_4">
                    <button class="add_item darkmysite_ignore"></button>
                </div>
            </div>

        </div>
    </div>


</div>