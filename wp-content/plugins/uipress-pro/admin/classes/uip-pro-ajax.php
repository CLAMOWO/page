<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Main uipress ajax class. Loads all ajax functions for the main uipress functionality
 * @since 3.0.0
 */
class uip_pro_ajax
{
  public function __construct()
  {
  }

  public function load_ajax()
  {
    //Pro actions
    add_action('wp_ajax_uip_get_pro_app_data', [$this, 'uip_get_pro_app_data']);
    add_action('wp_ajax_uip_save_uip_pro_data', [$this, 'uip_save_uip_pro_data']);
    add_action('wp_ajax_uip_remove_uip_pro_data', [$this, 'uip_remove_uip_pro_data']);
    //Analytics actions
    add_action('wp_ajax_uip_build_google_analytics_query', [$this, 'uip_build_google_analytics_query']);
    add_action('wp_ajax_uip_save_google_analytics', [$this, 'uip_save_google_analytics']);
    add_action('wp_ajax_uip_save_access_token', [$this, 'uip_save_access_token']);
    add_action('wp_ajax_uip_remove_analytics_account', [$this, 'uip_remove_analytics_account']);
    //Plugin actions
    add_action('wp_ajax_uip_get_plugin_updates', [$this, 'uip_get_plugin_updates']);
    add_action('wp_ajax_uip_update_plugin', [$this, 'uip_update_plugin']);
    add_action('wp_ajax_uip_search_directory', [$this, 'uip_search_directory']);
    add_action('wp_ajax_uip_install_plugin', [$this, 'uip_install_plugin']);
    add_action('wp_ajax_uip_activate_plugin', [$this, 'uip_activate_plugin']);
    add_action('wp_ajax_uip_get_shortcode', [$this, 'uip_get_shortcode']);
    //Role editing actions
    add_action('wp_ajax_uip_get_all_roles', [$this, 'uip_get_all_roles']);
    add_action('wp_ajax_uip_get_all_capabilities', [$this, 'uip_get_all_capabilities']);
    add_action('wp_ajax_uip_update_role', [$this, 'uip_update_role']);
    add_action('wp_ajax_uip_delete_role', [$this, 'uip_delete_role']);
    add_action('wp_ajax_uip_create_role', [$this, 'uip_create_role']);
    add_action('wp_ajax_uip_delete_cap', [$this, 'uip_delete_cap']);
    add_action('wp_ajax_uip_create_cap', [$this, 'uip_create_cap']);
    //content navigator
    add_action('wp_ajax_uip_get_navigator_defaults', [$this, 'uip_get_navigator_defaults']);
    add_action('wp_ajax_uip_get_default_content', [$this, 'uip_get_default_content']);
    add_action('wp_ajax_uip_create_folder', [$this, 'uip_create_folder']);
    add_action('wp_ajax_uip_get_folder_content', [$this, 'uip_get_folder_content']);
    add_action('wp_ajax_uip_update_item_folder', [$this, 'uip_update_item_folder']);
    add_action('wp_ajax_uip_delete_folder', [$this, 'uip_delete_folder']);
    add_action('wp_ajax_uip_update_folder', [$this, 'uip_update_folder']);
    add_action('wp_ajax_uip_duplicate_post', [$this, 'uip_duplicate_post']);
    add_action('wp_ajax_uip_delete_post_from_folder', [$this, 'uip_delete_post_from_folder']);
  }

  /**
   * Deletes folder
   * @since 3.0.93
   */
  public function uip_delete_post_from_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $postID = sanitize_text_field($_POST['postID']);

