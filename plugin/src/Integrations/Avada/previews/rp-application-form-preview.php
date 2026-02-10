<?php
/**
 * Fusion Builder Live Preview Template für RP: Bewerbungs-Formular
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-application-form-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.title ) { #>
		<span>Titel: {{ params.title }}</span><br>
	<# } #>
	<# if ( params.job_id ) { #>
		<span>Job-ID: {{ params.job_id }}</span>
	<# } else { #>
		<span>Job: Automatisch</span>
	<# } #>
	<# if ( params.show_progress === 'false' ) { #>
		<br><span>Ohne Fortschrittsanzeige</span>
	<# } #>
</script>
