<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Mobipaid_Blocks_Support extends AbstractPaymentMethodType {
	
	protected $name = 'mobipaid'; // payment gateway id

	public function initialize() {
		// get payment gateway settings
		$this->settings = get_option( "woocommerce_{$this->name}_settings", array() );		
	}

	public function is_active() {
		return ! empty( $this->settings[ 'enabled' ] ) && 'yes' === $this->settings[ 'enabled' ];
	}

	public function get_payment_method_script_handles() {

		wp_register_script(
			'mobipaid-blocks-integration',
			plugin_dir_url( __DIR__ ) . 'assets/js/mobipaid-block.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
			),
			null, // or time() or filemtime( ... ) to skip caching
			true
		);

		return array( 'mobipaid-blocks-integration' );

	}

	public function get_payment_method_data() {
		return array(
			'title'        => $this->get_setting( 'title' ),
			'description'  => $this->get_setting( 'description' ),
            'icon'         => plugin_dir_url( __DIR__ ) . 'assets/img/mp-logo.png'
		);
	}

}