<?php
/**
 * Fusion Builder Live Preview Template für RP: Stellenkategorien
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="fusion-builder-block-module-rp-job-categories-preview-template">
	<h4 class="fusion_module_title">
		<span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>
		{{ fusionAllElements[element_type].name }}
	</h4>
	<span>Spalten: {{ params.columns || '4' }} | Sortierung: {{ params.orderby || 'name' }}</span>
	<# if ( params.show_count === 'false' ) { #>
		<br><span>Ohne Anzahl</span>
	<# } #>
	<# if ( params.hide_empty === 'false' ) { #>
		<br><span>Leere Kategorien anzeigen</span>
	<# } #>
</script>
