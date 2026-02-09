<?php
/**
 * Fusion Builder Live Preview Template für RP: AI Job-Match
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-ai-job-match-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.job_id ) { #>
		<span>Job-ID: {{ params.job_id }}</span>
	<# } else { #>
		<span>Job: Automatisch</span>
	<# } #>
	<# if ( params.title ) { #>
		<br><span>Button: {{ params.title }}</span>
	<# } #>
	<# if ( params.style === 'outline' ) { #>
		<br><span>Style: Outline</span>
	<# } #>
</script>
