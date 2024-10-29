<?php

/**
 * Plugin Name: Auto Import Coupons from vcommission
 * Version: 1.0
 * Description: Auto import coupons and deals from vcommission.com website to WP coupon & deals plugin.
 * Author: Sanoj Sharma
 * Author Email: sanojsharma88@gmail.com
 * License: GPLv2 or later
 *
 */

// If accessed directly, exit.

if ( !defined( 'ABSPATH' ) ) {
    die;
}

require_once dirname( __FILE__ ) . '/includes/wp_vc-settings.php';
require_once dirname( __FILE__ ) . '/includes/wp_vc-function.php';
define('WPVC_PREFIX','coupon_details_');

// CRON JOB 
if ( ! wp_next_scheduled( 'wpvc_coupon_hook' ) ) {
	wp_schedule_event( time(), 'twicedaily', 'wpvc_coupon_hook',array($api_key) );
}
add_action('wpvc_coupon_hook', 'wpvc_fetch_coupon_function');

register_activation_hook( __FILE__, 'wpvc_plugin_activate' );
function wpvc_plugin_activate(){
    // Require parent plugin
    if ( ! is_plugin_active( 'wp-coupons-and-deals/wp-coupons-deals.php' ) and current_user_can( 'activate_plugins' ) ) {
        wp_die('Sorry, but this plugin requires the <a href="https://wordpress.org/plugins/wp-coupons-and-deals/" target="_blank">WP Coupons and Deals</a> to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}