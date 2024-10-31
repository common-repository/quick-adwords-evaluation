<?php
/*
Plugin Name: Quick AdWords Evaluation
Plugin URI: https://getavalanche.com/free-audit
Description: Have a Certified AdWords Professional evaluate and provide advice for your AdWords account.
Version: 0.1
Author: Joe Giancaspro
Author URI: https://getavalanche.com
*/

/*  Copyright 2017 Joe Giancaspro (email : joe@getavalanche.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

// ------------------------------------------------------------------------
// REQUIRE MINIMUM VERSION OF WORDPRESS:                                               
// ------------------------------------------------------------------------
// THIS IS USEFUL IF YOU REQUIRE A MINIMUM VERSION OF WORDPRESS TO RUN YOUR
// PLUGIN. IN THIS PLUGIN THE WP_EDITOR() FUNCTION REQUIRES WORDPRESS 3.3 
// OR ABOVE. ANYTHING LESS SHOWS A WARNING AND THE PLUGIN IS DEACTIVATED.                    
// ------------------------------------------------------------------------

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function adwdseval_requires_wordpress_version() {
	global $wp_version;
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );

	if ( version_compare($wp_version, "3.3", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
		}
	}
}
add_action( 'admin_init', 'adwdseval_requires_wordpress_version' );

// ------------------------------------------------------------------------
// PLUGIN PREFIX:                                                          
// ------------------------------------------------------------------------
// A PREFIX IS USED TO AVOID CONFLICTS WITH EXISTING PLUGIN FUNCTION NAMES.
// WHEN CREATING A NEW PLUGIN, CHANGE THE PREFIX AND USE YOUR TEXT EDITORS 
// SEARCH/REPLACE FUNCTION TO RENAME THEM ALL QUICKLY.
// ------------------------------------------------------------------------

// 'adwdseval_ prefix 

// ------------------------------------------------------------------------
// REGISTER HOOKS & CALLBACK FUNCTIONS:
// ------------------------------------------------------------------------
// HOOKS TO SETUP DEFAULT PLUGIN OPTIONS, HANDLE CLEAN-UP OF OPTIONS WHEN
// PLUGIN IS DEACTIVATED AND DELETED, INITIALISE PLUGIN, ADD OPTIONS PAGE.
// ------------------------------------------------------------------------

// Set-up Action and Filter Hooks
register_uninstall_hook(__FILE__, 'adwdseval_delete_plugin_options');
add_action('admin_init', 'adwdseval_init');
add_action('admin_menu', 'adwdseval_add_options_page');
add_filter('plugin_action_links', 'adwdseval_plugin_action_links', 10, 2);

// --------------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_uninstall_hook(__FILE__, 'adwdseval_delete_plugin_options')
// --------------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE USER DEACTIVATES AND DELETES THE PLUGIN. IT SIMPLY DELETES
// THE PLUGIN OPTIONS DB ENTRY (WHICH IS AN ARRAY STORING ALL THE PLUGIN OPTIONS).
// --------------------------------------------------------------------------------------

// Delete options table entries ONLY when plugin deactivated AND deleted
function adwdseval_delete_plugin_options() {
	delete_option('adwdseval_options');
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_init', 'adwdseval_init' )
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_init' HOOK FIRES, AND REGISTERS YOUR PLUGIN
// SETTING WITH THE WORDPRESS SETTINGS API. YOU WON'T BE ABLE TO USE THE SETTINGS
// API UNTIL YOU DO.
// ------------------------------------------------------------------------------

// Init plugin options to white list our options
function adwdseval_init(){
	register_setting( 'adwdseval_plugin_options', 'adwdseval_options', 'adwdseval_validate_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_menu', 'adwdseval_add_options_page');
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_menu' HOOK FIRES, AND ADDS A NEW OPTIONS
// PAGE FOR YOUR PLUGIN TO THE SETTINGS MENU.
// ------------------------------------------------------------------------------

// Add menu page
function adwdseval_add_options_page() {
	add_menu_page('AdWords Evaluation', 'AdWords Evaluation', 'manage_options', __FILE__, 'adwdseval_render_form');
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION SPECIFIED IN: add_options_page()
// ------------------------------------------------------------------------------
// THIS FUNCTION IS SPECIFIED IN add_options_page() AS THE CALLBACK FUNCTION THAT
// ACTUALLY RENDER THE PLUGIN OPTIONS FORM AS A SUB-MENU UNDER THE EXISTING
// SETTINGS ADMIN MENU.
// ------------------------------------------------------------------------------

// Render the Plugin options form
function adwdseval_render_form() {
	?>
	<div class="wrap">
		<?php $options = get_option('adwdseval_options'); ?>

    <?php if(empty($options['api_key'])) { ?>
      <h2>Get a Free AdWords Evaluation</h2>
      <p>
        Have a Google Certified analyst review your AdWords account to highlight areas of improvement. 
        This free service is provided by <a href="https://getavalanche.com">Avalanche Media</a>, a Google Partner and advocate for 
        high quality and effective digital advertising campaigns.
      </p>
      <p>To get started, all you need to do is link your AdWords account and get an API Key from the link below.</p>
      <p>
        <a href="http://wp.getavalanche.com" target="_blank" class="button button-primary">Obtain API Key</a>
      </p>
    <?php } else { ?> 
      <?php adwdseval_fetch_from_api($options['api_key']); ?>
    <?php } ?>

		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
		
      <?php settings_fields('adwdseval_plugin_options'); ?>
			<!-- Table Structure Containing Form Controls -->
			<!-- Each Plugin Option Defined on a New Table Row -->
			<table class="form-table">
				<tr>
					<th scope="row">API Key</th>
					<td>
						<input type="text" size="57" name="adwdseval_options[api_key]" value="<?php echo $options['api_key']; ?>" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
			</p>

		</form>
	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, returns a sanitized array.
function adwdseval_validate_options( $input ) {
	 // strip html from textboxes
	$input['api_key'] =  wp_filter_nohtml_kses($input['api_key']); // Sanitize input (strip html tags, and escape characters)

  if(empty($input['api_key'])) {
    add_settings_error(
      'adwdseval_options',
      'key-blank',
      'API Key is required.',
      'error'
    );
  }
 	return $input;
}

function adwdseval_fetch_from_api($api_key) {
  $request = wp_remote_get( 'http://wp.getavalanche.com/api/accounts?api_key='.$api_key );
  if( is_wp_error( $request ) ) {
    return false;
  }
  $body = wp_remote_retrieve_body( $request );
  $data = json_decode( $body );
  if ( $data->error ) {
    echo '<p>You\'re account was not found. Please obtain a new <a href="http://wp.getavalanche.com" target="_blank">API Key</a> or email <a href="mailto:support@getavalanche.com">support@getavalanche.com</a></p>'; 
  } else {
    echo $data->html_response;
  }
}

// Display a Settings link on the main Plugins page
function adwdseval_plugin_action_links( $links, $file ) {
  if ( $file == plugin_basename( __FILE__ ) ) {
		$adwdseval_links = '<a href="'.get_admin_url().'options-general.php?page=quick-adwords-evaluation/quick-adwords-evaluation.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $adwdseval_links );
	}

	return $links;
}
