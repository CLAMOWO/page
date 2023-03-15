<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_app
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->menu = [];
    $this->submenu = [];
    $this->menuStatus = false;
    $this->toolbarStatus = false;
    $this->toolbar = "";
    $this->themeStatus = false;
    $this->front = false;
    $this->network = false;
    $this->masterMenu = [];
    $this->currentURL = "";
    $this->uipMasterMenu = [];
  }

  /**
   * Loads UiPress Classes and plugins
   * @since 2.2
   */

  public function run()
  {
    add_action("login_init", [$this, "login_actions"]);

    if ($this->are_we_disabled()) {
      return;
    }

    $uri = $_SERVER["REQUEST_URI"];
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") || $_SERVER["SERVER_PORT"] == 443 ? "https://" : "http://";
    $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    $currentURL = $url;
    $this->currentURL = $currentURL;

    //LOAD FOLDERS AND ADMIN BAR ON FRONT
    //CHECKS WE ARE NOT ON STANDARD ADMIN PAGE, LOGIN PAGE AND THE URL DOESN'T CONTAIN ADMIN URL (/WP-ADMIN/)
    if (!is_admin() && stripos($_SERVER["SCRIPT_NAME"], wp_login_url()) === false && stripos($currentURL, admin_url()) === false) {
      add_action("wp", [$this, "load_actions_front"]);
      add_action("init", [$this, "command_center_actions"]);
      add_action("wp_enqueue_media", [$this, "start_folder_system"]);
      $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, "uipress", $this->pathURL);
      $uipressFolders->ajax();
      return;
    }

    add_action("plugins_loaded", [$this, "whitelabel_uip"]);
    add_action("all_plugins", [$this, "rename_uip_plugin_table"], 10, 1);

    add_action("admin_enqueue_scripts", [$this, "add_scripts_and_styles"]);
    add_action("admin_head", [$this, "add_custom_css_js"], 0);
    add_filter("admin_footer_text", [$this, "change_footer_admin"], 11);
    add_filter("update_footer", "__return_empty_string", 11);
    add_filter("manage_plugins_columns", [$this, "add_plugin_status_column"]);
    add_filter("manage_plugins-network_columns", [$this, "add_plugin_status_column"]);
    add_action("manage_plugins_custom_column", [$this, "add_plugin_status"], 10, 3);
    add_action("admin_init", [$this, "redirect_to_overview"]);
    add_filter("admin_init", [$this, "check_data_connection"]);

    add_filter("admin_footer", [$this, "add_iframe_overlay"]);

    //TOOLBAR ACTIONS
    add_action("admin_init", [$this, "toolbar_actions"]);
    //TOOLBAR ACTIONS
    add_action("admin_init", [$this, "command_center_actions"]);
    //MENU ACTIONS
    add_action("admin_init", [$this, "menu_actions"]);
    ///HTML CLASSES
    add_action("admin_xml_ns", [$this, "html_attributes"]);
    //LOAD FOLDERS ON EDIT POST / PAGES
    add_action("current_screen", [$this, "start_post_folders"], 10);

    add_action("init", [$this, "uip_create_folders_cpt"]);
    //AJAX
    add_action("wp_ajax_uip_save_prefs", [$this, "uip_save_prefs"]);
    add_action("wp_ajax_uip_master_search", [$this, "uip_master_search"]);
    add_action("wp_ajax_uipress_get_create_types", [$this, "uipress_get_create_types"]);
    add_action("wp_ajax_uipress_get_updates", [$this, "uipress_get_updates"]);
    add_action("wp_ajax_uipress_get_notices", [$this, "uipress_get_notices"]);
    add_action("wp_ajax_uip_save_user_prefs", [$this, "uip_save_user_prefs"]);

    //REGISTER UIPRESS SETTINGS
    add_action("admin_init", [$this, "check_for_network"]);
    add_filter("uipress_register_settings", [$this, "get_app_settings_options"], 1, 2);

    //FOLDER OPTIONS
    add_action("wp_enqueue_media", [$this, "start_folder_system"]);
    $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, "uipress", $this->pathURL);
    $uipressFolders->ajax();
  }

  /**
   * Kills uipress on specific pages
   * @since 2.3.0.3
   */

  public function are_we_disabled()
  {
    ///MOTION.PAGE
    if (isset($_GET["page"])) {
      if ($_GET["page"] == "motionpage") {
        return true;
      }
    }
    ///LATEPOINT
    if (isset($_GET["page"])) {
      if ($_GET["page"] == "latepoint") {
        return true;
      }
    }
    ///MOTION.PAGE IFRAME
    if (isset($_GET["motionpage_iframe"])) {
      if ($_GET["motionpage_iframe"] == "true") {
        return true;
      }
    }
    ///WC WPML WIZZARD
    if (isset($_GET["page"])) {
      if ($_GET["page"] == "wcml-setup") {
        return true;
      }
    }

    ///WC WPML WIZZARD
    if (isset($_GET["page"])) {
      if ($_GET["page"] == "wp-mail-smtp-setup-wizard") {
        return true;
      }
    }

    ///FREESOUL DEACTIVATE PLUGINS
    if (isset($_GET["page"])) {
      if ($_GET["page"] == "eos_dp_menu") {
        return true;
      }
    }

    //$utils = new uipress_util();
    //$hidden = $utils->get_option("advanced", "safe-key");

    //if ($hidden) {
    //if (isset($_GET["no_uip"])) {
    //if ($_GET["no_uip"] == $hidden) {
    //return true;
    //}
    //}
    //}

    return false;
  }

  /**
   * Changes name of UiPress in plugin table and hides if enabled
   * @since 2.2.3
   */

  public function add_iframe_overlay()
  {
    $utils = new uipress_util();
    $dynamicEnabled = $utils->get_option("general", "dynamic-loading");

    if (!$dynamicEnabled) {
      return;
    }
    if (isset($_GET["uip-ajax-page"])) {
      if ($_GET["uip-ajax-page"] == "1") {
        return;
      }
    }
    ?>
    <div id="uip-dynamic-loader-wrap">
      <iframe id="uip-ajax-overlay" src="" 
      title="UiPress Ajax Overlay"></iframe>
      <div id="uip-ajax-loader">
        <div class="uip-loader"></div>
      </div>
      <div class="uip-position-fixed uip-right-0 uip-top-0 uip-padding-xs uip-margin-right-xs" id="uip-close-full-screen">
        <div class="uip-padding-xxs uip-border-round hover:uip-background-grey uip-background-muted uip-cursor-pointer uip-toolbar-link uip-no-underline uip-no-outline uip-border-box uip-flex uip-flex-middle uip-flex-center" onclick="jQuery('body').toggleClass('uip-full-screen')">
          <span class="uip-text-muted"><?php _e("Exit fullscreen", "uipress"); ?></span>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * Changes name of UiPress in plugin table and hides if enabled
   * @since 2.3
   */

  public function rename_uip_plugin_table($all_plugins)
  {
    $utils = new uipress_util();
    $hidden = $utils->get_option("general", "hide-plugin");
    $author = $utils->get_option("general", "rename-plugin-author");
    $link = $utils->get_option("general", "rename-plugin-link");

    if ($hidden) {
      unset($all_plugins["uipress/uipress.php"]);
      return $all_plugins;
    }

    $uip = $all_plugins["uipress/uipress.php"];

    if ($author && $author != "") {
      $uip["Author"] = $author;
    }

    if ($link && $link != "") {
      $uip["AuthorURI"] = $link;
      $uip["PluginURI"] = $link;
    }

    $uip["Name"] = $this->pluginName;

    $all_plugins["uipress/uipress.php"] = $uip;
    return $all_plugins;
  }

  /**
   * Changes name of UiPress
   * @since 2.3
   */

  public function whitelabel_uip()
  {
    $utils = new uipress_util();
    $newname = $utils->get_option("general", "rename-plugin");

    if ($newname != false && $newname != "") {
      $this->pluginName = $newname;
    }
  }

  /**
   * Return Plugin Name
   * @since 2.3
   */

  public function return_plugin_name()
  {
    return $this->pluginName;
  }

  /**
   * Adds login actions and hooks
   * @since 2.1
   */

  public function login_actions()
  {
    add_action("login_head", [$this, "add_login_styles"], 0);
    add_filter("login_body_class", [$this, "add_login_body_classes"]);
    add_filter("login_headerurl", [$this, "login_logo_url"]);
    //add_filter("login_redirect", [$this, "redirect_to_overview_after_login"], 10, 3);
    add_filter("language_attributes", [$this, "html_attributes_login_page"], 10, 2);

    $utils = new uipress_util();
    $langSelec = $utils->get_option("login", "remove-language-selector");

    if ($langSelec == "true") {
      add_filter("login_display_language_dropdown", "__return_false");
    }

    add_action("login_header", [$this, "uip_start_login_wrapper"]);
    add_action("login_footer", [$this, "uip_end_login_wrapper"]);
  }

  /**
   * Adds a wrap to the login page
   * @since 2.2.9.2
   */

  public function uip_start_login_wrapper()
  {
    echo '<div class="uip-login-wrap">';
  }

  /**
   * Adds a wrap to the login page
   * @since 2.2.9.2
   */

  public function uip_end_login_wrapper()
  {
    echo "</div><!-- END OF UIP WRAP -->";
  }

  /**
   * Checks to see if we are on a network admin page
   * @since 2.1
   */

  public function check_for_network()
  {
    if (is_network_admin()) {
      $this->network = true;
    }
  }

  /**
   * Creates custom folder post type
   * @since 1.4
   */
  public function uip_create_folders_cpt()
  {
    $labels = [
      "name" => _x("Folder", "post type general name", "uipress"),
      "singular_name" => _x("folder", "post type singular name", "uipress"),
      "menu_name" => _x("Folders", "admin menu", "uipress"),
      "name_admin_bar" => _x("Folder", "add new on admin bar", "uipress"),
      "add_new" => _x("Add New", "folder", "uipress"),
      "add_new_item" => __("Add New Folder", "uipress"),
      "new_item" => __("New Folder", "uipress"),
      "edit_item" => __("Edit Folder", "uipress"),
      "view_item" => __("View Folder", "uipress"),
      "all_items" => __("All Folders", "uipress"),
      "search_items" => __("Search Folders", "uipress"),
      "not_found" => __("No Folders found.", "uipress"),
      "not_found_in_trash" => __("No Folders found in Trash.", "uipress"),
    ];
    $args = [
      "labels" => $labels,
      "description" => __("Add New Folder", "uipress"),
      "public" => false,
      "publicly_queryable" => false,
      "show_ui" => false,
      "show_in_menu" => false,
      "query_var" => false,
      "has_archive" => false,
      "hierarchical" => false,
    ];
    register_post_type("admin2020folders", $args);
  }

  public function start_folder_system()
  {
    if (!is_user_logged_in()) {
      return;
    }

    if (isset($_GET["page"])) {
      if ($_GET["page"] == "uip-content") {
        return;
      }
    }

    $utils = new uipress_util();
    $foldersOn = $utils->get_option("folders", "status");
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("folders", "disabled-for", true));

    if ($foldersOn == "true" || $foldersDisabledForUser) {
      return;
    }

    require_once $this->path . "admin/classes/folders.php";
    $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, "uipress", $this->pathURL);
    $utils = new uipress_util();

    if (!wp_script_is("uip-app", "enqueued")) {
      ///MENU APP
      wp_enqueue_script("uip-app", $this->pathURL . "assets/js/uip-app.min.js", ["jquery"], $this->version, true);
      wp_localize_script("uip-app", "uip_ajax", [
        "ajax_url" => admin_url("admin-ajax.php"),
        "security" => wp_create_nonce("uip-security-nonce"),
        "preferences" => json_encode($utils->get_user_preferences()),
        "masterPrefs" => json_encode($this->get_master_prefs()),
        "translations" => json_encode($this->get_translations()),
        "defaults" => json_encode($this->get_defaults()),
        "network" => $this->network,
        "front" => json_encode($this->front),
      ]);
    }

    if (!wp_script_is("uip-vue", "enqueued")) {
      wp_enqueue_script("uip-vue", $this->pathURL . "assets/js/uip-vue.js", ["jquery"], $this->version);
    }

    if (!is_rtl()) {
      if (!wp_style_is("uip-app", "enqueued")) {
        ///GOOGLE ICONS
        //wp_register_style("uip-icons", $this->pathURL . "assets/css/uip-icons.css", [], $this->version);
        //wp_enqueue_style("uip-icons");
        ///MAIN APP CSS
        wp_register_style("uip-app", $this->pathURL . "assets/css/uip-app.css", [], $this->version);
        wp_enqueue_style("uip-app");
      }
    } else {
      if (!wp_style_is("uip-app-rtl", "enqueued")) {
        ///GOOGLE ICONS
        //wp_register_style("uip-icons", $this->pathURL . "assets/css/uip-icons.css", [], $this->version);
        //wp_enqueue_style("uip-icons");
        ///MAIN APP CSS
        wp_register_style("uip-app-rtl", $this->pathURL . "assets/css/uip-app-rtl.css", [], $this->version);
        wp_enqueue_style("uip-app-rtl");
      }
    }

    add_action("admin_footer", [$uipressFolders, "build_media_template"]);
    add_action("wp_footer", [$uipressFolders, "build_media_template"]);
  }

  /**
   * Adds folders to posts and pages
   * @since 2.2
   */

  public function start_post_folders()
  {
    if (!is_user_logged_in()) {
      return;
    }

    $screen = get_current_screen();
    if ($screen->base != "edit") {
      return;
    }

    $utils = new uipress_util();
    $foldersOn = $utils->get_option("folders", "status");
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("folders", "disabled-for", true));

    if ($foldersOn == "true" || $foldersDisabledForUser) {
      return;
    }

    $folders_post_types = $utils->get_option("folders", "folders-post-tyes", true);

    if (!is_array($folders_post_types)) {
      return;
    }

    if (!in_array($screen->post_type, $folders_post_types)) {
      return;
    }

    add_action("admin_footer", [$this, "build_post_folders"]);
    add_action("admin_xml_ns", [$this, "html_attributes_folders"]);

    foreach ($folders_post_types as $post_type) {
      add_filter("manage_" . $post_type . "_posts_columns", [$this, "uip_add_drag_column"]);
      if ($post_type == "page") {
        add_action("manage_pages_custom_column", [$this, "uip_add_drag_icon"], 10, 2);
      }
    }
    add_action("manage_posts_custom_column", [$this, "uip_add_drag_icon"], 10, 2);
  }

  /**
   * Adds draggable column to posts
   * @since 2.2
   */
  public function uip_add_drag_column($columns)
  {
    $newcolumns["uip_draggable"] = "";
    $result = array_merge($newcolumns, $columns);
    return $result;
  }

  /**
   * Adds draggable icon to posts
   * @since 2.2
   */
  function uip_add_drag_icon($column_id, $post_id)
  {
    //run a switch statement for all of the custom columns created
    switch ($column_id) {
      case "uip_draggable":
        echo '<div class="uip-flex uip-w-28">';
        echo '<span class="material-icons-outlined uip-cursor-drag uip-post-drag" data-id="' . $post_id . '" draggable="true">drag_indicator</span>';
        echo "</div>";
        break;

      //add more items here as needed, just make sure to use the column_id in the filter for each new item.
    }
  }
  /**
   * Adds folders to posts and pages
   * @since 2.2
   */

  public function build_post_folders()
  {
    require_once $this->path . "admin/classes/folders.php";
    $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, "uipress", $this->pathURL);
    $screen = get_current_screen();
    $posttype = $screen->post_type;
    ?>
    <script>
    const uipContentPage = '<?php echo $posttype; ?>';
    </script>
    <div class="uip-post-folders">
      <div class="uip-margin-bottom-s uip-text-bold uip-background-muted uip-padding-xs uip-border-round uip-flex uip-flex-center uip-text-bold uip-body-font"><?php _e("Folders", "uipress"); ?></div>
    <?php $uipressFolders->output_for_content(); ?>
    </div>
    <?php
  }

  /**
   * Adds attr to html
   * @since 2.2
   */
  public function html_attributes_folders()
  {
    echo 'uip-post-folders="true"';
  }

  /**
   * Adds front actions for toolbar / menu
   * @since 2.2
   */

  public function load_actions_front()
  {
    if (!is_user_logged_in()) {
      return;
    }
    //KILLS UIP MENU FOR IFRAME ADMIN PAGES
    if (isset($_GET["uip_no_menu"])) {
      if ($_GET["uip_no_menu"] == "true") {
        add_filter("show_admin_bar", "__return_false");
        return;
      }
    }
    //KILLS UIP MENU FOR BRICKS BUILDER
    if (isset($_GET["bricks"])) {
      if ($_GET["bricks"] == "run") {
        return;
      }
    }

    //KILLS UIP MENU FOR OXYGEN BUILDER
    if (isset($_GET["ct_builder"])) {
      if ($_GET["ct_builder"] == "true") {
        return;
      }
    }
    //KILLS UIP MENU FOR DIVI BUILDER
    if (isset($_GET["et_fb"])) {
      if ($_GET["et_fb"] == "1") {
        return;
      }
    }

    //KILLS UIP MENU FOR ELEMENTOR BUILDER
    if (isset($_GET["action"])) {
      if ($_GET["action"] == "elementor") {
        return;
      }
    }
    //KILLS UIP MENU FOR ELEMENTOR PREVIEW
    if (isset($_GET["elementor-preview"])) {
      return;
    }
    //KILLS UIP MENU FOR BEAVER BUILDER
    if (isset($_GET["fl_builder"])) {
      return;
    }

    //error_log(get_post_type());

    $menuStatus = $this->menu_actions_front();
    $toolbarStatus = $this->toolbar_actions_front();

    if ($menuStatus || $toolbarStatus) {
      add_filter("uipress_register_settings", [$this, "get_app_settings_options"], 1, 2);
      add_action("wp_enqueue_scripts", [$this, "add_scripts_and_styles"]);
      $styles = new uipress_styles($this->version, $this->pluginName, $this->path, "uipress", $this->pathURL);
      add_action("wp_body", [$styles, "add_user_styles"]);
      add_filter("language_attributes", [$this, "html_attributes_front"], 999, 2);
      add_action("wp_head", [$styles, "add_custom_css_js_front"]);
      //$this->add_custom_css_js_front();
    }
    if ($menuStatus) {
      add_filter("language_attributes", [$this, "html_attributes_front_menu"], 10, 2);
    }
    add_filter("wp_footer", [$this, "remove_menu_toolbar_dynamic"], 10);
  }

  /**
   * Removes default toolbar / menu on the frontend when dynamic loading is enabled
   * @since 2.4.2
   */
  public function remove_menu_toolbar_dynamic()
  {
    $utils = new uipress_util();
    $dynamic = $utils->get_option("general", "dynamic-loading");

    if (!$dynamic) {
      return;
    }?>
    <style>
      html[uip-ajax-page="true"] #wpadminbar{
        display: none;
      }
      html[uip-ajax-page="true"] {
        margin-top: 0 !important;
      }
      html[uip-ajax-page="true"] #uip-admin-menu-front-container{
        display: none !important;
      }
    </style>
    <script>
      jQuery(window).load(function () {
        if (window.self !== window.top) {
          jQuery("html").attr("uip-ajax-page", "true");
          jQuery("html").attr("uip-toolbar", "false");
          jQuery("html").attr("uip-toolbar-front", "false");
          jQuery("html").attr("uip-admin-menu-front", "false");
          jQuery("#uip-ajax-loader").show();
          jQuery(window).load(function () {
            jQuery("#uip-ajax-loader").hide();
          });
        }
      })
    </script>
    <?php
  }
  /**
   * Adds toolbar module for the front
   * @since 2.2
   */
  public function toolbar_actions_front()
  {
    if (!is_admin_bar_showing()) {
      return false;
    }

    if (!is_user_logged_in()) {
      return false;
    }

    $utils = new uipress_util();
    $this->toolbarStatus = $utils->get_option("toolbar", "status");
    $loadFront = $utils->get_option("toolbar", "load-front");
    $hideFront = $utils->get_option("toolbar", "hide-admin");

    if (!is_singular("uip-admin-page")) {
      if ($hideFront == "true") {
        add_filter("show_admin_bar", "is_blog_admin");
        return false;
      }

      if ($loadFront != "true") {
        return false;
      }
    }

    if ($this->toolbarStatus) {
      return false;
    }

    $this->toolbarStatus = $utils->valid_for_user($utils->get_option("toolbar", "disabled-for", true));

    if ($this->toolbarStatus) {
      return false;
    }

    $this->front = true;

    add_action("wp_head", [$this, "capture_admin_bar"]);
    add_action("wp_footer", [$this, "build_toolbar"]);

    return true;
  }

  /**
   * Adds command center actions
   * @since 2.2
   */
  public function command_center_actions()
  {
    if (!is_user_logged_in()) {
      return;
    }

    $utils = new uipress_util();

    $this->commandStatus = $utils->valid_for_user($utils->get_option("command-center", "enabled-for", true));

    if (!$this->commandStatus) {
      return;
    }

    add_filter("admin_footer", [$this, "add_command_center"]);
    add_filter("wp_footer", [$this, "add_command_center"]);

    if (!wp_script_is("uip-app", "enqueued")) {
      //STYLES
      ///GOOGLE FONTS
      wp_register_style("uip-font", $this->pathURL . "assets/css/uip-font.css", [], $this->version);
      wp_enqueue_style("uip-font");

      ///MAIN APP CSS
      if (is_rtl()) {
        wp_register_style("uip-app-rtl", $this->pathURL . "assets/css/uip-app-rtl.css", [], $this->version);
        wp_enqueue_style("uip-app-rtl");
      } else {
        wp_register_style("uip-app", $this->pathURL . "assets/css/uip-app.css", [], $this->version);
        wp_enqueue_style("uip-app");
      }
      //SCRIPTS
      wp_enqueue_script("uip-vue", $this->pathURL . "assets/js/uip-vue.js", ["jquery"], $this->version);
      wp_enqueue_script("uip-app", $this->pathURL . "assets/js/uip-app.min.js", ["jquery"], $this->version, true);
      wp_localize_script("uip-app", "uip_ajax", [
        "ajax_url" => admin_url("admin-ajax.php"),
        "security" => wp_create_nonce("uip-security-nonce"),
        "preferences" => json_encode($utils->get_user_preferences()),
        "masterPrefs" => json_encode($this->get_master_prefs()),
        "translations" => json_encode($this->get_translations()),
        "defaults" => json_encode($this->get_defaults()),
        "network" => $this->network,
        "front" => json_encode($this->front),
      ]);
    }

    wp_enqueue_script("uip-command-center", $this->pathURL . "assets/js/uip-command-center.min.js", ["uip-app"], $this->version, true);

    //ajax
    add_action("wp_ajax_uip_get_contextual", [$this, "uip_get_contextual"]);
    add_action("wp_ajax_uip_delete_item_from_command", [$this, "uip_delete_item_from_command"]);
    add_action("wp_ajax_uip_modify_plugin_status", [$this, "uip_modify_plugin_status"]);
    add_action("wp_ajax_uip_duplicate_item_from_command", [$this, "uip_duplicate_item_from_command"]);
    add_action("wp_ajax_uip_search_wp_directory", [$this, "uip_search_wp_directory"]);
    add_action("wp_ajax_uip_site_global_search", [$this, "uip_site_global_search"]);
    add_action("wp_ajax_uip_get_quick_actions", [$this, "uip_get_quick_actions"]);
  }

  /**
   * Adds menu module for the front
   * @since 2.2.9.2
   */
  public function menu_actions_front()
  {
    if (!is_user_logged_in()) {
      return false;
    }

    if (is_admin() || is_customize_preview()) {
      return;
    }
    //only check for disable conditions if we are not on a custom admin page
    if (!is_singular("uip-admin-page")) {
      $utils = new uipress_util();
      $this->toolbarStatus = $utils->get_option("menu", "status");
      $loadFront = $utils->get_option("menu", "load-front");

      $loadfor = $utils->get_option("menu", "load-front-for", true);
      $conditionalLoad = $utils->valid_for_user($loadfor);

      if ($conditionalLoad != true && !empty($loadfor)) {
        return false;
      }

      if ($loadFront != "true") {
        return false;
      }

      if ($this->toolbarStatus) {
        return false;
      }

      $this->toolbarStatus = $utils->valid_for_user($utils->get_option("menu", "disabled-for", true));

      if ($this->toolbarStatus) {
        return false;
      }
    }

    $this->front = true;

    add_action("wp_footer", [$this, "output_menu_front"], 1);
    add_action("wp_footer", [$this, "output_admin_menu_front"], 2);

    return true;
  }

  /**
   * Passes menu to be outputted
   * @since 2.1.6
   */
  public function output_menu_front()
  {
    $userid = get_current_user_id();
    $mastermenu = get_transient("uip_admin_menu-" . $userid);

    if (!$mastermenu) {
      $utils = new uipress_util();
      $mastermenu["prefs"] = $utils->get_user_preferences();
      $mastermenu["menu"] = [];
    }

    $this->print_admin_menu_front($mastermenu);
  }

  /**
   * Captures admin bar for later output
   * @since 2.1.6
   */

  function capture_admin_bar()
  {
    ob_start();

    wp_admin_bar_render();

    $this->toolbar = ob_get_clean();
  }

  /**
   * Adds toolbar module actions
   * @since 2.2
   */
  public function toolbar_actions()
  {
    $utils = new uipress_util();
    $debug = new uipress_debug();
    $this->toolbarStatus = $utils->get_option("toolbar", "status");

    if ($this->toolbarStatus) {
      return;
    }

    $this->toolbarStatus = $utils->valid_for_user($utils->get_option("toolbar", "disabled-for", true));

    if ($this->toolbarStatus) {
      return;
    }

    add_action("admin_head", [$this, "build_toolbar"]);
    add_filter("pre_get_posts", [$this, "uip_modify_query"]);

    $showNotices = $utils->get_option("toolbar", "notification-center-disabled");
    $dataC = $debug->check_network_connection();
    ///CAPTURE ADMIN NOTICES
    if (!$showNotices && $dataC) {
      add_action("admin_notices", [$this, "start_capture_admin_notices"], -99);
      add_action("admin_notices", [$this, "capture_admin_notices"], 999);
    }
  }
  /**
   * Adds menu module actions
   * @since 2.2
   */
  public function menu_actions()
  {
    add_action("parent_file", [$this, "capture_wp_menu"], 9999);

    $utils = new uipress_util();
    $this->menuStatus = $utils->get_option("menu", "status");

    if ($this->menuStatus) {
      return;
    }

    $this->menuStatus = $utils->valid_for_user($utils->get_option("menu", "disabled-for", true));

    if ($this->menuStatus) {
      return;
    }

    add_action("adminmenu", [$this, "print_admin_menu"], 1);
    add_action("adminmenu", [$this, "output_admin_menu"], 2);
    add_action("admin_enqueue_scripts", [$this, "remove_menu_styles"]);
  }

  public function html_attributes()
  {
    $utils = new uipress_util();
    $prefs = $utils->get_user_preferences();

    if (isset($prefs["menuShrunk"])) {
      if ($prefs["menuShrunk"]) {
        echo 'menu-folded="true"';
      }
    }

    if (isset($prefs["darkmode"])) {
      if ($prefs["darkmode"]) {
        echo 'data-theme="dark"';
      }
    }

    if (!$this->toolbarStatus) {
      echo 'uip-toolbar="true"';
    }

    if (!$this->menuStatus) {
      echo 'uip-admin-menu="true"';
    }

    $flyouttoolbar = $utils->get_option("toolbar", "flyout-toolbar");

    if ($flyouttoolbar) {
      echo 'uip-flyout-toolbar="true"';
    }

    if (isset($_GET["uip-ajax-page"])) {
      if ($_GET["uip-ajax-page"] == "1") {
        echo 'uip-ajax-page="true"';
      }
    }

    $themeDisabled = $utils->get_option("theme", "status");
    $themeDisabledFor = $utils->valid_for_user($utils->get_option("theme", "disabled-for", true));

    if (!$themeDisabled && !$themeDisabledFor) {
      echo 'uip-admin-theme="true"';
    }
  }

  /**
   * Adds html attributes to front
   * @since 2.2
   */
  public function html_attributes_front($output, $doctype)
  {
    if (stripos($_SERVER["SCRIPT_NAME"], strrchr(wp_login_url(), "/")) == false) {
      $utils = new uipress_util();
      $loginDarkMode = $utils->get_option("login", "login-dark-mode");
      $prefs = $utils->get_user_preferences();
      $attributes = "";

      if (isset($prefs["darkmode"])) {
        if ($prefs["darkmode"]) {
          $attributes = $attributes . " data-theme=\"dark\"";
        }
      }

      if (isset($prefs["menuShrunk"])) {
        if ($prefs["menuShrunk"]) {
          echo " menu-folded=\"true\"";
        }
      }

      if (is_singular("uip-admin-page")) {
        $attributes = $attributes . " uip-admin-page=\"true\"";
      }

      $toolbar = $utils->get_option("toolbar", "status");

      $flyouttoolbar = $utils->get_option("toolbar", "flyout-toolbar");

      if ($flyouttoolbar) {
        $attributes = $attributes . " uip-flyout-toolbar=\"true\"";
      }

      if ($toolbar != true && !$this->toolbarStatus) {
        $attributes = $attributes . " uip-toolbar=\"true\"";
        $attributes = $attributes . " uip-toolbar-front=\"true\"";
      }
    }

    return $output . $attributes;
  }

  /**
   * Adds html attributes to front
   * @since 2.2
   */
  public function html_attributes_front_menu($output, $doctype)
  {
    if (stripos($_SERVER["SCRIPT_NAME"], strrchr(wp_login_url(), "/")) == false) {
      $output = $output . 'uip-admin-menu="true"';
      $output = $output . 'uip-admin-menu-front="true"';
    }

    return $output;
  }

  /**
   * Adds html attributes to login page
   * @since 2.2
   */
  public function html_attributes_login_page($output, $doctype)
  {
    if (stripos($_SERVER["SCRIPT_NAME"], strrchr(wp_login_url(), "/")) !== false) {
      $utils = new uipress_util();
      $loginDarkMode = $utils->get_option("login", "login-dark-mode");

      if ($loginDarkMode == "true") {
        $output = $output . ' data-theme="dark"';
      }
    }

    return $output;
  }

  public function check_data_connection()
  {
    $debug = new uipress_debug();
    $debug->check_connection();
  }
  /**
   * Removes wordpress link on login page
   * @since 2.2
   */
  public function login_logo_url($url)
  {
    return get_home_url();
  }
  /**
   * Adds a uip body class to the login page
   * @since 2.2
   */

  public function add_login_body_classes($classes)
  {
    $utils = new uipress_util();
    $loginDisabled = $utils->get_option("login", "status");
    //$loginDisabledFor = $utils->valid_for_user($utils->get_option("login", "disabled-for", true));

    if ($loginDisabled != "true") {
      $classes[] = "uip-login";
    }

    return $classes;
  }

  /**
   * Loads all required styles and scripts for UiPress Login
   * @since 2.2
   */

  public function add_login_styles()
  {
    ///GOOGLE FONTS
    wp_register_style("uip-font", $this->pathURL . "assets/css/uip-font.css", [], $this->version);
    wp_enqueue_style("uip-font");

    ///GOOGLE ICONS
    //wp_register_style("uip-icons", $this->pathURL . "assets/css/uip-icons.css", [], $this->version);
    //wp_enqueue_style("uip-icons");

    ///MAIN APP CSS
    if (is_rtl()) {
      wp_register_style("uip-app", $this->pathURL . "assets/css/uip-app-rtl.css", [], $this->version);
      wp_enqueue_style("uip-app");
    } else {
      wp_register_style("uip-app", $this->pathURL . "assets/css/uip-app.css", [], $this->version);
      wp_enqueue_style("uip-app");
    }

    //SET LOGO
    $utils = new uipress_util();
    $logo = $utils->get_option("login", "login-logo");
    $loginBg = $utils->get_option("login", "login-background");

    if (!$logo) {
      $logo = $this->pathURL . "assets/img/default_logo.svg";
    }
    ?>
      <style type="text/css"> body.uip-login h1 a {  background-image:url('<?php echo $logo; ?>')  !important; } </style>
      <?php
      if ($loginBg) { ?>
      <style type="text/css"> body.uip-login::before {  background-image:url('<?php echo $loginBg; ?>')  !important; } </style> 
      <?php }

      $this->add_custom_css_js();
  }

  /**
   * Adds custom css and javascript
   * @since 2.2
   */

  public function add_custom_css_js()
  {
    $utils = new uipress_util();
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("advanced", "disabled-for", true));

    $favicon = $utils->get_option("general", "admin-favicon");

    if ($favicon != false && $favicon != "") {
      echo '<link rel="shortcut icon" type="image/jpg" href="' . $favicon . '"/>';
    }

    if ($foldersDisabledForUser) {
      return;
    }

    $css = $utils->get_option("advanced", "admin-css");
    $js = $utils->get_option("advanced", "admin-js");
    $html = $utils->get_option("advanced", "admin-html");

    if ($css != "") {
      echo '<style type="text/css" id="uip-user-custom-css">';
      echo html_entity_decode(stripslashes($css));
      echo "</style>";
    }

    if ($js != "") {
      echo '<script id="uip-user-custom-js">';
      echo html_entity_decode(stripslashes($js));
      echo "</script>";
    }

    if ($html != "") {
      echo html_entity_decode(stripslashes($html));
    }
  }

  /**
   * Adds custom css and javascript on the front end
   * @since 2.2
   */

  public function add_custom_css_js_front()
  {
    $utils = new uipress_util();

    $css = $utils->get_option("advanced", "admin-css");
    $js = $utils->get_option("advanced", "admin-js");

    if ($css != "") {
      echo '<style type="text/css" id="uip-user-custom-css">';
      echo html_entity_decode(stripslashes($css));
      echo "</style>";
    }

    if ($js != "") {
      echo '<script id="uip-user-custom-js">';
      echo html_entity_decode(stripslashes($js));
      echo "</script>";
    }
  }

  /**
   * Loads all required styles and scripts for UiPress base app
   * @since 2.2
   */

  public function add_scripts_and_styles()
  {
    $utils = new uipress_util();

    ///GOOGLE FONTS
    wp_register_style("uip-font", $this->pathURL . "assets/css/uip-font.css", [], $this->version);
    wp_enqueue_style("uip-font");

    ///MAIN APP CSS
    if (is_rtl()) {
      wp_register_style("uip-app-rtl", $this->pathURL . "assets/css/uip-app-rtl.css", [], $this->version);
      wp_enqueue_style("uip-app-rtl");
    } else {
      wp_register_style("uip-app", $this->pathURL . "assets/css/uip-app.css", [], $this->version);
      wp_enqueue_style("uip-app");
    }

    //CUSTOM ATT
    add_filter("style_loader_tag", [$this, "uip_add_type_attribute"], 10, 4);
    //VUE
    wp_enqueue_script("uip-vue", $this->pathURL . "assets/js/uip-vue.js", ["jquery"], $this->version);

    ///MENU APP
    wp_enqueue_script("uip-app", $this->pathURL . "assets/js/uip-app.min.js", ["jquery"], $this->version, true);
    wp_localize_script("uip-app", "uip_ajax", [
      "ajax_url" => admin_url("admin-ajax.php"),
      "security" => wp_create_nonce("uip-security-nonce"),
      "preferences" => json_encode($utils->get_user_preferences()),
      "masterPrefs" => json_encode($this->get_master_prefs()),
      "translations" => json_encode($this->get_translations()),
      "defaults" => json_encode($this->get_defaults()),
      "network" => $this->network,
      "front" => json_encode($this->front),
    ]);

    ///TOOLBAR APP
    wp_enqueue_script("uip-toolbar-app", $this->pathURL . "assets/js/uip-toolbar.min.js", ["uip-app"], $this->version, true);

    $scripts = $utils->get_option("advanced", "enqueue-scripts");
    $styles = $utils->get_option("advanced", "enqueue-styles");

    if (is_array($scripts) && count($scripts) > 0) {
      foreach ($scripts as $key => $value) {
        wp_enqueue_script("uipress-custom-script-" . $key, $value, ["jquery"], $this->version);
      }
    }

    if (is_array($styles) && count($styles) > 0) {
      foreach ($styles as $key => $value) {
        wp_register_style("uipress-custom-style-" . $key, $value, [], $this->version);
        wp_enqueue_style("uipress-custom-style-" . $key);
      }
    }

    $this->load_plugin_css();
  }

  /**
   * Adds a module tag to uip-user-app
   * @since 2.3.5
   */

  public function uip_add_type_attribute($tag, $handle, $src, $media)
  {
    // if not your script, do nothing and return original $tag
    if ("uip-app" == $handle || "uip-app-rtl" == $handle) {
      $tag = '<link rel="stylesheet preload prefetch" as="style" href="' . $src . '" id="' . $handle . '" media="' . $media . '" crossorigin="false">';
      return $tag;
    }

    // change the script tag by adding type="module" and return it.
    //$tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    return $tag;
  }

  /**
   * Adds supporting stylesheets for other plugins
   * @since 2.2
   */
  public function load_plugin_css()
  {
    $supportedplugins["woocommerce"] = $this->pathURL . "assets/css/plugins/woocommerce.css";
    $supportedplugins["advanced-custom-fields"] = $this->pathURL . "assets/css/plugins/advanced-custom-fields.css";
    $supportedplugins["advanced-custom-fields-pro"] = $this->pathURL . "assets/css/plugins/advanced-custom-fields.css";
    $supportedplugins["breeze"] = $this->pathURL . "assets/css/plugins/breeze.css";
    $supportedplugins["cartflows"] = $this->pathURL . "assets/css/plugins/cartflows.css";
    $supportedplugins["codepress-admin-columns"] = $this->pathURL . "assets/css/plugins/codepress-admin-columns.css";
    $supportedplugins["contact-form-7"] = $this->pathURL . "assets/css/plugins/contact-form-7.css";
    $supportedplugins["elementor"] = $this->pathURL . "assets/css/plugins/elementor.css";
    $supportedplugins["fluentform"] = $this->pathURL . "assets/css/plugins/fluentform.css";
    $supportedplugins["gravityforms"] = $this->pathURL . "assets/css/plugins/gravityforms.css";
    $supportedplugins["smart-slider-3"] = $this->pathURL . "assets/css/plugins/smart-slider-3.css";
    $supportedplugins["smart-slider-3-pro"] = $this->pathURL . "assets/css/plugins/smart-slider-3.css";
    $supportedplugins["wp-seopress"] = $this->pathURL . "assets/css/plugins/wp-seopress.css";
    $supportedplugins["wp-seopress-pro"] = $this->pathURL . "assets/css/plugins/wp-seopress.css";
    $supportedplugins["ws-form"] = $this->pathURL . "assets/css/plugins/ws-form.css";
    $supportedplugins["ws-form-pro"] = $this->pathURL . "assets/css/plugins/ws-form.css";
    $supportedplugins["groundhogg"] = $this->pathURL . "assets/css/plugins/groundhogg.css";
    $supportedplugins["groundhogg-pro"] = $this->pathURL . "assets/css/plugins/groundhogg.css";
    $supportedplugins["wordfence"] = $this->pathURL . "assets/css/plugins/wordfence.css";
    $supportedplugins["code-snippets"] = $this->pathURL . "assets/css/plugins/code-snippets.css";
    $supportedplugins["lifterlms"] = $this->pathURL . "assets/css/plugins/lifter-lms.css";
    $supportedplugins["revslider"] = $this->pathURL . "assets/css/plugins/revslider.css";

    $activeplugins = get_option("active_plugins");
    foreach ($activeplugins as $plugin) {
      $string = explode("/", $plugin);
      $pluginname = $string[0];

      if (isset($supportedplugins[$pluginname])) {
        if ($supportedplugins[$pluginname] != "") {
          wp_register_style("uipress-" . $pluginname, $supportedplugins[$pluginname], [], $this->version);
          wp_enqueue_style("uipress-" . $pluginname);
        }
      }
    }
  }

  /**
   * Removes and replaces default admin mneu css
   * @since 2.2
   */

  public function remove_menu_styles()
  {
    wp_dequeue_style("admin-menu");
    wp_deregister_style("admin-menu");
    wp_register_style("admin-menu", $this->pathURL . "assets/css/uip-blank.css", [], $this->version);
    wp_enqueue_style("admin-menu");
  }

  /**
   * Changes wp footer text
   * @since 2.2
   */
  public function change_footer_admin()
  {
    $utils = new uipress_util();
    $hidden = $utils->get_option("general", "hide-footer");
    $footerText = $utils->get_option("general", "footer-text");

    if ($hidden == "true") {
      echo "";
      return;
    }

    if ($footerText != "") {
      echo $footerText;
      return;
    }

    echo 'Powered by <a href="https://wordpress.org/">WordPress</a> & <a href="https://www.uipress.co/">UiPress</a>';
  }

  /**
   * Adds columns header to plugin table
   * @since 2.2
   */
  public function add_plugin_status_column($columns)
  {
    $newCoumns = [];

    foreach ($columns as $key => $value) {
      $newCoumns[$key] = $value;

      if ($key == "cb") {
        $newCoumns["status"] = __("Status", "uipress");
      }
    }

    return $newCoumns;
  }

  /**
   * Adds plugin status to plugins table
   * @since 2.2
   */
  public function add_plugin_status($column_name, $plugin_file, $plugin_data)
  {
    if ("status" == $column_name) {
      if (is_plugin_active($plugin_file)) {
        echo '<span class="uip-padding-left-xxs uip-padding-right-xxs uip-background-green-wash uip-border-round uip-margin-top-xs uip-display-table-cell uip-text-bold uip-text-green">' .
          __("active", "uipress") .
          "</span>";
      } else {
        echo '<span class="uip-padding-left-xxs uip-padding-right-xxs uip-background-orange-wash uip-border-round uip-margin-top-xs uip-display-table-cell uip-text-bold uip-text-orange">' .
          __("inactive", "uipress") .
          "</span>";
      }
    }
  }

  /**
   * Builds Master Preferences
   * @since 2.2
   */
  public function get_master_prefs()
  {
    $allSettings = apply_filters("uipress_register_settings", [], $this->network);
    return $allSettings;
  }

  /**
   * Gets basic default info for app
   * @since 2.2
   */
  public function get_defaults()
  {
    $arg = [
      "default" => "404",
      "size" => "200",
    ];

    $img = get_avatar_url(get_current_user_id(), $arg);

    $front = false;

    if (!is_admin() && is_singular("uip-admin-page") != true) {
      $front = true;
    }

    $defaults = [
      "logo" => esc_url($this->pathURL . "assets/img/default_logo.svg"),
      "darkLogo" => esc_url($this->pathURL . "assets/img/default_logo_dark.svg"),
      "adminHome" => $this->get_admin_home_url(),
      "adminURL" => admin_url(),
      "siteHome" => get_home_url(),
      "logOut" => wp_logout_url(),
      "siteName" => html_entity_decode(get_bloginfo("name")),
      "front" => $front,
      "user" => [
        "initial" => $this->get_user_details("initial"),
        "username" => $this->get_user_details("username"),
        "email" => $this->get_user_details("email"),
        "img" => $img,
      ],
    ];
    return $defaults;
  }

  /**
   * Capture admin notices
   * @since 2.9
   */

  public function start_capture_admin_notices()
  {
    ob_start();
  }

  /**
   * End Capture admin notices and save out to transient
   * @since 2.9
   */

  public function capture_admin_notices()
  {
    $userid = get_current_user_id();
    $notices = ob_get_clean();

    set_transient("uip-admin-notices-" . $userid, $notices, 0.5 * HOUR_IN_SECONDS);
  }

  /**
   * Gets default or custom admin home url
   * @since 2.2
   */

  public function get_admin_home_url()
  {
    $utils = new uipress_util();
    $redirect = $utils->get_option("general", "redirect-overview");
    $redirectCustom = $utils->get_option("general", "redirect-custom");

    $redirect_to = admin_url();

    if ($redirect == "true" && !$redirectCustom) {
      $redirect_to = admin_url() . "admin.php?page=uip-overview";
    }

    if ($redirectCustom && $redirectCustom != "") {
      if ($utils->isAbsoluteUrl($redirectCustom)) {
        $redirect_to = $redirectCustom;
      } else {
        $redirect_to = admin_url() . $redirectCustom;
      }
    }

    return $redirect_to;
  }

  /**
   * Gets user info
   * @since 2.2
   */

  public function get_user_details($type)
  {
    $current_user = wp_get_current_user();

    $username = $current_user->user_login;
    $email = $current_user->user_email;
    $first = $current_user->user_firstname;
    $last = $current_user->user_lastname;

    if ($type == "username") {
      return strtolower($username);
    }

    if ($type == "email") {
      return strtolower($email);
    }

    if ($type == "initial") {
      if ($first == "" || $last == "") {
        $name_string = str_split($username, 1);
        $name_string = $name_string[0];
      } else {
        $name_string = str_split($username, 1)[0];
      }

      if (strlen($name_string) != strlen(iconv("UTF-8", "UTF-8//IGNORE", $name_string))) {
        $name_string = str_split($username, 1)[0];
      }

      return strtolower($name_string);
    }
  }

  /**
   * Builds Translations
   * @since 2.2
   */
  public function get_translations()
  {
    $translations["menuPreferences"] = __("Menu Preferences", "uipress");
    $translations["hideSearchBar"] = __("Hide search bar", "uipress");
    $translations["hideIcons"] = __("Hide Icons", "uipress");
    $translations["showSubmenuHover"] = __("Show submenu on hover", "uipress");
    $translations["searchMenu"] = __("Search Menu", "uipress");
    $translations["preFeature"] = __("Pro Feature", "uipress");
    $translations["search"] = __("Search", "uipress");
    $translations["view"] = __("View", "uipress");
    $translations["edit"] = __("Edit", "uipress");
    $translations["showMore"] = __("Show more", "uipress");
    $translations["otherMatches"] = __("other matches", "uipress");
    $translations["nothingFound"] = __("Nothing found", "uipress");
    $translations["viewSite"] = __("View Site", "uipress");
    $translations["viewDashboard"] = __("Dashboard", "uipress");
    $translations["searchSite"] = __("Search Site", "uipress");
    $translations["create"] = __("Create", "uipress");
    $translations["createNew"] = __("Create New", "uipress");
    $translations["viewSite"] = __("View Site", "uipress");
    $translations["updates"] = __("Updates", "uipress");
    $translations["preferences"] = __("Preferences", "uipress");
    $translations["darkMode"] = __("Dark mode", "uipress");
    $translations["showScreenOptions"] = __("Show screen options toggle", "uipress");
    $translations["screenOptions"] = __("Screen options", "uipress");
    $translations["hideLegacy"] = __("Hide admin bar links (left)", "uipress");
    $translations["logOut"] = __("Logout", "uipress");
    $translations["notifications"] = __("Notifications", "uipress");
    $translations["hideNotification"] = __("Hide notification", "uipress");
    $translations["hiddenNotification"] = __("hidden notifications", "uipress");
    $translations["showAll"] = __("show all", "uipress");
    $translations["notificationHidden"] = __("Notifiction Hidden", "uipress");
    $translations["toggleMenu"] = __("Toggle Menu", "uipress");
    $translations["chooseUserRole"] = __("Choose users or roles", "uipress");
    $translations["searchUserRole"] = __("Search users and roles", "uipress");
    $translations["chooseImage"] = __("Choose Image", "uipress");
    $translations["choosePostTypes"] = __("Choose Post Types", "uipress");
    $translations["searchPostTypes"] = __("Searach Post Types", "uipress");
    $translations["searchPostTypes"] = __("Searach Post Types", "uipress");
    $translations["somethingWrong"] = __("Something went wrong", "uipress");
    $translations["settingsSaved"] = __("Settings saved", "uipress");
    $translations["nothingFound"] = __("Nothing found", "uipress");
    $translations["default"] = __("Default", "uipress");
    $translations["addFile"] = __("Add File", "uipress");
    $translations["urlToFile"] = __("URL to file", "uipress");
    $translations["remove"] = __("Remove", "uipress");
    $translations["allMedia"] = __("All media", "uipress");
    $translations["allContent"] = __("All Content", "uipress");
    $translations["noFolder"] = __("No folder", "uipress");
    $translations["folders"] = __("Folders", "uipress");
    $translations["newFolder"] = __("New Folder", "uipress");
    $translations["folderName"] = __("Folder Name", "uipress");
    $translations["color"] = __("Colour", "uipress");
    $translations["name"] = __("Name", "uipress");
    $translations["editFolder"] = __("Edit Folder", "uipress");
    $translations["update"] = __("Update", "uipress");
    $translations["oneFile"] = __("1 File", "uipress");
    $translations["files"] = __("files", "uipress");
    $translations["noFolders"] = __("You haven't created a folder yet", "uipress");
    $translations["removeFromFolder"] = __("Remove from folder", "uipress");
    $translations["unlockNotificationCenter"] = __("Upgrade to pro to unlock the notification center. View, edit and organise all your plugin and theme notifications in one place", "uipress");
    $translations["unlockSearch"] = __("Upgrade to pro to gain full control of search results and included post types", "uipress");
    $translations["notValidJson"] = __("Please select a valid JSON file", "uipress");
    $translations["fileToBig"] = __("File is to big", "uipress");
    $translations["stylesImported"] = __("Styles Imported", "uipress");
    $translations["settingsImported"] = __("Settings Imported", "uipress");
    $translations["removeLicence"] = __("Remove Licence", "uipress");
    $translations["isActivated"] = sprintf(__("%s Pro is active", "uipress"), $this->pluginName);
    $translations["uipressPro"] = sprintf(__("%s Pro", "uipress"), $this->pluginName);
    $translations["activate"] = __("Activate", "uipress");
    $translations["addProLicence"] = __("Add a pro licence to unlock pro features.", "uipress");
    $translations["chooseIcon"] = __("Choose Icon", "uipress");
    $translations["confirmDelete"] = __("Are you sure you want to delete this?", "uipress");
    $translations["importStarted"] = __("Import started", "uipress");
    $translations["confirmReset"] = __("Are you sure you want to reset the settings?", "uipress");
    $translations["lastSevenDays"] = __("Last 7 Days", "uipress");
    $translations["last30days"] = __("Last 30 Days", "uipress");
    $translations["thisMonth"] = __("This Month", "uipress");
    $translations["lastMonth"] = __("Last Month", "uipress");
    $translations["today"] = __("Today", "uipress");
    $translations["yesterday"] = __("Yesterday", "uipress");
    $translations["selected"] = __("selected", "uipress");
    $translations["themeLibrary"] = __("Theme Library", "uipress");
    $translations["library"] = __("Library", "uipress");
    $translations["importTheme"] = __("Import Theme", "uipress");
    $translations["proTemplate"] = __("Pro template", "uipress");
    $translations["themeImported"] = __("Theme Imported", "uipress");
    $translations["madeBy"] = __("Made by", "uipress");
    $translations["settings"] = __("Settings", "uipress");
    $translations["failedfolders"] = __("Failed to fetch folder content", "uipress");
    $translations["user"] = __("user", "uipress");
    $translations["users"] = __("users", "uipress");
    $translations["totalFound"] = __("total found", "uipress");
    $translations["toNavigate"] = __("to navigate", "uipress");
    $translations["toSelect"] = __("to select", "uipress");
    $translations["toClose"] = __("to close", "uipress");
    $translations["forceSearchDirectory"] = __("search directory", "uipress");
    $translations["confirmDelete"] = __("Are you sure you want to delete this?", "uipress");
    $translations["confirmCopy"] = __("Are you sure you want to duplicate this?", "uipress");
    $translations["confirmPluginDelete"] = __("Are you sure you want to delete this plugin from your server?", "uipress");
    $translations["pluginUpdated"] = __("Plugin updated", "uipress");
    $translations["searchDirectory"] = __("Nothing found, search plugin directory?", "uipress");
    $translations["chooseActions"] = __("Select actions", "uipress");
    $translations["searchActions"] = __("Search actions", "uipress");
    $translations["experimental"] = __("Experimental", "uipress");
    return $translations;
  }

  /**
   * Blocks default wp menu output
   * @since 2.2
   */
  public function capture_wp_menu($parent_file)
  {
    ///CHECK FOR CUSTOM MENU FIRST
    $userid = get_current_user_id();
    $utils = new uipress_util();

    ///NO CUSTOM MENU SO PREPARE DEFAULT MENU
    global $menu, $submenu, $self, $parent_file, $submenu_file, $plugin_page, $typenow;
    $this->menu = $menu;
    //CREATE MENU CONSTRUCTOR OBJECT
    $mastermenu["self"] = $self;
    $mastermenu["parent_file"] = $parent_file;
    $mastermenu["submenu_file"] = $submenu_file;
    $mastermenu["plugin_page"] = $plugin_page;
    $mastermenu["typenow"] = $typenow;
    $mastermenu["menu"] = $menu;
    $mastermenu["submenu"] = $submenu;
    ///FORMAT DEFAULT MENU
    $menuOptions = $this->uip_format_admin_menu($mastermenu);
    $formattedMenu = $menuOptions["menu"];

    $mastermenu["menu"] = $formattedMenu;
    $mastermenu["OGmenu"] = $formattedMenu;
    $mastermenu["prefs"] = $utils->get_user_preferences();
    $mastermenu["availableTop"] = $menuOptions["availableTop"];
    $mastermenu["availableSub"] = $menuOptions["availableSub"];

    //$this->print_admin_menu($mastermenu);

    $this->uipMasterMenu = $mastermenu;
    set_transient("uip_admin_menu-" . $userid, $mastermenu, DAY_IN_SECONDS);

    return $parent_file;
  }

  /**
   * Outputs admin menu to js const on frontend
   * @since 2.2.8
   */
  public function print_admin_menu_front($menu)
  {
    if (!is_array($menu) && count($menu) < 1) {
      $menu = [];
    }

    $menuString = json_encode($menu);
    if (!$menuString) {
      $menu = [];
      $menu["menu"] = [];
      error_log("Admin Menu Corrupted: UiPress");
    }
    ob_start();
    ?>
    <script id="uip-admin-menu-const">
      const uipMasterMenu = <?php print $menuString; ?>
    </script>
    <?php print ob_get_clean();
  }

  /**
   * Outputs admin menu to js const
   * @since 2.2.8
   */
  public function print_admin_menu()
  {
    $menu = $this->uipMasterMenu;
    $ogMenu = $menu;
    $userid = get_current_user_id();
    $utils = new uipress_util();
    $usergenerated = [];

    if (!is_network_admin()) {
      $usergenerated = apply_filters("uipress_get_custom_menu", $usergenerated);
    }

    if ($usergenerated && is_array($usergenerated->menu) && count($usergenerated->menu) > 0) {
      $usergeneratedMenu = $usergenerated->menu;
      $autoUpdate = $usergenerated->autoUpdate;
      $menu = [];
      $menu["menu"] = $usergeneratedMenu;
      $menu["customMenu"] = "true";
      $menu["prefs"] = $utils->get_user_preferences();
      $menu["availableTop"] = $ogMenu["availableTop"];
      $menu["availableSub"] = $ogMenu["availableSub"];
      $menu["customTop"] = $usergenerated->availableTop;
      $menu["customSub"] = $usergenerated->availableSub;
      $menu["autoUpdate"] = $autoUpdate;
      $menu["OGmenu"] = $ogMenu["menu"];
      $this->uipMasterMenu = $menu;
      set_transient("uip_admin_menu-" . $userid, $menu, DAY_IN_SECONDS);
    }

    $menuString = json_encode($menu);
    if (!$menuString) {
      $menu = [];
      $menu["menu"] = [];
      error_log("Admin Menu Corrupted: UiPress");
    }
    ob_start();
    ?>
    <script id="uip-admin-menu-const">
         const uipMasterMenu = <?php print $menuString; ?>
    </script>
    <?php print ob_get_clean();
  }

  /**
   * Redirect wp-admin requests to overview page
   * @since 2.2
   */
  public function redirect_to_overview()
  {
    if (!is_user_logged_in()) {
      return;
    }

    $fullLink = trim((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $adminUrl = trim(admin_url());

    if ($fullLink != $adminUrl) {
      return;
    }

    $utils = new uipress_util();
    $redirect = $utils->get_option("general", "redirect-overview");
    $redirectCustom = $utils->get_option("general", "redirect-custom");

    $redirect_to = admin_url("?redirect=1");

    if ($redirect == "true" && !$redirectCustom) {
      $redirect_to = admin_url() . "admin.php?page=uip-overview";
    }

    if ($redirectCustom && $redirectCustom != "") {
      if ($utils->isAbsoluteUrl($redirectCustom)) {
        $redirect_to = $redirectCustom;
      } else {
        $redirect_to = admin_url() . $redirectCustom;
      }
    }
    wp_redirect($redirect_to);
  }

  /**
   * Redirect after login
   * @since 2.2
   */
  public function redirect_to_overview_after_login($redirect_to, $request, $user)
  {
    if ($GLOBALS["pagenow"] === "wp-login.php") {
      return $redirect_to;
    }

    $utils = new uipress_util();
    $redirect = $utils->get_option("general", "redirect-overview");
    $redirectCustom = $utils->get_option("general", "redirect-custom");

    //$redirect_to = admin_url();

    if (is_multisite()) {
      $user_info = get_userdata($user->ID);
      $adURL = get_admin_url($user_info->primary_blog, "admin.php?page=uip-overview");
    }

    if ($redirect == "true" && !$redirectCustom) {
      if (is_multisite()) {
        $user_info = get_userdata($user->ID);
        $redirect_to = get_admin_url($user_info->primary_blog, "admin.php?page=uip-overview");
      } else {
        $redirect_to = admin_url("admin.php?page=uip-overview");
      }
    }

    if ($redirectCustom && $redirectCustom != "") {
      if ($utils->isAbsoluteUrl($redirectCustom)) {
        $redirect_to = $redirectCustom;
      } else {
        $redirect_to = admin_url($redirectCustom);
        if (is_multisite()) {
          $user_info = get_userdata($user->ID);
          $redirect_to = get_admin_url($user_info->primary_blog, $redirectCustom);
        }
      }
    }
    return $redirect_to;
  }
  /**
   * Outputs toolbar block
   * @since 2.2
   */
  public function build_toolbar()
  {
    ob_start();

    if (!$this->front) {
      echo wp_admin_bar_render();
    } else {
      echo $this->toolbar;
    }
    $tb = ob_get_clean();
    $utils = new uipress_util();
    $flyouttoolbar = $utils->get_option("toolbar", "flyout-toolbar");

    if ($flyouttoolbar) { ?>
      <div id="uip-floating-toolbar">
        <div @mouseover="floatingActive = true" @mouseleave="floatingActive = false">
          <floating-toolbar :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences" :updatefloat="floatingActive"></floating-toolbar>
          <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-position-absolute uip-right-100p uip-top-0 uip-background-muted uip-shadow uip-padding-xxs uip-scale-in-right uip-legacy-admin uip-hidden uip-border-round-left" :class="{'uip-nothidden' : floatingActive}">
            <div class="uip-margin-top-xxs">
            <?php echo $tb; ?>
            </div>
          </div>
        </div>
        
      </div>
      <?php } else { ?>
  
  <div id="uip-toolbar" class="uip-padding-s uip-border-box uip-body-font">
    <?php echo $this->toolbar_loader(); ?>
    <div id="uip-toolbar-content" v-if="!loading"> 
      
      
      <div v-if="!loading" class="uip-flex">
        <div class="uip-flex uip-flex-center uip-margin-right-xxs uip-hidden" v-if="!defaults.front && isSmallScreen()" :class="{'uip-nothidden' : !defaults.front && isSmallScreen()}">
          <a href="#" class="material-icons-outlined uip-background-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-toolbar-link uip-no-underline uip-no-outline uip-margin-right-xs" @click="toggleMenu()">menu_open</a>
        </div>
        <div class="uip-flex uip-flex-center" v-if="defaults.front || isSmallScreen()">
          <toolbar-logo :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></toolbar-logo>
        </div>
        <div class="uip-legacy-admin uip-flex-grow" v-if="!isSmallScreen()">
          <div class="uip-hidden" 
          :class="{'uip-nothidden' : showLegacy()}" >
            <?php echo $tb; ?>
          </div>
        </div>
        <div class="uip-legacy-admin uip-flex-grow uip-hidden" :class="{'uip-nothidden' : showLegacy()}" v-if="isSmallScreen()">
          <uip-dropdown type="icon" icon="bolt" pos="full-screen" size="small">
            <?php echo $tb; ?>
          </uip-dropdown>
        </div>
        
        <div class="uip-flex uip-flex-center" >
          <toolbar-search :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></toolbar-search>
        </div>
        <div class="uip-flex uip-flex-center">
          <toolbar-links :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></toolbar-links>
        </div>
        <div class="uip-flex uip-flex-center">
          <toolbar-create :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></toolbar-create>
        </div>
        <div class="uip-flex uip-flex-center">
          <toolbar-offcanvas :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></toolbar-create>
        </div>
      </div>
    </div>
  </div>
  
  <?php }
  }

  /**
   * Outputs UIP toolbar loading placeholder
   * @since 2.2
   */
  public function toolbar_loader()
  {
    ?>
      <div v-if="loading && !isSmallScreen" class="uip-flex uip-gap-xs uip-flex-center">
        <div >
          <svg class="" height="34" width="75">
            <rect width="75" height="34" rx="5" fill="#bbbbbb2e"/>
          </svg>
        </div>
        <div >
          <svg class="" height="34" width="75">
            <rect width="75" height="34" rx="5" fill="#bbbbbb2e"/>
          </svg>
        </div>
        <div class="uip-flex-grow">
        </div>
        <div class="">
          <svg height="28" width="28"><circle cx="14" cy="14" r="14" stroke-width="0" fill="#bbbbbb2e" /></svg>
        </div>
      </div>
    <?php
  }
  /**
   * Outputs UIP admin menu
   * @since 2.2
   */
  public function output_admin_menu()
  {
    global $menu;
    //restore wp menu
    $menu = $this->menu;?>
	<div id="uip-admin-menu" class="uip-flex uip-flex-column">
	</div>
	
	<?php
  }

  /**
   * Outputs UIP admin menu on the front
   * @since 2.2.9.2
   */
  public function output_admin_menu_front()
  {
    ?>
    <div id="uip-admin-menu-front-container">
      <div id="uip-admin-menu" class="uip-flex uip-flex-column">
      </div>
    </div>
  
  <?php
  }

  /**
   * Saves user prefs from menu
   * @since 2.2
   */
  public function uip_save_prefs()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $prefs = $utils->clean_ajax_input($_POST["userPref"]);

      if ($prefs == "" || !is_array($prefs)) {
        $returndata["error"] = true;
        $returndata["message"] = __("No preferences supplied to save", "uipress");
        echo json_encode($returndata);
        die();
      }

      $userid = get_current_user_id();
      $state = update_user_meta($userid, "uip-prefs", $prefs);

      if ($state) {
        $returndata = [];
        $returndata["success"] = true;
        $returndata["message"] = __("Preferences saved", "uipress");
        echo json_encode($returndata);
      } else {
        $returndata["error"] = true;
        $returndata["message"] = __("Unable to save user preferences", "uipress");
        echo json_encode($returndata);
        die();
      }
    }
    die();
  }

  /**
   * Modifies query to search in meta AND title
   * @since 2.9
   */
  public function uip_modify_query($q)
  {
    if ($title = $q->get("_uip_meta_or_title")) {
      add_filter("get_meta_sql", function ($sql) use ($title) {
        global $wpdb;

        // Only run once:
        static $nr = 0;
        if (0 != $nr++) {
          return $sql;
        }

        // Modify WHERE part:
        $sql["where"] = sprintf(" AND ( %s OR %s ) ", $wpdb->prepare("{$wpdb->posts}.post_title like '%%%s%%'", $title), mb_substr($sql["where"], 5, mb_strlen($sql["where"])));
        return $sql;
      });
    }
  }

  /**
   * Gets contextual quick actions for command search
   * @since 2.3.9
   */

  public function uip_get_quick_actions()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      ///SWITCHTODARKMODE

      $returndata = [];
      $returndata["actions"] = $this->return_quick_actions();
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Gets contextual quick actions for command search
   * @since 2.3.9
   */

  public function return_quick_actions($search = null)
  {
    $actions = [];
    ///CREATE POST
    $temp = [];
    $temp["title"] = __("Create new post", "uipress");
    $temp["actionType"] = "link";
    $temp["icon"] = "add_circle";
    $temp["link"] = get_admin_url(null, "post-new.php");
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    ///CREATE PAGE
    $temp = [];
    $temp["title"] = __("Create new page", "uipress");
    $temp["actionType"] = "link";
    $temp["icon"] = "add_circle";
    $temp["link"] = get_admin_url(null, "post-new.php?post_type=page");
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    ///CREATE PAGE
    $temp = [];
    $temp["title"] = __("View site", "uipress");
    $temp["actionType"] = "link";
    $temp["icon"] = "cottage";
    $temp["link"] = get_home_url();
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    ///CREATE PAGE
    $temp = [];
    $temp["title"] = __("View dashboard", "uipress");
    $temp["actionType"] = "link";
    $temp["icon"] = "dashboard";
    $temp["link"] = get_admin_url();
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    $temp = [];
    $temp["title"] = __("Switch to dark mode", "uipress");
    $temp["actionType"] = "function";
    $temp["icon"] = "dark_mode";
    $temp["function"] = "darkmode";
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    $temp = [];
    $temp["title"] = __("Switch to light mode", "uipress");
    $temp["actionType"] = "function";
    $temp["icon"] = "light_mode";
    $temp["function"] = "lightmode";
    $temp["type"] = "quickAction";
    $actions[] = $temp;

    if ($search != null) {
      $filtered = [];
      foreach ($actions as $item) {
        if (strpos(strtolower($item["title"]), $search) !== false) {
          $filtered[] = $item;
        }
      }
      return $filtered;
    }

    return $actions;
  }

  /**
   * Searches all WP content
   * @since 2.3.9
   */

  public function return_filtered_menu($term)
  {
    $userid = get_current_user_id();
    $mastermenu = get_transient("uip_admin_menu-" . $userid);

    if (!$mastermenu || !is_array($mastermenu) || empty($mastermenu) || !isset($mastermenu["menu"])) {
      return [];
    }

    $menuitems = $mastermenu["menu"];
    $matcheditems = [];

    foreach ($menuitems as $item) {
      if (is_object($item)) {
        if ($item->type == "sep") {
          continue;
        }
        $item = json_decode(json_encode($item), true);
      } elseif (is_array($item)) {
        if ($item["type"] == "sep") {
          continue;
        }
      }

      ///TOP LEVEL
      if (strpos(strtolower($item[0]), $term) !== false) {
        $matcheditems[] = $item;
      }
      //SUB LEVEL
      if (isset($item["submenu"]) && is_array($item["submenu"])) {
        foreach ($item["submenu"] as $sub) {
          if (strpos(strtolower($sub[0]), $term) !== false) {
            $sub["parentItem"] = $item[0];
            $matcheditems[] = $sub;
          }
        }
      }
    }

    return $matcheditems;
  }

  /**
   * Searches all WP content
   * @since 1.4
   */

  public function uip_master_search()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $term = $_POST["search"];
      $page = $_POST["currentpage"];
      $perpage = $_POST["perpage"];
      $utils = new uipress_util();

      $post_types_enabled = $utils->get_option("toolbar", "post-types-search");

      if ($post_types_enabled == "" || !$post_types_enabled || !is_array($post_types_enabled)) {
        $post_types = "any";
      } else {
        $post_types = $post_types_enabled;
      }

      //BUILD SEARCH ARGS//
      $args = [
        "_uip_meta_or_title" => $term,
        "posts_per_page" => $perpage,
        "post_type" => $post_types,
        "paged" => $page,
        "post_status" => "all",
        "meta_query" => [
          "relation" => "OR",
          [
            "value" => $term,
            "compare" => "LIKE",
          ],
        ],
      ];

      if (isset($_POST["posttypes"])) {
        $postTypes = $_POST["posttypes"];
        $args["post_type"] = $postTypes;
        $args_meta["post_type"] = $postTypes;
      }
      if (isset($_POST["categories"])) {
        $categories = $_POST["categories"];
        $args["category"] = $categories;
        $args_meta["category"] = $categories;
      }
      if (isset($_POST["users"])) {
        $users = $_POST["users"];
        $args["author__in"] = $users;
        $args_meta["author__in"] = $users;
      }

      $result = new WP_Query($args);
      $result->post_count = count($result->posts);

      $foundposts = $result->posts;
      $searchresults = [];
      $categorized = [];
      $categ = [];

      foreach ($foundposts as $item) {
        $temp = [];
        $author_id = $item->post_author;
        $title = $item->post_title;
        $status = get_post_status_object(get_post_status($item->ID));
        $label = $status->label;

        $postype_single = get_post_type($item);
        $postype = get_post_type_object($postype_single);
        $postype_label = $postype->label;

        if (!$postype_label) {
          $postype_label = __("Unkown Post Type", "uipress");
        }
        if (!$label || $label == "") {
          $label = __("Unkown", "uipress");
        }

        $editurl = get_edit_post_link($item, "&");
        $public = get_permalink($item);

        if ($postype_single == "attachment" && wp_attachment_is_image($item)) {
          $temp["image"] = wp_get_attachment_thumb_url($item->ID);
        }

        if ($postype_single == "attachment") {
          $temp["attachment"] = true;

          $mime = get_post_mime_type($item->ID);
          $actualMime = explode("/", $mime);
          $actualMime = $actualMime[1];

          $temp["mime"] = $actualMime;
        }

        $temp["name"] = $title;

        if ($term != "") {
          $foundtitle = str_ireplace($term, "<highlight>" . $term . "</highlight>", $title);
          $temp["name"] = $foundtitle;
        }

        $temp["editUrl"] = $editurl;
        $temp["type"] = $postype_label;
        $temp["status"] = $label;
        $temp["author"] = get_the_author_meta("user_login", $author_id);
        $temp["date"] = get_the_date("j M y", $item);
        $temp["url"] = $public;

        $categorized[$postype_single]["label"] = $postype_label;
        $categorized[$postype_single]["found"][] = $temp;

        $searchresults[] = $temp;
      }

      $totalFound = $result->found_posts;
      $totalPages = $result->max_num_pages;

      $returndata = [];
      $returndata["founditems"] = $searchresults;
      $returndata["totalfound"] = $totalFound;
      $returndata["totalpages"] = $totalPages;
      $returndata["categorized"] = $categorized;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Searches site wide content
   * @since 2.3.9
   */

  public function uip_site_global_search()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $term = $_POST["search"];
      $page = $_POST["currentpage"];
      $perpage = $_POST["perpage"];
      $utils = new uipress_util();

      $post_types_enabled = $utils->get_option("toolbar", "post-types-search");

      $post_types = "any";

      //BUILD SEARCH ARGS//
      $args = [
        "_uip_meta_or_title" => $term,
        "posts_per_page" => $perpage,
        "post_type" => $post_types,
        "paged" => $page,
        "post_status" => "all",
        "meta_query" => [
          "relation" => "OR",
          [
            "value" => $term,
            "compare" => "LIKE",
          ],
        ],
      ];

      $result = new WP_Query($args);
      $result->post_count = count($result->posts);

      $foundposts = $result->posts;
      $searchresults = [];
      $categorized = [];
      $categ = [];

      foreach ($foundposts as $item) {
        $temp = [];
        $author_id = $item->post_author;
        $title = $item->post_title;
        $status = get_post_status_object(get_post_status($item->ID));
        $label = $status->label;

        $postype_single = get_post_type($item);
        $postype = get_post_type_object($postype_single);
        $postype_label = "";
        if (isset($postype->label)) {
          $postype_label = $postype->label;
        }

        if (!$postype_label) {
          $postype_label = __("Unkown post type", "uipress");
        }
        if (!$label || $label == "") {
          $label = __("Unkown", "uipress");
        }

        $editurl = get_edit_post_link($item, "&");
        $public = get_permalink($item);

        if ($postype_single == "attachment" && wp_attachment_is_image($item)) {
          $temp["image"] = wp_get_attachment_thumb_url($item->ID);
        }

        if ($postype_single == "attachment") {
          $temp["attachment"] = true;

          $mime = get_post_mime_type($item->ID);
          $actualMime = explode("/", $mime);

          if (isset($actualMime[1])) {
            $actualMime = $actualMime[1];
          }

          $temp["mime"] = $actualMime;
        } else {
          $img = get_the_post_thumbnail_url($item->ID);

          if ($img) {
            $temp["image"] = $img;
          }
        }

        $temp["name"] = $title;

        if ($term != "") {
          $foundtitle = str_ireplace($term, "<uip-highlight>" . $term . "</uip-highlight>", $title);
          $temp["name"] = $foundtitle;
        }

        $temp["editUrl"] = $editurl;
        $temp["type"] = $postype_label;
        $temp["status"] = $label;
        $temp["author"] = get_the_author_meta("user_login", $author_id);
        $temp["date"] = get_the_date("j M y", $item);
        $temp["url"] = $public;
        $temp["id"] = $item->ID;

        $categorized[] = $temp;

        $searchresults[] = $temp;
      }

      $totalFound = $result->found_posts;
      $totalPages = $result->max_num_pages;

      $lc = null;
      if ($term != "") {
        $lc = strtolower($term);
      }

      $actions = $this->return_quick_actions($term);
      $menu = $this->return_filtered_menu($term);
      $plugins = $this->return_filtered_plugins($term);

      $filtered = array_merge($actions, $categorized, $menu, $plugins);

      $returndata = [];
      $returndata["founditems"] = $searchresults;
      $returndata["totalfound"] = $totalFound;
      $returndata["totalpages"] = $totalPages;
      $returndata["categorized"] = $filtered;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Returns plugins in search results
   * @since 2.3.9
   */
  public function return_filtered_plugins($term)
  {
    $plugins = get_plugins();
    $filtered = [];

    foreach ($plugins as $key => $value) {
      $plugin = $value;
      if (strpos(strtolower($plugin["Name"]), $term) !== false) {
        $plugin["type"] = "plugin";
        $plugin["slug"] = $key;
        $plugin["name"] = $plugin["Name"];
        $plugin["author"] = $plugin["Author"];
        $filtered[] = $plugin;
      }
    }

    return $filtered;
  }

  /**
   * Gets the specified post types for the toolbar create button
   * @since 2.1.6
   */

  public function uipress_get_create_types()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $post_types_create = $utils->get_option("toolbar", "post-types-create");

      if ($post_types_create == "" || !$post_types_create) {
        $args = ["public" => true];
        $output = "objects";
        $post_types = get_post_types($args, $output);
      } else {
        $args = [];
        $output = "objects";
        $post_types = get_post_types($args, $output);
      }

      ///FORMAT POST TYPES
      $formattedPostTypes = [];

      foreach ($post_types as $type) {
        $temp = [];

        if ($post_types_create == "" || !$post_types_create) {
          $name = $type->name;
          $temp["href"] = admin_url("post-new.php?post_type=" . $name);
          $temp["name"] = $type->labels->singular_name;
          $temp["icon"] = $type->menu_icon;
          $temp["all"] = $type;
          $formattedPostTypes[] = $temp;
        } else {
          if (in_array($type->name, $post_types_create)) {
            $name = $type->name;
            $temp["href"] = admin_url("post-new.php?post_type=" . $name);
            $temp["icon"] = $type->menu_icon;
            $temp["name"] = $type->labels->singular_name;
            $formattedPostTypes[] = $temp;
          }
        }
      }

      $returndata = [];
      $returndata["types"] = $formattedPostTypes;
      echo json_encode($returndata);
    }

    die();
  }

  /**
   * Gets contextual actions for item
   * @since 2.3.9
   */

  public function uip_get_contextual()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $item = $utils->clean_ajax_input($_POST["item"]);
      $mac = $utils->clean_ajax_input($_POST["mac"]);
      $actions = [];

      if ($item["type"] == "plugin") {
        if (isset($item["pluginType"]) && $item["pluginType"] == "remote") {
          if (!function_exists("get_plugins")) {
            require_once ABSPATH . "wp-admin/includes/plugin.php";
          }
          $all_plugins = get_plugins();
          foreach ($all_plugins as $key => $value) {
            if (strpos($key, $item["slug"]) !== false) {
              $item["slug"] = $key;
              $item["pluginType"] = false;
              break;
            } else {
              continue;
            }
          }
        }
      }

      //PLUGINS
      if ($item["type"] == "plugin") {
        if (isset($item["pluginType"]) && $item["pluginType"] == "remote") {
          $temp["title"] = __("Install plugin", "uipress");
          $temp["icon"] = "file_download";
          $temp["type"] = "action";
          $temp["action"] = "install_plugin";
          $actions[] = $temp;

          $temp["title"] = __("View details", "uipress");
          $temp["icon"] = "info";
          $temp["type"] = "link";
          $temp["url"] = "https://wordpress.org/plugins/" . $item["slug"];
          $temp["newtab"] = true;
          $actions[] = $temp;
        } else {
          $update_plugins = get_site_transient("update_plugins");
          if (!empty($update_plugins->response)) {
            $updates = $update_plugins->response;
          } else {
            $updates = [];
          }

          if (isset($updates[$item["slug"]])) {
            $temp["title"] = __("Update", "uipress");
            $temp["icon"] = "upgrade";
            $temp["type"] = "action";
            $temp["action"] = "upgrade_plugin";
            $actions[] = $temp;
          }

          if (is_plugin_active($item["slug"])) {
            $temp["title"] = __("Deactivate", "uipress");
            $temp["icon"] = "toggle_off";
            $temp["type"] = "action";
            $temp["action"] = "deactivate_plugin";
            $actions[] = $temp;
          } else {
            $temp["title"] = __("Activate", "uipress");
            $temp["icon"] = "toggle_on";
            $temp["type"] = "action";
            $temp["action"] = "activate_plugin";
            $actions[] = $temp;

            $temp["title"] = __("Delete", "uipress");
            $temp["icon"] = "delete";
            $temp["type"] = "action";
            $temp["action"] = "delete_plugin";
            $actions[] = $temp;
          }
        }
      }
      ///POSTS
      else {
        $id = $item["id"];
        $postType = get_post_type($id);

        $command = "ctrl";
        if ($mac == true) {
          $command = "cmd";
        }

        $type = get_post_type($id);

        $actions = [];
        $temp["title"] = __("Edit", "uipress");
        $temp["icon"] = "edit";
        $temp["type"] = "link";
        $temp["shortcut"] = [$command, "e"];
        $temp["url"] = get_edit_post_link($id, "&");
        $actions[] = $temp;

        $temp["title"] = __("View", "uipress");
        $temp["icon"] = "launch";
        $temp["type"] = "link";
        $temp["shortcut"] = [$command, "o"];
        $temp["url"] = get_the_permalink($id);
        $actions[] = $temp;

        if ($postType != "attachment") {
          $temp["title"] = __("Duplicate", "uipress");
          $temp["icon"] = "content_copy";
          $temp["type"] = "action";
          $temp["action"] = "duplicate";
          $temp["shortcut"] = [$command, "o"];
          $actions[] = $temp;
        }

        $temp["title"] = __("Delete", "uipress");
        $temp["icon"] = "delete";
        $temp["type"] = "action";
        $temp["action"] = "delete";
        $temp["shortcut"] = [$command, "o"];
        $actions[] = $temp;
      }
      $returndata = [];
      $returndata["actions"] = $actions;
      $returndata["log"] = $item;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Deletes item from command search
   * @since 2.3.9
   */

  public function uip_delete_item_from_command()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $id = $utils->clean_ajax_input($_POST["id"]);

      if (!current_user_can("delete_post", $id)) {
        $message = __("You don't have the capability to delete this item", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      $status = wp_delete_post($id, true);

      if (!$status) {
        $message = __("Unable to delete this item", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      $message = __("Item deleted", "uipress");
      $returndata["message"] = $message;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Modifies plugin status from command
   * @since 2.3.9
   */

  public function uip_modify_plugin_status()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $item = $utils->clean_ajax_input($_POST["item"]);
      $action = $utils->clean_ajax_input($_POST["plugin_action"]);

      if ($item["type"] == "plugin") {
        if (isset($item["pluginType"]) && $item["pluginType"] == "remote") {
          if (!function_exists("get_plugins")) {
            require_once ABSPATH . "wp-admin/includes/plugin.php";
          }
          $all_plugins = get_plugins();
          foreach ($all_plugins as $key => $value) {
            if (strpos($key, $item["slug"]) !== false) {
              $item["slug"] = $key;
              $item["pluginType"] = false;
              break;
            } else {
              continue;
            }
          }
        }
      }

      if ($action == "deactivate_plugin" || $action == "activate_plugin") {
        if (!current_user_can("activate_plugins")) {
          $message = __("You don't have the capability to change this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }
      }

      if ($action == "delete_plugin") {
        if (!current_user_can("delete_plugins")) {
          $message = __("You don't have the capability to delete this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }
      }

      if ($action == "deactivate_plugin") {
        deactivate_plugins($item["slug"]);
        $message = __("Plugin deactivated", "uipress");
      }

      if ($action == "activate_plugin") {
        ob_start();
        $status = activate_plugins($item["slug"]);
        ob_get_clean();
        if (!$status) {
          $message = __("Unable to activate this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }
        $message = __("Plugin activated", "uipress");
      }

      if ($action == "delete_plugin") {
        $status = delete_plugins([$item["slug"]]);
        if (!$status) {
          $message = __("Unable to delete this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }
        $message = __("Plugin deleted", "uipress");
      }

      if ($action == "upgrade_plugin") {
        include_once ABSPATH . "wp-admin/includes/class-wp-upgrader.php";
        ob_start();
        $upgrader = new Plugin_Upgrader();
        $upgraded = $upgrader->upgrade($item["slug"]);
        $list = ob_get_contents();
        ob_end_clean();
        if (!$upgraded) {
          $message = __("Unable to upgrade this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }
        $message = __("Plugin updated", "uipress");
      }

      if ($action == "install_plugin") {
        include_once ABSPATH . "wp-admin/includes/class-wp-upgrader.php";
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $status = $upgrader->install($item["downloadLink"]);
        if (!$status) {
          $message = __("Unable to install this plugin", "uipress");
          $returndata["error"] = true;
          $returndata["message"] = $message;
          echo json_encode($returndata);
          die();
        }

        error_log($status);
        $message = __("Plugin installed", "uipress");
      }

      $returndata["message"] = $message;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Searches wp directory
   * @since 2.3.9
   */

  public function uip_search_wp_directory()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $term = $utils->clean_ajax_input($_POST["term"]);

      if (!$term || $term == "") {
        $message = __("Please enter something to search for", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }
      include_once ABSPATH . "wp-admin/includes/plugin-install.php";

      $plugins = plugins_api("query_plugins", [
        "per_page" => 20,
        "search" => $term,
        "fields" => [
          "short_description" => true,
          "description" => false,
          "sections" => false,
          "tested" => false,
          "requires" => false,
          "rating" => true,
          "ratings" => false,
          "downloaded" => false,
          "downloadlink" => true,
          "last_updated" => false,
          "added" => false,
          "tags" => false,
          "slug" => true,
          "compatibility" => false,
          "homepage" => true,
          "versions" => false,
          "donate_link" => false,
          "reviews" => false,
          "banners" => false,
          "icons" => true,
          "active_installs" => false,
          "group" => false,
          "contributors" => false,
        ],
      ]);

      $formatted = [];
      foreach ($plugins->plugins as $plugin) {
        $temp = [];
        $temp["name"] = $plugin["name"];
        $temp["Name"] = $plugin["name"];
        $temp["Title"] = $plugin["name"];
        $temp["downloadLink"] = $plugin["download_link"];
        $temp["shortDes"] = $plugin["short_description"];
        $temp["type"] = "plugin";
        $temp["pluginType"] = "remote";

        if ($plugin["rating"] > 0) {
          $rating = round($plugin["rating"] / 20, 1);
        } else {
          $rating = 0;
        }
        $temp["rating"] = $rating;

        $starred = "<span class='uip-flex uip-text-orange'>";
        for ($x = 0; $x < 5; $x++) {
          if (floor($rating) - $x >= 1) {
            $starred .= '<span class="material-icons-outlined">star</span>';
          } elseif ($rating - $x > 0) {
            $starred .= '<span class="material-icons-outlined">star_half</span>';
          } else {
            $starred .= '<span class="material-icons-outlined">grade</span>';
          }
        }
        $starred .= "</span>";

        $temp["author"] = '<div class="uip-flex uip-gap-s">' . $plugin["author"] . $starred . "</div>";
        if (isset($plugin["icons"]) && isset($plugin["icons"]["1x"])) {
          $temp["image"] = $plugin["icons"]["1x"];
        }
        $temp["slug"] = $plugin["slug"];

        $formatted[] = $temp;
      }

      if (count($formatted) < 1) {
        $returndata["message"] = __("No plugins found", "uipress");
      } else {
        $returndata["message"] = sprintf(__("Found %s plugins", "uipress"), $plugins->info["results"]);
      }
      $returndata["plugins"] = $formatted;
      $returndata["og"] = $plugins;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Deletes item from command search
   * @since 2.3.9
   */

  public function uip_duplicate_item_from_command()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $id = $utils->clean_ajax_input($_POST["id"]);

      if (!$id || $id == "") {
        $message = __("Invalid item id", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      $this->uip_duplicate_item($id);

      $message = __("Item duplicated", "uipress");
      $returndata["message"] = $message;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Duplicates a single post
   * @since 2.9
   */
  public function uip_duplicate_item($post_id)
  {
    global $wpdb;
    $post = get_post($post_id);

    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    $args = [
      "comment_status" => $post->comment_status,
      "ping_status" => $post->ping_status,
      "post_author" => $new_post_author,
      "post_content" => $post->post_content,
      "post_excerpt" => $post->post_excerpt,
      "post_name" => $post->post_name,
      "post_parent" => $post->post_parent,
      "post_password" => $post->post_password,
      "post_status" => "draft",
      "post_title" => $post->post_title . "-" . __("copy", "uipress"),
      "post_type" => $post->post_type,
      "to_ping" => $post->to_ping,
      "menu_order" => $post->menu_order,
    ];

    $new_post_id = wp_insert_post($args);

    if (!$new_post_id) {
      return false;
    }

    $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
    foreach ($taxonomies as $taxonomy) {
      $post_terms = wp_get_object_terms($post_id, $taxonomy, ["fields" => "slugs"]);
      wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
    }

    $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
    if (count($post_meta_infos) != 0) {
      $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
      foreach ($post_meta_infos as $meta_info) {
        $meta_key = $meta_info->meta_key;
        if ($meta_key == "_wp_old_slug") {
          continue;
        }
        $meta_value = addslashes($meta_info->meta_value);
        $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
      }

      $sql_query .= implode(" UNION ALL ", $sql_query_sel);
      $wpdb->query($sql_query);
    }

    $postobject = get_post($new_post_id);

    return true;
  }

  /**
   * Gets uipress updates
   * @since 2.1.6
   */

  public function uipress_get_updates()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $showUpdateOption = $utils->get_option("toolbar", "updates-disabled-for", true);
      $showUpdates = false;

      if (is_array($showUpdateOption) && !empty($showUpdateOption)) {
        $showUpdates = $utils->valid_for_user($showUpdateOption);
      } elseif (current_user_can("install_plugins")) {
        $showUpdates = true;
      }

      if (!$showUpdates) {
        $returndata = [];
        $returndata["updates"] = [];
        $returndata["total"] = 0;
        echo json_encode($returndata);
        die();
      }

      $updates = $this->get_total_updates();
      $adminurl = get_admin_url();

      $formatted = [
        "wordpress" => [
          "total" => $updates["wordpress"],
          "title" => __("Core", "uipress"),
          "icon" => "system_update_alt",
          "href" => $adminurl . "update-core.php",
        ],
        "theme" => [
          "total" => $updates["themeCount"],
          "title" => __("Themes", "uipress"),
          "icon" => "extension",
          "href" => $adminurl . "themes.php",
        ],
        "plugins" => [
          "total" => $updates["pluginCount"],
          "title" => __("Plugins", "uipress"),
          "icon" => "color_lens",
          "href" => $adminurl . "plugins.php",
        ],
      ];

      $returndata = [];
      $returndata["updates"] = $formatted;
      $returndata["total"] = $updates["total"];
      echo json_encode($returndata);
    }

    die();
  }

  /**
   * Gets uipress notices
   * @since 2.1.6
   */

  public function uipress_get_notices()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $showNotificationOption = $utils->get_option("toolbar", "notifcations-disabled-for", true);
      $showNotices = false;

      if (is_array($showNotificationOption) && !empty($showNotificationOption)) {
        $showNotices = $utils->valid_for_user($showNotificationOption);
      } elseif (current_user_can("manage_options")) {
        $showNotices = true;
      }

      if (!$showNotices) {
        $returndata["notices"] = [];
        $returndata["supressed"] = [];
        $returndata["test"] = $showNotices;
        echo json_encode($returndata);
        die();
      }
      $userid = get_current_user_id();
      $notices = get_transient("uip-admin-notices-" . $userid);

      $supressedNotifications = $utils->get_user_preference("uip-supressed-notifications");

      if (!is_array($supressedNotifications)) {
        $supressedNotifications = [];
      }

      $returndata = [];
      $returndata["notices"] = $notices;
      $returndata["supressed"] = $supressedNotifications;
      echo json_encode($returndata);
    }

    die();
  }

  /**
   * Sets user preferences from ajax
   * @since 1.4
   */
  public function uip_save_user_prefs()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $pref = $utils->clean_ajax_input($_POST["pref"]);
      $value = $utils->clean_ajax_input($_POST["value"]);

      if ($pref == "") {
        $message = __("No preferences supplied to save", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      $userid = get_current_user_id();
      $current = get_user_meta($userid, "uip-prefs", true);

      if (is_array($current)) {
        $current[$pref] = $value;
      } else {
        $current = [];
        $current[$pref] = $value;
      }

      $state = update_user_meta($userid, "uip-prefs", $current);

      if ($state) {
        $returndata = [];
        $returndata["success"] = true;
        $returndata["message"] = __("Preferences saved", "uipress");
        echo json_encode($returndata);
      } else {
        $message = __("Unable to save user preferences", "uipress");
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }
    }
    die();
  }

  /**
   * Gets and formats all wp updates
   * @since 2.1.6
   */

  public function get_total_updates()
  {
    $returndata = [];
    $returndata["total"] = 0;
    $returndata["wordpress"] = 0;
    $returndata["theme"] = 0;
    $returndata["themeCount"] = 0;
    $returndata["plugin"] = 0;
    $returndata["pluginCount"] = 0;

    if (!is_admin()) {
      return $returndata;
    }

    if (!current_user_can("install_plugins")) {
      return $returndata;
    }

    $totalupdates = 0;

    if (is_super_admin() && is_admin()) {
      ////GET UPDATES
      $pluginupdates = get_plugin_updates();
      $themeupdates = get_theme_updates();
      $wordpressupdates = get_core_updates();

      if (isset($wordpressupdates[0])) {
        $wpversion = $wordpressupdates[0]->version;
        global $wp_version;

        if ($wpversion > $wp_version) {
          $wordpressupdates = 1;
        } else {
          $wordpressupdates = 0;
        }
      } else {
        $wordpressupdates = 0;
      }

      $totalupdates = count($pluginupdates) + count($themeupdates) + $wordpressupdates;

      $returndata["total"] = $totalupdates;
      $returndata["wordpress"] = $wordpressupdates;
      $returndata["theme"] = $themeupdates;
      $returndata["themeCount"] = count($themeupdates);
      $returndata["plugin"] = $pluginupdates;
      $returndata["pluginCount"] = count($pluginupdates);
    }

    return $returndata;
  }

  /**
   * Processes custom mneu and find active object
   * @since 2.2
   */
  public function uip_get_active_menu_item($usergenerated, $mastermenu)
  {
    $submenu_as_parent = true;
    $self = $mastermenu["self"];
    $parent_file = $mastermenu["parent_file"];
    $submenu_file = $mastermenu["submenu_file"];
    $plugin_page = $mastermenu["plugin_page"];
    $typenow = $mastermenu["typenow"];
    $processed = [];

    foreach ($usergenerated as $key => $item) {
      $item->active = false;

      if ($item->type == "sep") {
        $processed[] = $item;
        continue;
      }

      if (isset($item->submenu)) {
        $submenu_items = $item->submenu;

        if (!empty($submenu_items)) {
          foreach ($submenu_items as $key => $subitem) {
            $subitem->active = false;
          }
        }
      }
    }

    return $usergenerated;
  }

  /**
   * Processes menu for output
   * @since 2.2
   */
  public function uip_format_admin_menu($mastermenu, $submenu_as_parent = true)
  {
    $self = $mastermenu["self"];
    $parent_file = $mastermenu["parent_file"];
    $submenu_file = $mastermenu["submenu_file"];
    $plugin_page = $mastermenu["plugin_page"];
    $typenow = $mastermenu["typenow"];
    $menu = $mastermenu["menu"];
    $submenu = $mastermenu["submenu"];

    $first = true;
    $returnmenu = [];
    $returnsubmenu = [];
    $availableTopItems = [];
    $availableSubItems = [];

    foreach ($menu as $key => $item) {
      $admin_is_parent = false;
      $class = [];
      $aria_attributes = "";
      $aria_hidden = "";
      $is_separator = false;

      if ($first) {
        $class[] = "wp-first-item";
        $first = false;
      }

      $submenu_items = [];
      if (!empty($submenu[$item[2]])) {
        $class[] = "wp-has-submenu";
        $submenu_items = $submenu[$item[2]];
      }

      if (($parent_file && $item[2] === $parent_file) || (empty($typenow) && $self === $item[2])) {
        if (!empty($submenu_items)) {
          $class[] = "wp-has-current-submenu wp-menu-open";
          $item["active"] = true;
        } else {
          $class[] = "current";
          $aria_attributes .= 'aria-current="page"';
          $item["active"] = true;
        }
      } else {
        $class[] = "wp-not-current-submenu";
        $item["active"] = false;
        if (!empty($submenu_items)) {
          $aria_attributes .= 'aria-haspopup="true"';
        }
      }

      if (!empty($item[4])) {
        $class[] = esc_attr($item[4]);
      }

      $class = implode(" ", $class);
      $id = !empty($item[5]) ? ' id="' . preg_replace("|[^a-zA-Z0-9_:.]|", "-", $item[5]) . '"' : "";
      $img = "";
      $img_style = "";
      $img_class = " dashicons-before";

      if (false !== strpos($class, "wp-menu-separator")) {
        $is_separator = true;
      }

      $title = wptexturize($item[0]);

      // Hide separators from screen readers.
      if ($is_separator) {
        $aria_hidden = ' aria-hidden="true"';

        $item["type"] = "sep";

        if (isset($menu_item["name"])) {
          $item["name"] = $item["name"];
        }
      } else {
        $item["id"] = $item[5];
        $item["name"] = $item[0];
        $item["icon"] = $this->get_icon($item);
        $item["classes"] = $class;
        $item["type"] = "menu";
      }

      //$classes = $this->get_menu_clases($menu_item,$thesubmenu);

      if ($is_separator) {
      } elseif ($submenu_as_parent && !empty($submenu_items)) {
        $submenu_items = array_values($submenu_items); // Re-index.
        $menu_hook = get_plugin_page_hook($submenu_items[0][2], $item[2]);
        $menu_file = $submenu_items[0][2];
        $pos = strpos($menu_file, "?");

        if (false !== $pos) {
          $menu_file = substr($menu_file, 0, $pos);
        }

        if (!empty($menu_hook) || ("index.php" !== $submenu_items[0][2] && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !file_exists(ABSPATH . "/wp-admin/$menu_file"))) {
          $admin_is_parent = true;
          $item["url"] = "admin.php?page=" . $submenu_items[0][2];
        } else {
          $item["url"] = $submenu_items[0][2];
        }
      } elseif (!empty($item[2]) && current_user_can($item[1])) {
        $menu_hook = get_plugin_page_hook($item[2], "admin.php");
        $menu_file = $item[2];
        $pos = strpos($menu_file, "?");

        if (false !== $pos) {
          $menu_file = substr($menu_file, 0, $pos);
        }

        if (!empty($menu_hook) || ("index.php" !== $item[2] && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !file_exists(ABSPATH . "/wp-admin/$menu_file"))) {
          $admin_is_parent = true;
          $item["url"] = "admin.php?page=" . $item[2];
        } else {
          $item["url"] = $item[2];
        }
      }

      if ($is_separator) {
      } else {
        ///CREATE UNIQUE ID FOR MENU ITEMS
        $item["uid"] = hash("ripemd160", $item["id"] . $item["url"]);
        array_push($availableTopItems, $item["uid"]);
      }

      if (!empty($submenu_items)) {
        $first = true;
        $tempsub = [];

        foreach ($submenu_items as $sub_key => $sub_item) {
          $sub_item["active"] = false;

          if (!current_user_can($sub_item[1])) {
            continue;
          }

          $class = [];
          $aria_attributes = "";

          if ($first) {
            $class[] = "wp-first-item";
            $first = false;
          }

          $menu_file = $item[2];
          $pos = strpos($menu_file, "?");

          if (false !== $pos) {
            $menu_file = substr($menu_file, 0, $pos);
          }

          // Handle current for post_type=post|page|foo pages, which won't match $self.
          $self_type = !empty($typenow) ? $self . "?post_type=" . $typenow : "nothing";

          if (isset($submenu_file)) {
            if ($submenu_file === $sub_item[2]) {
              $class[] = "current";
              $aria_attributes .= ' aria-current="page"';
            }
            // If plugin_page is set the parent must either match the current page or not physically exist.
            // This allows plugin pages with the same hook to exist under different parents.
          } elseif (
            (!isset($plugin_page) && $self === $sub_item[2]) ||
            (isset($plugin_page) && $plugin_page === $sub_item[2] && ($item[2] === $self_type || $item[2] === $self || file_exists($menu_file) === false))
          ) {
            $class[] = "current";
            $aria_attributes .= ' aria-current="page"';
          }

          if (!empty($sub_item[4])) {
            $class[] = esc_attr($sub_item[4]);
          }

          $class = $class ? ' class="' . implode(" ", $class) . '"' : "";

          $menu_hook = get_plugin_page_hook($sub_item[2], $item[2]);
          $sub_file = $sub_item[2];
          $pos = strpos($sub_file, "?");
          if (false !== $pos) {
            $sub_file = substr($sub_file, 0, $pos);
          }

          $title = wptexturize($sub_item[0]);

          if ($aria_attributes != "") {
            $sub_item["active"] = true;
          }

          if (!empty($menu_hook) || ("index.php" !== $sub_item[2] && file_exists(WP_PLUGIN_DIR . "/$sub_file") && !file_exists(ABSPATH . "/wp-admin/$sub_file"))) {
            // If admin.php is the current page or if the parent exists as a file in the plugins or admin directory.
            if ((!$admin_is_parent && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !is_dir(WP_PLUGIN_DIR . "/{$item[2]}")) || file_exists($menu_file)) {
              $sub_item_url = add_query_arg(["page" => $sub_item[2]], $item[2]);
            } else {
              $sub_item_url = add_query_arg(["page" => $sub_item[2]], "admin.php");
            }

            $sub_item_url = $sub_item_url;
            //echo "<li$class><a href='$sub_item_url'$class$aria_attributes>$title</a></li>";
            $sub_item["url"] = $sub_item_url;
          } else {
            //echo "<li$class><a href='{$sub_item[2]}'$class$aria_attributes>$title</a></li>";
            $sub_item["url"] = $sub_item[2];
          }

          $sub_item["name"] = $sub_item[0];
          $sub_item["id"] = $item["id"] . $sub_item["url"];
          $sub_item["type"] = "menu";
          $sub_item["uid"] = hash("ripemd160", $sub_item["id"] . $sub_item["url"]);
          array_push($availableSubItems, $sub_item["uid"]);
          array_push($tempsub, $sub_item);
        }

        $item["submenu"] = $tempsub;
        //echo '</ul>';
      }
      //echo '</li>';
      $submenu_items = [];
      if (!empty($submenu[$item[2]])) {
        $returnsubmenu[$item[2]] = $tempsub;
      }

      array_push($returnmenu, $item);
    }

    $returner["menu"] = $returnmenu;
    $returner["availableTop"] = $availableTopItems;
    $returner["availableSub"] = $availableSubItems;

    return $returner;
  }

  /**
   * Gets menu icon
   * @since 2.2
   */

  public function get_icon($menu_item)
  {
    /// LIST OF AVAILABLE MENU ICONS
    $icons = [
      "dashicons-dashboard" => "grid_view",
      "dashicons-admin-post" => "article",
      "dashicons-database" => "perm_media",
      "dashicons-admin-media" => "collections",
      "dashicons-admin-page" => "description",
      "dashicons-admin-comments" => "forum",
      "dashicons-admin-appearance" => "palette",
      "dashicons-admin-plugins" => "extension",
      "dashicons-admin-users" => "people",
      "dashicons-admin-tools" => "build_circle",
      "dashicons-chart-bar" => "bar_chart",
      "dashicons-admin-settings" => "tune",
    ];

    // SET MENU ICON
    $theicon = "";
    $wpicon = $menu_item[6];

    if (isset($menu_item["icon"])) {
      if ($menu_item["icon"] != "") {
        ob_start(); ?><span class="uk-icon-button" uk-icon="icon:<?php echo $menu_item["icon"]; ?>;ratio:0.8"></span><?php return ob_get_clean();
      }
    }

    if (isset($icons[$wpicon])) {
      //ICON IS SET BY ADMIN 2020
      ob_start(); ?><span class="material-icons-outlined"><?php echo $icons[$wpicon]; ?></span><?php return ob_get_clean();
    }

    if (!$theicon) {
      if (strpos($wpicon, "uip-admin-page-icon ") !== false) {

        //ICON IS CUSTOM ADMIN PAGE ICON
        $strippedIcon = str_replace("uip-admin-page-icon ", "", $wpicon);
        ob_start();
        ?><span class="material-icons-outlined"><?php echo $strippedIcon; ?></span><?php return ob_get_clean();
      } elseif (strpos($wpicon, "http") !== false || strpos($wpicon, "data:") !== false) {
        ///ICON IS IMAGE
        ob_start(); ?><span class="uip-icon-image uip-background-muted uip-border-round uip-h-18 uip-w-18" style="background-image: url(<?php echo $wpicon; ?>);"></span><?php return ob_get_clean();
      } else {
        ///ICON IS ::BEFORE ELEMENT
        ob_start(); ?><div class="wp-menu-image dashicons-before <?php echo $wpicon; ?> uip-background-muted uip-border-round uip-h-18 uip-w-18 uip-icon-image"></div><?php return ob_get_clean();
      }
    }
  }

  /**
   * Adds container for command centre
   * @since 2.3.9
   */
  public function add_command_center()
  {
    ?>
    <div id="uip-command-center"></div>
    <?php
  }

  /**
   * Returns option value from given settings object
   * @since 2.2.9.2
   */

  public function get_option_value_from_object($uipOptions, $module_name, $option_name, $returnArray = false)
  {
    $option = "";

    if (isset($uipOptions[$module_name]["options"][$option_name]["value"])) {
      $value = $uipOptions[$module_name]["options"][$option_name]["value"];
      if ($value != "") {
        $option = $value;
      }
    }

    if ($returnArray == true) {
      if ($option == "") {
        $option = [];
      }
    }

    if ($option == "false") {
      $option = false;
    }

    if ($option == "true") {
      $option = true;
    }

    return $option;
  }

  /**
   * Returns settings options for settings page
   * @since 2.2
   */
  public function get_app_settings_options($settings, $network)
  {
    $utils = new uipress_util();
    $debug = new uipress_debug();
    $allOptions = $utils->get_options_object();

    $moduleName = "general";
    $category = [];
    $options = [];

    $category["module_name"] = $moduleName;
    $category["label"] = __("General", "uipress");
    $category["description"] = __("General options", "uipress");
    $category["icon"] = "settings";

    if ($network) {
      $temp = [];
      $temp["name"] = __("Network Override", "uipress");
      $temp["description"] = __("If enabled, all settings applied here will be pushed to subsites.", "uipress");
      $temp["type"] = "switch";
      $temp["optionName"] = "network_override";
      $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
      $temp["premium"] = true;
      $options[$temp["optionName"]] = $temp;
    }

    $temp = [];
    $temp["name"] = __("Set Dark Mode as Default", "uipress");
    $temp["description"] = __("If enabled, dark mode will default to true for users that haven't set a preference.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "dark-default";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Set Dark Mode according to prefers color scheme", "uipress");
    $temp["description"] = __("UiPress will detetct the OS color scheme and update accordingly", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "dark-prefers-color-scheme";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable dark mode", "uipress");
    $temp["description"] = __("If enabled, dark mode switch will not be available.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "dark-disabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Enable Dynamic Loading", "uipress");
    $temp["description"] = sprintf(
      __("If enabled, UiPress will dynamically load admin content without the need for page refreshes. This will only work with %s admin menu", "uipress"),
      $this->pluginName
    );
    $temp["type"] = "switch";
    $temp["optionName"] = "dynamic-loading";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $temp["experimental"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Set overview page as homepage", "uipress");
    $temp["description"] = __("If enabled, the overview page will be the homepage when logging in and when accessing the admin area.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "redirect-overview";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Set custom page as homepage", "uipress");
    $temp["description"] = __(
      "If enabled, the page you choose will be the homepage when logging in and when accessing the admin area. For admin pages use a relative URL (path after /wp-admin/), for other pages use an absolute URL",
      "uipress"
    );
    $temp["type"] = "text";
    $temp["optionName"] = "redirect-custom";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Rename %s", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("White label %s by changing the name displayed.", "uipress"), $this->pluginName);
    $temp["type"] = "text";
    $temp["optionName"] = "rename-plugin";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Rename %s plugin author", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("White label %s by changing the author in the plugin table.", "uipress"), $this->pluginName);
    $temp["type"] = "text";
    $temp["optionName"] = "rename-plugin-author";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Change %s plugin URL", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("White label %s by changing the plugin URL in the plugin table.", "uipress"), $this->pluginName);
    $temp["type"] = "text";
    $temp["optionName"] = "rename-plugin-link";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("App Icon", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("Replace %s plugin icon with your own", "uipress"), $this->pluginName);
    $temp["type"] = "image";
    $temp["optionName"] = "app-icon";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Admin Favicon", "uipress"), $this->pluginName);
    $temp["description"] = __("Add an image to be used as the site favicon for admin pages.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "admin-favicon";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Hide %s from plugin table", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("If enabled, %s will be hidden from the plugin table", "uipress"), $this->pluginName);
    $temp["type"] = "switch";
    $temp["optionName"] = "hide-plugin";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Hide Footer", "uipress");
    $temp["description"] = __("Hide the footer text that shows at the bottom of every admin page", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "hide-footer";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Footer Text", "uipress");
    $temp["description"] = __("Text entered here will be present at the bottom of admin pages", "uipress");
    $temp["type"] = "textarea";
    $temp["optionName"] = "footer-text";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $temp["premium"] = true;
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    $moduleName = "menu";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Menu", "uipress");
    $category["description"] = __("Creates new admin menu.", "uipress");
    $category["icon"] = "list";

    $temp = [];
    $temp["name"] = __("Disable Admin Menu Module", "uipress");
    $temp["description"] = __("Creates new admin menu.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Menu Disabled for", "uipress");
    $temp["description"] = sprintf(__("%s menu will be disabled for any users or roles you select", "uipress"), $this->pluginName);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Logo Light Mode", "uipress");
    $temp["description"] = __("Sets the logo for the admin bar in light mode.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "light-logo";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Logo Dark Mode", "uipress");
    $temp["description"] = __("Optional dark mode logo for admin bar.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "dark-logo";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Load %s Menu on front end", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("If enabled, %s menu will load on the front end. Please note, this will not work on all themes and styling will vary", "uipress"), $this->pluginName);
    $temp["type"] = "switch";
    $temp["premium"] = true;
    $temp["optionName"] = "load-front";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Only Load %s Menu on front end for", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("Only load front end menu for selected roles and usernames", "uipress"), $this->pluginName);
    $temp["type"] = "user-role-select";
    $temp["premium"] = true;
    $temp["optionName"] = "load-front-for";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Collapsed Menu Logo Light Mode", "uipress");
    $temp["description"] = __("Optional logo for when the menu is collapsed.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "light-logo-collapsed";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Collapsed Menu Logo Dark Mode", "uipress");
    $temp["description"] = __("Optional dark nmode logo for when the menu is collapsed.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "dark-logo-collapsed";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Show site title in menu", "uipress");
    $temp["description"] = __("If enabled, the site title will be displayed in the menu next to the logo", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "show-site-logo";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable Search", "uipress");
    $temp["description"] = __("Disables admin menu search.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "search-enabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Set collapsed menu as default", "uipress");
    $temp["description"] = __("If enabled, the menu will default to the shrunk menu for users that haven't set a preference..", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "shrunk-default";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    ///////TOOL BAR OPTIONS
    $moduleName = "toolbar";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Toolbar", "uipress");
    $category["description"] = __("Creates new admin toolbar.", "uipress");
    $category["icon"] = "build_circle";

    $temp = [];
    $temp["name"] = __("Disable ToolBar Module?", "uipress");
    $temp["description"] = __("Creates new admin bar, adds user off canvas menu, builds global search and notification center.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Admin Bar Disabled For", "uipress");
    $temp["description"] = sprintf(__("%s toolbar module will be disabled for any users or roles you select", "uipress"), $this->pluginName);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Hide admin bar links (left side)", "uipress");
    $temp["description"] = __("Disables legacy links on left side of admin bar for all users. Also hides the user preference.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "legacy-admin";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Enable floating flyout toolbar", "uipress");
    $temp["description"] = __("Changes the toolbar to a floating flyout toolbar", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "flyout-toolbar";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable Search", "uipress");
    $temp["description"] = __("Disables search icon and global search function from admin bar.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "search-disabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable Create Button", "uipress");
    $temp["description"] = __("Disables the 'create' button in the admin bar.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "new-enabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Open View Website in new tab", "uipress");
    $temp["description"] = __("When enabled, clicking on view website or the home button will open in a new browser tab", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "view-new-tab";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable View Website Button", "uipress");
    $temp["description"] = __("Disables the view website link button in the admin bar.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "view-enabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = sprintf(__("Load %s admin bar on front end", "uipress"), $this->pluginName);
    $temp["description"] = sprintf(__("If enabled, %s toolbar will load on the front end. Please note, this will not work on all themes and styling will vary", "uipress"), $this->pluginName);
    $temp["type"] = "switch";
    $temp["premium"] = true;
    $temp["optionName"] = "load-front";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Hide admin bar on front end", "uipress");
    $temp["description"] = __("If enabled, front end admin bar will not load.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "hide-admin";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable Notification Center", "uipress");
    $temp["description"] = __("If disabled, notifcations will show in the normal way", "uipress");
    $temp["type"] = "switch";
    $temp["premium"] = true;
    $temp["optionName"] = "notification-center-disabled";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Post Types available in Search", "uipress");
    $temp["description"] = __("The global search will only search the selected post types.", "uipress");
    $temp["type"] = "post-type-select";
    $temp["optionName"] = "post-types-search";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Post Types available in create button (new)", "uipress");
    $temp["description"] = __("Only the selected post types will show up in the create dropdown.", "uipress");
    $temp["type"] = "post-type-select";
    $temp["optionName"] = "post-types-create";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Only show notifications to", "uipress");
    $temp["description"] = sprintf(__("%s will hide all notifications from all users except those selected below", "uipress"), $this->pluginName);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "notifcations-disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Only show updates to", "uipress");
    $temp["description"] = sprintf(__("%s will hide all updates from all users except those selected below", "uipress"), $this->pluginName);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "updates-disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    ////////THEME OPTIONS
    $moduleName = "theme";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Theme", "uipress");
    $category["description"] = __("Styles page content.", "uipress");
    $category["icon"] = "brush";

    $temp = [];
    $temp["name"] = __("Disable Admin Theme Module", "uipress");
    $temp["description"] = __("When the theme is disabled, pages will be styles in the original way.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Theme Disabled for", "uipress");
    $temp["description"] = __("When the theme is disabled, pages will be styles in the original way for selected users or roles.", "uipress");
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    ////////LOGIN OPTIONS
    $moduleName = "login";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Login", "uipress");
    $category["description"] = __("Styles page content.", "uipress");
    $category["icon"] = "login";

    $temp = [];
    $temp["name"] = __("Disable Login Module", "uipress");
    $temp["description"] = __("When the login module is disabled, the login page will be displayed in the original way.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["premium"] = false;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Dark Mode", "uipress");
    $temp["description"] = __("Puts the login page in dark mode for all users.", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "login-dark-mode";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Disable Language Selector", "uipress");
    $temp["description"] = __("Removes the language selector added to the login page", "uipress");
    $temp["type"] = "switch";
    $temp["optionName"] = "remove-language-selector";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Login Logo", "uipress");
    $temp["description"] = __("Sets the logo for the login page", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "login-logo";
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Login Background", "uipress");
    $temp["description"] = __("Sets an optional background image on the login page.", "uipress");
    $temp["type"] = "image";
    $temp["optionName"] = "login-background";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    ////////ADVANCED OPTIONS
    $moduleName = "advanced";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Advanced", "uipress");
    $category["description"] = __("Styles page content.", "uipress");
    $category["icon"] = "code";

    $temp = [];
    $temp["name"] = __("Advanced Disabled For", "uipress");
    $temp["description"] = __("Code added here will not load for any users or roles you select", "uipress");
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Enqueue scripts", "uipress");
    $temp["description"] = __("Add scripts to the head of every admin page and login page", "uipress");
    $temp["type"] = "multiple-text";
    $temp["optionName"] = "enqueue-scripts";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Enqueue styles", "uipress");
    $temp["description"] = __("Add stylesheets to the head of every admin page and login page", "uipress");
    $temp["type"] = "multiple-text";
    $temp["optionName"] = "enqueue-styles";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Safe Mode Key", "uipress");
    $temp["description"] = __(
      "Add a private key to allow you to disable uipress on specific pages with query paramaters. For example, add '?no_uip=your_key' to a URL and it wil stop uipress from loading on that page.",
      "uipress"
    );
    $temp["type"] = "text";
    $temp["optionName"] = "safe-key";
    $temp["password"] = true;
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"]);
    //$options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Admin CSS", "uipress");
    $temp["description"] = __("CSS added here will be loaded on every admin page as well as the login page", "uipress");
    $temp["type"] = "code-block";
    $temp["language"] = "css";
    $temp["optionName"] = "admin-css";
    $temp["premium"] = true;
    $temp["value"] = html_entity_decode(stripslashes($this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"])));
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Admin JavaScript", "uipress");
    $temp["description"] = __("JavaScript added here will be loaded on every admin page as well as the login page", "uipress");
    $temp["type"] = "code-block";
    $temp["language"] = "javascript";
    $temp["optionName"] = "admin-js";
    $temp["premium"] = true;
    $temp["value"] = html_entity_decode(stripslashes($this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"])));
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("HTML for document head", "uipress");
    $temp["description"] = __("Add HTML here to be added to every admin page and login page head section", "uipress");
    $temp["type"] = "code-block";
    $temp["language"] = "HTML";
    $temp["optionName"] = "admin-html";
    $temp["premium"] = true;
    $temp["value"] = html_entity_decode(stripslashes($this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"])));
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    ////////COMMAND OPTIONS
    $moduleName = "command-center";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Command center - beta", "uipress");
    $category["description"] = __(
      "Options for the uipress command center, which is disabled by default. The uipress command center can be accessed with the keyboard shortcut (cmd + k | ctrl + k)",
      "uipress"
    );
    $category["icon"] = "manage_search";

    $temp = [];
    $temp["name"] = __("Command Center enabled for", "uipress");
    $temp["description"] = __("Choose who has access to the command center.", "uipress");
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "enabled-for";
    $temp["premium"] = true;
    $temp["value"] = $this->get_option_value_from_object($allOptions, $moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;
    $settings["dataConnect"] = $debug->check_network_connection();

    return $settings;
  }
}
