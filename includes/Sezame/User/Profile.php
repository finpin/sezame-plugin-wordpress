<?php

namespace Sezame\User;

class Profile {
	protected $_nonce = null;

	public function __construct() {
		add_action( 'profile_personal_options', array( $this, 'init' ) );
		add_action( 'personal_options_update', Array( $this, 'save_options' ) );
		add_action( 'delete_user', array( $this, 'delete_user' ) );
		add_action( 'wp_ajax_sezame_removepairing_action', array( $this, 'removepairing_action_callback' ) );
		add_action( 'wp_ajax_sezame_savefraudoption_action', array( $this, 'savefraudoption_action_callback' ) );
		add_action( 'parse_request', array( $this, 'dispatch_request' ) );
	}

	public function dispatch_request( $wp ) {
		if ( $wp->query_vars['sezame_action'] == 'qrcode' ) {
			require SEZAME_DIR . 'pair.php';
		}
	}

	/**
	 * @param \WP_User $user
	 */
	public function init( $user ) {
		if ( ! \Sezame\Options::getInstance()->is_available() ) {
			return;
		}

		?>
		<h3><?php _e( 'Sezame' ) ?></h3>

		<table class="form-table">
			<tr class="user-user-login-wrap">
				<th><label for="sezame_enable"><?php _e( 'Pairing', 'sezame' ); ?></label></th>
				<td>
					<?php
					if ( \Sezame\Model::getInstance()->is_paired( $user ) ) {
						print get_submit_button( __( 'Remove pairing', 'sezame' ), 'secondary', 'sezame-removepair' );
					} else {
						add_thickbox();
						$imgurl = plugins_url( 'sezame' ) . '/assets/pair-with-sezame.png';
						printf( '<a href="%s/index.php?sezame_action=qrcode&width=600&height=600" class="thickbox"><img src="%s" title="pair with sezame"/></a>', get_site_url(), $imgurl );

						?>
						<script type="text/javascript">
							jQuery(document).ready(function (jQuery) {
								var old_tb_remove = window.tb_remove;

								window.tb_remove = function () {
									old_tb_remove(); // calls the tb_remove() of the Thickbox plugin
									document.location.reload(true);
								};
							});
						</script>
						<?php
					}
					?>
				</td>
			</tr>
			<?php
			if ( \Sezame\Options::getInstance()->has_fraud() ) {
				?>
				<tr class="user-user-login-wrap">
					<th><label for="sezame_fraud"><?php _e( 'Fraud protection', 'sezame' ); ?></label></th>
					<td>
						<?php
						printf( '<input type="checkbox" value="true" id="sezame_fraud" name="sezame_fraud" %s/>', get_user_option( 'sezame_fraud' ) ? 'checked="checked"' : '' );
						_e( "You will get a notification on your Sezame App, if password login was used.", 'sezame' )
						?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}

	public function save_options( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'sezame_fraud', (int) ( $_POST['sezame_fraud'] == 'true' ) );
	}


	public function removepairing_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$current_user = wp_get_current_user();

		try {
			\Sezame\Model::getInstance()->unpair( $current_user );

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function savefraudoption_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$current_user = wp_get_current_user();
		if ( ! is_object( $current_user ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Forbidden' ) );
		}

		try {
			update_user_meta( $current_user->ID, 'sezame_fraud', (int) ( $_POST['sezame_fraud'] == 'true' ) );
			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function delete_user( $id, $reassign = null ) {
		$user = new \WP_User( $id );

		if ( ! $user->exists() ) {
			return;
		}

		if ( ! \Sezame\Options::getInstance()->is_available() ) {
			return;
		}

		if ( \Sezame\Model::getInstance()->is_paired( $user ) ) {
			\Sezame\Model::getInstance()->unpair( $user );
		}
	}

}