/**
 * StatusBadge - Zeigt den Status einer E-Mail an
 *
 * @package RecruitingPlaybook
 */

import PropTypes from 'prop-types';

/**
 * StatusBadge Komponente
 *
 * @param {Object} props        Props
 * @param {string} props.status Status der E-Mail
 * @return {JSX.Element} Badge
 */
export function StatusBadge( { status } ) {
	const i18n = window.rpEmailData?.i18n || {};

	const statusLabels = {
		sent: i18n.statusSent || 'Gesendet',
		failed: i18n.statusFailed || 'Fehlgeschlagen',
		pending: i18n.statusPending || 'Ausstehend',
		scheduled: i18n.statusScheduled || 'Geplant',
		cancelled: i18n.statusCancelled || 'Storniert',
	};

	const statusClasses = {
		sent: 'rp-status--success',
		failed: 'rp-status--error',
		pending: 'rp-status--warning',
		scheduled: 'rp-status--info',
		cancelled: 'rp-status--neutral',
	};

	return (
		<span className={ `rp-status ${ statusClasses[ status ] || '' }` }>
			{ statusLabels[ status ] || status }
		</span>
	);
}

StatusBadge.propTypes = {
	status: PropTypes.oneOf( [ 'sent', 'failed', 'pending', 'scheduled', 'cancelled' ] ),
};
