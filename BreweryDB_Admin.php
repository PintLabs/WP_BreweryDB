<?php 
/*
 * @copyright 2013
 * @version 2.0.0
 * @author Shaun Farrell - PintLabs L.L.C.
 * @link http://www.brewerydb.com/
 * @package WP_BreweryDB
 * 
 */

class BreweryDB_Admin {

	function BreweryDB_Admin() {
		register_activation_hook('BreweryDb_Admin', 'activate_plugin');
		register_deactivation_hook('BreweryDb_Admin', 'deactivate_plugin');
	}
	
	function activate_plugin() {
		add_option('brewerydb_apikey', '');
		add_option('brewerydb_cachetime' , 86400);
	}
	
	function deactivate_plugin() {
		delete_option('brewerydb_apikey');
		delete_option('brewerydb_cachetime');
	}
	
	function admin_menu() {
		add_options_page('Brewery DB Options', 'Brewery DB', 'manage_options', 'my-unique-identifier', array('BreweryDb_Admin', 'my_plugin_options'));
	}
	
	function my_plugin_options() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		if ($_POST) {
			update_option('brewerydb_apikey', $_POST['brewerydb_apikey']);
			update_option('brewerydb_cachetime', $_POST['brewerydb_cachetime']);
		}
		?>
		<div class="wrap">
			<h2>Brewery DB Options</h2>
			<p>You can register for an API key at <a href="http://www.brewerydb.com/developers">http://www.brewerydb.com/developers</a></p>
			<form method="post">
    			<table class="form-table">
        			<tr valign="top">
        				<th scope="row">API Key</th>
        				<td><input type="text" name="brewerydb_apikey" value="<?php echo get_option('brewerydb_apikey'); ?>" /></td>
        			</tr>
         
			        <tr valign="top">
			        	<th scope="row">Cache Expiration</th>
			        	<td><input type="text" name="brewerydb_cachetime" value="<?php echo get_option('brewerydb_cachetime'); ?>" /></td>
			        </tr>
			        
			    </table>
    
    			<p class="submit">
    				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    			</p>
    		</form>
		</div>
	 <?php
	}
}
