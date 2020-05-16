<?php

/**
 * Plugin Name: ACF Agency Workflow
 * Description: Create, move, distribute and sync your Field Groups as JSON.
 * Version: 1.2.1
 * Plugin URI: https://github.com/Brugman/acf-agency-workflow
 * Author: Tim Brugman
 * Author URI: https://timbr.dev/
 */

if ( !defined( 'ABSPATH' ) )
    exit;

include 'functions.php';

/**
 * Respond to JSON changes.
 */

add_action( 'admin_init', function () {

    // Require ACF Pro.
    if ( !aaw_is_acf_active() )
        return;

    // Require administrator.
    if ( !current_user_can( 'manage_options' ) )
        return;

    // Only on the Dashboard and Field Groups page.
    global $pagenow;

    $is_dashboard         = ( $pagenow == 'index.php' );
    $is_field_groups_page = ( $pagenow == 'edit.php' && $_GET == [ 'post_type' => 'acf-field-group' ] );

    if ( !$is_dashboard && !$is_field_groups_page )
        return;

    // Add & update field groups.
    aaw_act_on_added_json();

    // Remove field groups.
    aaw_act_on_removed_json();
});

/**
 * Respond to Field Group Editor changes.
 */

add_action( 'acf/delete_field_group', function ( $field_group ) {
    // Delete field group from JSON.
    aaw_delete_field_group_from_json( $field_group['key'] );
});

/**
 * Replace the FG trash buttons with delete buttons.
 */

add_filter( 'page_row_actions', function ( $actions, $post ) {

    if ( $post->post_type == 'acf-field-group' )
    {
        // Remove trash.
        unset( $actions['trash'] );
        // Add delete.
        $actions['delete'] = '<a href="'.get_delete_post_link( $post->ID, '', true ).'" aria-label="Delete “'.$post->post_title.'” permanently">'.__( 'Delete Permanently' ).'</a>';
    }

    return $actions;

}, 10, 2 );

/**
 * Display JSON save locations list.
 */

add_action( 'acf/render_field_group_settings', function ( $field_group ) {

    $choices = [];

    $path_to_plugins = dirname( dirname( __FILE__ ) );
    $path_to_themes = dirname( get_stylesheet_directory() );

    $load_dirs = acf_get_setting('load_json');

    if ( empty( $load_dirs ) )
        return;

    foreach ( $load_dirs as $load_dir )
    {
        $display_title = $load_dir;
        if ( strpos( $load_dir, $path_to_plugins ) !== false )
            $display_title = __( 'Plugin', 'acf-agency-workflow' ).': '.substr( str_replace( $path_to_plugins, '', $load_dir ), 1 );
        if ( strpos( $load_dir, $path_to_themes ) !== false )
        {
            $label = __( 'Theme', 'acf-agency-workflow' );
            if ( is_child_theme() )
            {
                if ( strpos( $load_dir, get_stylesheet_directory() ) !== false )
                    $label = __( 'Theme (child)', 'acf-agency-workflow' );
                if ( strpos( $load_dir, get_template_directory() ) !== false )
                    $label = __( 'Theme (parent)', 'acf-agency-workflow' );
            }

            $display_title = $label.': '.substr( str_replace( $path_to_themes, '', $load_dir ), 1 );
        }

        $choices[ $load_dir ] = $display_title;

        if ( file_exists( $load_dir.'/'.$field_group['key'].'.json' ) )
            $load_dir_selected = $load_dir;
    }

    asort( $choices );

    $choices = [ 'default' => __( 'Default' ) ] + $choices;

    acf_render_field_wrap([
        'label'        => __( 'JSON Save Path', 'acf-agency-workflow' ),
        'instructions' => __( 'Where do you want the Field Group saved?', 'acf-agency-workflow' ),
        'type'         => 'select',
        'name'         => 'json_save_path',
        'prefix'       => 'acf_field_group',
        'value'        => $load_dir_selected ?? 'default',
        'choices'      => $choices,
    ]);
});

/**
 * Get selected JSON save location.
 */

$global_preferred_save_path = false;

add_action( 'acf/update_field_group', function ( $field_group ) {

    // Skip everything if this is not a FGE location move.
    if ( !isset( $field_group['json_save_path'] ) )
        return $field_group;

    // Delete JSON cache for this field group.
    aaw_delete_field_group_from_json( $field_group['key'] );

    // Reset save location.
    global $global_preferred_save_path;
    $global_preferred_save_path = false;

    // Store save location.
    if ( $field_group['json_save_path'] != 'default' )
        $global_preferred_save_path = $field_group['json_save_path'];

    return $field_group;

}, 1, 1 );

/**
 * Set selected JSON save location.
 */

add_action( 'acf/settings/save_json', function ( $path ) {

    global $global_preferred_save_path;

    // Set save location.
    if ( $global_preferred_save_path )
        return $global_preferred_save_path;

    return $path;

}, 999 );

