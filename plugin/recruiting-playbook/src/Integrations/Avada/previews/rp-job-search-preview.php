<?php
/**
 * Fusion Builder Live Preview Template für RP: Stellensuche
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-job-search-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<span>Filter:</span>
	<# if ( params.show_search !== 'false' ) { #>
		<span> Suche</span>
	<# } #>
	<# if ( params.show_category !== 'false' ) { #>
		<span> | Kategorie</span>
	<# } #>
	<# if ( params.show_location !== 'false' ) { #>
		<span> | Standort</span>
	<# } #>
	<# if ( params.show_type !== 'false' ) { #>
		<span> | Art</span>
	<# } #>
	<br><span>Spalten: {{ params.columns || '1' }} | Limit: {{ params.limit || '10' }}</span>
</script>
