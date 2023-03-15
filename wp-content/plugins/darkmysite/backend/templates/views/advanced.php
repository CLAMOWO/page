<div id="darkmysite_advanced" style="display: none;">

    <div class="darkmysite_body_header">
        <div class="darkmysite_body_header_details">
            <div class="darkmysite_body_header_details_logo darkmysite_ignore">
                <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "sidebar/sidebar_menu_advanced.svg") ?>">
            </div>
            <div class="darkmysite_body_header_details_headline">
                <h2>ADVANCED SETTINGS</h2>
                <p>Advanced Customization Settings of DarkMySite</p>
            </div>
        </div>
        <button class="darkmysite_body_header_save_btn darkmysite_ignore" onclick="darkmysite_save()">SAVE CHANGES</button>
    </div>



    <div class="darkmysite_body_header_separator darkmysite_ignore"></div>




    <div class="darkmysite_section_header">
        <h3>HTML/CSS Restriction</h3>
        <p>Allow or Disallow dark mode on HTML tags, CSS class or CSS ids</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Allow Only Elements</h4>
            <p>Dark mode will be applied only to these elements.</p>
            <textarea placeholder="Enter comma separated HTML tags, CSS class or CSS ids. Example: div, #site-header, .my-footer" rows="3"></textarea>
            <span class="with_checkbox"><input type="checkbox" disabled>Force to keep designs correct if background color or text color is not working properly for allowed elements.</span>
        </div>
        <div class="darkmysite_section_block_separator" style="height: 0; background: transparent;"></div>
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Disallowed Elements</h4>
            <p>Dark mode will not be applied to these elements.</p>
            <textarea placeholder="Enter comma separated HTML tags, CSS class or CSS ids. Example: div, #site-header, .my-footer" rows="3"></textarea>
            <span class="with_checkbox"><input type="checkbox" disabled>Force to keep designs correct if background color or text color is changed on disallowed elements.</span>
        </div>
    </div>





    <div class="darkmysite_section_header">
        <h3>Page Restriction</h3>
        <p>Allow or Disallow dark mode on WordPress Pages</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Allow Only Pages</h4>
            <p>Dark mode will be applied only to these pages.</p>
            <textarea rows="3"></textarea>
            <span>Choose the pages only where dark mode and floating switch can work. No other pages will be able to process dark mode.</span>
        </div>
        <div class="darkmysite_section_block_separator" style="height: 0; background: transparent;"></div>
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Disallowed Pages</h4>
            <p>Dark mode will not be applied to these pages.</p>
            <textarea rows="3"></textarea>
            <span>Choose the pages where dark mode and floating switch can not work. Other pages will be able to process dark mode.</span>
        </div>
    </div>



    <div class="darkmysite_section_header">
        <h3>Post Restriction</h3>
        <p>Allow or Disallow dark mode on WordPress Posts</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Allow Only Posts</h4>
            <p>Dark mode will be applied only to these posts.</p>
            <textarea rows="3"></textarea>
            <span>Choose the posts only where dark mode and floating switch can work. No other posts will be able to process dark mode.</span>
        </div>
        <div class="darkmysite_section_block_separator" style="height: 0; background: transparent;"></div>
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Disallowed Posts</h4>
            <p>Dark mode will not be applied to these posts.</p>
            <textarea rows="3"></textarea>
            <span>Choose the posts where dark mode and floating switch can not work. Other posts will be able to process dark mode.</span>
        </div>
    </div>




    <div class="darkmysite_section_header">
        <h3>Custom CSS</h3>
        <p>Custom CSS code to apply on dark mode or on both mode</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Dark Mode CSS</h4>
            <p>The CSS code will only be applied when dark mode is active.</p>
            <textarea placeholder="Example:&#10;#site-header{&#10;    background-color: blue;&#10;    color: white;&#10;}" rows="6"></textarea>
            <span class="with_checkbox"><input type="checkbox" disabled>CSS rules should be applied to all children of the CSS selectors.</span>
            <span>Custom CSS selectors are automatically identified as Disallowed Elements, to ignore, use :not-disallowed pseudo class. i.e. body:not-disallowed{...}</span>
        </div>
        <div class="darkmysite_section_block_separator"></div>
        <div class="darkmysite_textarea_setting pro_lock" onclick="darkmysite_show_pro_popup(``, ``)">
            <h4>Normal Mode CSS</h4>
            <p>The CSS code will be applied on both normal mode and dark mode.</p>
            <textarea placeholder="Example:&#10;#site-header{&#10;    background-color: blue;&#10;    color: white;&#10;}" rows="6"></textarea>
        </div>
    </div>


    <div class="darkmysite_section_header">
        <h3>Use via Shortcode</h3>
        <p>Show Floating Switch on any place of your website using Shortcode</p>
    </div>
    <div class="darkmysite_section_block">
        <div class="darkmysite_shortcode_setting">
            <h4 onclick="darkmysite_copy_shortcode(this)">[darkmysite switch="1"]</h4>
            <p>The above is a shortcode where the switch number represents which Floating Switch to be displayed.</p>
        </div>
    </div>


</div>