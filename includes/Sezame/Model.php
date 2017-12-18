<?php

namespace Sezame;

class Model {

	/**
	 * @return \Sezame\Model
	 */
	public static function getInstance() {
		static $_instance = Array();

		$class = get_called_class();
		if ( ! isset( $_instance[ $class ] ) ) {
			$_instance[ $class ] = new $class;
		}

		return $_instance[ $class ];
	}

	/**
	 * @return \SezameLib\Client
	 */
	public function get_client($bootstrap = false) {
		if ($bootstrap)
			return new \SezameLib\Client();

		return new \SezameLib\Client(
			\Sezame\Options::getInstance()->get_certificate(),
			\Sezame\Options::getInstance()->get_privatekey(),
			\Sezame\Options::getInstance()->get_keypassword() );
	}

	/**
	 * @param $username
	 *
	 * @return \SezameLib\Response\Auth
	 */
	public function login( $username ) {
		$client = $this->get_client();

		return $client->authorize()->setUsername( $username )->send();
	}

	/**
	 * @param $auth_id
	 *
	 * @return \SezameLib\Response\Status
	 */
	public function status( $auth_id ) {
		$client = $this->get_client();

		return $client->status()->setAuthId( $auth_id )->send();
	}

	public function fraud( $username ) {
		$client = $this->get_client();

		return $client->authorize()->setUsername( $username )->setType( 'fraud' )->setTimeout( 1440 )->send();
	}

	public function register( $email, $name ) {
		$options = \Sezame\Options::getInstance();

		$client          = $this->get_client(true);
		$registerRequest = $client->register()->setEmail( $email )->setName( $name );

		$registerResponse = $registerRequest->send();

		$options->set_clientcode( $registerResponse->getClientCode() )
		        ->set_sharedsecret( $registerResponse->getSharedSecret() )
		        ->set_email( $email )
		        ->save();

		return $registerResponse;
	}

	public function cancel() {
		if ( \Sezame\Options::getInstance()->is_available() ) {
			$this->get_client()->cancel()->send();
		}
		\Sezame\Options::getInstance()->cancel()->save();

		return $this;
	}

	public function sign() {
		$options = \Sezame\Options::getInstance();

		$client = $this->get_client(true);

		$signRequest  = $client->sign()->setCSR( $options->get_csr() )->setSharedSecret( $options->get_sharedsecret() );
		$signResponse = $signRequest->send();

		if ( $signResponse->isNotfound() ) {
			throw new \Exception( 'notfound' );
		}

		$options->set_certificate( $signResponse->getCertificate() )->save();

		return $signResponse;
	}

	public function makecsr( $name, $email, $keypassword = null ) {
		$options = \Sezame\Options::getInstance();

		$client = $this->get_client();
		$csrKey = $client->makeCsr( $options->get_clientcode(), $email, $keypassword,
			Array(
				'organizationName' => $name,
			) );

		$options->set_csr( $csrKey->csr )->set_privatekey( $csrKey->key )->set_keypassword( $keypassword )->save();

		return $csrKey;
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public function is_paired( $user ) {
		$response = $this->get_client()->linkStatus()->setUsername( $user->user_login )->send();

		return $response->isLinked();
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public function unpair( $user ) {
		$removeRequest = $this->get_client()->linkDelete()->setUsername( $user->user_login );

		return $removeRequest->send();
	}

	public function pair( $user ) {
		$linkRequest = $this->get_client()->link()->setUsername( $user->user_login );

		return $linkRequest->send();
	}
}