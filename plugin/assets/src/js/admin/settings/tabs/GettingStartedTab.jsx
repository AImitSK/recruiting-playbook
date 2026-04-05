/**
 * Getting Started Tab Component
 *
 * Quick start guide with shortcodes, Gutenberg blocks, and documentation links.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Check, Copy, BookOpen, Code, Blocks, Video, ExternalLink } from 'lucide-react';

/**
 * Code snippet with copy button
 */
function CodeSnippet({ code, label }) {
	const [copied, setCopied] = useState(false);

	const handleCopy = () => {
		navigator.clipboard.writeText(code);
		setCopied(true);
		setTimeout(() => setCopied(false), 2000);
	};

	return (
		<div className="rp-mt-3">
			{label && (
				<p className="rp-text-sm rp-font-medium rp-mb-2 rp-text-gray-700">
					{label}
				</p>
			)}
			<div className="rp-flex rp-items-center rp-gap-2 rp-bg-gray-50 rp-p-3 rp-rounded-md rp-border rp-border-gray-200">
				<code className="rp-flex-1 rp-font-mono rp-text-sm rp-text-gray-800">
					{code}
				</code>
				<Button
					variant="outline"
					size="sm"
					onClick={handleCopy}
					style={{ minWidth: '80px' }}
				>
					{copied ? (
						<>
							<Check className="rp-w-4 rp-h-4 rp-mr-1" />
							{__('Copied!', 'recruiting-playbook')}
						</>
					) : (
						<>
							<Copy className="rp-w-4 rp-h-4 rp-mr-1" />
							{__('Copy', 'recruiting-playbook')}
						</>
					)}
				</Button>
			</div>
		</div>
	);
}

/**
 * Documentation link
 */
function DocLink({ href, children }) {
	return (
		<a
			href={href}
			target="_blank"
			rel="noopener noreferrer"
			className="rp-inline-flex rp-items-center rp-text-blue-600 hover:rp-text-blue-700 rp-text-sm rp-font-medium"
		>
			{children}
			<ExternalLink className="rp-w-3 rp-h-3 rp-ml-1" />
		</a>
	);
}

/**
 * GettingStartedTab Component
 *
 * @return {JSX.Element} Component
 */
