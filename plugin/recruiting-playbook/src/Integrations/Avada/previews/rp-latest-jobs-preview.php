<?php
/**
 * Fusion Builder Live Preview Template für RP: Neueste Stellen
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-latest-jobs-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<# if ( params.title ) { #>
		<span>Titel: {{ params.title }}</span><br>
	<# } #>
	<span>Limit: {{ params.limit || '5' }}</span>
	<# if ( params.category ) { #>
		<span> | Kategorie: {{ params.category }}</span>
	<# } #>
	<# if ( params.columns && params.columns !== '0' ) { #>
		<br><span>Spalten: {{ params.columns }}</span>
	<# } else { #>
		<br><span>Listenansicht</span>
	<# } #>
</script>
