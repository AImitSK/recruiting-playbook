<?php
/**
 * Setup-Wizard Template
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Recruiting Playbook Setup', 'recruiting-playbook' ); ?></title>
	<?php wp_print_styles( 'rp-wizard' ); ?>
	<?php wp_print_styles( 'dashicons' ); ?>
</head>
<body class="rp-wizard-body">
	<div class="rp-wizard-container">
		<div class="rp-wizard-header">
			<h1>
				<span class="dashicons dashicons-groups"></span>
				Recruiting Playbook
			</h1>
		</div>

		<?php $this->renderProgress(); ?>

		<div class="rp-wizard-content">
			<?php call_user_func( $this->steps[ $this->current_step ]['handler'] ); ?>
		</div>
	</div>

	<?php wp_print_scripts( 'jquery' ); ?>
	<?php wp_print_scripts( 'rp-wizard' ); ?>
</body>
</html>
