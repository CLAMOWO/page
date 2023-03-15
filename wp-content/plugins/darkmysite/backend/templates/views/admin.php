<div id="darkmysite_admin" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_admin.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>Admin Panel Dark Mode</h2>
                <p>Customize Dark Mode in WordPress Admin Panel</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>



    <div class="darkmysite_section_header">
        <h3>Admin Panel Dark Control</h3>
        <p>Settings to Control Dark Mode on your WordPress Admin Panel</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_checkbox_setting darkmysite_enable_admin_dark_mode">
            <label class="darkmysite_checkbox_item darkmysite_ignore"><input type="checkbox" <?php echo esc_attr($settings["enable_admin_dark_mode"] == "1" ? "checked" : "") ?>><span class="darkmysite_checkbox_checkmark"></span></label>
            <div class="darkmysite_checkbox_setting_details">
                <h4>Enable Admin Panel Dark Mode</h4>
                <p>Check to enable Dark Mode in WordPress Admin Panel.</p>
            </div>
        </div>
    </div>



    <div class="darkmysite_section_header">
        <h3>Page Restriction</h3>
        <p>Disallow admin panel dark mode on specific pages</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Disallowed Pages</h4>
            <p>Dark mode will not be applied to these admin pages.</p>
            <textarea placeholder="Enter comma separated page slugs. Example: darkmysite-dashboard, elementor" rows="3"></textarea>
            <span>If the admin page URL is like http://example.com/wp-admin/admin.php?page=<b>darkmysite-dashboard</b> then <b>darkmysite-dashboard</b> is it's page slug.</span>
        </div>
    </div>



</div>