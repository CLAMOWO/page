<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles UIP global settings
 * @since 3.0.92
 */
class uip_pro_site_settings extends uip_site_settings
{
  public function __construct()
  {
  }

  public function run()
  {
    add_action('plugins_loaded', [$this, 'set_site_settings'], 2);
  }

  /**
   * Calls appropiate functions if the settings are set and defined
   * @since 3.0.92
   */
  public function set_site_settings()
  {
    if (!defined('uip_site_settings')) {
      return;
    }
    $this->uip_site_settings_object = json_decode(uip_site_settings);

    add_action('admin_head', [$this, 'add_head_code'], 99);
    add_action('all_plugins', [$this, 'remove_uip_plugin_table'], 10, 1);
    add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
    add_filter('admin_body_class', [$this, 'push_role_to_body_class']);
  }

  /**
   * Adds current roles as body classes on the admin
   * @since 3.0.92
   */
  function push_role_to_body_class($classes)
  {
    if (!isset($this->uip_site_settings_object->advanced) || !isset($this->uip_site_settings_object->advanced->addRoleToBody)) {
      return $classes;
    }

    $addHead = $this->uip_site_settings_object->advanced->addRoleToBody;

    if ($addHead == 'uiptrue') {
      $user = new WP_User(get_current_user_id());

      if (!empty($user->roles) && is_array($user->roles)) {
        foreach ($user->roles as $role) {
          $classes .= ' ' . strtolower($role);
        }
      }
    }

    return $classes;
  }

  /**
   * Adds user enqueued scripts and styles
   * @since 3.0.92
   */
  public function add_scripts_and_styles()
  {
    if (!isset($this->uip_site_settings_object->advanced)) {
      return;
    }

    if (isset($this->uip_site_settings_object->advanced->enqueueScripts)) {
      $scripts = $this->uip_site_settings_object->advanced->enqueueScripts;

      if (is_array($scripts)) {
        foreach ($scripts as $script) {
          wp_enqueue_script($script->id, $script->value, []);
        }
      }
    }

    if (!isset($this->uip_site_settings_object->advanced->enqueueStyles)) {
      return;
    }

    $styles = $this->uip_site_settings_object->advanced->enqueueStyles;

    if (is_array($styles)) {
      foreach ($styles as $style) {
        wp_register_style($style->id, $style->value, []);
        wp_enqueue_style($style->id);
      }
    }
  }

  /**
   * Adds user code to the head of admin pages
   * @since 3.0.92
   */
  public function add_head_code()
  {
    if (!isset($_GET['uip-framed-page']) || $_GET['uip-framed-page'] != '1') {
      return;
    }
    $utils = new uip_util();
    if (!isset($this->uip_site_settings_object->advanced) || !isset($this->uip_site_settings_object->advanced->htmlHead)) {
      return;
    }

    $code = $this->uip_site_settings_object->advanced->htmlHead;
    if ($code == '' || $code == 'uipblank') {
      return;
    }

    echo $utils->clean_ajax_input_width_code(html_entity_decode($code));
  }

  /**
   * Hides uipress from plugins table
   * @since 3.0.92
   */
  public function remove_uip_plugin_table($all_plugins)
  {
    if (!isset($this->uip_site_settings_object->whiteLabel) || !isset($this->uip_site_settings_object->whiteLabel->hidePlugins)) {
      return $all_plugins;
    }

    $hidden = $this->uip_site_settings_object->whiteLabel->hidePlugins;

    if ($hidden == 'uiptrue') {
      unset($all_plugins['uipress-lite/uipress-lite.php']);
      unset($all_plugins['uipress-pro/uipress-pro.php']);
      return $all_plugins;
    }
    return $all_plugins;
  }
}
