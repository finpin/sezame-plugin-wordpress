<?php

namespace Sezame\Admin;

class Settings {
	protected $_nonce = null;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'wp_ajax_sezame_register_action', array( $this, 'register_action_callback' ) );
		add_action( 'wp_ajax_sezame_sign_action', array( $this, 'sign_action_callback' ) );
		add_action( 'wp_ajax_sezame_signexpert_action', array( $this, 'signexpert_action_callback' ) );
		add_action( 'wp_ajax_sezame_cancel_action', array( $this, 'cancel_action_callback' ) );
		add_action( 'wp_ajax_sezame_makecsr_action', array( $this, 'makecsr_action_callback' ) );
		add_action( 'admin_head', array( $this, 'page_css' ) );
	}

	public function add_menu() {
		add_menu_page(
			"Sezame", // page title
			'Sezame', // menu title
			"manage_options", // caps
			'sezame_page', // slug, id
			array( $this, 'create_page' ),
			SEZAME_URL . 'assets/sezame-menu-icon.png'
		);
	}

	public function page_css() {
		$itunesImg  = plugins_url( 'sezame' ) . '/assets/apple-itunes-badge.png';
		$playImg  = plugins_url( 'sezame' ) . '/assets/google-play-badge.png';

		echo '<style type="text/css">';
		echo <<<CSS
		.get-it-playstore {
			background-image: url('$playImg');
			width: 200px;
			height: 59px;
			display: inline-block;
			margin: 10px;
		}
	
		.get-it-itunes {
			background-image: url('$itunesImg');
			width: 162px;
			height: 59px;
			display: inline-block;
			margin: 10px;
		}
	
		.get-it {
		}
		
		.get-it a {
			text-decoration: none;
		}

CSS;
		echo '</style>';
	}

	public function page_init() {

		register_setting( 'sezame_options', // option group
			'sezame_settings',  // option name, value in option_name field of wp_options table
			array( $this, 'sanitize' ) );

		add_settings_section(
			'section_settings', // ID
			__( 'Settings', 'sezame' ), // Title
			array( $this, 'print_section_settings_info' ), // Callback
			'sezame_page' // Page
		);

		add_settings_field(
			'exportmode',
			__( 'Expert mode', 'sezame' ),
			array( $this, 'field_expertmode_callback' ),
			'sezame_page',
			'section_settings'
		);

		add_settings_field(
			'enabled',
			__( 'Enabled', 'sezame' ),
			array( $this, 'field_enabled_callback' ),
			'sezame_page',
			'section_settings'
		);

		add_settings_field(
			'timeout',
			__( 'Timeout', 'sezame' ),
			array( $this, 'field_timeout_callback' ),
			'sezame_page',
			'section_settings'
		);

		add_settings_field(
			'fraud',
			__( 'Fraud protection', 'sezame' ),
			array( $this, 'field_fraud_callback' ),
			'sezame_page',
			'section_settings'
		);

		if ( \Sezame\Options::getInstance()->has_exportmode() ) {
			$this->page_init_expert();
		} else {
			$this->page_init_standard();
		}

	}

	public function page_init_standard() {
		// step1
		add_settings_section(
			'section_step1',
			__( 'Step 1', 'sezame' ),
			array( $this, 'print_section_step1_info' ),
			'sezame_page'
		);

		add_settings_field(
			'email',
			__( 'E-Mail', 'sezame' ),
			array( $this, 'field_email_callback' ),
			'sezame_page',
			'section_step1'
		);

		add_settings_field(
			'registeraction',
			'',
			array( $this, 'field_registeraction_callback' ),
			'sezame_page',
			'section_step1'
		);


		// step2
		add_settings_section(
			'section_step2',
			__( 'Step 2', 'sezame' ),
			array( $this, 'print_section_step2_info' ),
			'sezame_page'
		);

		add_settings_field(
			'signaction',
			'',
			array( $this, 'field_signaction_callback' ),
			'sezame_page',
			'section_step2'
		);

		// cancel
		add_settings_section(
			'section_cancel',
			__( 'Cancel service', 'sezame' ),
			array( $this, 'print_section_cancel_info' ),
			'sezame_page'
		);

		add_settings_field(
			'cancelaction',
			'',
			array( $this, 'field_cancelaction_callback' ),
			'sezame_page',
			'section_cancel'
		);

	}

	public function page_init_expert() {
		// step1
		add_settings_section(
			'section_step1_expert',
			__( 'Step 1', 'sezame' ),
			array( $this, 'print_section_step1_expert_info' ),
			'sezame_page'
		);

		add_settings_field(
			'clientcode',
			__( 'Client code', 'sezame' ),
			array( $this, 'field_clientcode_callback' ),
			'sezame_page',
			'section_step1_expert'
		);

		add_settings_field(
			'sharedsecret',
			__( 'Shared secret', 'sezame' ),
			array( $this, 'field_sharedsecret_callback' ),
			'sezame_page',
			'section_step1_expert'
		);

		// step2
		add_settings_section(
			'section_step2_expert',
			__( 'Step 2', 'sezame' ),
			array( $this, 'print_section_step2_expert_info' ),
			'sezame_page'
		);

		add_settings_field
		(
			'keypassword',
			__( 'Key password', 'sezame' ),
			array( $this, 'field_keypassword_callback' ),
			'sezame_page',
			'section_step2_expert'
		);

		add_settings_field(
			'makescsraction',
			'',
			array( $this, 'field_makecsraction_callback' ),
			'sezame_page',
			'section_step2_expert'
		);

		add_settings_field(
			'csr',
			__( 'CSR', 'sezame' ),
			array( $this, 'field_csr_callback' ),
			'sezame_page',
			'section_step2_expert'
		);

		add_settings_field(
			'privatekey',
			__( 'Private key', 'sezame' ),
			array( $this, 'field_privatekey_callback' ),
			'sezame_page',
			'section_step2_expert'
		);

		add_settings_field(
			'privatekey',
			__( 'Private key', 'sezame' ),
			array( $this, 'field_privatekey_callback' ),
			'sezame_page',
			'section_step2_expert'
		);

		// step3
		add_settings_field(
			'signexpertaction',
			'',
			array( $this, 'field_signexpertaction_callback' ),
			'sezame_page',
			'section_step3_expert'
		);

		add_settings_section(
			'section_step3_expert',
			__( 'Step 3', 'sezame' ),
			array( $this, 'print_section_step3_expert_info' ),
			'sezame_page'
		);

		add_settings_field(
			'certificate',
			__( 'Certificate', 'sezame' ),
			array( $this, 'field_certificate_callback' ),
			'sezame_page',
			'section_step3_expert'
		);
	}


	/**
	 * Options page callback
	 */
	public function create_page() {

		?>
		<div class="wrap">
			<h2><?php echo __( 'Sezame Settings', 'sezame' ) ?></h2>
			<?php settings_errors(); ?>

			<div></div>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'sezame_options' );
				do_settings_sections( 'sezame_page' );
				submit_button( null, 'primary', 'submit', false );
				?>
			</form>
		</div>
		<?php
	}

	public function print_section_settings_info() {
		print __( 'Setup Status: ', 'sezame' );
		if ( \Sezame\Options::getInstance()->is_available() ) {
			print  __( "Sezame is functional", 'sezame' );
		} else {
			print __( \Sezame\Options::getInstance()->get_state(), 'sezame' );
		}
	}

	public function print_section_step1_info() {
		print  __( 'Get the Sezame app via your favorite appstore and register with sezame. Enter your recovery E-Mail address below and press the register button.', 'sezame' );

		echo <<<HTML
		<div class="get-it">
			<a href="https://play.google.com/store/apps/details?id=com.bytefex.finprin.sezame_v1" target="_blank">
				<div class="get-it-playstore"></div>
			</a>
			<a href="https://itunes.apple.com/us/app/sezame/id1080201225?l=de&ls=1&mt=8" target="_blank">
				<div class="get-it-itunes"></div>
			</a>
		</div>
HTML;
	}

	public function print_section_step2_info() {
		print  __( 'After registering finally sign up with Sezame, by pressing the Sign button, after this step Sezame is functional:', 'sezame' );
	}

	public function print_section_step1_expert_info() {
		print  __( 'Get the Shared secret and Client code using the sezame support channels, or use existing ones.', 'sezame' );
	}

	public function print_section_step2_expert_info() {
		print  __( 'Generate the CSR and private Key or paste existing values.', 'sezame' );
	}

	public function print_section_step3_expert_info() {
		print  __( 'Get the certificate by contacting Sezame support channels, or paste an existing certificate.', 'sezame' );
	}

	public function print_section_cancel_info() {
		print  __( 'Cancel Sezame, this will disable your registration:', 'sezame' );
	}

	public function field_enabled_callback() {
		printf(
			'<input type="checkbox" value="Y" id="sezame-setting-enabled" name="sezame_settings[enabled]" autocomplete="off" %s/>',
			\Sezame\Options::getInstance()->is_enabled() ? 'checked="checked"' : ''
		);
	}

	public function field_expertmode_callback() {
		printf(
			'<input type="checkbox" value="Y" id="sezame-setting-expertmode" name="sezame_settings[expertmode]" autocomplete="off" %s/>',
			\Sezame\Options::getInstance()->has_exportmode() ? 'checked="checked"' : ''
		);
		printf( '<p class="description">%s</p>', __( 'Set up Szeame using existing credentials, or get the credentials from the Sezame support channels, without the need of installing the Sezame app.', 'sezame' ) );
	}

	public function field_timeout_callback() {
		printf(
			'<input type="text" id="sezame-setting-timeout" name="sezame_settings[timeout]" value="%s" style="width:50px;" autocomplete="off"/>',
			esc_attr( \Sezame\Options::getInstance()->get_timeout() )
		);
		printf( '<p class="description">%s</p>', __( 'Authentication timeout in seconds.', 'sezame' ) );
	}

	public function field_fraud_callback() {
		printf(
			'<input type="checkbox" value="Y" id="sezame-setting-fraud" name="sezame_settings[fraud]" autocomplete="off" %s/>',
			\Sezame\Options::getInstance()->has_fraud() ? 'checked="checked"' : ''
		);
		printf( '<p class="description">%s</p>', __( 'Enable fraud protection, users get a notification about password logins.', 'sezame' ) );
	}

	public function field_email_callback() {
		printf(
			'<input type="text" id="sezame-setting-email" name="sezame_settings[email]" value="%s" style="width:400px;"/>',
			esc_attr( \Sezame\Options::getInstance()->get_email() )
		);
		printf( '<p class="description">%s</p>', __( 'Recovery E-Mail entered on the sezame app, needed for auto activation.', 'sezame' ) );
	}

	public function field_registeraction_callback() {
		$type = ( \Sezame\Options::getInstance()->is_new() || \Sezame\Options::getInstance()->is_registered() ) ? 'secondary' : 'secondary disabled';
		print get_submit_button( 'Register', $type, 'sezame-register' );
		printf( '<p class="description">%s</p>', __( 'After pressing this button, you will get a notification on your Sezame app, please acknowledge this request.', 'sezame' ) );
	}

	public function field_signaction_callback() {
		$type = \Sezame\Options::getInstance()->is_registered() ? 'secondary' : 'secondary disabled';
		print get_submit_button( __( 'Sign', 'sezame' ), $type, 'sezame-sign' );
	}

	public function field_signexpertaction_callback() {
		print get_submit_button( __( 'Sign', 'sezame' ), 'secondary', 'sezame-expert-sign' );
	}

	public function field_cancelaction_callback() {
		$type = \Sezame\Options::getInstance()->is_new() ? 'secondary disabled' : 'secondary';
		print get_submit_button( __( 'Cancel', 'sezame' ), $type, 'sezame-cancel' );
	}

	public function field_makecsraction_callback() {
		print get_submit_button( __( 'Make CSR', 'sezame' ), 'secondary', 'sezame-makecsr' );
	}

	public function field_clientcode_callback() {
		printf(
			'<input type="text" id="sezame-setting-clientcode" name="sezame_settings[clientcode]" value="%s" style="width:400px;"/>',
			esc_attr( \Sezame\Options::getInstance()->get_clientcode() )
		);
		printf( '<p class="description">%s</p>', __( 'The Client code for uniquely identifying your blog.', 'sezame' ) );
	}

	public function field_sharedsecret_callback() {
		printf(
			'<input type="text" id="sezame-setting-sharedsecret" name="sezame_settings[sharedsecret]" value="%s" style="width:600px;"/>',
			esc_attr( \Sezame\Options::getInstance()->get_sharedsecret() )
		);
		printf( '<p class="description">%s</p>', __( 'The Shared secret.', 'sezame' ) );
	}

	public function field_keypassword_callback() {
		printf(
			'<input type="text" id="sezame-setting-keypassword" name="sezame_settings[keypassword]" value="%s" style="width:400px;"/>',
			esc_attr( \Sezame\Options::getInstance()->get_keypassword() )
		);
		printf( '<p class="description">%s</p>', __( 'The password for protecting your private key.', 'sezame' ) );
	}

	public function field_csr_callback() {
		printf(
			'<textarea id="sezame-setting-csr" name="sezame_settings[csr]" style="width:700px;height:200px;"/>%s</textarea>',
			esc_attr( \Sezame\Options::getInstance()->get_csr() )
		);
		printf( '<p class="description">%s</p>', __( 'The CSR.', 'sezame' ) );
	}

	public function field_privatekey_callback() {
		printf(
			'<textarea id="sezame-setting-privatekey" name="sezame_settings[privatekey]" style="width:700px;height:200px;"/>%s</textarea>',
			esc_attr( \Sezame\Options::getInstance()->get_privatekey() )
		);
		printf( '<p class="description">%s</p>', __( 'Your private key.', 'sezame' ) );
	}

	public function field_certificate_callback() {
		printf(
			'<textarea id="sezame-setting-certificate" name="sezame_settings[certificate]" style="width:700px;height:200px;"/>%s</textarea>',
			esc_attr( \Sezame\Options::getInstance()->get_certificate() )
		);
		printf( '<p class="description">%s</p>', __( 'The certificate.', 'sezame' ) );
	}

	public function sanitize( $input ) {
		return \Sezame\Options::getInstance()->sanitize( $input );
	}

	public function register_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		if ( ! isset( $_POST['email'] ) || ! strlen( $_POST['email'] ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'E-Mail is required', 'sezame' ) ) );
		}

		$email = $_POST['email'];
		if ( ! is_email( $email ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'E-Mail address is not valid', 'sezame' ) ) );
		}

		if ( isset( $_POST['fraud'] ) ) {
			\Sezame\Options::getInstance()->set_fraud( wp_validate_boolean( $_POST['fraud'] ) );
		}

		if ( isset( $_POST['enabled'] ) ) {
			\Sezame\Options::getInstance()->set_enabled( wp_validate_boolean( $_POST['enabled'] ) );
		}

		if ( isset( $_POST['timeout'] ) ) {
			\Sezame\Options::getInstance()->set_timeout( $_POST['timeout'] );
		}

		try {
			$blogname = is_multisite() ? get_blog_option( null, 'blogname' ) : get_option( 'blogname' );

			\Sezame\Model::getInstance()->register( $email, $blogname );

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			if ( $e->getCode() == 404 ) {
				$message = __( 'E-Mail address not found.', 'sezame' );
			}
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function sign_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$options = \Sezame\Options::getInstance();

		try {
			if ( ! strlen( $options->get_sharedsecret() ) || ! strlen( $options->get_clientcode() ) ) {
				status_header( 400 );
				wp_send_json( array( 'message' => __( 'Please register first', 'sezame' ) ) );
			}

			$blogname = is_multisite() ? get_blog_option( null, 'blogname' ) : get_option( 'blogname' );
			\Sezame\Model::getInstance()->makecsr( $blogname, $options->get_email() );
			\Sezame\Model::getInstance()->sign();

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function signexpert_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$options = \Sezame\Options::getInstance();

		if ( ! strlen( $options->get_sharedsecret() ) || ! strlen( $options->get_csr() ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'Please set the CSR', 'sezame' ) ) );
		}

		try {

			\Sezame\Model::getInstance()->sign();

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

	public function cancel_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$options = \Sezame\Options::getInstance();

		try {

			if ( ! strlen( $options->get_certificate() ) || ! strlen( $options->get_privatekey() ) ) {
				status_header( 400 );
				wp_send_json( array( 'message' => __( 'Please register first', 'sezame' ) ) );
			}

			\Sezame\Model::getInstance()->cancel();

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}


	public function makecsr_action_callback() {
		if ( ! wp_verify_nonce( $_POST['sezameNonce'], 'sezame-ajax-nonce' ) ) {
			status_header( 403 );
			wp_send_json( array( 'message' => 'Invalid nonce' ) );
		}

		$keypassword = null;
		if ( isset( $_POST['keypassword'] ) || strlen( $_POST['keypassword'] ) ) {
			$keypassword = $_POST['keypassword'];
		}

		if ( ! isset( $_POST['clientcode'] ) || ! strlen( $_POST['clientcode'] ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'Client code is required', 'sezame' ) ) );
		}

		if ( ! isset( $_POST['sharedsecret'] ) || ! strlen( $_POST['sharedsecret'] ) ) {
			status_header( 400 );
			wp_send_json( array( 'message' => __( 'Shared secret is required', 'sezame' ) ) );
		}

		$options = \Sezame\Options::getInstance();
		$options->set_keypassword( $keypassword )->set_sharedsecret( $_POST['sharedsecret'] )->set_clientcode( $_POST['clientcode'] );

		try {

			$blogname = is_multisite() ? get_blog_option( null, 'blogname' ) : get_option( 'blogname' );

			\Sezame\Model::getInstance()->makecsr( $blogname, get_bloginfo( 'admin_email' ), $keypassword );

			wp_send_json( true );
		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			status_header( 400 );
			wp_send_json( array( 'message' => $message ) );
		}

	}

}