      //No folder id
      if (!$postID || $postID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No post to delete', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($postID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Post does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Incorrect caps
      if (!current_user_can('delete_post', $postID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You do not have the correct capabilities to delete this post', 'uipress-pro');
        wp_send_json($returndata);
      }

      //Delete but leave in the trash just in case
      $status = wp_delete_post($postID, false);

      //Something went wrong
      if (!$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to delete the post right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Duplicates post
   * @since 3.0.93
   */
  public function uip_duplicate_post()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      global $wpdb;
      $utils = new uip_util();
      $post_id = sanitize_text_field($_POST['postID']);

      //No folder id
      if (!$post_id || $post_id == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No post to duplicate', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($post_id)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Post does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }

      $post = get_post($post_id);

      $current_user = wp_get_current_user();
      $new_post_author = $current_user->ID;
      $updatedTitle = $post->post_title . ' (' . __('copy', 'uipress-pro') . ')';

      $args = [
        'comment_status' => $post->comment_status,
        'ping_status' => $post->ping_status,
        'post_author' => $new_post_author,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_name' => $post->post_name,
        'post_parent' => $post->post_parent,
        'post_password' => $post->post_password,
        'post_status' => 'draft',
        'post_title' => $updatedTitle,
        'post_type' => $post->post_type,
        'to_ping' => $post->to_ping,
        'menu_order' => $post->menu_order,
      ];

      $new_post_id = wp_insert_post($args);

      if (!$new_post_id) {
        return false;
      }

      $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
      foreach ($taxonomies as $taxonomy) {
        $post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
      }

      $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
      if (count($post_meta_infos) != 0) {
        $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
        foreach ($post_meta_infos as $meta_info) {
          $meta_key = $meta_info->meta_key;
          if ($meta_key == '_wp_old_slug') {
            continue;
          }
          $meta_value = addslashes($meta_info->meta_value);
          $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
        }

        $sql_query .= implode(' UNION ALL ', $sql_query_sel);
        $wpdb->query($sql_query);
      }

      $postobject = get_post($new_post_id);

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['newID'] = $new_post_id;
      $returndata['newTitle'] = $updatedTitle;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes folder
   * @since 3.0.93
   */
  public function uip_update_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderID = sanitize_text_field($_POST['folderId']);
      $title = sanitize_text_field($_POST['title']);
      $color = sanitize_text_field($_POST['color']);

      //No folder id
      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder to update', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Incorrect caps
      if (!current_user_can('edit_post', $folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You do not have the correct capabilities to update this folder', 'uipress-pro');
        wp_send_json($returndata);
      }

      //Tittle is blank
      if (!$title || $title == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder title is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      $my_post = [
        'ID' => $folderID,
        'post_title' => wp_strip_all_tags($title),
      ];

      //Update the post into the database
      $status = wp_update_post($my_post);

      //Something went wrong
      if (!$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to update the folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($color && $color != '') {
        update_post_meta($folderID, 'uip-folder-color', $color);
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes folder
   * @since 3.0.93
   */
  public function uip_delete_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderID = sanitize_text_field($_POST['folderId']);
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));

      //No folder id
      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder to delete', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Incorrect caps
      if (!current_user_can('delete_post', $folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You do not have the correct capabilities to delete this folder', 'uipress-pro');
        wp_send_json($returndata);
      }

      $status = wp_delete_post($folderID, true);

      //Something went wrong
      if (!$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to delete the folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      $this->removeFromFolder($folderID, $postTypes);

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Removes folder from items
   * @since 3.0.93
   */
  public function removeFromFolder($folderID, $postTypes)
  {
    //Get all posts in this folder and remove the id
    if (!$postTypes || empty($postTypes)) {
      $args = ['public' => true];
      $output = 'names';
      $operator = 'and';
      $types = get_post_types($args, $output, $operator);
      $postTypes = [];
      foreach ($types as $type) {
        $postTypes[] = $type;
      }
    }

    if (!in_array('uip-ui-folder', $postTypes)) {
      $postTypes[] = 'uip-ui-folder';
    }
    //Get folder contents
    $args = [
      'post_type' => $postTypes,
      'posts_per_page' => -1,
      'post_status' => ['publish', 'draft', 'inherit'],
      'meta_query' => [
        [
          'key' => 'uip-folder-parent',
          'value' => serialize(strval($folderID)),
          'compare' => 'LIKE',
        ],
      ],
    ];

    $query = new WP_Query($args);
    $foundPosts = $query->get_posts();

    foreach ($foundPosts as $post) {
      $currentFolders = get_post_meta($post->id, 'uip-folder-parent', true);

      if (in_array($folderID, $currentFolders)) {
        $new = [];
        foreach ($current as $fol) {
          if ($fol != $folderID) {
            $new[] = $fol;
          }
        }
        $current = array_values(array_unique($new));
        update_post_meta($post->id, 'uip-folder-parent', $current);
      }

      //Recursively remove folders inside folders
      $type = get_post_type($post->ID);
      if ($type == 'uip-ui-folder') {
        if (current_user_can('delete_post', $post->ID)) {
          wp_delete_post($post->ID, true);
        }
        $this->removeFromFolder($post->ID, $postTypes);
      }
    }
  }

  /**
   * Updates item folder after drag and drop
   * @since 3.0.93
   */
  public function uip_update_item_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $item = $utils->clean_ajax_input(json_decode(stripslashes($_POST['item'])));
      $newParent = sanitize_text_field($_POST['newParent']);

      if (!$item || empty($item)) {
        $returndata['error'] = true;
        $returndata['message'] = __('No item to update', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($item->type == 'uip-ui-folder') {
        if ($newParent != 'uipfalse') {
          $newParent = [$newParent];
        }
        update_post_meta($item->id, 'uip-folder-parent', $newParent);
      } else {
        $current = get_post_meta($item->id, 'uip-folder-parent', true);

        if (!$current || !is_array($current)) {
          $current = [];
        }

        //If old parent is in current parent, remove it
        if (in_array($item->parent, $current)) {
          $currentid = $item->parent;

          $new = [];
          foreach ($current as $fol) {
            if ($fol == $currentid) {
              $fol = $newParent;
            }
            $new[] = $fol;
          }
          $current = array_values(array_unique($new));
        } else {
          array_push($current, $newParent);
        }
        update_post_meta($item->id, 'uip-folder-parent', $current);
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets content for give folder
   * @since 3.0.93
   */
  public function uip_get_folder_content()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));
      $page = sanitize_text_field($_POST['page']);
      $search = sanitize_text_field($_POST['search']);
      $folderID = sanitize_text_field($_POST['id']);
      $authorLimit = sanitize_text_field($_POST['limitToauthor']);

      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder given to fetch content for', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$page || $page == '') {
        $page = 1;
      }

      if (!$postTypes || empty($postTypes)) {
        $args = ['public' => true];
        $output = 'names';
        $operator = 'and';
        $types = get_post_types($args, $output, $operator);
        $postTypes = [];
        foreach ($types as $type) {
          $postTypes[] = $type;
        }
      }

      if (!in_array('uip-ui-folder', $postTypes)) {
        $postTypes[] = 'uip-ui-folder';
      }

      //Get folder contents
      $args = [
        'post_type' => $postTypes,
        'posts_per_page' => 10,
        'paged' => $page,
        'post_status' => ['publish', 'draft', 'inherit'],
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
          [
            'key' => 'uip-folder-parent',
            'value' => serialize(strval($folderID)),
            'compare' => 'LIKE',
          ],
        ],
      ];

      if ($authorLimit == 'true') {
        $args['author'] = get_current_user_id();
      }

      if ($search && $search != '' && $search != 'undefined') {
        $args['s'] = $search;
      }

      $query = new WP_Query($args);
      $totalFound = $query->found_posts;
      $foundPosts = $query->get_posts();

      $formatted = [];
      foreach ($foundPosts as $post) {
        $link = get_permalink($post->ID);
        $editLink = get_edit_post_link($post->ID, '&');
        $type = get_post_type($post->ID);
        $canDelete = current_user_can('delete_post', $post->ID);

        $temp = [];
        $temp['id'] = $post->ID;
        $temp['title'] = $post->post_title;
        $temp['status'] = $post->post_status;
        $temp['edit_href'] = $editLink;
        $temp['view_href'] = $link;
        $temp['type'] = $type;
        $temp['canDelete'] = $canDelete;
        $temp['parent'] = $folderID;

        if ($type == 'uip-ui-folder') {
          $temp['count'] = $this->get_folder_content_count($post->ID, $postTypes, $authorLimit);
          $temp['color'] = get_post_meta($post->ID, 'uip-folder-color', true);
        }

        $formatted[] = $temp;
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['content'] = $formatted;
      $returndata['totalFound'] = $totalFound;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Creates new folder
   * @since 3.0.93
   */
  public function uip_create_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderParent = sanitize_text_field($_POST['folderParent']);
      $folderName = sanitize_text_field($_POST['folderName']);
      $folderColor = sanitize_text_field($_POST['folderColor']);

      if (!$folderParent || $folderParent == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to create content folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$folderName || $folderName == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder name is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      $updateArgs = [
        'post_title' => wp_strip_all_tags($folderName),
        'post_status' => 'publish',
        'post_type' => 'uip-ui-folder',
      ];

      $updatedID = wp_insert_post($updateArgs);

      if (!$updatedID || $updatedID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to create content folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($folderParent != 'uipfalse') {
        $folderParent = [$folderParent];
      }
      update_post_meta($updatedID, 'uip-folder-parent', $folderParent);
      update_post_meta($updatedID, 'uip-folder-color', $folderColor);

      $temp = [];
      $temp['id'] = $updatedID;
      $temp['title'] = $folderName;
      $temp['parent'] = $folderParent;
      $temp['count'] = 0;
      $temp['color'] = $folderColor;
      $temp['content'] = [];
      $temp['canDelete'] = true;
      $temp['type'] = 'uip-ui-folder';

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['folder'] = $temp;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets default post type content for the content navigator
   * @since 3.0.93
   */
  public function uip_get_default_content()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $postType = sanitize_text_field($_POST['postType']);
      $page = sanitize_text_field($_POST['page']);
      $search = sanitize_text_field($_POST['search']);
      $authorLimit = sanitize_text_field($_POST['limitToauthor']);

      if (!$postType || $postType == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No post type to fetch content for', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!post_type_exists($postType)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Post type does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$page || $page == '') {
        $page = 1;
      }
      //Get template
      $args = [
        'post_type' => $postType,
        'posts_per_page' => 10,
        'paged' => $page,
        'post_status' => ['publish', 'draft', 'inherit'],
      ];

      if ($authorLimit == 'true') {
        $args['author'] = get_current_user_id();
      }

      if ($search && $search != '' && $search != 'undefined') {
        $args['s'] = $search;
      }

      $query = new WP_Query($args);
      $totalFound = $query->found_posts;
      $foundPosts = $query->get_posts();

      $formatted = [];
      foreach ($foundPosts as $post) {
        $link = get_permalink($post->ID);
        $editLink = get_edit_post_link($post->ID, '&');
        $type = get_post_type($post->ID);
        $canDelete = current_user_can('delete_post', $post->ID);

        $temp = [];
        $temp['id'] = $post->ID;
        $temp['title'] = $post->post_title;
        $temp['status'] = $post->post_status;
        $temp['edit_href'] = $editLink;
        $temp['view_href'] = $link;
        $temp['type'] = $type;
        $temp['canDelete'] = $canDelete;

        $formatted[] = $temp;
      }

      if (empty($formatted)) {
        $formatted = [];
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['content'] = $formatted;
      $returndata['totalFound'] = $totalFound;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Builds default folders for post content
   * @since 3.0.93
   */
  public function uip_get_navigator_defaults()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $types = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));
      $authorLimit = sanitize_text_field($_POST['limitToauthor']);

      if (!is_array($types) || empty($types)) {
        $types = false;
      }

      //No limit on specific post types so let's fetch all public ones
      $args = ['public' => true];
      $output = 'objects';
      $operator = 'and';
      $post_types = get_post_types($args, $output, $operator);
      //Build array of post types with nice name
      $formatted = [];
      foreach ($post_types as $type) {
        if ($types) {
          if (!in_array($type->name, $types)) {
            continue;
          }
        }

        $temp = [];
        $temp['name'] = $type->labels->singular_name;
        $temp['label'] = $type->labels->name;
        $temp['type'] = $type->name;
        $temp['count'] = 0;
        $temp['content'] = [];
        $temp['new_href'] = admin_url('post-new.php?post_type=' . $type->name);

        //Count posts
        if ($authorLimit == 'true') {
          $args = [
            'author' => get_current_user_id(),
            'post_type' => $type->name,
            'post_status' => ['publish', 'pending', 'draft', 'future'],
          ];
          $postCount = new WP_Query($args);
          $temp['count'] = $postCount->found_posts;
        } else {
          $allposts = wp_count_posts($type->name);

          if (isset($allposts->publish)) {
            $temp['count'] = $allposts->publish;
          }
          if (isset($allposts->draft)) {
            $temp['count'] += $allposts->draft;
          }
          if (isset($allposts->inherit)) {
            $temp['count'] += $allposts->inherit;
          }
        }

        $formatted[] = $temp;
      }

      ////
      ///Get base folders
      ////
      $args = [
        'post_type' => 'uip-ui-folder',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
          [
            'key' => 'uip-folder-parent',
            'value' => 'uipfalse',
            'compare' => '=',
          ],
        ],
      ];

      if ($authorLimit == 'true') {
        $args['author'] = get_current_user_id();
      }

      $query = new WP_Query($args);
      $foundFolders = $query->get_posts();

      $formattedFolders = [];
      foreach ($foundFolders as $folder) {
        $canDelete = current_user_can('delete_post', $folder->ID);

        $temp = [];
        $temp['id'] = $folder->ID;
        $temp['title'] = $folder->post_title;
        $temp['parent'] = 'uipfalse';
        $temp['count'] = $this->get_folder_content_count($folder->ID, $types, $authorLimit);
        $temp['color'] = get_post_meta($folder->ID, 'uip-folder-color', true);
        $temp['type'] = 'uip-ui-folder';
        $temp['content'] = [];
        $temp['canDelete'] = $canDelete;
        $formattedFolders[] = $temp;
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['postTypes'] = $formatted;
      $returndata['baseFolders'] = $formattedFolders;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Counts folder content
   * @since 3.0.92
   */

  public function get_folder_content_count($folderID, $postTypes, $authorLimit)
  {
    if (!$postTypes || empty($postTypes)) {
      $args = ['public' => true];
      $output = 'names';
      $operator = 'and';
      $types = get_post_types($args, $output, $operator);
      $postTypes = [];
      foreach ($types as $type) {
        $postTypes[] = $type;
      }
    }

    if (!in_array('uip-ui-folder', $postTypes)) {
      $postTypes[] = 'uip-ui-folder';
    }
    //Get folder count
    $args = [
      'post_type' => $postTypes,
      'posts_per_page' => -1,
      'post_status' => ['publish', 'draft', 'inherit'],
      'fields' => 'ids',
      'meta_query' => [
        [
          'key' => 'uip-folder-parent',
          'value' => serialize(strval($folderID)),
          'compare' => 'LIKE',
        ],
      ],
    ];

    if ($authorLimit == 'true') {
      $args['author'] = get_current_user_id();
    }

    $query = new WP_Query($args);
    $totalInFolder = $query->found_posts;
    if ($totalInFolder == null) {
      $totalInFolder = 0;
    }
    return $totalInFolder;
  }

  /**
   * Deletes cap
   * @since 3.0.92
   */

  public function uip_create_cap()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $roleName = sanitize_text_field($_POST['rolename']);
      $roleLabel = sanitize_text_field($_POST['rolelabel']);
      $cap = sanitize_text_field($_POST['cap']);

      if (!current_user_can('edit_users')) {
        $returndata['error'] = true;
        $returndata['message'] = __("You don't have sufficent priviledges to manage capabilities", 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$roleName) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role name receieved to edit', 'uipress-pro');
        wp_send_json($returndata);
      }
      if (!$roleLabel) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role label receieved to edit', 'uipress-pro');
        wp_send_json($returndata);
      }
      if (!$cap) {
        $returndata['error'] = true;
        $returndata['message'] = __('No cap name receieved to create', 'uipress-pro');
        wp_send_json($returndata);
      }

      $customcap = strtolower($cap);

      $currentRole = get_role($roleName);
      $currentRole->add_cap($customcap, false);
      $currentcaps = $currentRole->capabilities;

      remove_role($roleName);
      $status = add_role($roleName, $roleLabel, $currentcaps);

      if ($status == null) {
        $returndata['error'] = true;
        $returndata['message'] = __('Something has gone wrong', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata['success'] = true;
      $returndata['message'] = __('Capability added', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes cap
   * @since 3.0.92
   */

  public function uip_delete_cap()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $roleName = sanitize_text_field($_POST['rolename']);
      $roleLabel = sanitize_text_field($_POST['rolelabel']);
      $cap = sanitize_text_field($_POST['cap']);

      if (!current_user_can('edit_users')) {
        $returndata['error'] = true;
        $returndata['message'] = __("You don't have sufficent priviledges to manage capabilities", 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$roleName) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role name receieved to edit', 'uipress-pro');
        wp_send_json($returndata);
      }
      if (!$roleLabel) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role label receieved to edit', 'uipress-pro');
        wp_send_json($returndata);
      }
      if (!$cap) {
        $returndata['error'] = true;
        $returndata['message'] = __('No cap receieved to remove', 'uipress-pro');
        wp_send_json($returndata);
      }

      $customcap = strtolower($cap);

      $currentRole = get_role($roleName);
      $currentRole->remove_cap($customcap, false);
      $currentcaps = $currentRole->capabilities;

      remove_role($roleName);
      $status = add_role($roleName, $roleLabel, $currentcaps);

      if ($status == null) {
        $returndata['error'] = true;
        $returndata['message'] = __('Something has gone wrong', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata['success'] = true;
      $returndata['message'] = __('Capability removed', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Creates role
   * @since 2.3.5
   */

  public function uip_create_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $newrole = $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));
      $caps = $utils->clean_ajax_input(json_decode(stripslashes($_POST['caps'])));

      if (!current_user_can('edit_users')) {
        $returndata['error'] = true;
        $returndata['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$newrole) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role data receieved to create', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!isset($newrole->label) || $newrole->label == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Role label is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!isset($newrole->name) || $newrole->name == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (wp_roles()->is_role($newrole->name)) {
        $returndata['error'] = true;
        $returndata['message'] = __('A role with that name already exists', 'uipress-pro');
        wp_send_json($returndata);
      }

      $capabilities = [];
      if (is_object($caps)) {
        foreach ($caps as $key => $value) {
          if ($value == 'true' || $value === true || $value == true) {
            $capabilities[$key] = true;
          } else {
            $capabilities[$key] = false;
          }
        }
      }

      $status = add_role($newrole->name, $newrole->label, $capabilities);

      if ($status == null) {
        $returndata['error'] = true;
        $returndata['message'] = __('Something has gone wrong', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata['success'] = true;
      $returndata['message'] = __('Role created', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes role
   * @since 2.3.5
   */

  public function uip_delete_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $newrole = $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));

      if (!current_user_can('delete_users')) {
        $returndata['error'] = true;
        $returndata['message'] = __("You don't have sufficent priviledges to delete roles", 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$newrole) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role data receieved to delete', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!isset($newrole->name) || $newrole->name == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Role name is required to perform delete', 'uipress-pro');
        wp_send_json($returndata);
      }

      $user = wp_get_current_user();
      $currentRoles = $user->roles;

      if (in_array($newrole->name, $currentRoles)) {
        $returndata['error'] = true;
        $returndata['message'] = __("You can't delete a role that is currenty assigned to yourself", 'uipress-pro');
        wp_send_json($returndata);
      }

      remove_role($newrole->name);

      $returndata['success'] = true;
      $returndata['message'] = __('Role removed', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Updates role info
   * @since 2.3.5
   */

  public function uip_update_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $newrole = $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));

      if (!current_user_can('edit_users')) {
        $returndata['error'] = true;
        $returndata['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$newrole) {
        $returndata['error'] = true;
        $returndata['message'] = __('No role data receieved to save', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!isset($newrole->label) || $newrole->label == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      $capabilities = [];
      if (is_object($newrole->caps)) {
        foreach ($newrole->caps as $key => $value) {
          if ($value == 'true' || $value === true || $value == true) {
            $capabilities[$key] = true;
          } else {
            $capabilities[$key] = false;
          }
        }
      }

      remove_role($newrole->name);
      $status = add_role($newrole->name, $newrole->label, $capabilities);

      if ($status == null) {
        $returndata['error'] = true;
        $returndata['message'] = __('Something has gone wrong', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata['success'] = true;
      $returndata['message'] = __('Role updated', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets all role capabilities
   * @since 3.0.92
   */

  public function uip_get_all_capabilities()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $caps = $utils->get_all_role_capabilities();

      $returnData['totalFound'] = count($caps);
      $returnData['caps'] = $caps;
      $returnData['success'] = true;

      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Gets role data
   * @since 3.0.92
   */

  public function uip_get_all_roles()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $filters = $utils->clean_ajax_input(json_decode(stripslashes($_POST['filters'])));

      //SET SEARCH QUERY
      $s_query = '';
      if (isset($filters->search)) {
        $s_query = $filters->search;
      }

      global $wp_roles;
      $all_roles = [];

      foreach ($wp_roles->roles as $key => $value) {
        $temp = [];

        if (!isset($value['name']) || $value['name'] == '') {
          continue;
        }

        if ($s_query != '') {
          if (strpos(strtolower($value['name']), strtolower($s_query)) === false) {
            continue;
          }
        }

        $temp['name'] = $key;
        $temp['label'] = $value['name'];
        $temp['caps'] = $value['capabilities'];
        $temp['granted'] = count($value['capabilities']);

        if (empty($temp['caps'])) {
          $temp['caps'] = new stdclass();
        }

        $args = [
          'number' => -1,
          'role__in' => [$key],
        ];

        $user_query = new WP_User_Query($args);
        $allUsers = $user_query->get_results();

        $count = 0;
        $userHolder = [];
        if (!empty($allUsers)) {
          foreach ($allUsers as $user) {
            $userHolder[] = $user->user_login;
            $count += 1;
            if ($count > 4) {
              break;
            }
          }
        }

        $temp['users'] = $userHolder;
        $temp['usersCount'] = $user_query->get_total();
        array_push($all_roles, $temp);
      }

      usort($all_roles, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
      });

      $returnData['totalFound'] = count($wp_roles->role_objects);
      $returnData['roles'] = $all_roles;
      $returnData['success'] = true;

      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Activates plugins from the plugin update block
   * @since 3.0.0
   */
  public function uip_activate_plugin()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $slug = sanitize_text_field($_POST['slug']);

      if (!current_user_can('activate_plugins')) {
        $message = __("You don't have necessary permissions to activate plugins", 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }

      if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $all_plugins = get_plugins();
      foreach ($all_plugins as $key => $value) {
        if (strpos($key, $slug) !== false) {
          $slug = $key;
          break;
        } else {
          continue;
        }
      }

      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      ob_start();
      $status = activate_plugins($slug);
      ob_get_clean();

      if (!$status) {
        $message = __('Unable to activate this plugin', 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }
      $returndata['message'] = __('Plugin activated', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Runs and returns given shortcode
   * @since 3.0.96
   */
  public function uip_get_shortcode()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $shortCode = stripslashes(sanitize_text_field($_POST['shortCode']));

      if (!$shortCode) {
        $message = __('Unable to run shortcode', 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }

      ob_start();

      echo do_shortcode($shortCode);

      $code = ob_get_clean();

      $returndata = [];
      $returndata['shortCode'] = $code;
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Updates plugins from the plugin update block
   * @since 3.0.0
   */
  public function uip_install_plugin()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $downloadLink = sanitize_text_field($_POST['downloadLink']);

      if (!current_user_can('install_plugins')) {
        $message = __("You don't have necessary permissions to install plugins", 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }

      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      $skin = new WP_Ajax_Upgrader_Skin();
      $upgrader = new Plugin_Upgrader($skin);
      $status = $upgrader->install($downloadLink);

      if (!$status) {
        $message = __('Unable to install this plugin', 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }
      $returndata['message'] = __('Plugin installed', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Searches plugins from the wp directory
   * @since 3.0.0
   */
  public function uip_search_directory()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $search = sanitize_text_field($_POST['search']);
      $page = sanitize_text_field($_POST['page']);

      include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

      if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      $plugins = plugins_api('query_plugins', [
        'per_page' => 10,
        'search' => $search,
        'page' => $page,
        'fields' => [
          'short_description' => true,
          'description' => true,
          'sections' => false,
          'tested' => true,
          'requires' => true,
          'requires_php' => true,
          'rating' => true,
          'ratings' => false,
          'downloaded' => true,
          'downloadlink' => true,
          'last_updated' => true,
          'added' => false,
          'tags' => false,
          'slug' => true,
          'compatibility' => false,
          'homepage' => true,
          'versions' => false,
          'donate_link' => false,
          'reviews' => false,
          'banners' => true,
          'icons' => true,
          'active_installs' => true,
          'group' => false,
          'contributors' => false,
          'screenshots' => true,
        ],
      ]);

      $returndata['message'] = __('Plugins found', 'uipress-pro');
      $returndata['plugins'] = $utils->clean_ajax_input_width_code($plugins->plugins);
      $returndata['totalFound'] = $utils->clean_ajax_input_width_code($plugins->info['results']);
      $returndata['totalPages'] = $utils->clean_ajax_input_width_code($plugins->info['pages']);
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Updates plugins from the plugin update block
   * @since 3.0.0
   */
  public function uip_update_plugin()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $slug = sanitize_text_field($_POST['slug']);

      if (!current_user_can('update_plugins')) {
        $message = __("You don't have necessary permissions to update plugins", 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }

      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      ob_start();
      $upgrader = new Plugin_Upgrader();
      $upgraded = $upgrader->upgrade($slug);
      $list = ob_get_contents();
      ob_end_clean();

      if (!$upgraded) {
        $message = __('Unable to upgrade this plugin', 'uipress-pro');
        $returndata['error'] = true;
        $returndata['message'] = $message;
        wp_send_json($returndata);
      }
      $returndata['message'] = __('Plugin updated', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets plugin updates
   * @since 3.0.0
   */
  public function uip_get_plugin_updates()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();

      $updates = get_plugin_updates();

      $returndata = [];
      $returndata['updates'] = $updates;
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Saves google data
   * @since 3.0.0
   */
  public function uip_remove_analytics_account()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $saveAccountToUser = sanitize_text_field($_POST['saveAccountToUser']);

      if ($saveAccountToUser == 'true') {
        $google = $utils->get_user_preference('google_analytics');
      } else {
        $google = $utils->get_uip_option('google_analytics');
      }

      if (!is_array($google)) {
        $google = [];
      }

      $google['view'] = false;
      $google['code'] = false;

      if ($saveAccountToUser == 'true') {
        $utils->save_user_preference('google_analytics', $google);
      } else {
        $utils->update_uip_option('google_analytics', $google);
      }

      $returndata = [];
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Saves google data
   * @since 3.0.0
   */
  public function uip_save_access_token()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $token = sanitize_text_field($_POST['token']);

      if (!$token || $token == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Inccorrect token sent to server', 'uipress-pro');
        wp_send_json($returndata);
      }

      $google = $utils->get_uip_option('google_analytics');

      if (!is_array($google)) {
        $google = [];
      }

      $google['token'] = $token;

      $utils->update_uip_option('google_analytics', $google);

      $returndata = [];
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Saves google data
   * @since 3.0.0
   */
  public function uip_save_google_analytics()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $data = $utils->clean_ajax_input(json_decode(stripslashes($_POST['analytics'])));
      $saveAccountToUser = sanitize_text_field($_POST['saveAccountToUser']);

      if (!is_object($data)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Inccorrect data passed to server', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!isset($data->view) || !isset($data->code)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Inccorrect data passed to server', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($saveAccountToUser == 'true') {
        $google = $utils->get_user_preference('google_analytics');
      } else {
        $google = $utils->get_uip_option('google_analytics');
      }

      if (!is_array($google)) {
        $google = [];
      }

      $google['view'] = $data->view;
      $google['code'] = $data->code;

      if ($saveAccountToUser == 'true') {
        $utils->save_user_preference('google_analytics', $google);
      } else {
        $utils->update_uip_option('google_analytics', $google);
      }

      $returndata = [];
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets required data for analytics request
   * @since 3.0.0
   */
  public function uip_build_google_analytics_query()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $data = $utils->get_uip_option('uip_pro');

      $saveAccountToUser = sanitize_text_field($_POST['saveAccountToUser']);

      if ($saveAccountToUser == 'true') {
        $google = $utils->get_user_preference('google_analytics');
      } else {
        $google = $utils->get_uip_option('google_analytics');
      }

      if (!$data || !isset($data['key'])) {
        $returndata['error'] = true;
        $returndata['message'] = __('You need a licence key to use analytics blocks', 'uipress-pro');
        $returndata['error_type'] = 'no_licence';
        $returndata['url'] = false;
        wp_send_json($returndata);
      }

      if (!$google || !isset($google['view']) || !isset($google['code'])) {
        $returndata['error'] = true;
        $returndata['message'] = __('You need to connect a google analytics account to display data', 'uipress-pro');
        $returndata['error_type'] = 'no_google';
        $returndata['url'] = false;
        wp_send_json($returndata);
      }

      $key = $data['key'];
      $instance = $data['instance'];
      $code = $google['code'];
      $view = $google['view'];
      $domain = get_home_url();

      if ($key == '' || $code == '' || $view == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('You need to connect a google analytics account to display data', 'uipress-pro');
        $returndata['error_type'] = 'no_google';
        $returndata['url'] = false;
        wp_send_json($returndata);
      }

      $token = '';
      if (isset($google['token']) && $google['token'] != '') {
        $token = $google['token'];
      }

      $theQuery = sanitize_url("https://analytics.uipress.co/view.php?code={$code}&view={$view}&key={$key}&instance={$instance}&uip3=1&gafour=true&d={$domain}&uip_token=$token");

      $returndata = [];
      $returndata['success'] = true;
      $returndata['url'] = $theQuery;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Removes pro app data
   * @since 3.0.0
   */
  public function uip_remove_uip_pro_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $utils->update_uip_option('uip_pro', false);
      $returndata = [];
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Fetches pro app data
   * @since 3.0.0
   */
  public function uip_get_pro_app_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();

      $data = $utils->get_uip_option('uip_pro');

      $returndata = [];
      $returndata['uip_pro'] = $data;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Saves pro app data
   * @since 3.0.0
   */
  public function uip_save_uip_pro_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $key = sanitize_text_field($_POST['key']);
      $instance = sanitize_text_field($_POST['instance']);
      //Get current data
      $data = $utils->get_uip_option('uip_pro');

      if (!is_array($data)) {
        $data = [];
      }

      $data['key'] = $key;
      $data['instance'] = $instance;

      $utils->update_uip_option('uip_pro', $data);

      $returndata = [];
      $returndata['success'] = true;
      wp_send_json($returndata);
    }
    die();
  }
}
