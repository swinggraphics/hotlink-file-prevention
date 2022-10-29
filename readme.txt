=== Hotlink File Prevention ===
Contributors: electricmill, swinggraphics
Tags: admin, attachments, files, hotlink, images, media
Requires at least: 3.8
Tested up to: 6.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple hotlink protection for individual files in the media library.

== Description ==

Hotlink File Prevention (HFP) offers simple hotlink protection that can be turned on/off for individual files in the WordPress media library.

"Hotlinking" is when a file, such as an image or PDF, is linked to from another website or entered manually in a web browser's location bar. HFP only allows your file to be viewed on your website.

Hotlink protection is provided via `.htaccess` rules in the `wp-content/uploads` directory.

== Basic Usage ==

Once the HFP plugin is activated, you will have two new features in the media library:

1. Within the Screen Options tab (list view only), check box for the "Hotlink Prevention" column.
1. To protect a file, edit the file and scroll down to the checkbox labelled "Hotlink Protection".

Any asset that is checked will have "Yes" displayed in the "Hotlink Prevention" column; otherwise, this column will be blank.

= Note about "Open in new tab" option =

When you use the "Open in new tab" option for links, WordPress adds `rel="noreferrer"`, which effectively makes the link act like direct access, and the link will be blocked for files protected using HFP.

== Installation ==

1. Go to "Plugins > Add New" in the WordPress admin area.
1. Search for "Hotlink File Prevention".
1. Install, then Activate the plugin.

For more installation options and instructions, see ["Installing Plugins" on WordPress.org](https://wordpress.org/support/article/managing-plugins/#installing-plugins).

== Frequently Asked Questions ==

= How does HFP work?

HFP creates an Apache `.htaccess` file in the `wp-content/uploads` directory. It sets a HTTP_REFERER check and RewriteRule for each file that has hotlink protection applied. Toggling hotlink protection on/off dynamically adds/removes RewriteRule statements.

= Can it be used with any type of media file?

Yes, it works with any file that you upload to the media library.

= Are my files absolutely safe using this plugin?

Here's the deal: This plugin makes it harder for people to hotlink to your files, but if they are highly technical, they will be able to do things like fake the HTTP referrer.

= Does it with with web servers other than Apache?

The server must process rewrite rules in `.htaccess`. So HFP will work on Apache and LightSpeed servers, but not NGINX.

== Screenshots ==

1. Checkbox in the file edit dialog

== Upgrade Notice ==

= 2.0.0 =
* Actually works with multiple files now
* Works cleanly when deactivated and reactivated

= 1.1.0 =
* Updated to work with newer versions of WordPress.

== Changelog ==

= 2.0.0 =
* Track protected files in wp_options table instead of individual post meta
* Fixed htaccess rules to work with multiple protected files
* Use insert_with_markers() to handle writing to htaccess
* Added CSS for media library table column
* Added uninstall hook

= 1.1.0 =
* Modernized the code, and got it working again!
* Updated README
* Changed some strings, and made them translation-ready
* Moved Hotlink Protection column before Date
* Added deactivation hook to remove htaccess file

= 1.0.1 =
* commented out error reporting
* now uses just filename (followed by full path and name) in .htaccess; previously this was full path and location alone. Version 1.0.0 users should delete .htaccess from /uploads directory and rebuild file by clicking "update" on any file in Media Library that has "Yes" for Hotlink File Prevention.

= 1.0.0 =
* Development version and Alpha release.