export function GettingStartedTab() {
	return (
		<div className="rp-space-y-6">
			{/* Welcome Card */}
			<Card>
				<CardHeader>
					<CardTitle className="rp-text-2xl">
						{__('Welcome to Recruiting Playbook!', 'recruiting-playbook')}
					</CardTitle>
					<CardDescription>
						{__('Get started quickly with this step-by-step guide. Learn how to display job listings and application forms on your website.', 'recruiting-playbook')}
					</CardDescription>
				</CardHeader>
			</Card>

			{/* Step 1: Job Listings */}
			<Card>
				<CardHeader>
					<div className="rp-flex rp-items-start rp-gap-3">
						<div className="rp-flex rp-items-center rp-justify-center rp-w-8 rp-h-8 rp-rounded-full rp-bg-blue-100 rp-text-blue-600 rp-font-bold rp-flex-shrink-0">
							1
						</div>
						<div className="rp-flex-1">
							<CardTitle className="rp-flex rp-items-center rp-gap-2">
								<Code className="rp-w-5 rp-h-5" />
								{__('Display Job Listings', 'recruiting-playbook')}
							</CardTitle>
							<CardDescription>
								{__('Show all your open positions on any page or post', 'recruiting-playbook')}
							</CardDescription>
						</div>
					</div>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<div>
						<h4 className="rp-font-medium rp-text-gray-900 rp-mb-2">
							{__('Classic Editor & Page Builders', 'recruiting-playbook')}
						</h4>
						<p className="rp-text-sm rp-text-gray-600 rp-mb-2">
							{__('Copy this shortcode and paste it into any page:', 'recruiting-playbook')}
						</p>
						<CodeSnippet code="[rp_jobs]" />
					</div>

					<div className="rp-border-t rp-border-gray-200 rp-pt-4">
						<h4 className="rp-font-medium rp-text-gray-900 rp-mb-2">
							{__('Gutenberg Block Editor', 'recruiting-playbook')}
						</h4>
						<ol className="rp-text-sm rp-text-gray-600 rp-space-y-1 rp-list-decimal rp-list-inside">
							<li>{__('Click the "+" button to add a new block', 'recruiting-playbook')}</li>
							<li>{__('Search for "Job List" or "Recruiting Playbook"', 'recruiting-playbook')}</li>
							<li>{__('Insert the "Job List" block', 'recruiting-playbook')}</li>
						</ol>
					</div>

					<div className="rp-bg-blue-50 rp-border rp-border-blue-200 rp-rounded-md rp-p-3">
						<p className="rp-text-sm rp-text-blue-800">
							<strong>{__('Tip:', 'recruiting-playbook')}</strong>{' '}
							{__('You can customize the display using shortcode parameters. See the documentation for all available options.', 'recruiting-playbook')}
						</p>
					</div>

					<DocLink href="https://recruiting-playbook.com/docs/shortcodes">
						{__('View all shortcode parameters →', 'recruiting-playbook')}
					</DocLink>
				</CardContent>
			</Card>

			{/* Step 2: Gutenberg Blocks */}
			<Card>
				<CardHeader>
					<div className="rp-flex rp-items-start rp-gap-3">
						<div className="rp-flex rp-items-center rp-justify-center rp-w-8 rp-h-8 rp-rounded-full rp-bg-blue-100 rp-text-blue-600 rp-font-bold rp-flex-shrink-0">
							2
						</div>
						<div className="rp-flex-1">
							<CardTitle className="rp-flex rp-items-center rp-gap-2">
								<Blocks className="rp-w-5 rp-h-5" />
								{__('Available Gutenberg Blocks', 'recruiting-playbook')}
							</CardTitle>
							<CardDescription>
								{__('Use our custom blocks in the Block Editor for more control', 'recruiting-playbook')}
							</CardDescription>
						</div>
					</div>
				</CardHeader>
				<CardContent className="rp-space-y-3">
					<div className="rp-grid rp-grid-cols-1 md:rp-grid-cols-2 rp-gap-3">
						<div className="rp-p-3 rp-border rp-border-gray-200 rp-rounded-md">
							<h5 className="rp-font-medium rp-text-gray-900 rp-mb-1">
								{__('Job List Block', 'recruiting-playbook')}
							</h5>
							<p className="rp-text-xs rp-text-gray-600">
								{__('Display job listings with filter options', 'recruiting-playbook')}
							</p>
						</div>
						<div className="rp-p-3 rp-border rp-border-gray-200 rp-rounded-md">
							<h5 className="rp-font-medium rp-text-gray-900 rp-mb-1">
								{__('Application Form Block', 'recruiting-playbook')}
							</h5>
							<p className="rp-text-xs rp-text-gray-600">
								{__('Standalone application form', 'recruiting-playbook')}
							</p>
						</div>
						<div className="rp-p-3 rp-border rp-border-gray-200 rp-rounded-md">
							<h5 className="rp-font-medium rp-text-gray-900 rp-mb-1">
								{__('Job Categories Block', 'recruiting-playbook')}
							</h5>
							<p className="rp-text-xs rp-text-gray-600">
								{__('Show all job categories', 'recruiting-playbook')}
							</p>
						</div>
						<div className="rp-p-3 rp-border rp-border-gray-200 rp-rounded-md">
							<h5 className="rp-font-medium rp-text-gray-900 rp-mb-1">
								{__('Featured Jobs Block', 'recruiting-playbook')}
							</h5>
							<p className="rp-text-xs rp-text-gray-600">
								{__('Highlight specific job openings', 'recruiting-playbook')}
							</p>
						</div>
					</div>

					<DocLink href="https://recruiting-playbook.com/docs/gutenberg-blocks">
						{__('Complete Gutenberg Blocks guide →', 'recruiting-playbook')}
					</DocLink>
				</CardContent>
			</Card>

			{/* Documentation & Support */}
			<Card>
				<CardHeader>
					<CardTitle className="rp-flex rp-items-center rp-gap-2">
						<BookOpen className="rp-w-5 rp-h-5" />
						{__('Documentation & Support', 'recruiting-playbook')}
					</CardTitle>
					<CardDescription>
						{__('Find detailed guides, video tutorials, and get help', 'recruiting-playbook')}
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-3">
					<div className="rp-grid rp-grid-cols-1 md:rp-grid-cols-3 rp-gap-3">
						<a
							href="https://recruiting-playbook.com/docs/getting-started"
							target="_blank"
							rel="noopener noreferrer"
							className="rp-flex rp-flex-col rp-items-center rp-p-4 rp-border rp-border-gray-200 rp-rounded-md hover:rp-border-blue-300 hover:rp-bg-blue-50 rp-transition-colors rp-text-center"
						>
							<BookOpen className="rp-w-8 rp-h-8 rp-text-blue-600 rp-mb-2" />
							<span className="rp-font-medium rp-text-gray-900 rp-text-sm">
								{__('Getting Started Guide', 'recruiting-playbook')}
							</span>
							<span className="rp-text-xs rp-text-gray-500 rp-mt-1">
								{__('Step-by-step setup', 'recruiting-playbook')}
							</span>
						</a>

						<a
							href="https://recruiting-playbook.com/docs/shortcodes"
							target="_blank"
							rel="noopener noreferrer"
							className="rp-flex rp-flex-col rp-items-center rp-p-4 rp-border rp-border-gray-200 rp-rounded-md hover:rp-border-blue-300 hover:rp-bg-blue-50 rp-transition-colors rp-text-center"
						>
							<Code className="rp-w-8 rp-h-8 rp-text-blue-600 rp-mb-2" />
							<span className="rp-font-medium rp-text-gray-900 rp-text-sm">
								{__('Shortcodes Reference', 'recruiting-playbook')}
							</span>
							<span className="rp-text-xs rp-text-gray-500 rp-mt-1">
								{__('All parameters & examples', 'recruiting-playbook')}
							</span>
						</a>

						<a
							href="https://recruiting-playbook.com/docs/gutenberg-blocks"
							target="_blank"
							rel="noopener noreferrer"
							className="rp-flex rp-flex-col rp-items-center rp-p-4 rp-border rp-border-gray-200 rp-rounded-md hover:rp-border-blue-300 hover:rp-bg-blue-50 rp-transition-colors rp-text-center"
						>
							<Blocks className="rp-w-8 rp-h-8 rp-text-blue-600 rp-mb-2" />
							<span className="rp-font-medium rp-text-gray-900 rp-text-sm">
								{__('Gutenberg Blocks', 'recruiting-playbook')}
							</span>
							<span className="rp-text-xs rp-text-gray-500 rp-mt-1">
								{__('Block editor tutorial', 'recruiting-playbook')}
							</span>
						</a>
					</div>

					<div className="rp-border-t rp-border-gray-200 rp-pt-3 rp-mt-4">
						<p className="rp-text-sm rp-text-gray-600 rp-mb-2">
							{__('Need help? Our support team is here for you:', 'recruiting-playbook')}
						</p>
						<div className="rp-flex rp-gap-3">
							<DocLink href="https://recruiting-playbook.com/support">
								{__('Contact Support', 'recruiting-playbook')}
							</DocLink>
							<DocLink href="https://recruiting-playbook.com/docs">
								{__('Full Documentation', 'recruiting-playbook')}
							</DocLink>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default GettingStartedTab;
