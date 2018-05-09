<?php
/*
Plugin Name:  Disqus Importer
Plugin URI:   https://kaushalsubedi.com/
Description:  Imports Disqus Plugins
Version:      1.0
Author:       Kaushal Subedi
Author URI:   https://kaushalsubedi.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once("admin/admin.php");

add_action( 'admin_menu', 'DisqusImportAdmin::registerAdminView' );

add_action('upload_mimes', 'allowedMimes');

function allowedMimes($mimes = array()) {

	$mimes['xml'] = "application/xml";

	return $mimes;
}