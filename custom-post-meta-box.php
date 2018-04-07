<?php
/*
Plugin Name:  Custom Post Metabox
Plugin URI:   http://saugatbhattarai.com.np/
Description:  A Custom Post MetaBox
Version:      1.0.0
Author:       Saugat Bhattarai
Author URI:   http://saugatbhattarai.com.np/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
*/

function cpmb_add_admin_styles() {
	wp_enqueue_style('cpmb-admin',plugins_url('custom-post-meta-box/css/admin.css'));
}
add_action('admin_enqueue_scripts','cpmb_add_admin_styles');

function cpmb_add_admin_scripts() {

	$screen = get_current_screen();
	if( 'post' == $screen->id ) {
		wp_enqueue_script('cpmb-admin',plugins_url( 'custom-post-meta-box/js/admin.js' ) );
	}
}
add_action('admin_enqueue_scripts','cpmb_add_admin_scripts');

function cpmb_add_meta_box() {

	add_meta_box(
		'cpmb_audio',
		'Add MP3',
		'cpmb_display_meta_box',
		'post',
		'side',
		'core'
	);
}

add_action('add_meta_boxes','cpmb_add_meta_box');

function cpmb_display_meta_box( $post ) {

	wp_nonce_field( plugin_basename(__FILE__), 'cpmb-nonce-field'); 
	
	$html = '';

	if ( 'invalid-file-type' == get_post_meta($post->ID, 'mp3' , true ) ) {

		$html .= "<div id ='invalid-file-type' class='error'>";
			$html .= '<p> You are trying to upload a file other than webm. </p>';
		$html .= '</div>';
	}

	$html .= '<label id="mp3-title" for="mp3-title">';
		$html .= 'Title of MP3';
	$html .= '</label> '; 
	$html .= '<input type="text" id="mp3-title" name="mp3-title" value="' .get_post_meta($post->ID,'mp3-title',true).'" placeholder="You are Beautiful by James Blunt">';
	$html .= '<label id="mp3-file" for="mp3-file">';
		$html .= 'MP3 File';
	$html .= '</label> '; 
	$html .= '<input type="file" id="mp3-file" name="mp3-file" value="">';
	
	echo $html;
}


function cpmb_save_meta_box_data($post_id) {

	if ( cpmb_user_can_save($post_id,'cpmb-nonce-field')) {


		if( isset($_POST['mp3-title']) && 0 < count(strlen(trim($_POST['mp3-title'] ) ) ) ){

			$mp3_title = stripslashes(strip_tags($_POST['mp3-title']));
			update_post_meta($post_id,'mp3-title',$mp3_title);
			
		}

		if ( isset( $_FILES['mp3-file'] ) && !empty( $_FILES['mp3-file'] ) ) {

			if ( cpmb_is_valid_mp3( $_FILES['mp3-file']['name'] ) ) {
				$response = wp_upload_bits( $_FILES['mp3-file']['name'],null, file_get_contents($_FILES['mp3-file']['tmp_name'] ) );

				if (0 == strlen(trim($response['error']))) {

					update_post_meta($post_id,'mp3',$response['url']);
				}
			}
			else {
				update_post_meta( $post_id,'mp3','invalid-file-type');
			}
		}
	}
}
add_action('save_post','cpmb_save_meta_box_data');

function cpmb_display_mp3( $content ) {
	
	if ( is_single()) {

		if ( 'invalid-file-type' != get_post_meta(get_the_ID(), 'mp3' , true ) ) {
			$html = '<a href="' .get_post_meta(get_the_ID(), "mp3", true ). '">';
				$html .= get_post_meta( get_the_ID(),'mp3-title',true );
			$html .= '</a>';
			$content .= $html;
		}
	}

	return $content;
}
add_action('the_content','cpmb_display_mp3');

function cpmb_is_valid_mp3( $filename) {

	$path_parts = pathinfo( $filename );
	return 'webm' == strtolower( $path_parts['extension']);

}

function cpmb_user_can_save($post_id,$nonce) {

	$is_autosave = wp_is_post_autosave( $post_id );
	$is_revision = wp_is_post_revision( $post_id );
	$is_valid_nonce = (isset($_POST[ $nonce ]) && wp_verify_nonce($_POST[ $nonce ], plugin_basename(__FILE__)));

	return ! ($is_autosave || $is_revision) && $is_valid_nonce;
}