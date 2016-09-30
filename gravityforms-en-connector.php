<?php
/*
Plugin Name: Gravity Forms Engaging Networks Connector
Plugin URI: 
Description: Sends Gravity Form data to Engaging Networks
Version: 1.0
Author: Stefan Dürrenberger, Greenpeace Switzerland
Text Domain: enaddon
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gravity Forms Engaging Networks Connector is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Gravity Forms Engaging Networks Connector is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Gravity Forms Engaging Networks Connector. 
If not, see http://www.gnu.org/licenses/gpl-2.0.html
*/


define( 'GF_EN_ADDON_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_En_AddOn_Bootstrap', 'load' ), 5 );

class GF_En_AddOn_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gfenaddon.php' );

		GFAddOn::register( 'GFEnAddOn' );
	}
}

function gf_en_addon() {
	return GFEnAddOn::get_instance();
}


// Define new cron schedule every 5 minutes
function gf_en_cron_schedules($schedules){
	if(!isset($schedules["5min"])){
		$schedules["5min"] = array(
			'interval' => 5*60,
			'display' => __('Once every 5 minutes'));
	}
	if(!isset($schedules["1min"])){
		$schedules["1min"] = array(
			'interval' => 1*60,
			'display' => __('Once every minute'));
	}
	return $schedules;
}
add_filter('cron_schedules','gf_en_cron_schedules');


// On plugin activation schedule our cron event
register_activation_hook( __FILE__, 'gf_en_register_cron' );

function gf_en_register_cron(){
	// Use wp_next_scheduled to check if the event is already scheduled
	$timestamp = wp_next_scheduled( 'gf_en_run_cron' );

	if( $timestamp == false ){ // schedule daily backups since it hasn't been done previously
		// Schedule the event for right now, then to repeat every 5 minutes using the hook 'gf_en_send_data_cron'
		wp_schedule_event( time(), '5min', 'gf_en_run_cron' );
	}

}

// What the cron is actually running every 5 minutes
// Not that Engaging Networks has a limit of 100 entries per IP address every 5 minutes, so it doesn't work to run this more often.
function gf_en_run_cron() {
	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	require_once( 'class-gfenaddon.php' );

	$gf_en_addon = gf_en_addon();

	$gf_en_addon->runCron();
}
add_action( 'gf_en_run_cron', 'gf_en_run_cron' );