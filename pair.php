<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user = wp_get_current_user();
$response = \Sezame\Model::getInstance()->pair( $current_user );

if ( $response->isDuplicate() ) {
	print "user already has been linked";
	die;
}

$qrCode = $response->getQrCode( $current_user->user_login );
$url    = plugins_url( 'sezame' );
$qrCode->setPath( __DIR__ . '/assets/data' );
$qrCode->setImagePath( __DIR__ . '/assets/image' );
$qrCode->setLabelFontPath( __DIR__ . '/assets/font/opensans.ttf' );

$itunesImg  = plugins_url( 'sezame' ) . '/assets/apple-itunes-badge.png';
$playImg  = plugins_url( 'sezame' ) . '/assets/google-play-badge.png';

echo <<<HTML

	<style type="text/css">
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
	</style>
	
	<div class="get-it">
		<a href="https://play.google.com/store/apps/details?id=com.bytefex.finprin.sezame_v1" target="_blank">
			<div class="get-it-playstore"></div>
		</a>
		<a href="https://itunes.apple.com/us/app/sezame/id1080201225?l=de&ls=1&mt=8" target="_blank">
			<div class="get-it-itunes"></div>
		</a>
	</div>
HTML;

printf( '<div>%s</div>', __( 'Open the Sezame App and use the pairing function to take a shot of this QR code:', 'sezame' ) );

printf( '<img style="padding: 60px 0 0 130px;" src="%s"/>', $qrCode->getDataUri() );

die;