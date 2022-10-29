<?php
/**
 * Plugin Name: Hotlink File Prevention
 * Plugin URI:
 * Description: Protect individual files from being hotlinked.
 * Version: 2.0.0
 * Author: Greg Perham
 * Author URI: https://github.com/swinggraphics
 * Credits: Original version by Kevin Peyton (electricmill)
 * Requires at least: 4.6
 * Tested up to: 6.1
 * Requires PHP: 5.6
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotlink-file-prevention
 */

/**
 * @TODO: check for compatibility on activation
 * @TODO: add global hotlink prevention option
 * @TODO: test multisite compatibility
 */


// Block direct access
defined( 'WPINC' ) || die;


if ( ! class_exists( 'HFP_Plugin' ) ) {

	class HFP_Plugin {

		private $hfp_ids;
		static $option_name = 'hfp_protected_files';

		public function __construct() {

			$this->hfp_ids = get_option( self::$option_name, array() );

			add_action( 'admin_init', array( &$this, 'hfp_columns' ) );
			add_filter( 'attachment_fields_to_edit', array( &$this, 'hfp_attachment_fields_edit' ), null, 2 );
			add_filter( 'attachment_fields_to_save', array( &$this, 'hfp_attachment_fields_save' ), null, 2 );
			add_action( 'delete_post', array( &$this, 'hfp_delete_attachment' ), 10 );

			register_activation_hook( __FILE__, array( &$this, 'activate_hfp' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate_hfp' ) );

		}


		public function hfp_attachment_fields_edit( $form_fields, $post ) {

			$hfp_protect = $this->is_protected( $post->ID );
			$checked = $hfp_protect ? 'checked' : '';
			$name = "attachments[{$post->ID}][hfp_protect]";

			$form_fields[ 'hfp' ] = array(
				'label' => '',
				'input' => 'html',
				'html'  => "<input type='checkbox' {$checked} name='{$name}' id='{$name}' /> <label for='{$name}'>" . __( 'Hotlink Protection', 'hotlink-file-prevention' ) . "</label>",
				'value' => $hfp_protect,
				'helps' => __( 'Block access from other sites and direct URL', 'hotlink-file-prevention' ),
			);
			return $form_fields;

		}


		public function hfp_attachment_fields_save( $post, $attachment ) {

			if ( isset( $attachment[ 'hfp_protect' ] ) ) {
				$hfp_protect = ( 'on' == $attachment[ 'hfp_protect' ] ) ? true : false;
			} else {
				$hfp_protect = false;
			}
			delete_post_meta( $post[ 'ID' ], '_hfp_prevention' ); // remove in future
			$this->update_hfp_ids( $post[ 'ID' ], $hfp_protect );
			return $post;

		}


		public function hfp_delete_attachment( $post_id ) {

			if ( 'attachment' == get_post_type( $post_id ) ) {
				$this->update_hfp_ids( $post_id, false );
			}

		}


		private function is_protected( $post_id ) {

			return in_array( $post_id, $this->hfp_ids );

		}


		private function update_hfp_ids( $post_id, $hfp_protect ) {

			if ( $hfp_protect ) {
				if ( ! $this->is_protected( $post_id ) ) {
					$this->hfp_ids[] = $post_id;
				}
			} else {
				if ( ( $k = array_search( $post_id, $this->hfp_ids ) ) !== false ) {
					unset( $this->hfp_ids[ $k ] );
				}
			}
			if ( ! $post_id && ! $this->hfp_ids ) return;
			update_option( self::$option_name, $this->hfp_ids, 'no' );

			$this->update_hfp_htaccess();

		}


		private function update_hfp_htaccess() {

			$wp_upload_dir = wp_upload_dir();
			$upload_url = trailingslashit( $wp_upload_dir[ 'baseurl' ] );
			$upload_dir = trailingslashit( $wp_upload_dir[ 'basedir' ] );
			$htaccess_file = $upload_dir . '.htaccess';

			$hfp_htaccess = array();
			if ( $hfp_count = count( $this->hfp_ids ) ) {

				$hfp_htaccess[] = 'RewriteEngine On';
				$hfp_htaccess[] = 'RewriteCond %{HTTP_REFERER} ^' . trailingslashit( get_site_url() ) . ' [NC]';
				$hfp_htaccess[] = "RewriteRule .? - [S=$hfp_count]";

				foreach ( $this->hfp_ids as $id ) {
					$media_url = wp_get_attachment_url( $id );
					$media_filepath = str_replace( $upload_url, '', $media_url );
					$hfp_htaccess[] = "RewriteRule $media_filepath - [NC,L,F]";
				}

			}

			if ( ( ! file_exists( $htaccess_file ) && is_writable( $upload_dir ) ) || is_writable( $htaccess_file ) ) {
				insert_with_markers( $htaccess_file, 'Hotlink File Prevention', $hfp_htaccess );
			}

		}


		// Media Library list view column
		public function hfp_columns() {

			add_filter( 'manage_media_columns', array( &$this, 'hfp_column' ) );
			add_action( 'manage_media_custom_column', array( &$this, 'hfp_column_value' ), 10, 2 );
			add_filter( 'manage_upload_sortable_columns', array( &$this, 'hfp_column_sortable' ) );
			add_action( 'admin_head', function() {
				echo "<style>.upload-php .column-hfp {width:10.75em;}</style>\n";
			} );

		}

		public function hfp_column( $cols ) {

			$date = $cols[ 'date' ];
			unset( $cols[ 'date' ] );
			$cols[ 'hfp' ] = __( 'Hotlink Protection', 'hotlink-file-prevention' );
			$cols[ 'date' ] = $date;
			return $cols;

		}

		public function hfp_column_value( $column_name, $post_id ) {

			if ( $this->is_protected( $post_id ) ) _e( 'Yes', 'hotlink-file-prevention' );

		}

		public function hfp_column_sortable( $cols ) {

			$cols[ 'hfp' ] = 'hfp';
			return $cols;

		}


		public function activate_hfp() {

			$this->update_hfp_ids( 0, false );
			register_uninstall_hook( __FILE__, 'HFP_Plugin::uninstall_hfp' );

		}

		public function deactivate_hfp() {

			$this->hfp_ids = array();
			$this->update_hfp_htaccess();

		}

		public static function uninstall_hfp() {

			delete_option( self::$option_name );

			$wp_upload_dir = wp_upload_dir();
			$htaccess_file = trailingslashit( $wp_upload_dir[ 'basedir' ] ) . '.htaccess';
			$htaccess_content = file_get_contents( $htaccess_file );
			$r = "/# BEGIN Hotlink File Prevention[\s\S]+?# END Hotlink File Prevention/si";
			$htaccess_content = trim( preg_replace( $r, '', $htaccess_content ) );

			if ( $htaccess_content ) {
				file_put_contents( $htaccess_file, $htaccess_content );
			} else {
				unlink( $htaccess_file );
			}

		}

	} // end HFP_Plugin

	// Run
	$hfp_plugin = new HFP_Plugin();

} // end if class_exists
