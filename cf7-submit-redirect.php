<?php

/**
 * @link              http://store.wphound.com/?plugin=cf7-submit-redirect
 * @since             1.0.0
 * @package           Cf7_Submit_Redirect
 *
 * @wordpress-plugin
 * Plugin Name:       Cf7 Submit Redirect
 * Plugin URI:        http://store.wphound.com/?plugin=cf7-submit-redirect
 * Description:      An add-on for Contact Form 7 Plugin that redirect visitors to success pages or thank you pages after submission of form. 
 * Version:           1.0.0
 * Author:            WP Hound
 * Author URI:        http://www.wphound.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cf7-submit-redirect
 */


function cf7_submit_success_page_admin_notice() {
    if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
        $wpcf7_path = plugin_dir_path( dirname(__FILE__) ) . 'contact-form-7/wp-contact-form-7.php';
        $wpcf7_plugin_data = get_plugin_data( $wpcf7_path, false, false);
        $wpcf7_version = (int)preg_replace('/[.]/', '', $wpcf7_plugin_data['Version']);
        if ( $wpcf7_version < 100 ) {
            $wpcf7_version = $wpcf7_version * 10;
        }
        if ( $wpcf7_version < 390 ) {
            echo '<div class="error"><p><strong>Warning: </strong>Contact Form 7 - version 4.0.0 and above required for Submit Redirect Plugin use. Please upgrade now.</p></div>';
        }
    }
    else {
        echo '<div class="error"><p>Contact Form 7 is not activated. The Contact Form 7 Plugin must be installed and activated before you can use Cf7 Submit Redirect.</p></div>';
    }
}
add_action( 'admin_notices', 'cf7_submit_success_page_admin_notice' );



add_filter( 'wpcf7_load_js', '__return_false' );



function cf7_submit_redirect_page_add_meta_boxes() {
    add_meta_box( 'cf7-submit-redirect-settings', 'Submit Redirect Page', 'cf7_submit_redirect_page_metaboxes', '', 'form', 'low');
}
add_action( 'wpcf7_add_meta_boxes', 'cf7_submit_redirect_page_add_meta_boxes' );


function cf7_submit_redirect_add_page_panels($panels) {
	?><style>li#submit-redirect-panel-tab a {background: #0073aa; color:#ffffff;}</style>
	<?php 
    $panels['submit-redirect-panel'] = array( 'title' => 'Add Redirect Page', 'callback' => 'cf7_success_page_panel_meta' );
    return $panels;
}
add_action( 'wpcf7_editor_panels', 'cf7_submit_redirect_add_page_panels' );



function cf7_submit_redirect_page_metaboxes( $post ) {
    wp_nonce_field( 'cf7_submit_redirect_page_metaboxes', 'cf7_submit_redirect_page_metaboxes_nonce' );
    $cf7_success_pages = get_post_meta( $post->id(), '_cf7_submit_success_page_key', true );


    $dropdown_options = array (
            'echo' => 0,
            'name' => 'cf7-submit-redirect-page-id', 
            'show_option_none' => '-Select a Redirect page-', 
            'option_none_value' => '0',
            'selected' => $cf7_success_pages
        );

    echo '<fieldset>
            <legend>Select a page that redirect on successfully submission of form.</legend>' .
            wp_dropdown_pages( $dropdown_options ) .
         '</fieldset>';
}
function cf7_success_page_panel_meta( $post ) { ?>

	<?php 
    wp_nonce_field( 'cf7_submit_redirect_page_metaboxes', 'cf7_submit_redirect_page_metaboxes_nonce' );
    $cf7_success_pages = get_post_meta( $post->id(), '_cf7_submit_success_page_key', true );

    $dropdown_options = array (
            'echo' => 0,
            'name' => 'cf7-submit-redirect-page-id', 
            'show_option_none' => '-Select a Redirect page-', 
            'option_none_value' => '0',
            'selected' => $cf7_success_pages
        );?>
<div id=redirectdiv>
<?php 
    echo '<h3 id="redirecttitle">Redirect Page Settings</h3>
          <fieldset>
            <legend id=redirecttagline>Select a page that redirect on successfully submission of form.</legend>' .
            wp_dropdown_pages( $dropdown_options ) .
         '</fieldset>'; ?></div><?php 
}

function cf7_submit_success_page_save_contact_form( $contact_form ) {
    $contact_form_id = $contact_form->id();

    if ( !isset( $_POST ) || empty( $_POST ) || !isset( $_POST['cf7-submit-redirect-page-id'] ) ) {
        return;
    }
    else {
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['cf7_submit_redirect_page_metaboxes_nonce'], 'cf7_submit_redirect_page_metaboxes' ) ) {
            return;
        }
        // Update the stored value
        update_post_meta( $contact_form_id, '_cf7_submit_success_page_key', absint($_POST['cf7-submit-redirect-page-id'] ));
    }
}
add_action( 'wpcf7_after_save', 'cf7_submit_success_page_save_contact_form' );



function cf7_success_page_after_form_create( $contact_form ){
    $contact_form_id = $contact_form->id();


    if ( !empty( $_REQUEST['post'] ) && !empty( $_REQUEST['_wpnonce'] ) ) {
        $old_form_id = get_post_meta( $_REQUEST['post'], '_cf7_submit_success_page_key', true );
    }
    update_post_meta( $contact_form_id, '_cf7_submit_success_page_key', absint($old_form_id ));
}
add_action( 'wpcf7_after_create', 'cf7_success_page_after_form_create' );



function cf7_submit_success_page_form_submitted( $contact_form ) {
    $contact_form_id = $contact_form->id();

    $success_page = get_post_meta( $contact_form_id, '_cf7_submit_success_page_key', true );
    if ( !empty($success_page) ) {
        wp_redirect( get_permalink( $success_page ) );
        die();
    }
}
add_action( 'wpcf7_mail_sent', 'cf7_submit_success_page_form_submitted' );
