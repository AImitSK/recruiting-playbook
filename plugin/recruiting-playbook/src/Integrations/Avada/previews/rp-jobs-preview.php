<?php
/**
 * Fusion Builder Live Preview Template für RP: Stellenliste
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-jobs-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.columns || params.limit ) { #>
		<span>Spalten: {{ params.columns || '2' }} | Limit: {{ params.limit || '10' }}</span>
	<# } #>
	<# if ( params.category ) { #>
		<br><span>Kategorie: {{ params.category }}</span>
	<# } #>
	<# if ( params.location ) { #>
		<br><span>Standort: {{ params.location }}</span>
	<# } #>
	<# if ( params.featured === 'true' ) { #>
		<br><span>Nur Featured</span>
	<# } #>
</script>
