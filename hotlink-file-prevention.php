<?php
/**
 * Plugin Name: Hotlink File Prevention
 * Plugin URI:
 * Description: Protect individual files from being hotlinked.
 * Version: 1.1.0
 * Author: Greg Perham
 * Author URI: https://github.com/swinggraphics
 * Credits: Original version by Kevin Peyton (electricmill)
 * Requires at least: 4.6
 * Requires PHP: 5.6
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotlink-file-prevention
 */

/**
 * @TODO: check for compatibility on activation
 * @TODO: track ids in options so we can recreate rules upon reactivation
 * @TODO: target our rules with BEGIN and END comments, in case other plugins also edit htaccess
 * @TODO: add global hotlink prevention option
 * @TODO: add multisite compatibility
 */


// Block direct access
defined( 'WPINC' ) || die;


if ( ! class_exists( 'HFP_Plugin' ) ) {

	class HFP_Plugin {

		private $upload_url;
		private $filename_htaccess;
		private $htaccess_header;

		public function __construct() {

			$wp_upload_dir = wp_upload_dir();
			$this->upload_url = $wp_upload_dir[ 'baseurl' ] . '/';
			$this->filename_htaccess = $wp_upload_dir[ 'basedir' ] . '/.htaccess';

			$this->htaccess_header = "# BEGIN Hotlink File Prevention\n";
			$this->htaccess_header = "RewriteEngine On\n";
			$this->htaccess_header .= "RewriteCond %{HTTP_REFERER} !^" . trailingslashit( get_site_url() ) . " [NC]";

			add_action( 'admin_init', array( &$this, 'hfp_columns' ) );
			add_filter( 'attachment_fields_to_edit', array( &$this, 'hfp_attachment_fields_edit' ), null, 2 );
			add_filter( 'attachment_fields_to_save', array( &$this, 'hfp_attachment_fields_save' ), null, 2 );
			add_action( 'delete_post', array( &$this, 'hfp_delete_attachment' ), 10 );

			register_deactivation_hook( __FILE__, array( &$this, 'deactivate_hfp' ) );

		}


		public function hfp_attachment_fields_edit( $form_fields, $post ) {

			$hfp_prevention = (bool) get_post_meta( $post->ID, '_hfp_prevention', true );
			$checked = $hfp_prevention ? 'checked' : '';
			$name = "attachments[{$post->ID}][hfp_prevention]";

			$form_fields[ 'hfp' ] = array(
				'label' => '',
				'input' => 'html',
				'html'  => "<input type='checkbox' {$checked} name='{$name}' id='{$name}' /> <label for='{$name}'>" . __( 'Hotlink Protection', 'hotlink-file-prevention' ) . "</label>",
				'value' => $hfp_prevention,
				'helps' => __( 'Block access from other sites and direct URL', 'hotlink-file-prevention' ),
			);
			return $form_fields;

		}


		public function hfp_attachment_fields_save( $post, $attachment ) {

			if ( isset( $attachment[ 'hfp_prevention' ] ) ) {
				$hfp_prevention = ( 'on' == $attachment[ 'hfp_prevention' ] ) ? 1 : 0;
			} else {
				$hfp_prevention = 0;
			}
			update_post_meta( $post[ 'ID' ], '_hfp_prevention', $hfp_prevention );
			$this->update_hfp_htaccess( $post[ 'ID' ], $hfp_prevention );
			return $post;

		}


		public function hfp_delete_attachment( $post_id ) {

			if ( 'attachment' == get_post_type( $post_id ) ) {
				$this->update_hfp_htaccess( $post_id, 0 );
			}

		}


		private function update_hfp_htaccess( $post_id, $hfp_prevention ) {

			$found = false;
			if ( file_exists( $this->filename_htaccess ) ) {
				$htaccess = explode( "\n", file_get_contents( $this->filename_htaccess ) );
			} else {
				$htaccess = explode( "\n", $this->htaccess_header );
			}

			$media_url = wp_get_attachment_url( $post_id );
			$media_filepath = str_replace( $this->upload_url, '', $media_url );
			foreach ( $htaccess as $index => $line ) {
				if ( strstr( $line, $media_filepath ) ) {
					if ( 0 == $hfp_prevention ) unset( $htaccess[ $index ] );
					$found = true;
					break;
				}
			}
			if ( ! $found && 1 == $hfp_prevention ) {
				array_push( $htaccess, "RewriteRule $media_filepath - [NC,L,F]" );
			}
			file_put_contents( $this->filename_htaccess, implode( "\n", $htaccess ) );

		}


		// Media Library list view column
		public function hfp_columns() {

			add_filter( 'manage_media_columns', array( &$this, 'hfp_column' ) );
			add_action( 'manage_media_custom_column', array( &$this, 'hfp_column_value' ), 10, 2 );
			add_filter( 'manage_upload_sortable_columns', array( &$this, 'hfp_column_sortable' ) );

		}

		public function hfp_column( $cols ) {

			$date = $cols[ 'date' ];
			unset( $cols[ 'date' ] );
			$cols[ 'hfp' ] = __( 'Hotlink Protection', 'hotlink-file-prevention' );
			$cols[ 'date' ] = $date;
			return $cols;

		}

		public function hfp_column_value( $column_name, $id ) {

			$meta = wp_get_attachment_metadata( $id );
			$hfp = get_post_meta( $id, '_hfp_prevention', true );
			if ( 1 == $hfp ) _e( 'Yes', 'hotlink-file-prevention' );

		}

		public function hfp_column_sortable( $cols ) {

			$cols[ 'hfp' ] = 'hfp';
			return $cols;

		}


		public function deactivate_hfp() {

			if ( file_exists( $this->filename_htaccess ) ) {
				unlink( $this->filename_htaccess );
			}

		}

	} // end HFP_Plugin

	// Run
	$hfp_plugin = new HFP_Plugin();

} // end if class_exists
