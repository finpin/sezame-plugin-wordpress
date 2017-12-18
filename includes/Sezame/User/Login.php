<?php

namespace Sezame\User;

class Login {
	protected $_nonce = null;
	protected $_options = Array();
	protected $_sezame_used = false;

	// logged in user
	/**
	 * @var \WP_User null
	 */
	protected $_user = null;

	public function __construct( $has_woocommerce ) {
		add_action( 'login_footer', array( $this, 'init' ) );
		if ( $has_woocommerce ) {
			add_action( 'woocommerce_login_form_start', array( $this, 'print_woocommerce_messagebox' ) );
			add_action( 'woocommerce_login_form_end', array( $this, 'init' ) );
		}
		add_action( 'wp_ajax_nopriv_sezame_login_action', array( $this, 'login_action_callback' ) );
		add_action( 'wp_ajax_nopriv_sezame_status_action', array( $this, 'status_action_callback' ) );
		add_filter( 'login_message', array( $this, 'login_message' ) );
		add_filter( 'wp_login', array( $this, 'fraud' ) );
		add_filter( 'authenticate', array( $this, 'authenticate' ), 1, 3 );
	}

	public function init() {
		if ( ! \Sezame\Options::getInstance()->is_available() ) {
			return;
		}
		$this->print_popup();
	}

	/**
	 * Hook into authenticate and return user, this skips password authentication
	 *
	 * @param $user
	 * @param $username
	 * @param $password
	 *
	 * @return \WP_User
	 */
	public function authenticate( $user, $username, $password ) {
		return $this->_user;
	}

	public function login_message( $message ) {
		return $message . '<p class="message" style="display: none" id="sezame-message-box"></p>';
	}

