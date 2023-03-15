<?php

$settings = $this->settings->get_all_darkmysite_settings();

?>


<div class="darkmysite_main">

    <div class="darkmysite_sidebar">
        <?php include DARKMYSITE_PATH . "backend/templates/views/sidebar.php"; ?>
    </div>

    <div class="darkmysite_body">
        <?php include DARKMYSITE_PATH . "backend/templates/views/control.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/admin.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/switch.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/preset.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/media.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/video.php"; ?>
        <?php include DARKMYSITE_PATH . "backend/templates/views/advanced.php"; ?>
    </div>

</div>


<div class="darkmysite_pro_popup_container">
    <div class="darkmysite_pro_popup_dark_bg"></div>
    <div class="darkmysite_pro_popup">
        <img src="<?php echo esc_url(DARKMYSITE_IMG_DIR . "others/pro_tag.svg") ?>">
        <h3>Go Premium</h3>
        <p>This feature is only available in the Pro Version</p>
        <div class="darkmysite_pro_popup_action">
            <a class="darkmysite_get_pro_btn" href="<?php echo esc_url(DARKMYSITE_SERVER); ?>" target="_blank">Get Pro</a>
            <button onclick="darkmysite_close_pro_popup()" class="darkmysite_pro_popup_close_btn">Cancel</button>
        </div>
    </div>
</div>


<script type="text/javascript">

    jQuery(document).ready(function($){
        'use strict';
        var host = "<?php echo esc_url(DARKMYSITE_URL); ?>";
        darkmysite_admin_init(host);
    });

</script>