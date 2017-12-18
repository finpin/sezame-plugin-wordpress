<?php

namespace Sezame\User;

class MyAccount {
	protected $_nonce = null;

	public function __construct() {
//		add_action( 'woocommerce_after_my_account', array( $this, 'init' ) );
		add_action( 'woocommerce_edit_account_form_end', array( $this, 'init' ) );
	}

	public function init() {
		$user = wp_get_current_user();
		if ( ! \Sezame\Options::getInstance()->is_available() ) {
			return;
		}

		?>
		<h2><?php _e( 'Sezame' ) ?></h2>
		<p class="form-row form-row-wide">
			<?php
			if ( \Sezame\Model::getInstance()->is_paired( $user ) ) {
				printf( '<a href="#" class="edit" id="sezame-removepair">%s</a>', __( 'Remove pairing', 'sezame' ) );
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
		</p>
		<?php
		if ( \Sezame\Options::getInstance()->has_fraud() ) {
			?>
			<p class="form-row form-row-wide">
				<label for="sezame-fraud-option"><?php _e( 'Fraud protection', 'sezame' ); ?></label>
				<?php
				printf( '<input type="checkbox" value="true" id="sezame-fraud-option" name="sezame_fraud" %s/>&nbsp;',
					get_user_option( 'sezame_fraud' ) ? 'checked="checked"' : '' );
				_e( "You will get a notification on your Sezame App, if password login was used.", 'sezame' )
				?>
			</p>
			<?php
		}
		?>
		</p>
		<?php
	}

	public function save_options( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'sezame_fraud', (int) ( $_POST['sezame_fraud'] == 'true' ) );
	}


}