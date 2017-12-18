<?php

namespace Sezame;

class Options {
	protected $_options = array();

	protected $_defaults = array(
		'enabled'      => false,
		'expertmode'   => false,
		'timeout'      => 15,
		'state'        => 'new',
		'fraud'        => false,
		'clientcode'   => null,
		'sharedsecret' => null,
		'certificate'  => null,
		'csr'          => null,
		'privatekey'   => null,
		'keypassword'  => null
	);

	protected $_nonce = null;

	/**
	 * @return \Sezame\Options
	 */
	public static function getInstance() {
		static $_instance = Array();

		$class = get_called_class();
		if ( ! isset( $_instance[ $class ] ) ) {
			$_instance[ $class ] = new $class;
		}

		return $_instance[ $class ];
	}

	public function __construct() {
		$this->_options = get_option( 'sezame_settings' );
		if ( $this->_options === false ) {
			$this->_options = $this->_defaults;
		}

		return $this->_options;
	}

	public function get() {
		return $this->_options;
	}


	public function is_available() {
		if ( ! isset( $this->_options['state'] ) || ! isset( $this->_options['enabled'] ) ) {
			return false;
		}

		if ( ! $this->_options['enabled'] ) {
			return false;
		}

		return $this->is_completed();
	}

	public function is_completed() {
		return $this->_options['state'] == 'completed';
	}

	public function is_registered() {
		return $this->_options['state'] == 'registered';
	}

	public function is_new() {
		return $this->_options['state'] == 'new';
	}

	public function is_enabled() {
		return $this->_options['enabled'];
	}

	public function set_enabled( $bool ) {
		$this->_options['enabled'] = $bool;

		return $this;
	}

	public function get_state() {
		return $this->_options['state'];
	}

	public function set_state( $state ) {
		$this->_options['state'] = $state;

		return $this;
	}

	public function has_exportmode() {
		return $this->_options['expertmode'];
	}

	public function get_timeout() {
		return $this->_options['timeout'];
	}

	public function set_timeout( $timeout ) {
		$this->_options['timeout'] = (int) $timeout;

		return $this;
	}

	public function has_fraud() {
		return $this->_options['fraud'];
	}

	public function set_fraud( $bool ) {
		$this->_options['fraud'] = $bool;

		return $this;
	}

	public function get_email() {
		return $this->_options['email'];
	}

	public function set_email( $email ) {
		$this->_options['email'] = $email;

		return $this;
	}

	public function get_clientcode() {
		return $this->_options['clientcode'];
	}

	public function set_clientcode( $clientcode ) {
		$this->_options['clientcode'] = $clientcode;

		return $this;
	}

	public function get_sharedsecret() {
		return $this->_options['sharedsecret'];
	}

	public function set_sharedsecret( $sharedsecret ) {
		$this->_options['sharedsecret'] = $sharedsecret;

		return $this;
	}

	public function get_csr() {
		return $this->_options['csr'];
	}

	public function set_csr( $csr ) {
		$this->_options['csr'] = $csr;

		return $this;
	}

	public function get_certificate() {
		return $this->_options['certificate'];
	}

	public function set_certificate( $certificate ) {
		$this->_options['certificate'] = $certificate;

		return $this;
	}

	public function get_privatekey() {
		return $this->_options['privatekey'];
	}

	public function set_privatekey( $privatekey ) {
		$this->_options['privatekey'] = $privatekey;

		return $this;
	}

	public function get_keypassword() {
		return $this->_options['keypassword'];
	}

	public function set_keypassword( $keypassword ) {
		$this->_options['keypassword'] = $keypassword;

		return $this;
	}

	public function cancel() {
		$this->_options['privatekey']   = '';
		$this->_options['certificate']  = '';
		$this->_options['csr']          = '';
		$this->_options['sharedsecret'] = '';
		$this->_options['clientcode']   = '';
		$this->_options['enabled']      = false;
		$this->_options['state']        = 'new';

		return $this;
	}

	public function sanitize( $input ) {
		$new_input = $this->_defaults;

		if ( isset( $input['enabled'] ) ) {
			$new_input['enabled'] = is_bool( $input['enabled'] ) ? $input['enabled'] : bool_from_yn( $input['enabled'] );
		} else {
			$new_input['enabled'] = false;
		}

		if ( isset( $input['expertmode'] ) ) {
			$new_input['expertmode'] = is_bool( $input['expertmode'] ) ? $input['expertmode'] : bool_from_yn( $input['expertmode'] );
		} else {
			$new_input['expertmode'] = false;
		}

		if ( isset( $input['timeout'] ) ) {
			$new_input['timeout'] = (int) $input['timeout'];
		}

		if ( isset( $input['fraud'] ) ) {
			$new_input['fraud'] = is_bool( $input['fraud'] ) ? $input['fraud'] : bool_from_yn( $input['fraud'] );
		} else {
			$new_input['fraud'] = false;
		}

		if ( isset( $input['email'] ) && strlen( $input['email'] ) ) {
			$new_input['email'] = sanitize_email( $input['email'] );
			if ( ! is_email( $new_input['email'] ) ) {
				add_settings_error( 'sezame_settings[email]', 'sezame-setting-email', 'E-Mail is invalid' );
			}
		} else {
			$new_input['email'] = $this->_options['email'];
		}

		if ( isset( $input['clientcode'] ) && preg_match( '/^[a-f0-9.]+$/', $input['clientcode'] ) ) {
			$new_input['clientcode'] = $input['clientcode'];
		} else {
			$new_input['clientcode'] = $this->_options['clientcode'];
		}

		if ( isset( $input['sharedsecret'] ) && preg_match( '/^[a-f0-9]+$/', $input['sharedsecret'] ) ) {
			$new_input['sharedsecret'] = $input['sharedsecret'];
		} else {
			$new_input['sharedsecret'] = $this->_options['sharedsecret'];
		}

		if ( isset( $input['privatekey'] ) ) {
			$new_input['privatekey'] = $input['privatekey'];
		} else {
			$new_input['privatekey'] = $this->_options['privatekey'];
		}

		if ( isset( $input['keypassword'] ) ) {
			$new_input['keypassword'] = $input['keypassword'];
		} else {
			$new_input['keypassword'] = $this->_options['keypassword'];
		}

		if ( isset( $input['csr'] ) ) {
			$new_input['csr'] = $input['csr'];
		} else {
			$new_input['csr'] = $this->_options['csr'];
		}

		if ( isset( $input['certificate'] ) ) {
			$new_input['certificate'] = $input['certificate'];
		} else {
			$new_input['certificate'] = $this->_options['certificate'];
		}

		if ( strlen( $new_input['clientcode'] ) && strlen( $new_input['sharedsecret'] ) ) {
			if ( strlen( $new_input['certificate'] ) && strlen( $new_input['privatekey'] ) ) {
				$new_input['state'] = 'completed';
			} else {
				$new_input['state'] = 'registered';
			}
		} else {
			$new_input['state'] = 'new';
		}

		return $new_input;
	}

	public function save() {
		if ( strlen( $this->get_sharedsecret() && strlen( $this->get_clientcode() ) ) ) {
			if ( strlen( $this->get_certificate() && strlen( $this->get_privatekey() ) ) ) {
				$this->set_state( 'completed' );
			} else {
				$this->set_state( 'registered' );
			}
		} else {
			$this->set_state( 'new' );
		}

		wp_cache_delete( 'alloptions', 'options' );
		update_option( 'sezame_settings', $this->get() );
	}
}