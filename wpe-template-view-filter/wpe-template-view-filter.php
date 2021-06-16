<?php
/**
* Plugin Name: WP Engine Template View Filter
* Plugin URI: https://github.com/chriskleckner
* Description: Adds dropdown to Filter by template on Pages admin. Displays template info in the admin bar for logged in Admins and Authors when viweing pages.
* Version: 1.0
* Author: Chris Kleckner
* Author URI: https://github.com/chriskleckner
*/

// By default, WordPress Authors cannot manage Pages so this function adds the capability. This could be extended in an options page to enable access and further define capabilities. 
function wpe_template_view_filter_required_role_caps() {
  // Gets the simple_role role object.
  $role = get_role( 'author' );
  // Add a new capability.
  $role->add_cap( 'read', true );
  $role->add_cap( 'edit_pages', true );
  $role->add_cap( 'publish_pages', true );
  $role->add_cap( 'delete_pages', true );

}
// Add simple_role capabilities, priority must be after the initial role definition.
add_action( 'init', 'wpe_template_view_filter_required_role_caps', 11 );

// Add Template info to Admin Bar
add_action( 'admin_bar_menu', 'show_current_page_template', 999);
function show_current_page_template( $wp_admin_bar ) {

  if ( ! is_admin() ) {

    if( current_user_can('author') || current_user_can('administrator') ) {

      $templates = wp_get_theme()->get_page_templates();
      $template_name = '';

      foreach($templates as $key => $val) {
        if( $key == basename( get_page_template() ) ) {
          $template_name = $val;
        }
      }

      $args = array(
        'id' => 'current_page_template',
        'title' => 'Template: '.$template_name.' ('.basename( get_page_template() ).')'
      );

      $wp_admin_bar->add_node( $args );
    }
  }
}

// Add Column to Pages list
add_filter( 'manage_pages_columns', 'page_column_views' );
function page_column_views( $defaults )
{
  if( current_user_can('author') || current_user_can('administrator') ) {
    $defaults['template'] = __('Template', 'textdomain');
    return $defaults;
  }
}


// Display Template value for page
add_action( 'manage_pages_custom_column', 'page_custom_column_views', 5, 2 );
function page_custom_column_views( $column_name, $id )
{
  if( current_user_can('author') || current_user_can('administrator') ) {

    if ( $column_name === 'template' ) {

      $set_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

      if ( $set_template == 'default' ) {
        echo __('Default Template', 'textdomain');
      }

      $templates = get_page_templates();

      ksort( $templates );

      foreach ( array_keys( $templates ) as $template ) :
        if ( $set_template == $templates[$template] ) echo $template;
      endforeach;
    }
  }
}

// Add template filter dropdown to Pages list
function wpe_template_view_filter_dropdown() {

  if( current_user_can('author') || current_user_can('administrator') ) {

    global $typenow;
    global $wp_query;

    if ( $typenow == 'page' ) {
      echo '<select name="template" id="template">';

      echo '<option value="">All Templates</option>';

      $templates = get_page_templates();

      foreach( $templates as $key => $val ) {
        $selected = isset($_GET['template']) && $_GET['template']!='' ? selected( $val, $_GET['template'] ) : '';
        echo '<option value="'.esc_attr( $val ).'" '.$selected.'>'.esc_attr( $key ).'</option>';
      }

      echo '</select>';

    }
  }
}
add_action( 'restrict_manage_posts', 'wpe_template_view_filter_dropdown' );

// Execute selected Template filter on Pages query
function wpe_template_filter_by_selected_template( $query ) {

  global $pagenow;

// Get the post type
  $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

  if ( is_admin() && $pagenow=='edit.php' && $post_type == 'page'
    && isset( $_GET['template'] ) && $_GET['template'] !='' ) {

    $query->query_vars['meta_key'] = '_wp_page_template';
  $query->query_vars['meta_value'] = $_GET['template'];
  $query->query_vars['meta_compare'] = '=';
}

}
add_filter( 'parse_query', 'wpe_template_filter_by_selected_template' );

