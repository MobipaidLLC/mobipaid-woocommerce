<?php
/**
 * Plugin Name:          Mobipaid
 * Plugin URI:           https://github.com/MobipaidLLC/mobipaid-woocommerce
 * Description:          Receive payments using Mobipaid.
 * Version:              1.1.0
 * Requires at least:    5.0
 * Tested up to:         6.6.2
 * WC requires at least: 3.9.0
 * WC tested up to:      9.2.3
 * Requires PHP:         7.0
 * Author:               Mobipaid
 * Author URI:           https://mobipaid.com
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          mobipaid
 * Domain Path:          /languages
 *
 * @package Mobipaid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MOBIPAID_PLUGIN_VERSION', '1.1.0' );

register_activation_hook( __FILE__, 'mobipaid_activate_plugin' );
register_uninstall_hook( __FILE__, 'mobipaid_uninstall_plugin' );

// Declare compatibility with custom_order_tables and cart_checkout_blocks for WooCommerce.
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables', 
				__FILE__, 
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

/**
 * Process when activate plugin.
 */
function mobipaid_activate_plugin() {
	// add or update plugin version to database.
	$mobipaid_plugin_version = get_option( 'mobipaid_plugin_version' );
	if ( ! $mobipaid_plugin_version ) {
		add_option( 'mobipaid_plugin_version', MOBIPAID_PLUGIN_VERSION );
	} else {
		update_option( 'mobipaid_plugin_version', MOBIPAID_PLUGIN_VERSION );
	}
}

/**
 * Process when delete plugin.
 */
function mobipaid_uninstall_plugin() {
	delete_option( 'mobipaid_plugin_version' );
	delete_option( 'woocommerce_mobipaid_settings' );
}

/**
 * Initial plugin.
 */
function mobipaid_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once plugin_basename( 'includes/class-mobipaid.php' );
	load_plugin_textdomain( 'mobipaid', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	add_filter( 'woocommerce_payment_gateways', 'mobipaid_add_gateway' );
}
add_action( 'plugins_loaded', 'mobipaid_init', 0 );

/**
 * Add mobipaid to woocommerce payment gateway.
 *
 * @param array $methods Payment methods.
 */
function mobipaid_add_gateway( $methods ) {
	$methods[] = 'Mobipaid';
	return $methods;
}

/**
 * Add plugin settings link.
 *
 * @param array $links Links.
 */
function mobipaid_plugin_links( $links ) {
	$settings_url = add_query_arg(
		array(
			'page'    => 'wc-settings',
			'tab'     => 'checkout',
			'section' => 'mobipaid',
		),
		admin_url( 'admin.php' )
	);

	$plugin_links = array(
		'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'mobipaid' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mobipaid_plugin_links' );

/**
 * Add mobipaid query vars.
 *
 * @param array $vars Query vars.
 */
function mobipaid_add_query_vars_filter( $vars ) {
	$vars[] = 'response';
	$vars[] = 'mp_token';
	return $vars;
}
add_filter( 'query_vars', 'mobipaid_add_query_vars_filter' );
 
add_action('rest_api_init', 'addResponseHandlerApi');

function addResponseHandlerApi()
 {
  // use hook to receive response url.
  register_rest_route('woocommerce_mobipaid_api', 'response_url', [
   'methods'  => 'POST',
   'callback' => 'handleResponseUrl',
  ]);
 }

function handleResponseUrl()
{
	require_once 'includes/class-mobipaid.php';
	$mobipaid =  new Mobipaid();

    return $mobipaid->response_page();
}

add_action( 'woocommerce_blocks_loaded', 'mobipaid_gateway_block_support' );
function mobipaid_gateway_block_support() {

	if( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	// here we're including our "gateway block support class"
	require_once __DIR__ . '/includes/class-mobipaid-blocks-support.php';

	// registering the PHP class we have just included
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new Mobipaid_Blocks_Support );
		}
	);

}