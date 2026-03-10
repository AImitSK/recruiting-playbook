<?php
/**
 * Setup-Wizard Template
 *
 * Rendert innerhalb des WordPress-Admin-Wrappers.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rp-wizard-body">
	<div class="rp-wizard-container">
		<div class="rp-wizard-header">
			<h1>
				<span class="rp-wizard-logo">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</span>
				Recruiting Playbook
			</h1>
		</div>

		<?php $this->renderProgress(); ?>

		<div class="rp-wizard-content">
			<?php call_user_func( $this->steps[ $this->current_step ]['handler'] ); ?>
		</div>
	</div>
</div>
