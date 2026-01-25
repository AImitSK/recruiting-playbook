<?php
/**
 * E-Mail Base Layout Template
 *
 * Basis-HTML-Struktur für alle E-Mails.
 * Responsive Design mit inline CSS für maximale E-Mail-Client-Kompatibilität.
 *
 * Verfügbare Variablen:
 * - $content     : Der E-Mail-Inhalt (HTML)
 * - $subject     : Betreff der E-Mail
 * - $company     : Firmenname
 * - $logo_url    : URL zum Firmenlogo (optional)
 * - $footer_text : Footer-Text (optional)
 * - $unsubscribe_url : Abmelde-URL (optional)
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Standardwerte.
$company         = $company ?? get_bloginfo( 'name' );
$logo_url        = $logo_url ?? '';
$footer_text     = $footer_text ?? '';
$unsubscribe_url = $unsubscribe_url ?? '';
$primary_color   = apply_filters( 'rp_email_primary_color', '#0073aa' );
$text_color      = apply_filters( 'rp_email_text_color', '#333333' );
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $subject ?? '' ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		/* Reset */
		body, table, td, p, a, li, blockquote {
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		table, td {
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}
		img {
			-ms-interpolation-mode: bicubic;
			border: 0;
			height: auto;
			line-height: 100%;
			outline: none;
			text-decoration: none;
		}
		body {
			height: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			width: 100% !important;
		}

		/* Responsive */
		@media screen and (max-width: 600px) {
			.email-container {
				width: 100% !important;
			}
			.fluid {
				max-width: 100% !important;
				height: auto !important;
			}
			.stack-column {
				display: block !important;
				width: 100% !important;
			}
		}
	</style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

	<!-- Preheader (versteckter Text für E-Mail-Vorschau) -->
	<div style="display: none; font-size: 1px; color: #f4f4f4; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
		<?php echo esc_html( wp_strip_all_tags( substr( $content ?? '', 0, 150 ) ) ); ?>
	</div>

	<!-- Wrapper-Tabelle -->
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f4f4;">
		<tr>
			<td align="center" style="padding: 20px 10px;">

				<!-- E-Mail Container -->
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

					<?php
					// Header einbinden.
					include __DIR__ . '/partials/header.php';
					?>

					<!-- Content -->
					<tr>
						<td style="padding: 30px 40px; color: <?php echo esc_attr( $text_color ); ?>; font-size: 16px; line-height: 1.6;">
							<?php
							// Der eigentliche E-Mail-Inhalt.
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is already escaped by caller
							echo $content;
							?>
						</td>
					</tr>

					<?php
					// Footer einbinden.
					include __DIR__ . '/partials/footer.php';
					?>

				</table>
				<!-- /E-Mail Container -->

			</td>
		</tr>
	</table>
	<!-- /Wrapper-Tabelle -->

</body>
</html>
