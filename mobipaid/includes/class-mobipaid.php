<?php
/**
 * Mobipaid Class
 *
 * @package Mobipaid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/class-mobipaid-api.php';

/**
 * Mobipaid class.
 *
 * @extends WC_Payment_Gateway
 */
class Mobipaid extends WC_Payment_Gateway {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id = 'mobipaid';
		// title for backend.
		$this->method_title       = __( 'Mobipaid', 'mobipaid' );
		$this->method_description = __( 'Mobipaid redirects customers to Mobipaid to enter their payment information.', 'mobipaid' );
		// title for frontend.
		$this->icon     = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/img/mp-logo.png';
		$this->supports = array( 'refunds' );

		// setup backend configuration.
		$this->init_form_fields();
		$this->init_settings();

		// save woocomerce settings checkout tab section mobipaid.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// validate form fields when saved.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'validate_admin_options' ) );
		// use hook to receive response url.
		add_action( 'woocommerce_before_thankyou', array( $this, 'response_page' ) );
		// use hook to do full refund.
		add_action( 'woocommerce_order_edit_status', array( $this, 'process_full_refund' ), 10, 2 );
		// use hook to add notes when payment amount greater than order amount.
		add_action( 'woocommerce_order_status_changed', array( $this, 'add_full_refund_notes' ), 10, 3 );

		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->payment_type   = 'DB';
		$this->access_key     = $this->get_option( 'access_key' );
		$this->enable_logging = 'yes' === $this->get_option( 'enable_logging' );
		$this->is_test_mode   = 'mp_live' !== substr( $this->access_key, 0, 7 );
		$this->init_api();
	}

	/**
	 * Override function.
	 * Initialise settings form fields for mobipaid
	 * Add an array of fields to be displayed on the mobipaid settings screen.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'mobipaid' ),
				'label'   => __( 'Enable Mobipaid', 'mobipaid' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'title'          => array(
				'title'       => __( 'Title', 'mobipaid' ),
				'type'        => 'text',
				'description' => __( 'This is the title which the user sees during checkout.', 'mobipaid' ),
				'default'     => __( 'Mobipaid', 'mobipaid' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'mobipaid' ),
				'type'        => 'text',
				'description' => __( 'This is the description which the user sees during checkout.', 'mobipaid' ),
				'default'     => 'Pay with Mobipaid',
				'desc_tip'    => true,
			),
			'access_key'     => array(
				'title'       => __( 'Access Key', 'mobipaid' ),
				'type'        => 'password',
				'description' => __( '* This is the access key, received from Mobipaid developer portal. ( required )', 'mobipaid' ),
				'default'     => '',
			),
			'enable_logging' => array(
				'title'   => __( 'Enable Logging', 'mobipaid' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable transaction logging for mobipaid.', 'mobipaid' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Show error notice if access key is empty.
	 */
	public function validate_admin_options() {
		$post_data  = $this->get_post_data();
		$access_key = $this->get_field_value( 'access_key', $this->form_fields, $post_data );
		if ( empty( $access_key ) ) {
			WC_Admin_Settings::add_error( __( 'Please enter an access key!', 'mobipaid' ) );
		}
	}

	/**
	 * Override function.
	 * Disable if access key is empty.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = parent::is_available();
		if ( empty( $this->access_key ) ) {
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
	public static function get_order_prop( $order, $prop ) {
		switch ( $prop ) {
			case 'order_total':
				$getter = array( $order, 'get_total' );
				break;
			default:
				$getter = array( $order, 'get_' . $prop );
				break;
		}

		return is_callable( $getter ) ? call_user_func( $getter ) : $order->{ $prop };
	}

	/**
	 * Log system processes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @param string $level Log level.
	 */
	public function log( $message, $level = 'info' ) {
		if ( $this->enable_logging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'mobipaid-' . $level, $message );
		}
	}

	/**
	 * Init the API class and set the access key.
	 */
	protected function init_api() {
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
	protected function get_payment_url( $order_id, $transaction_id ) {
		$order      = wc_get_order( $order_id );
		$currency   = $order->get_currency();
		$amount     = $this->get_order_prop( $order, 'order_total' );
		$token      = $this->generate_token( $order_id, $currency );
		$return_url = $this->get_return_url( $order );

		$body                     = array(
			'reference'    => $transaction_id,
			'payment_type' => $this->payment_type,
			'currency'     => $currency,
			'amount'       => (float) $amount,
			'cart_items'   => $this->get_cart_items( $order_id ),
			'cancel_url'   => wc_get_checkout_url(),
			'return_url'   => $return_url,
			'response_url' => $return_url . '&mp_token=' . $token,
		);
		$log_body                 = $body;
		$log_body['response_url'] = $return_url . '&mp_token=*****';
		$this->log( 'get_payment_url - body: ' . wp_json_encode( $log_body ) );

		$results = Mobipaid_API::generate_pos_link( $body );
		$this->log( 'get_payment_url - results: ' . wp_json_encode( $results ) );

		if ( 200 === $results['response']['code'] && 'success' === $results['body']['result'] ) {
			return $results['body']['long_url'];
		}

		if ( 422 === $results['response']['code'] && 'currency' === $results['body']['error_field'] ) {
			throw new Exception( __( 'We are sorry, currency is not supported. Please contact us.', 'mobipaid' ), 1 );
		}

		throw new Exception( __( 'Error while Processing Request: please try again.', 'mobipaid' ), 1 );
	}

	/**
	 * Get cart items.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function get_cart_items( $order_id ) {
		$cart_items = array();
		$order      = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$cart_items[] = array(
				'name' => $item->get_name(),
				'qty'  => $item->get_quantity(),
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
	public function process_payment( $order_id ) {
		$order          = wc_get_order( $order_id );
		$transaction_id = 'wc-' . $order->get_order_number();
		$secret_key     = wc_rand_hash();

		// * save transaction_id and secret_key first before call get_payment_url function.
		update_post_meta( $order->get_id(), '_mobipaid_transaction_id', $transaction_id );
		update_post_meta( $order->get_id(), '_mobipaid_secret_key', $secret_key );

		$payment_url = $this->get_payment_url( $order_id, $transaction_id );

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
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( $order && 'mobipaid' === $order->get_payment_method() ) {
			$payment_id = get_post_meta( $order->get_id(), '_mobipaid_payment_id', true );
			$body       = array(
				'email'  => $order->get_billing_email(),
				'amount' => (float) $amount,
			);
			$this->log( 'process_refund - request body ' . wp_json_encode( $body ) );
			$results = Mobipaid_API::do_refund( $payment_id, $body );
			$this->log( 'process_refund - results: ' . wp_json_encode( $results ) );

			if ( 200 === $results['response']['code'] && 'refund' === $results['body']['status'] ) {
				$order->add_order_note( __( 'Mobipaid partial refund successfull.' ) );
				$this->log( 'process_refund: Success' );
				return true;
			}

			$this->log( 'process_refund: Failed' );
			return new WP_Error( $results['response']['code'], __( 'Refund Failed', 'mobipaid' ) . ': ' . $results['body']['message'] );
		}
	}

	/**
	 * Process full refund when order status change from processing / completed to refunded.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status_to change status to.
	 */
	public function process_full_refund( $order_id, $status_to ) {
		$order = wc_get_order( $order_id );
		if ( $order && 'mobipaid' === $order->get_payment_method() ) {
			$status_from = $order->get_status();

			if ( ( 'processing' === $status_from || 'completed' === $status_from ) && 'refunded' === $status_to ) {
				$amount     = (float) $this->get_order_prop( $order, 'order_total' );
				$payment_id = get_post_meta( $order->get_id(), '_mobipaid_payment_id', true );
				$body       = array(
					'email'  => $order->get_billing_email(),
					'amount' => $amount,
				);
				$this->log( 'process_full_refund - request body ' . wp_json_encode( $body ) );
				$results = Mobipaid_API::do_refund( $payment_id, $body );
				$this->log( 'process_full_refund - do_refund results: ' . wp_json_encode( $results ) );

				if ( 200 === $results['response']['code'] && 'refund' === $results['body']['status'] ) {
					$this->restock_refunded_items( $order );
					$order->add_order_note( __( 'Mobipaid full refund successfull.' ) );
					$this->log( 'process_full_refund: Success' );
				} else {
					$this->log( 'process_full_refund: Failed' );
					$redirect = get_admin_url() . 'post.php?post=' . $order_id . '&action=edit';
					WC_Admin_Meta_Boxes::add_error( __( 'Refund Failed', 'mobipaid' ) . ':' . $results['body']['message'] );
					wp_safe_redirect( $redirect );
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
	public function add_full_refund_notes( $order_id, $status_from, $status_to ) {
		$order = wc_get_order( $order_id );
		if ( $order && 'mobipaid' === $order->get_payment_method() ) {
			if ( ( 'processing' === $status_from || 'completed' === $status_from ) && 'refunded' === $status_to ) {
				$order_amount = (float) $this->get_order_prop( $order, 'order_total' );
				$payment_id   = get_post_meta( $order->get_id(), '_mobipaid_payment_id', true );
				$results      = Mobipaid_API::get_payment( $payment_id );
				$this->log( 'add_full_refund_notes - get_payment results: ' . wp_json_encode( $results ) );
				if ( 200 === $results['response']['code'] ) {
					$payment_amount = (float) $results['body']['payment']['amount'];
					if ( $payment_amount > $order_amount ) {
						$order->add_order_note( __( 'Mobipaid notes: You still have amount to be refunded, because Merchant use tax/tip when customer paid. Please contact the merchant to refund the tax/tip amount.' ) );
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
	public function restock_refunded_items( $order ) {
		$refunded_line_items = array();
		$line_items          = $order->get_items();

		foreach ( $line_items as $item_id => $item ) {
			$refunded_line_items[ $item_id ]['qty'] = $item->get_quantity();
		}
		wc_restock_refunded_items( $order, $refunded_line_items );
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
	protected function generate_token( $order_id, $currency ) {
		$transaction_id = get_post_meta( $order_id, '_mobipaid_transaction_id', true );
		$secret_key     = get_post_meta( $order_id, '_mobipaid_secret_key', true );

		return md5( (string) $order_id . $currency . $transaction_id . $secret_key );
	}

	/**
	 * Page to handle response from the gateway.
	 * Get payment status and update order status.
	 *
	 * @param int $order_id - Order Id.
	 */
	public function response_page( $order_id ) {
		$token = get_query_var( 'mp_token' );

		if ( ! empty( $token ) ) {
			$this->log( 'get response from the gateway reponse url' );
			$res_body = file_get_contents( 'php://input' );
			$res_body = json_decode( $res_body, true );
			$response = json_decode( wp_unslash( $res_body['response'] ), true );
			$this->log( 'response_page - response: ' . wp_json_encode( $response ) );

			$transaction_id  = isset( $response['transaction_id'] ) ? $response['transaction_id'] : '';
			$result          = isset( $response['result'] ) ? $response['result'] : '';
			$payment_id      = isset( $response['payment_id'] ) ? $response['payment_id'] : '';
			$currency        = isset( $response['currency'] ) ? $response['currency'] : '';
			$generated_token = $this->generate_token( $order_id, $currency );
			$order           = wc_get_order( $order_id );

			if ( $order && 'mobipaid' === $order->get_payment_method() ) {
				if ( $token === $generated_token ) {
					if ( 'ACK' === $result ) {
						$this->log( 'response_page: update order status to processing' );
						$order_status = 'processing';
						$order_notes  = 'Mobipaid payment successfull:';
						update_post_meta( $order->get_id(), '_mobipaid_payment_id', $payment_id );
						update_post_meta( $order->get_id(), '_mobipaid_payment_result', 'succes' );
						$order->update_status( $order_status, $order_notes );
					} else {
						$this->log( 'response_page: update order status to failed' );
						$order_status = 'failed';
						$order_notes  = 'Mobipaid payment failed:';
						update_post_meta( $order->get_id(), '_mobipaid_payment_result', 'failed' );
						$order->update_status( $order_status, $order_notes );
					}
					die( 'OK' );
				} else {
					$this->log( 'response_page: FRAUD detected, token is not same with the generated token' );
				}
			}
		} else {
			$this->log( 'response_page: go to thank you page' );
		}
	}

}
