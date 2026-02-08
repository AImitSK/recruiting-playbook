/**
 * Recruiting Playbook - Gutenberg Blocks
 *
 * Entry point for all block registrations.
 * Pro-Feature: Native WordPress blocks for the Block Editor.
 *
 * @package
 */

// Block registrations will be added here as they are implemented.
// Each block is imported from its own directory.

// Phase 2: Core blocks
import './jobs';
import './job-search';
import './job-count';

// Phase 3: Additional blocks
import './featured-jobs';
import './latest-jobs';
import './job-categories';

// Phase 4: Form & AI blocks
import './application-form';
import './ai-job-finder';
import './ai-job-match';

// Temporary: Log that blocks are loaded (remove in production)
if ( process.env.NODE_ENV === 'development' ) {
	// eslint-disable-next-line no-console
	console.log( 'Recruiting Playbook Blocks loaded' );
}
