<?php
/**
 * Fusion Builder Live Preview Template für RP: Featured Stellen
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-featured-jobs-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.title ) { #>
		<span>Titel: {{ params.title }}</span><br>
	<# } #>
	<span>Limit: {{ params.limit || '3' }} | Spalten: {{ params.columns || '3' }}</span>
	<# if ( params.show_excerpt === 'false' ) { #>
		<br><span>Ohne Auszug</span>
	<# } #>
</script>