	public function login_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		if ( ! isset( $_POST['username'] ) || ! strlen( trim( $_POST['username'] ) ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'Username is required', 'sezame' ) ) );
		}

		$username = trim( $_POST['username'] );

		if ( is_email( $_POST['username'] ) ) {
			$user = get_user_by( 'email', $username );
		} else {
			$user = get_user_by( 'login', $username );
		}

		if ( $user === false ) {
			status_header( 404 );
			wp_send_json( array( 'message' => __( 'User not found', 'sezame' ) ) );
		}

		$username = $user->user_login;

		$ret           = new \stdClass();
		$ret->redirect = null;
		$ret->status   = 'initiated';

		try {

			$loginResponse = \Sezame\Model::getInstance()->login( $username );
			if ( $loginResponse->isNotfound() ) {
				status_header( 404 );
				wp_send_json( array( 'message' => __( 'User not found', 'sezame' ) ) );
			}

			if ( $loginResponse->isOk() ) {
				$ret->auth_id  = $loginResponse->getId();
				$ret->username = $username;
				$ret->started  = time();
			} else {
				status_header( 400 );
				wp_send_json( array( 'message' => __( 'Invalid login.', 'sezame' ) ) );
			}
			wp_send_json( $ret );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function status_action_callback() {

		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		if ( ! isset( $_POST['auth_id'] ) || ! isset( $_POST['started'] ) || ! isset( $_POST['username'] ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'Invalid login.', 'sezame' ) ) );
		}

		$auth_id  = $_POST['auth_id'];
		$username = $_POST['username'];
		$started  = $_POST['started'];
		$redirect = $_POST['redirect'];

		$ret           = new \stdClass();
		$ret->redirect = $redirect;
		$ret->status   = 'initiated';
		$ret->started  = $started;
		$ret->auth_id  = $auth_id;
		$ret->username = $username;
		$ret->message  = null;

		$timeout = (int) \Sezame\Options::getInstance()->get_timeout();

		if ( time() - $started > $timeout ) {
			$ret->status = 'timeout';
		} else {

			try {

				$statusResponse = \Sezame\Model::getInstance()->status( $auth_id );
				if ( $statusResponse->isAuthorized() ) {

					$this->_user = get_user_by( 'login', $username );
					if ( $this->_user !== false ) {
						$this->_sezame_used = true;
						$secure_cookie      = false;
						// invoke wp standard signon with dummy credentials
						if ( get_user_option( 'use_ssl', $this->_user->ID ) ) {
							$secure_cookie = true;
							force_ssl_admin( true );
						}

						if ( strlen( $redirect ) ) {
							if ( $secure_cookie && false !== strpos( $redirect, 'wp-admin' ) ) {
								$redirect = preg_replace( '|^http://|', 'https://', $redirect );
							}
						} else {
							$redirect = admin_url();
						}

						wp_signon( Array( 'user_login' => $username, 'user_password' => null ) );

						if ( ( empty( $redirect ) || $redirect == 'wp-admin/' || $redirect == admin_url() ) ) {
							// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
							if ( is_multisite() && ! get_active_blog_for_user( $this->_user->ID ) && ! is_super_admin( $this->_user->ID ) ) {
								$redirect = user_admin_url();
							} elseif ( is_multisite() && ! $this->_user->has_cap( 'read' ) ) {
								$redirect = get_dashboard_url( $this->_user->ID );
							} elseif ( ! $this->_user->has_cap( 'edit_posts' ) ) {
								$redirect = admin_url( 'profile.php' );
							}
						}

						$ret->redirect = $redirect;
						$ret->status   = 'authorized';

					} else {
						$ret->status = 'notfound';
					}
				}

				if ( $statusResponse->isDenied() ) {
					$ret->status = 'denied';
				}

			} catch ( \Exception $e ) {
				status_header( 400 );
				wp_send_json( array( 'message' => $e->getMessage() ) );
				//wp_send_json( array( 'message' => 'Invalid login.' ) );
			}
		}

		switch ( $ret->status ) {
			case 'timeout':
				$ret->message = __( 'The request has not been authorized in time.', 'sezame' );
				break;

			case 'denied':
				$ret->message = __( 'Login denied.', 'sezame' );
				break;

			case 'notfound':
				$ret->message = __( 'Invalid login.', 'sezame' );
				break;
		}

		wp_send_json( $ret );
	}

	/**
	 * @param $user_login
	 * @param \WP_user $user
	 */
	public function fraud( $user_login, $user = null ) {
		if ( $this->_sezame_used ) {
			return;
		}
		$user = get_user_by( 'login', $user_login );
		if ( $user && \Sezame\Options::getInstance()->has_fraud() && get_user_option( 'sezame_fraud', $user->ID ) ) {
			if ( get_user_option( 'sezame_fraud', $user->ID ) ) {
				\Sezame\Model::getInstance()->fraud( $user_login );
			}
		}
	}

	protected function print_popup() {
		$imgPath = plugins_url( 'sezame' ) . '/assets/';
		$imgurl  = plugins_url( 'sezame' ) . '/assets/login-with-sezame.png';
		$spacer  = plugins_url( 'sezame' ) . '/assets/spacer.gif';
		printf( '<p style="text-align: center; margin-top: 1em;"><a href="#" id="sezame-do-login"><img src="%s"/></a></p>', $imgurl );

		?>
		<div id="sezameLoginBubble">
			<div class="sezameclose"><a href="javascript:;" title="cancel" id="sezame-cancel-login"><img
						src="<?php echo $spacer ?>" width="64" height="64" alt="cancel" title="cancel"/></a></div>
			<div
				class="sezameMessage"><?php echo __( 'Please open the sezame app and authorize the authentication request!', 'sezame' ) ?></div>
		</div>
		<style type="text/css">
			#sezameLoginBubble {
				background: url('<?php echo $imgPath ?>login-bubble.png') 0 0 no-repeat;
				display: none;

				position: fixed;
				top: 50%;
				left: 50%;
				margin-left: -279px;
				margin-top: -279px;
				z-index: 200;
				width: 594px;
				height: 558px;
				text-align: center;
			}

			#sezameLoginBubble .sezameMessage {
				font-weight: 700;
				font-size: 20px;
				color: #628070;
				width: 325px;
				line-height: 22px;
				position: absolute;
				top: 200px;
				left: 110px;
			}

			#sezameLoginBubble .sezameclose {
				position: absolute;
				right: 6px;
				top: 257px;
				width: 64px;
				height: 64px;
			}

			@media (max-width: 736px) {
				#sezameLoginBubble {
					background: url('<?php echo $imgPath ?>login-bubble-responsive-1.png') 0 0 no-repeat;

					width: 297px;
					height: 279px;

					margin-left: -148px;
					margin-top: -139px;
				}

				#sezameLoginBubble .sezameMessage {
					width: 200px;
					position: absolute;
					top: 90px;
					left: 40px;
				}

				#sezameLoginBubble .sezameclose {
					position: absolute;
					right: 3px;
					top: 128px;
					width: 32px;
					height: 32px;
				}
		</style>

		<?php
	}

	public function print_woocommerce_messagebox() {
		print '<p class="message" style="display: none" id="sezame-message-box"></p>';
	}

}