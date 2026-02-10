/**
 * Utility functions for shadcn/ui
 *
 * @package RecruitingPlaybook
 */

import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind CSS classes
 *
 * @param {...any} inputs - Class names to merge
 * @return {string} Merged class names
 */
export function cn( ...inputs ) {
	return twMerge( clsx( inputs ) );
}
