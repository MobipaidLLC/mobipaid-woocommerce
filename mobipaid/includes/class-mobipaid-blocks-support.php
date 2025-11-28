<?php
/**
 * Mobipaid Blocks Support Class
 *
 * Provides integration with WooCommerce Blocks checkout.
 *
 * @package Mobipaid
 * @since   1.1.0
 */

if (! defined('ABSPATH')) {
 exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Mobipaid Blocks Support Class.
 *
 * @since 1.1.0
 */
final class Mobipaid_Blocks_Support extends AbstractPaymentMethodType {

 /**
  * Payment method name/ID.
  *
  * @var string
  */
 protected $name = 'mobipaid';

 /**
  * Initialize the payment method type.
  */
 public function initialize() {
  $this->settings = get_option("woocommerce_{$this->name}_settings", array());
 }

 /**
  * Check if payment method is active.
  *
  * @return bool
  */
 public function is_active() {
  return ! empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
 }

 /**
  * Register payment method scripts.
  *
  * @return array
  */
 public function get_payment_method_script_handles() {
  $script_path = 'assets/js/mobipaid-block.js';
  $script_url  = plugin_dir_url(__DIR__) . $script_path;
  $script_asset_path = dirname(__DIR__) . '/' . $script_path;
  $version = file_exists($script_asset_path) ? filemtime($script_asset_path) : MOBIPAID_PLUGIN_VERSION;

  wp_register_script(
   'mobipaid-blocks-integration',
   $script_url,
   array(
    'wc-blocks-registry',
    'wc-settings',
    'wp-element',
    'wp-html-entities',
   ),
   $version,
   true
  );

  return array('mobipaid-blocks-integration');
 }

 /**
  * Get payment method data for the client side.
  *
  * @return array
  */
 public function get_payment_method_data() {
  return array(
   'title'       => $this->get_setting('title'),
   'description' => $this->get_setting('description'),
   'icon'        => plugin_dir_url(__DIR__) . 'assets/img/mp-logo.png',
  );
 }
}