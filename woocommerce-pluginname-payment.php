<?php
/**
 * Plugin Name: Plugin Name for WC
 * Plugin URI: https://www.pluginuri.com
 * Description: Plugin Name description text (just a few words that explain this fact your plugin)
 * Author: Menders Digital (Ernest Mekntso NDE)
 * Author URI: https://www.authoruri.com
 * Requires at least: 4.3
 * Requires PHP: 7.0
 * Version: 1.0.0
 * Text Domain: wc-gateway-plugin_name
 *
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway
 * @author    Menders Digital (Ernest Mekntso NDE)
 * @category  Admin
 * @license   GPL-3.0-or-later
 * @copyright 2020 Plugin Name Payment. Tous  droits réservés.
 * 
 * This gateway integrate a payement API REST.
 */

if(!defined('ABSPATH')) exit;

define('WC_GATEWAY_URL', plugin_dir_url( __FILE__ ));
define('WC_GATEWAY_BASENAME', plugin_basename( __FILE__ ));

/**
 * Check if this gateway is available in the user's country based on currency.
 *
 * @return bool
 */
function is_valid_for_use() {
    return in_array(
        get_woocommerce_currency(),
        apply_filters(
            'woocommerce_supported_currencies', [ 'USD', 'XOF', 'XAF' ]
        ),
        true
    );
}

function wc_gateway_activation(){

    global $woocommerce;

    if(!is_plugin_active( 'woocommerce/woocommerce.php' )) {

        deactivate_plugins( WC_GATEWAY_BASENAME ); //the curent plugin automatically turn off

        $message ='<center>' . sprintf(__('WOOCOMMERCE NON ACTIVE : Afin de pouvoir utiliser le plugin %s, vous devez au préalable installer et activer le plugin WooCommerce!', 'wc-gateway-plugin_name'), 'Plugin Name Payment') . '</center>';
        wp_die( $message, 'Plugin Name Payment Alert Message', ['back_link' => true ] );

    }elseif( is_plugin_active( 'woocommerce/woocommerce.php' ) && (!version_compare($woocommerce->version, '4.1.0', '>='))) {

        deactivate_plugins( WC_GATEWAY_BASENAME ); //the curent plugin automatically turn off

        $message ='<center>' . sprintf(__('WOOCOMMERCE INCOMPATIBLE : Votre version de WooCommerce semble dépassée, bien vouloir procéder à une mise à jour !', 'wc-gateway-plugin_name'), 'Plugin Name Payment') . '</center>';
        echo '<span style="text-align: right;">' . wp_die( $message, 'Plugin Name Payment Alert Message', ['back_link' => true ] ) . '</span>';
    }
    
}
register_activation_hook( __FILE__, 'wc_gateway_activation' );


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 0.0.1
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_add_to_gateways' );




/**
 * Adds plugin page links
 * 
 * @since 0.0.1
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-gateway-plugin_name' ) . '">' . __( 'Configure', 'wc-gateway-plugin_name' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_gateway_plugin_links' );



function wc_gateway_init() {

    if( !class_exists('WC_Gateway')){
        require_once 'class-pluginnamegateway-config.php';
    }
	
}

add_action( 'plugins_loaded', 'wc_gateway_init', 11 );