/**
 * Remove save path value before it gets written to JSON.
 */

add_filter( 'acf/prepare_field_group_for_export', function ( $field_group ) {

    if ( isset( $field_group['json_save_path'] ) )
        unset( $field_group['json_save_path'] );

    return $field_group;
});

/**
 * Display sync feedback.
 */

$aaw_feedback = [];

add_action( 'admin_notices', function () {

    global $aaw_feedback;

    if ( empty( $aaw_feedback ) )
        return;

    foreach ( $aaw_feedback as $feedback )
        printf( '<div class="%1$s">%2$s</div>', 'notice notice-success', $feedback );
});

/**
 * Display a warning in the Custom Fields backend.
 */

add_action( 'admin_notices', function () {

    if ( !defined( 'WP_ENV' ) || WP_ENV == 'development' )
        return;

    if ( !isset( $_GET['post_type'] ) || $_GET['post_type'] != 'acf-field-group' )
        return;

    if ( isset( $_GET['page'] ) && $_GET['page'] == 'acf-settings-updates' )
        return;

    $feedback = '';
    $feedback .= '<p><strong>'.__( 'Non-development environment detected!', 'acf-agency-workflow' ).'</strong></p>';
    $feedback .= '<p>'.__( 'You are not on development environment. Please do not add or edit Field Groups. If you do not know why this message is here, contact the developer who installed <em>ACF Agency Workflow</em> before continuing.', 'acf-agency-workflow' ).'</p>';
    printf( '<div class="%1$s">%2$s</div>', 'notice notice-warning', $feedback );
});

add_filter('plugin_action_links_'.plugin_basename(__FILE__), function ( $links ) {

    $url = esc_url( add_query_arg(
		'page',
		dirname( plugin_basename(__FILE__) ) . '-settings',
		get_admin_url() . 'options-general.php'
    ) );

    $settings_link = $settings_link = "<a href='$url'>" . __( 'Settings', 'acf-agency-workflow' ) . '</a>';

    array_push(	$links,	$settings_link );

    return $links;
});

add_action( 'admin_menu', 'aaw_settings_menu' );
function aaw_settings_menu() {
    add_submenu_page('options-general.php', __('ACF Agency Workflow', 'acf-agency-workflow'), __('ACF Agency Workflow', 'acf-agency-workflow'), 'manage_options', dirname( plugin_basename(__FILE__) ) . '-settings', function() {
        $plugin_slug = dirname( plugin_basename(__FILE__) );

        if ( !current_user_can( 'manage_options' ) )  {
		    return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
        }

        ?>
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
           <?php
            // output security fields for the registered setting "wporg"
            settings_fields( $plugin_slug . '-settings' );

            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections( $plugin_slug . '-settings' );

            // output save settings button
            submit_button( 'Save Settings' );
        ?>
        </form>
                <?php
    }, 99 );
}

function aaw_settings_init() {
    $plugin_slug = dirname( plugin_basename(__FILE__) );

    // register a new setting for "acf-agency-workflow-settings" page
    register_setting( $plugin_slug . '-settings', $plugin_slug . '-wpenv' );

    // register a new section in the "acf-agency-workflow-settings" page
    add_settings_section(
        $plugin_slug . '-settings-section-wpenv',
        __( 'WordPress Environment', 'acf-agency-workflow' ),
        'acf_agency_workflow_settings_section_wpenv_cb',
        $plugin_slug . '-settings'
    );

    // register a new field in the "wporg_section_developers" section, inside the "wporg" page
    add_settings_field(
        $plugin_slug . '-wpenv', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __( 'Environment slug', 'acf-agency-workflow' ),
        'acf_agency_workflow_settings_field_wpenv_cb',
        $plugin_slug . '-settings',
        $plugin_slug . '-settings-section-wpenv',
        [
            'label_for' => 'wporg_field_pill',
            'class' => 'wporg_row',
            'wporg_custom_data' => 'custom',
        ]
    );
}

// pill field cb

// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function acf_agency_workflow_settings_field_wpenv_cb( $args ) {
    $plugin_slug = dirname( plugin_basename(__FILE__) );

    // get the value of the setting we've registered with register_setting()
    $options = get_option( $plugin_slug . '-wpenv' );

    // output the field
    ?>
        <input name="<? echo $plugin_slug ?>-wpenv" type="text" id="<? echo $plugin_slug ?>-wpenv" value="<? echo get_option($plugin_slug . '-wpenv') ?>" class="regular-text ltr">
        <p class="description">
        <?php esc_html_e( 'Field description goes here.', 'acf-agency-workflow' ); ?>
        </p>
        <?php
}
// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function acf_agency_workflow_settings_section_wpenv_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Explain why setting the WordPress environment slug is important, and what it does to the functionality of ACF Agency Workflow.', 'acf-agency-workflow' ); ?></p>
    <?php
}
/**
 * register our wporg_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'aaw_settings_init' );
