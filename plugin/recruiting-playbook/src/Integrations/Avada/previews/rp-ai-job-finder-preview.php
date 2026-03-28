<?php
/**
 * Fusion Builder Live Preview Template für RP: AI Job-Finder
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-ai-job-finder-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.title ) { #>
		<span>Titel: {{ params.title }}</span><br>
	<# } #>
	<span>Limit: {{ params.limit || '5' }}</span>
	<# if ( params.subtitle ) { #>
		<br><span>Untertitel: {{ params.subtitle }}</span>
	<# } #>
</script>
