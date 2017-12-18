<?php
/*
Plugin Name: Sezame
Plugin URI: http://wordpress.org/extend/plugins/sezame
Description: sezame is  a secure and simple multi-factor authentication solution. You only need the username and your fingerprint on your smartphone to log into any sezame-enabled site.
Version: 1.0.0
Author: Sezame
Author URI: https://seza.me/
License: MIT
License URI: http://opensource.org/licenses/MIT
*/

define( 'SEZAME_URL', plugin_dir_url( __FILE__ ) );
define( 'SEZAME_DIR', plugin_dir_path( __FILE__ ) );

spl_autoload_register( 'sezame_autoload' );

function sezame_autoload( $class ) {
	$base_dir = __DIR__ . '/includes/';

	$namespaces = array( 'Endroid', 'Buzz', 'SezameLib', 'Sezame' );
	$namespace  = null;
	foreach ( $namespaces as $ns ) {

		if ( strncmp( $ns, $class, strlen( $ns ) ) !== 0 ) {
			continue;
		} else {
			$namespace = $ns;
			break;
		}
	}
	if ( $namespace === null ) {
		return;
	}

	$file = $base_dir . str_replace( '\\', '/', $class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

if ( is_admin() ) {
	$my_settings_page = new Sezame\Admin\Settings();
}

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'sezame', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
} );


add_filter( 'query_vars', 'sezame_custom_query_vars' );

function sezame_custom_query_vars( $vars ) {
	$vars[] = 'sezame_action';

	return $vars;
}

add_action( 'init', function () {
	$active_plugins  = (array) get_option( 'active_plugins', array() );
	$has_woocommerce = in_array( 'woocommerce/woocommerce.php', $active_plugins );

	wp_enqueue_script( "sezame-javascript", plugins_url( 'sezame' ) . '/assets/sezame.js', array( 'jquery' ) );

	$requested_redirect_to = '';
	if ( ! is_ajax() ) {
		$requested_redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
		if ( ! strlen( $requested_redirect_to ) && $has_woocommerce ) {
			$requested_redirect_to = $_SERVER["REQUEST_URI"];
		}
	}

	if ( preg_match( '/wp-login\.php/', $requested_redirect_to ) ) {
		$requested_redirect_to = admin_url();
	}

	wp_localize_script( 'sezame-javascript', 'SezameAjax', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'sezame-ajax-nonce' ),
			'redirect' => $requested_redirect_to
		)
	);

	if ( ! is_user_logged_in() ) {
		new Sezame\User\Login( $has_woocommerce );
	} else {
		new Sezame\User\Profile();
		if ( $has_woocommerce ) {
			new Sezame\User\MyAccount();
		}
	}

} );


if ( ! function_exists( 'is_ajax' ) ) {
	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}