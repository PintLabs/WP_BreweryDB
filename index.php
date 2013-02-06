<?php
/**
 * Plugin Name: BreweryDB
 * Plugin URI: http://www.brewerydb.com/
 * Description: BreweryDB
 * Version: 2.0.0
 * Author: Shaun Farrell - PintLabs L.L.C.
 * Author URI: http://www.pintlabs.com/
 *
 * @copyright 2013
 * @version 2.0.0
 * @author Shaun Farrell - PintLabs L.L.C.
 * @link http://www.brewerydb.com/
 * @package WP_BreweryDB
 */

require_once 'BreweryDB.php';
require_once 'BreweryDB_Admin.php';
$brewerydb = new BreweryDB();

register_activation_hook( __FILE__, array( 'BreweryDB_Admin', 'activate_plugin' ) );
register_deactivation_hook( __FILE__, array( 'BreweryDB_Admin', 'deactivate_plugin' ) );

if ( is_admin() )
	add_action('admin_menu', array('BreweryDB_Admin', 'admin_menu'));