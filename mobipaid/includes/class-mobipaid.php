<?php
/**
 * Mobipaid Class
 *
 * @package Mobipaid
 */

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly.
}

require_once dirname(__FILE__) . '/class-mobipaid-api.php';

/**
 * Mobipaid class.
 *
 * @extends WC_Payment_Gateway
 */
class Mobipaid extends WC_Payment_Gateway
{

 /**
  * Plugin directory
  *
  * @var string
  */
 public $plugin_directory;

 /**
  * Plugin api url
  *
  * @var string
  */
 public $mobipaid_api_url;

 /**
  * Updated meta boxes
  *
  * @var boolean
  */
 private static $updated_meta_boxes = false;

 /**

  * Constructor
  */
 public function __construct()
 {
  $this->id = 'mobipaid';
  // title for backend.
  $this->method_title       = __('Mobipaid', 'mobipaid');
  $this->method_description = __('Mobipaid redirects customers to Mobipaid to enter their payment information.', 'mobipaid');
  // title for frontend.
  $this->icon     = WP_PLUGIN_URL . '/' . plugin_basename(dirname(dirname(__FILE__))) . '/assets/img/mp-logo.png';
  $this->supports = array(
   'subscriptions',
   'subscription_cancellation',
   'refunds',
  );
  $this->plugin_directory = plugin_dir_path(__FILE__);

  // setup backend configuration.
  $this->init_form_fields();
  $this->init_settings();

  // save woocomerce settings checkout tab section mobipaid.
  add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
  // validate form fields when saved.
  add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'validate_admin_options'));
 
  // use hook to do full refund.
  add_action('woocommerce_order_edit_status', array($this, 'process_full_refund'), 10, 2);
  // use hook to add notes when payment amount greater than order amount.
  add_action('woocommerce_order_status_changed', array($this, 'add_full_refund_notes'), 10, 3);
  // use hook to add button for stop recurring
  add_action('woocommerce_admin_order_data_after_order_details', array(&$this, 'stop_recurring_backend'));
  add_action('woocommerce_view_order', array(&$this, 'stop_recurring_frontend'));

  $this->title          = $this->get_option('title');
  $this->description    = $this->get_option('description');
  $this->payment_type   = 'DB';
  $this->access_key     = $this->get_option('access_key');
  $this->enable_logging = 'yes' === $this->get_option('enable_logging');
  $this->is_test_mode   = 'mp_live' !== substr($this->access_key, 0, 7);
  $this->init_api();
 }

 /**
  * Override function.
  * Initialise settings form fields for mobipaid
  * Add an array of fields to be displayed on the mobipaid settings screen.
  */
 public function init_form_fields()
 {
  $this->form_fields = array(
   'enabled'        => array(
    'title'   => __('Enable/Disable', 'mobipaid'),
    'label'   => __('Enable Mobipaid', 'mobipaid'),
    'type'    => 'checkbox',
    'default' => 'no',
   ),
   'title'          => array(
    'title'       => __('Title', 'mobipaid'),
    'type'        => 'text',
    'description' => __('This is the title which the user sees during checkout.', 'mobipaid'),
    'default'     => __('Mobipaid', 'mobipaid'),
    'desc_tip'    => true,
   ),
   'description'    => array(
    'title'       => __('Description', 'mobipaid'),
    'type'        => 'text',
    'description' => __('This is the description which the user sees during checkout.', 'mobipaid'),
    'default'     => 'Pay with Mobipaid',
    'desc_tip'    => true,
   ),
   'access_key'     => array(
    'title'       => __('Access Key', 'mobipaid'),
    'type'        => 'password',
    'description' => __('* This is the access key, received from Mobipaid developer portal. ( required )', 'mobipaid'),
    'default'     => '',
   ),
   'enable_logging' => array(
    'title'   => __('Enable Logging', 'mobipaid'),
    'type'    => 'checkbox',
    'label'   => __('Enable transaction logging for mobipaid.', 'mobipaid'),
    'default' => 'no',
   ),
  );
 }

 /**
  * Show error notice if access key is empty.
  */
 public function validate_admin_options()
 {
  $post_data  = $this->get_post_data();
  $access_key = $this->get_field_value('access_key', $this->form_fields, $post_data);
  if (empty($access_key)) {
   WC_Admin_Settings::add_error(__('Please enter an access key!', 'mobipaid'));
  }
 }

 /**
  * Override function.
  * Disable if access key is empty.
  *
  * @return bool
  */
 public function is_available()
 {
  $is_available = parent::is_available();
  if (empty($this->access_key)) {
   $is_available = false;
  }

  return $is_available;
 }

 /**
  * Get order property with compatibility check on order getter introduced
  * in WC 3.0.
  *
  * @since 1.0.0
  *
  * @param WC_Order $order Order object.
  * @param string   $prop  Property name.
  *
  * @return mixed Property value
  */
 public static function get_order_prop($order, $prop)
 {
  switch ($prop) {
   case 'order_total':
    $getter = array($order, 'get_total');
    break;
   default:
    $getter = array($order, 'get_' . $prop);
    break;
  }

  return is_callable($getter) ? call_user_func($getter) : $order->{$prop};
 }

 /**
  * Log system processes.
  *
  * @since 1.0.0
  *
  * @param string $message Log message.
  * @param string $level Log level.
  */
 public function log($message, $level = 'info')
 {
  if ($this->enable_logging) {
   if (empty($this->logger)) {
    $this->logger = new WC_Logger();
   }
   $this->logger->add('mobipaid-' . $level, $message);
  }
 }

 /**
  * Init the API class and set the access key.
  */
 protected function init_api()
 {
  Mobipaid_API::$access_key   = $this->access_key;
  Mobipaid_API::$is_test_mode = $this->is_test_mode;
 }

 /**
  * Get payment url.
  *
  * @param int    $order_id Order ID.
  * @param string $transaction_id Transaction ID.
  *
  * @return string
  * @throws \Exception Error.
  */
 protected function get_payment_url($order_id, $transaction_id)
 {
  $order        = wc_get_order($order_id);
  $currency     = $order->get_currency();
  $amount       = $this->get_order_prop($order, 'order_total');
  $token        = $this->generate_token($order_id, $currency);
  $response_url = $this->get_mobipaid_response_url($order_id);
  $return_url   = $this->get_return_url($order);

  $body = array(
   'payment_type' => $this->payment_type,
   'currency'     => $currency,
   'cancel_url'   => wc_get_checkout_url(),
   'response_url' => $response_url . '&mp_token=' . $token,
  );

  if ($this->is_subscription($order_id)) {
   $this->log('this is subcription product');

   $merge_paramters = array_merge($body, $this->get_mobipaid_subscription_parameter($order));
   unset($body);
   $body                     = array();
   $body                     = $merge_paramters;
   $log_body                 = $body;
   $log_body['response_url'] = $response_url . '&mp_token=*****';
   $body['redirect_url']     = $return_url;
   $body['reference_number'] = $transaction_id;
   $body['request_methods']  = array("WEB");

   $this->log('get_payment_url - body: ' . wp_json_encode($log_body));

   $results = Mobipaid_API::create_payment_request($body);
   $this->log('get_payment_url - results: ' . wp_json_encode($results));

  } else {
   $body['return_url']       = $return_url;
   $body['cart_items']       = $this->get_cart_items($order_id);
   $body['reference']        = $transaction_id;
   $body['amount']           = (float) $amount;
   $log_body                 = $body;
   $log_body['response_url'] = $return_url . '&mp_token=*****';
   $this->log('get_payment_url - body: ' . wp_json_encode($log_body));

   $results = Mobipaid_API::generate_pos_link($body);
   $this->log('get_payment_url - results: ' . wp_json_encode($results));
  }

  if (200 === $results['response']['code'] && 'success' === $results['body']['result']) {
   return $results['body']['long_url'];
  }

  if (422 === $results['response']['code'] && 'currency' === $results['body']['error_field']) {
   throw new Exception(__('We are sorry, currency is not supported. Please contact us.', 'mobipaid'), 1);
  }

  throw new Exception(__('Error while Processing Request: please try again.', 'mobipaid'), 1);
 }

 protected function get_mobipaid_response_url($order_id)
 {
  global $wp;

  return home_url() . "/wp-json/woocommerce_mobipaid_api/response_url?order_id=" . $order_id;
 }

 /**
  * Get checkout parameters for order that contain subscription product
  *
  * @param  string $property property of class WC_Orde.
  * @return array
  */
 protected function get_mobipaid_subscription_parameter($order)
 {

  $subscription_period                      = WC_Subscriptions_Order::get_subscription_period($order);
  $checkout_parameters                      = array();
  $checkout_parameters['payment_frequency'] = WC_Subscriptions_Order::get_subscription_period($order);

  $date_now = date('m/d/Y');

  if ($subscription_period == 'day') {
   throw new Exception(__('We are sorry, Mobipaid subcription Payment only support interval weekly, monthly, and yearly.', 'mobipaid'), 1);
  }

  if ($subscription_period == 'week') {
   $checkout_parameters['payment_frequency']  = 'WEEKLY';
   $checkout_parameters['payment_start_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($date_now . "+1 week"));
  } elseif ($subscription_period == 'month') {
   $checkout_parameters['payment_frequency']  = 'MONTHLY';
   $checkout_parameters['payment_start_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($date_now . "+1 month"));
  } else {
   $checkout_parameters['payment_frequency']  = 'YEARLY';
   $checkout_parameters['payment_start_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($date_now . "+1 year"));
  }

  $checkout_parameters['initial_payment_amount'] = (float) $this->get_order_prop($order, 'order_total');
  $unconvert_date                                = WC_Subscriptions_Manager::get_subscription_expiration_date(WC_Subscriptions_Manager::get_subscription_key($order->id), $order->customer_user);

  if (0 !== $unconvert_date) {
   if ($subscription_period == 'week') {
    $checkout_parameters['payment_end_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($unconvert_date . "-1 week"));
   } elseif ($subscription_period == 'month') {
    $checkout_parameters['payment_end_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($unconvert_date . "-1 month"));
   } else {
    $checkout_parameters['payment_end_date'] = date('Y-m-d\TH:i:s.000\Z', strtotime($unconvert_date . "-1 year"));
   }
  }

  $checkout_parameters['amount'] = (float) WC_Subscriptions_Order::get_recurring_total($order);
  return $checkout_parameters;
 }

 /**
  * Get cart items.
  *
  * @param int $order_id Order ID.
  * @return array
  */
 public function get_cart_items($order_id)
 {
  $cart_items = array();
  $order      = wc_get_order($order_id);

  foreach ($order->get_items() as $item_id => $item) {
   $product = $item->get_product();
   $sku     = $product->get_sku();
   if (!$sku) {
    $sku = '-';
   }
   $item_total = isset($item['recurring_line_total']) ? $item['recurring_line_total'] : $order->get_item_total($item);

   $cart_items[] = array(
    'sku'        => $sku,
    'name'       => $item->get_name(),
    'qty'        => $item->get_quantity(),
    'unit_price' => $item_total,
   );
  }

  return $cart_items;
 }

 /**
  * Override function.
  *
  * Send data to the API to get the payment url.
  * Redirect user to the payment url.
  * This should return the success and redirect in an array. e.g:
  *
  *        return array(
  *            'result'   => 'success',
  *            'redirect' => $this->get_return_url( $order )
  *        );
  *
  * @param int $order_id Order ID.
  * @return array
  */
 public function process_payment($order_id)
 {
  $order          = wc_get_order($order_id);
  $transaction_id = 'wc-' . $order->get_order_number();
  $secret_key     = wc_rand_hash();

  // * save transaction_id and secret_key first before call get_payment_url function.
  update_post_meta($order->get_id(), '_mobipaid_transaction_id', $transaction_id);
  update_post_meta($order->get_id(), '_mobipaid_secret_key', $secret_key);

  $payment_url = $this->get_payment_url($order_id, $transaction_id);

  return array(
   'result'   => 'success',
   'redirect' => $payment_url,
  );
 }

 /**
  * Process partial refund.
  *
  * If the gateway declares 'refunds' support, this will allow it to refund.
  * a passed in amount.
  *
  * @param  int    $order_id Order ID.
  * @param  float  $amount Refund amount.
  * @param  string $reason Refund reason.
  * @return boolean True or false based on success, or a WP_Error object.
  */
 public function process_refund($order_id, $amount = null, $reason = '')
 {
  $order = wc_get_order($order_id);
  if ($order && 'mobipaid' === $order->get_payment_method()) {
   $payment_id = get_post_meta($order->get_id(), '_mobipaid_payment_id', true);
   $body       = array(
    'email'  => $order->get_billing_email(),
    'amount' => (float) $amount,
   );
   $this->log('process_refund - request body ' . wp_json_encode($body));
   $results = Mobipaid_API::do_refund($payment_id, $body);
   $this->log('process_refund - results: ' . wp_json_encode($results));

   if (200 === $results['response']['code'] && 'refund' === $results['body']['status']) {
    $order->add_order_note(__('Mobipaid partial refund successfull.'));
    $this->log('process_refund: Success');
    return true;
   }

   $this->log('process_refund: Failed');
   return new WP_Error($results['response']['code'], __('Refund Failed', 'mobipaid') . ': ' . $results['body']['message']);
  }
 }

 /**
  * Process full refund when order status change from processing / completed to refunded.
  *
  * @param int    $order_id Order ID.
  * @param string $status_to change status to.
  */
 public function process_full_refund($order_id, $status_to)
 {
  $order = wc_get_order($order_id);
  if ($order && 'mobipaid' === $order->get_payment_method()) {
   $status_from = $order->get_status();

   if (('processing' === $status_from || 'completed' === $status_from) && 'refunded' === $status_to) {
    $amount     = (float) $this->get_order_prop($order, 'order_total');
    $payment_id = get_post_meta($order->get_id(), '_mobipaid_payment_id', true);
    $body       = array(
     'email'  => $order->get_billing_email(),
     'amount' => $amount,
    );
    $this->log('process_full_refund - request body ' . wp_json_encode($body));
    $results = Mobipaid_API::do_refund($payment_id, $body);
    $this->log('process_full_refund - do_refund results: ' . wp_json_encode($results));

    if (200 === $results['response']['code'] && 'refund' === $results['body']['status']) {
     $this->restock_refunded_items($order);
     $order->add_order_note(__('Mobipaid full refund successfull.'));
     $this->log('process_full_refund: Success');
    } else {
     $this->log('process_full_refund: Failed');
     $redirect = get_admin_url() . 'post.php?post=' . $order_id . '&action=edit';
     WC_Admin_Meta_Boxes::add_error(__('Refund Failed', 'mobipaid') . ':' . $results['body']['message']);
     wp_safe_redirect($redirect);
     exit;
    }
   }
  }
 }

 /**
  * Add notes if payment amount greater than order amount when order status change from processing / completed to refunded.
  *
  * @param int    $order_id Order ID.
  * @param string $status_from change status from.
  * @param string $status_to change status to.
  */
 public function add_full_refund_notes($order_id, $status_from, $status_to)
 {
  $order = wc_get_order($order_id);
  if ($order && 'mobipaid' === $order->get_payment_method()) {
   if (('processing' === $status_from || 'completed' === $status_from) && 'refunded' === $status_to) {
    $order_amount = (float) $this->get_order_prop($order, 'order_total');
    $payment_id   = get_post_meta($order->get_id(), '_mobipaid_payment_id', true);
    $results      = Mobipaid_API::get_payment($payment_id);
    $this->log('add_full_refund_notes - get_payment results: ' . wp_json_encode($results));
    if (200 === $results['response']['code']) {
     $payment_amount = (float) $results['body']['payment']['amount'];
     if ($payment_amount > $order_amount) {
      $order->add_order_note(__('Mobipaid notes: You still have amount to be refunded, because Merchant use tax/tip when customer paid. Please contact the merchant to refund the tax/tip amount.'));
     }
    }
   }
  }
 }

 /**
  * Increase stock for refunded items.
  *
  * @param obj $order Order.
  */
 public function restock_refunded_items($order)
 {
  $refunded_line_items = array();
  $line_items          = $order->get_items();

  foreach ($line_items as $item_id => $item) {
   $refunded_line_items[$item_id]['qty'] = $item->get_quantity();
  }
  wc_restock_refunded_items($order, $refunded_line_items);
 }

 /**
  * Use this generated token to secure get payment status.
  * Before call this function make sure _mobipaid_transaction_id and _mobipaid_secret_key already saved.
  *
  * @param int    $order_id - Order Id.
  * @param string $currency - Currency.
  *
  * @return string
  */
 protected function generate_token($order_id, $currency)
 {
  $transaction_id = get_post_meta($order_id, '_mobipaid_transaction_id', true);
  $secret_key     = get_post_meta($order_id, '_mobipaid_secret_key', true);

  return md5((string) $order_id . $currency . $transaction_id . $secret_key);
 }

 /**
  * Page to handle response from the gateway.
  * Get payment status and update order status.
  *
  * @param int $order_id - Order Id.
  */
 public function response_page()
 {
  $token    = $this->get_request_value('mp_token');
  $order_id = $this->get_request_value('order_id');

  if (!empty($token)) {
   $this->log('get response from the gateway reponse url');
   $response = $this->get_request_value('response');
   $this->log('response_page - original response: ' . $response);
   $response = json_decode($response, true);
   $this->log('response_page - formated response: ' . wp_json_encode($response));

   $payment_status = '';
   $payment_id     = '';
   $currency       = '';

   if (isset($response['status'])) {
    $payment_status = $response['status'];
   } elseif (isset($response['result'])) {
    $payment_status = $response['result'];
   }

   if (isset($response['payment_id'])) {
    $payment_id = $response['payment_id'];
   } elseif (isset($response['response']) && isset($response['response']['id'])) {
    $payment_id = $response['response']['id'];
   }

   if (isset($response['currency'])) {
    $currency = $response['currency'];
   } elseif (isset($response['response']) && isset($response['response']['currency'])) {
    $currency = $response['response']['currency'];
   }

   $generated_token = $this->generate_token($order_id, $currency);
   $order           = wc_get_order($order_id);
   $order->set_transaction_id($payment_id);

   if ($order && 'mobipaid' === $order->get_payment_method()) {

    if (isset($response['transaction_type'])) {
     if ($response['transaction_type'] == "scheduled" && 'ACK' === $payment_status && $token === $generated_token) {
      foreach (wcs_get_subscriptions_for_order($order_id) as $subscriptions) {
       $subscription = $subscriptions;
      }
      if ($subscription->has_status('active')) {
       $this->process_subcription_renewal_order($response, $subscription, $order);
      }
     }
     $this->log('failed create renewal order');
     die("failed create order");
    }

    if ($token === $generated_token) {
     if ('ACK' === $payment_status) {
      $this->log('response_page: update order status to processing');
      $order_status = 'processing';
      $order_notes  = 'Mobipaid payment successfull:';
      update_post_meta($order->get_id(), '_mobipaid_payment_id', $payment_id);
      update_post_meta($order->get_id(), '_mobipaid_payment_result', 'succes');
      $order->update_status($order_status, $order_notes);
     } else {
      $this->log('response_page: update order status to failed');
      $order_status = 'failed';
      $order_notes  = 'Mobipaid payment failed:';
      update_post_meta($order->get_id(), '_mobipaid_payment_result', 'failed');
      $order->update_status($order_status, $order_notes);
     }
     die('OK');
    } else {
     $this->log('response_page: FRAUD detected, token is not same with the generated token');
    }
   }
  } else {
   $this->log('response_page: go to thank you page');
  }
 }

 /**
  * crete renewal order using response from status_url
  *
  * @param  array $payment_response.
  * @param  string $subscription property of class WC_Subscription.
  * @return array
  */
 protected function process_subcription_renewal_order($payment_response, $subscription, $order)
 {
  $this->log('process renewal order');
  // // Generate a renewal order to record the failed payment
  $transaction_order  = wcs_create_renewal_order($subscription);
  $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
  $note               = __($order->get_payment_method() . " payment approved (Recurring payment ID:" . $payment_response['payment_id']);
  $transaction_order->add_order_note($note);
  $transaction_order->set_payment_method($order->get_payment_method());
  $transaction_order->update_status(get_option('skrill_completed_status', 'processing'), 'order_note');
  $this->log('succces create renewal order');

  exit();
 }

 /**
  * Checks whether order is part of subscription.
  *
  * @since 1.2.0
  *
  * @param int $order_id Order ID
  *
  * @return bool Returns true if order is part of subscription
  */
 public function is_subscription($order_id)
 {
  return (function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id)));
 }

 /**
  * [BACKEND] cancel recurring for backend
  * render update order button and process update order from gateway
  * from hook "woocommerce_admin_order_data_after_order_details"
  */
 public function stop_recurring_backend()
 {
  $post_type = $this->get_request_value('post_type');

  if (!self::$updated_meta_boxes && 'shop_order' !== $post_type) {
   $order_id = $this->get_request_value('post');

   $this->wc_order = wc_get_order($order_id);
   $order_status   = $this->wc_order->get_status();
   if ('mobipaid' === $this->wc_order->get_payment_method() && $this->is_subscription($order_id) && $order_status === "processing") {
    $is_show_warning_message = true;
    $is_show_update_order    = false;
    $warning_message         = 'subscription-cancelled';

    if ($order_status === "processing" && $this->wc_order->get_created_via() !== "subscription-cancelled") {
     $is_show_update_order    = true;
     $is_show_warning_message = false;
     $request_section         = $this->get_request_value('section');
     if ($order_id && 'cancel-recurring' === $request_section) {
      $this->log('Start start cancel recurring');
      $redirect = get_admin_url() . 'post.php?post=' . $this->wc_order->get_order_number() . '&action=edit';
      $this->process_cancel_recurring_order();
      if ($this->process_cancel_recurring_order()) {
       wp_safe_redirect($redirect);
       exit();

      } else {
       $error_message = __('Subscription can not be Cancelled', 'mobipaid');
       $this->redirect_order_detail($error_message);
      }
     }

     $cancel_reccuring_url = get_admin_url() . 'post.php?post=' . $order_id . '&action=edit&section=cancel-recurring';

     $is_show_warning_message = false;
     $is_show_update_order    = true;
     $warning_message         = '';
    }

    wc_get_template(
     'cancel-recurring.php',
     array(
      'update_order_url'        => $cancel_reccuring_url,
      'is_show_warning_message' => $is_show_warning_message,
      'warning_message'         => $warning_message,
      'is_show_update_order'    => $is_show_update_order,
      'is_frontend'             => false,
      'redirect_url'            => null
     ),
     $this->plugin_directory . '../templates/admin/order/',
     $this->plugin_directory . '../templates/admin/order/'
    );
   } // End if().
   self::$updated_meta_boxes = true;
  } // End if().
 } // End if().

 /**
  * [BACKEND] cancel recurring for frontend
  * render update order button and process update order from gateway
  * from hook "woocommerce_admin_order_data_after_order_details"
  */
 public function stop_recurring_frontend($order_id)
 {
  if (!self::$updated_meta_boxes) {
   $this->wc_order = wc_get_order($order_id);
   global $wp;
   $is_show_warning_message = true;
   $is_show_update_order    = false;
   $redirect_url = null;
   $warning_message         = 'subscription-cancelled';
   if ('mobipaid' === $this->wc_order->get_payment_method() && $this->is_subscription($order_id)) {

    $order_status = $this->wc_order->get_status();

    if ($order_status === "processing" && $this->wc_order->get_created_via() !== "subscription-cancelled") {
     $request_section = $this->get_request_value('section');
     if ($order_id && 'cancel-recurring' === $request_section) {
      $this->log('Start cancel recurring');
      if ($this->process_cancel_recurring_order()) {
       $key = 'section';
       $$redirect_url = preg_replace('~(\?|&)' . $key . '=[^&]*~', '$1', home_url($wp->request));
      }
     }

     $cancel_reccuring_url = home_url($wp->request) . '?section=cancel-recurring';

     $is_show_warning_message = false;
     $is_show_update_order    = true;
     $warning_message         = '';
    }

    wc_get_template(
     'cancel-recurring.php',
     array(
      'update_order_url'        => $cancel_reccuring_url,
      'is_show_warning_message' => $is_show_warning_message,
      'warning_message'         => $warning_message,
      'is_show_update_order'    => $is_show_update_order,
      'is_frontend'             => true,
      'redirect_url'            => $redirect_url
     ),
     $this->plugin_directory . '../templates/admin/order/',
     $this->plugin_directory . '../templates/admin/order/'
    );
   } // End if().
   self::$updated_meta_boxes = true;
  }
 } // End if().

 /**
  * [BACKEND] Process cancel recurring Order
  *
  * @param array  $transaction  - transaction.
  * @param string $order_status - order status.
  */
 protected function process_cancel_recurring_order()
 {
  $results = Mobipaid_API::cancel_subscription($this->wc_order->get_transaction_id());

  $this->log('cancel subscription - formated response: ' . wp_json_encode($results));

  if (200 === $results['response']['code'] && 'subscription_ended' === $results['body']['status']) {
   $this->wc_order->add_order_note(__('Mobipaid subscription Cancelled'));
   $this->log('cancel subscription: Success');
   $this->wc_order->set_created_via('subscription-cancelled');
   $this->wc_order->save();
   WC_Subscriptions_Manager::expire_subscriptions_for_order($this->wc_order);

   return true;
  }
  return false;
 }

 /**
  * Get request value
  *
  * @param  string         $key     - key.
  * @param  boolean|string $default - default.
  * @return boolean|string
  */
 public function get_request_value($key, $default = false)
 {
  if (isset($_POST['save']) && isset($_POST['_wpnonce']) && isset($_GET['page'])) { // input var okay.
   if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')) { // input var okay.
    return $default;
   }
  }
  if (isset($_REQUEST[$key])) { // input var okay.
   return sanitize_text_field(wp_unslash($_REQUEST[$key])); // input var okay.
  }
  return $default;
 }

 /**
  * [BACKEND] Redirect Order Detail
  * redirect to order detail and show error message if exist
  *
  * @param boolean|string $error_message - error message.
  */
 protected function redirect_order_detail($error_message = false)
 {
  $redirect = get_admin_url() . 'post.php?post=' . $this->wc_order->get_order_number() . '&action=edit';
  if ($error_message) {
   WC_Admin_Meta_Boxes::add_error($error_message);
  }
  wp_safe_redirect($redirect);
  exit;
 }

}
