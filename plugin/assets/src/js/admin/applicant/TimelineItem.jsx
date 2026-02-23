/**
 * Timeline Item Component
 *
 * Single entry in the activity timeline
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { getWpLocale } from '../utils/locale';

/**
 * Status labels
 */
const STATUS_LABELS = {
	new: __( 'New', 'recruiting-playbook' ),
	screening: __( 'Screening', 'recruiting-playbook' ),
	interview: __( 'Interview', 'recruiting-playbook' ),
	offer: __( 'Offer', 'recruiting-playbook' ),
	hired: __( 'Hired', 'recruiting-playbook' ),
	rejected: __( 'Rejected', 'recruiting-playbook' ),
	withdrawn: __( 'Withdrawn', 'recruiting-playbook' ),
};

/**
 * Get status label
 *
 * @param {string} status Status key
 * @return {string} Localized label
 */
function getStatusLabel( status ) {
	return STATUS_LABELS[ status ] || status;
}

/**
 * Timeline-Eintrag Komponente
 *
 * @param {Object} props      Props
 * @param {Object} props.item Timeline-Item Daten
 * @return {JSX.Element} Komponente
 */
export function TimelineItem( { item } ) {
	/**
	 * Format time
	 *
	 * @param {string} dateString ISO date
	 * @return {string} Formatted time
	 */
	const formatTime = ( dateString ) => {
		const date = new Date( dateString );
		return date.toLocaleTimeString( getWpLocale(), {
			hour: '2-digit',
			minute: '2-digit',
		} );
	};

	return (
		<div
			className={ `rp-timeline-item rp-timeline-item--${ item.category || 'other' }` }
			style={ { '--item-color': item.color || '#787c82' } }
		>
			<div className="rp-timeline-item__icon">
				<span className={ `dashicons ${ item.icon || 'dashicons-info' }` }></span>
			</div>

			<div className="rp-timeline-item__content">
				<div className="rp-timeline-item__header">
					{ item.user && (
						<img
							src={ item.user.avatar }
							alt={ item.user.name }
							className="rp-timeline-item__avatar"
						/>
					) }
					<span className="rp-timeline-item__message">
						{ item.user && <strong>{ item.user.name }</strong> }
						{ ' ' }
						{ item.message }
					</span>
					<span className="rp-timeline-item__time">
						{ formatTime( item.created_at ) }
					</span>
				</div>

				{ /* Status-Änderung */ }
				{ item.action === 'status_changed' && item.meta && (
					<div className="rp-timeline-item__detail rp-timeline-status-change">
						<span
							className="rp-status-badge"
							data-status={ item.meta.from }
						>
							{ getStatusLabel( item.meta.from ) }
						</span>
						<span className="dashicons dashicons-arrow-right-alt"></span>
						<span
							className="rp-status-badge"
							data-status={ item.meta.to }
						>
							{ getStatusLabel( item.meta.to ) }
						</span>
					</div>
				) }

				{ /* Notiz-Vorschau */ }
				{ item.action === 'note_added' && item.meta?.preview && (
					<div className="rp-timeline-item__detail rp-timeline-note-preview">
						<span className="dashicons dashicons-format-quote"></span>
						{ item.meta.preview }
					</div>
				) }

				{ /* Bewertung */ }
				{ ( item.action === 'rating_added' || item.action === 'rating_updated' ) && item.meta && (
					<div className="rp-timeline-item__detail rp-timeline-rating">
						{ item.action === 'rating_updated' && item.meta.from && (
							<>
								<span className="rp-timeline-rating-old">
									{ item.meta.from }
									<span className="dashicons dashicons-star-filled"></span>
								</span>
								<span className="dashicons dashicons-arrow-right-alt"></span>
							</>
						) }
						<span className="rp-timeline-rating-new">
							{ item.meta.to || item.meta.rating }
							<span className="dashicons dashicons-star-filled"></span>
						</span>
						{ item.meta.category && item.meta.category !== 'overall' && (
							<span className="rp-timeline-rating-category">
								({ getCategoryLabel( item.meta.category ) })
							</span>
						) }
					</div>
				) }

				{ /* E-Mail gesendet */ }
				{ item.action === 'email_sent' && item.meta && (
					<div className="rp-timeline-item__detail rp-timeline-email">
						<span className="dashicons dashicons-email"></span>
						{ item.meta.subject || item.meta.template || __( 'Email sent', 'recruiting-playbook' ) }
					</div>
				) }

				{ /* Dokument */ }
				{ ( item.action === 'document_viewed' || item.action === 'document_downloaded' ) && item.meta?.filename && (
					<div className="rp-timeline-item__detail rp-timeline-document">
						<span className="dashicons dashicons-media-document"></span>
						{ item.meta.filename }
					</div>
				) }

				{ /* Talent-Pool */ }
				{ item.action === 'talent_pool_added' && item.meta?.reason && (
					<div className="rp-timeline-item__detail rp-timeline-talent-pool">
						<span className="dashicons dashicons-format-quote"></span>
						{ item.meta.reason }
					</div>
				) }
			</div>
		</div>
	);
}

/**
 * Get category label
 *
 * @param {string} category Category key
 * @return {string} Localized label
 */
function getCategoryLabel( category ) {
	const labels = {
		overall: __( 'Overall', 'recruiting-playbook' ),
		skills: __( 'Professional competence', 'recruiting-playbook' ),
		culture_fit: __( 'Cultural fit', 'recruiting-playbook' ),
		experience: __( 'Experience', 'recruiting-playbook' ),
	};
	return labels[ category ] || category;
}
