<?php

class WC_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor for the Plugin Name Payment.
     */
    public function __construct() {

        global $woocommerce;
        $this->id                 = 'wc-gateway-plugin_name';
        $this->icon = apply_filters('woocommerce_gateway_icon', WC_GATEWAY_URL .'\assets\images\icon.png' );
        $this->has_fields         = false;
        $this->method_title       = __( 'Plugin Name Payment GateWay pour WooCommerce', 'wc-gateway-plugin_name' );
        $this->method_description = __( 'Plugin Name Payment GateWay pour WooCommerce redirigera vos clients vers la checkout page de Plugin Name Payment afin qu\'ils puissent proceder à leur paiement', 'wc-gateway-plugin_name' );
        $this->order_button_text  = __( 'Payer avec Plugin Name', 'wc-gateway-plugin_name' );


        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->enabled      = $this->get_option('enabled');
        $this->description  = $this->get_option( 'description' );
        $this->instructions = $this->get_option( 'instructions', $this->description );
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
    
        
        if ( version_compare( $woocommerce->version, '4.1.0', '>=' ) ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }

        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
        // Customer Emails
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
         
    }

    /**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_supported_currencies',
				array( 'XAF', 'XOF' )
			),
			true
		);
    }
    
    /**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
                    <strong>
                        <?php 
                            esc_html_e( 'Passerelle désactivé', 'wc-gateway-plugin_name' ); 
                        ?>
                    </strong>: 
                        <?php 
                            esc_html_e( 'Plugin Name Payment ne supporte pas la monnaie de votre boutique!', 'wc-gateway-plugin_name' ); 
                        ?>
				</p>
                <?php deactivate_plugins( WC_GATEWAY_BASENAME ); ?>
			</div>
			<?php
            
		}
	}


    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
    
        $this->form_fields = apply_filters( 
            
            'wc_form_fields', array(

                // payment platform access params
				'payment_platform_access' => array(
                    'title' => __('BASIC Plugin Name PAYMENT PLATFORM CONFIG', 'wc-gateway-plugin_name'),
                    'type' => 'title'
                ),
        
                'enabled' => array(
                    'title' => __('Activer/Desactiver','wc-gateway-plugin_name'),
                    'type' => 'checkbox',
                    'label' => $this ->title,
                    'default' => 'no'
                ),
                
                'title' => array(
                    'title'       => __( 'Title', 'wc-gateway-plugin_name' ),
                    'type'        => 'text',
                    'description' => __( 'Plugin Name facilite les paiements sur votre boutique en ligne', 'wc-gateway-plugin_name' ),
                    'default'     => $this->method_title,
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'wc-gateway-plugin_name' ),
                    'type'        => 'textarea',
                    'description'     => __( 'Ce message apparaitra dans la checkout page de votre boutique', 'wc-gateway-plugin_name' ),
                    'default'     => __('Vous serez automatiquement rediriger sur la page de paiement de Plugin Name afin de finaliser votre paiement!', 'wc-gateway-plugin_name'),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', 'wc-gateway-plugin_name' ),
                    'type'        => 'textarea',
                    'description' => __( 'Un message que vous enverez  à vos clients une fois leurs commandes traitées', 'wc-gateway-plugin_name' ),
                    'default'     => __('Ce texte sera envoyé au client une fois l\'opération traiter avec SUCCES', 'wc-gateway-plugin_name'),
                    'desc_tip'    => true,
                )            
            ) 
        );
    }
    
    // This function will be use to collect our informations then sent it to our API
	public function firstApiCall($ch) {
		$username = 'something';
		$password = 'something_else';
		$data = array("grant_type" => "client_credentials");

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept' => 'application/json', 'Content-Type' => 'application/json'));
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));	
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$return = json_decode(curl_exec($ch));

		return $return;
	}

    // The function above role has been explain, so this one is use after the previous function
	public function secondApiCall($request, $token, $order) {
		$items = [];
		$amount = 0;

		foreach ( $order->get_items() as $item_id => $item ) {
			$array['itemId'] = $item->get_product_id();
			$array['particulars'] = $item->get_name();
			$array['quantity'] = $item->get_quantity();
			$array['unitCost'] = $item->get_subtotal() / $item->get_quantity();
			$array['subTotal'] = $item->get_subtotal();
			$amount += $item->get_subtotal();

			array_push($items, $array);
		}
		
		$id = [
			'uuid' => uniqid(),
			'version' => 'v1'
		];

		$myData = [
			"currency" => "XAF",
			"customerName" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			"description" => $order->get_payment_method_title(),
			"email" => $order->get_billing_email(),
			"expiryDate" => date('Y-m-d'),
			"id" => $id,
			"items" => $items,
			"langKey" => "fr",
			"merchantReference" => uniqid(),
			"orderDate" => date('Y-m-d'),
			"phoneNumber" => $order->get_billing_phone(),
			"receiptUrl" => $this->get_return_url( $order ),
			"totalAmount" => $amount
		];

		$headers = ['Content-Type: application/json', 'Accept: application/json', 'x-dev-smobilpal-merchant: plascom', 'Authorization: Bearer ' . $token];

		curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($request, CURLOPT_HEADER, FALSE);
		curl_setopt($request, CURLOPT_POST, TRUE);
		curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($myData));
		curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
		$orderPluginName = json_decode(curl_exec($request));

		return $orderPluginName;

	}

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

        global $woocommerce;

        $order = wc_get_order( $order_id );

        $returnurl = $this->get_return_url( $order ) . '&wc_order_id=' . $order_id;
        // $returnurl = $this->get_return_url( $order );


        if( $order ) {
			// Your code here!
			$first_url = 'https://api.url_example.com/';
			$second_url = 'https://api.url_example2.com';

			$curl = curl_init($first_url);
			$tokenApiResponse = $this->firstApiCall($curl);
			
			$curl = curl_init($second_url);
			$orderPaymentPlugin = $this->secondApiCall($curl, $tokenApiResponse->access_token, $order);
			//Based on the response from your payment gateway, you can set the the order status to processing or completed if successful:
			$order->update_status('processing','Additional data like transaction id or reference number');

			//once the order is updated clear the cart and reduce the stock
			$woocommerce->cart->empty_cart();
			$order->reduce_order_stock();

			//if the payment processing was successful, return an array with result as success and redirect to the order-received/thank you page.
			return array(
			    'result' => 'success',
			    'redirect' => $orderPaymentPlugin->redirectUrl
			);

        }

    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }
	
	
    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    
        if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
            echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        }
    }



    // HAVE A FUN!!!
} 