<?php
/**
 * Mobipaid API Class
 *
 * @package Mobipaid
 */

if (!defined('ABSPATH')) {
 exit;
}

/**
 * Handles POS Link, Refunds and other API requests.
 *
 * @since 1.0.0
 */
class Mobipaid_API
{

 /**
  * API Access Key
  *
  * @var string
  */
 public static $access_key;

 /**
  * Is use test server or not
  *
  * @var bool
  */
 public static $is_test_mode = false;

 /**
  * API live url
  *
  * @var string
  */
 public static $api_live_url = 'https://live.mobipaid.io/v2';

 /**
  * API test url
  *
  * @var string
  */
 public static $api_test_url = 'https://test.mobipaid.io/v2';

 /**
  * Get API url
  *
  * @return string
  */
 public static function get_api_url()
 {
  if (self::$is_test_mode) {
   return self::$api_test_url;
  }
  return self::$api_live_url;
 }

 /**
  * Send request to the API
  *
  * @param string $url Url.
  * @param array  $body Body.
  * @param string $method Method.
  *
  * @return array
  */
 public static function send_request($url, $body = '', $method = 'GET')
 {
  $api_args['headers'] = array('Authorization' => 'Bearer ' . self::$access_key);
  if ('POST' === $method || 'PUT' === $method || 'DELETE' === $method) {
   $api_args['headers']['Content-Type'] = 'Application/json';
   $body                                = wp_json_encode($body);
  }
  $api_args['method']  = strtoupper($method);
  $api_args['body']    = $body;
  $api_args['timeout'] = 70;

  $results = wp_remote_request($url, $api_args);
  if (is_string($results['body'])) {
   $results['body'] = json_decode($results['body'], true);
  }

  return $results;
 }

 /**
  * Get pos link url with the API.
  *
  * @param array $body Body.
  *
  * @return array
  */
 public static function generate_pos_link($body)
 {
  $url = self::get_api_url() . '/pos/generate-link';
  return self::send_request($url, $body, 'POST');
 }

  /**
  * Get pos link detail with the API.
  *
  * @param string $payment_id Payment ID.
  *
  * @return array
  */
  public static function get_pos_link()
  {
   $url = self::get_api_url() . '/pos';
   return self::send_request($url);
  }

  /**
  * Create default pos link
  *
  * @param array $currency Currency.
  *
  * @return array
  */
  public static function create_default_pos_link(){
    $body = array();

    $body['currency'] = ['ZAR', 'USD', 'EUR'];
    $body['auto_generate_ref_number'] = 0;
    $body['ref_number_suffix'] = 1;
    $body['ref_number_prefix'] = 'MP';
    $body['tip_enabled'] = false;
    $body['moto_enabled'] = false;
    $body['share_button_enabled'] = false;

    $url = self::get_api_url() . '/pos';

    return self::send_request($url, $body, 'POST');
  }

 /**
  * Get payment detail with the API.
  *
  * @param string $payment_id Payment ID.
  *
  * @return array
  */
 public static function get_payment($payment_id)
 {
  $url = self::get_api_url() . '/payments/' . $payment_id;
  return self::send_request($url);
 }

 /**
  * create payment request for subcribtion.
  *
  * @param string $payment_id Payment ID.
  *
  * @return array
  */
 public static function create_payment_request($body)
 {
  $url = self::get_api_url() . '/payment-requests';
  return self::send_request($url, $body, 'POST');
 }

 /**
  * create payment request for subcribtion.
  *
  * @param string $payment_id Payment ID.
  *
  * @return array
  */
 public static function cancel_subscription($payment_id)
 {
  $url = self::get_api_url() . '/payments/subscription/' . $payment_id;
  return self::send_request($url,'','DELETE');
 }

 /**
  * Do refund with the API.
  *
  * @param string $payment_id Payment ID.
  * @param array  $body Body.
  *
  * @return array
  */
 public static function do_refund($payment_id, $body)
 {
  $url = self::get_api_url() . '/refunds/' . $payment_id;
  return self::send_request($url, $body, 'POST');
 }

}
