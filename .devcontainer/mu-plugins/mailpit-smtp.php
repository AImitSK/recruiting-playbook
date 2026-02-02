<?php
/**
 * Plugin Name: Mailpit SMTP Configuration
 * Description: Routes all emails through Mailpit for development testing
 * Version: 1.0.0
 */

// Only in development environment
if ( ! defined( 'ABSPATH' ) || wp_get_environment_type() !== 'development' ) {
	return;
}

add_action( 'phpmailer_init', function( $phpmailer ) {
	$phpmailer->isSMTP();
	$phpmailer->Host       = 'mailpit';
	$phpmailer->Port       = 1025;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = '';
});
