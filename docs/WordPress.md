🙌 Thank you for the changes "aimitsk"!

Our review tools have determined that your plugin is not yet ready for approval. 

You are receiving this email because our automated systems have identified one or more issues that must be resolved before your submission can proceed to be reviewed by a volunteer. 

🤖 Please note: This message was generated using a combination of algorithms and AI in varying proportions. It has not been individually reviewed by a human. All AI outputs are marked with the ✨ emoji. 

We kindly ask that you: 
•	Carefully review this email in full.
•	Address all listed issues.
•	Thoroughly test your updates.
•	Upload a corrected version of your plugin once everything has been resolved.

By doing so our tools will be happy to take another look at it. 
List of issues found

🔴 Trialware and Locked Features
Please review your plugin to ensure that it does not include any locked or restricted built-in functionality. This is not permitted under the WordPress.org Plugin Directory Guidelines you agreed to when submitting the plugin. 
❌ Guideline 5 – Trialware
Plugins must be fully functional. You may not: 
•	Lock, disable or limit built-in features behind a license key, trial period, usage limit, time, quota or any other kind of intended restriction.

Even if the locked feature is present in the code "just in case the user upgrades," it’s still not allowed. Your plugin may point out which features are available through a separated plugin, but that's it. All plugin code hosted on WordPress.org must be free and fully functional. 
🌐 Guideline 6 – Serviceware
Plugins may connect to a legitimate external service to perform certain functionality, provided: 
•	The service performs actual processing on external servers.
•	The functionality provided cannot be done locally by the plugin.
•	The service is clearly documented in your readme, including Terms of Use and Privacy Policy links.

For example: a "Spam checker" plugin that connects to a external service to check for spam (and thus uses it to provide that functionality) is generally acceptable. A plugin that simply checks a license key to unlock local features is not. 
✅ Ask yourself:
•	Does any function only work after a license check or payment?
•	Is any functionality in the plugin code disabled or limited until it’s unlocked?
•	Are there any limitations on the plugin after a certain amount of time or usage?

After excluding functionalities provided by legitimate external services, if the answer is yes to any of the above, the plugin does not comply. 
🔧 How to fix it:
•	Remove all license checks or other mechanisms that control access to features built in in the plugin code.
•	Remove or fully enable any built in features that are currently locked or limited.
•	Make sure external services are compliant and clearly documented.

ℹ️ Important clarification:
WordPress.org is not a marketplace. It's a repository for free, fully functional, GPL-compliant plugins. 

If you are not offering a service and want to offer additional features through a paid version, that code must be: 
•	Hosted elsewhere (e.g., your own website).
•	Not included in the plugin hosted on WordPress.org.
•	GPL compliant: Do not include any mechanisms that would prevent a plug-in from being used after a license has been checked.

✨ Implemented features are intentionally restricted behind Pro/license checks and upgrade gates, including kanban_board, email_templates, api_access, csv_export, talent pool/advanced applicant management, reporting, and custom_fields        


## Out of Date Libraries 

At least one of the 3rd party libraries you're using is out of date. Please upgrade to the latest stable version for better support and security. We do not recommend you use beta releases. 

From your plugin: 
freemius/wordpress-sdk 2.13.0 ! 2.13.1 Freemius WordPress SDK



## Check permission_callback in REST API Route

When using register_rest_route() or wp_register_ability() to define custom REST API endpoints, it is crucial to include a proper permission_callback. 

🔒 This callback function ensures that only authorized users can access or modify data through your endpoint. 

Code example, checking that the user can change options: 
register_rest_route( 'recruiting-playbook/v1', '/my-endpoint', array(
    'methods' => 'GET',
    'callback' => 'recruiting-playbook_callback_function',
    'permission_callback' => function() {
        return current_user_can( 'manage_options' );
    }
) );

Please check the register_rest_route() documentation and the current_user_can() documentation. 

✅ When a permission_callback is NOT Required: 

There are valid use cases for public endpoints, such as publicly available data (e.g., posts, public metadata) or endpoints designed for unauthenticated access (e.g., fetching public stats or information). 

In these cases, you should use __return_true as the permission_callback to indicate that the endpoint is intentionally public. 

🔒 When a permission_callback IS Required: 

For endpoints that involve sensitive data or actions (e.g., getting not public data, creating, updating, or deleting content). 

In these cases, you should always implement proper permission checks. 

Possible cases found on this plugin's code: 
src/Api/SettingsController.php:70 register_rest_route($this->namespace, '/' . $this->rest_base . '/company', [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_company'], 'permission_callback' => [$this, 'get_company_permissions_check']], ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'update_company'], 'permission_callback' => [$this, 'update_company_permissions_check'], 'args' => $this->get_company_args()], 'schema' => [$this, 'get_company_schema']]);
# ↳ Detected: get_company_permissions_check
# ↳ Detected: update_company_permissions_check
# ✨ The company settings read endpoint exposes settings data, including sender/company emails, to any user with edit_posts instead of restricting it like other settings endpoints.  



## Undocumented use of a 3rd Party / external service 

Plugins are permitted to require the use of third party/external services as long as they are clearly documented. 

When your plugin reach out to external services, you must disclose it. This is true even if you are the one providing that service. 

You are required to document it in a clear and plain language, so users are aware of: what data is sent, why, where and under which conditions. 

To do this, you must update your readme file to clearly explain that your plugin relies on third party/external services, and include at least the following information for each third party/external service that this plugin uses: 
•	What the service is and what it is used for.
•	What data is sent and when.
•	Provide links to the service's terms of service and privacy policy.
Remember, this is for your own legal protection. Use of services must be upfront and well documented. This allows users to ensure that any legal issues with data transmissions are covered. 

Example: 
== External services ==

This plugin connects to an API to obtain weather information, it's needed to show the weather information and forecasts in the included widget.

It sends the user's location every time the widget is loaded (If the location isn't available and/or the user hasn't given their consent, it displays a configurable default location).
This service is provided by "PRT Weather INC": terms of use, privacy policy.

🔗 Please verify that the terms and privacy links exist and they have the proper content. We will check those links in the next review. 

Example(s) from your plugin: 
assets/dist/js/blocks.js:1 ...lpText:i,docAnchor:n="",shortcode:s=""}){const c="https://developer.recruiting-playbook.de/docs/gutenberg-blocks",u=n?`${c}#${n}`:c;return(0,t.jsxs)(l.Placeholder,{icon:(0,t.jsx)(a,{}),label:e,instruc...

src/Integrations/Notifications/TeamsNotifier.php:331 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:370 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:201 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:252 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Avada/Elements/AbstractElement.php:118 return 'https://developer.recruiting-playbook.dev/docs/shortcodes#' . $shortcode;


Remember to check everything

While our algorithms and AI are not yet able to make definitive judgments on this, they have flagged potential issues with the following: 
•	Our tools detected several elements (such as functions, classes, global variables, constants, data storage keys, and some WordPress hooks) that do not use a consistent and distinctive prefix of at least four characters. With more than 60,000 plugins in the directory, proper prefixing is essential to prevent compatibility conflicts, fatal errors, and other difficult-to-diagnose issues. Please review your codebase and ensure that all applicable elements are consistently and uniquely prefixed. 
This applies even if you have verified that no other plugins are currently using the same names. The directory does not operate on a first-come, first-served basis — every developer is responsible for properly namespacing and prefixing their own code.

Please review and correct these items as needed. 

If this is not done on your end, a volunteer will need to check everything manually. This slows down the process and places unnecessary burden on the team. Submissions may be rejected if your actions (or inaction) make it clear that you have chosen not to follow the instructions. 

If you already checked and are confident that everything is properly implemented, there's no need to worry. We will review it, and if no issues are found, we will not need to follow up regarding this matter. 
👉 Continue with the review process.

Read this email thoroughly.

Take the time to thoroughly review and understand the issues identified by our tools. Examine the provided examples, consult the relevant documentation, and conduct any additional research necessary. The goal of our review process is to help you clearly understand the reported issues so you can resolve them effectively and prevent similar problems in future updates to your plugin. 
Please note that false positives are possible. As an automated system, we may occasionally make mistakes, and we apologize if anything has been flagged incorrectly. If you have doubts you can ask us for clarification, when doing so, please be clear, concise, and include a specific example so we can assist you efficiently. 
📋 Complete your checklist.

✔️ I fixed all the issues in my plugin based on the feedback I received and my own review, as I know that the Plugins Team may not share all cases of the same issue. I am familiar with tools such as Plugin Check, PHPCS + WPCS, and similar utilities to help me identify problems in my code. 
✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true.
⚠️ Do not skip this step. Testing is essential to make sure your fixes actually work and that you haven’t introduced new issues. 

✔️ I acknowledge that this review will be rejected if I overlook the issues or fail to test my code. 
✔️ I went to "Add your plugin" and uploaded the updated version. I can continue updating the code there throughout the review process — the team will always check the latest version. 
✔️ I replied to this email. I was concise and shared any clarifications or important context that the team needed to know.
I didn't list all the changes, as the team will review the entire plugin again and that is not necessary at all. 

ℹ️ To help speed up the review process, we kindly ask that you carefully verify and address all reported issues before resubmitting your code. 

We do our best to make these reviews as thorough as possible—but let’s be honest, I’m just a machine. If something looks off, it is probably my programmer’s fault (you’re welcome to direct your disappointment their way). On the other hand, if everything is spot-on, they’d certainly appreciate the gratitude. Either way, we truly value your patience, understanding and collaboration on making this process worthwhile and efficient. 

Review ID: AUTO recruiting-playbook/aimitsk/30Jan26/T6 26Mar26/3.9A7 (P0TDX278337HGN) 


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
{#HS:3214811727-965751#}  
On Fri, Mar 20, 2026 at 9:45 AM UTC, Stefan Kühne <s.kuehne@sk-online-marketing.de> wrote: 
Fixed all reported issues in version 1.2.34:
- Terms/Privacy URLs corrected to /legal/terms and /legal/privacy
- Alpine.js updated from 3.15.4 to 3.15.8
- External Services section extended with Microsoft Teams integration documentation
- Developer Documentation service now includes Terms/Privacy links
- REST API export endpoint now enforces candidate-specific permission checks

Am Do., 19. März 2026 um 19:03 Uhr schrieb WordPress.org Plugin Directory <plugins@wordpress.org>:

On Fri, Mar 20, 2026 at 9:44 AM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.34.
Comment: Fixed all reported issues in version 1.2.34:
- Terms/Privacy URLs corrected to /legal/terms and /legal/privacy
- Alpine.js updated from 3.15.4 to 3.15.8
- External Services section extended with Microsoft Teams integration documentation
- Developer Documentation service now includes Terms/Privacy links
- REST API export endpoint now enforces candidate-specific permission checks

https://wordpress.org/plugins/files/2026/01/20_09-44-55_recruiting-playbook-free.1.2.34.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Thu, Mar 19, 2026 at 6:03 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
🙌 Thank you for the changes "aimitsk"!

Our review tools have determined that your plugin is not yet ready for approval.

You are receiving this email because our automated systems have identified one or more issues that must be resolved before your submission can proceed to be reviewed by a volunteer. 

🤖 Please note: This message was generated using a combination of algorithms and AI in varying proportions. It has not been individually reviewed by a human. All AI outputs are marked with the ✨ emoji.

We kindly ask that you: 
•	Carefully review this email in full.
•	Address all listed issues.
•	Thoroughly test your updates.
•	Upload a corrected version of your plugin once everything has been resolved.

By doing so our tools will be happy to take another look at it. 
List of issues found

🔴 Trialware and Locked Features
Please review your plugin to ensure that it does not include any locked or restricted built-in functionality. This is not permitted under the WordPress.org Plugin Directory Guidelines you agreed to when submitting the plugin. 
❌ Guideline 5 – Trialware
Plugins must be fully functional. You may not: 
•	Lock, disable or limit built-in features behind a license key, trial period, usage limit, time, quota or any other kind of intended restriction.

Even if the locked feature is present in the code "just in case the user upgrades," it’s still not allowed. Your plugin may point out which features are available through a separated plugin, but that's it. All plugin code hosted on WordPress.org must be free and fully functional. 
🌐 Guideline 6 – Serviceware
Plugins may connect to a legitimate external service to perform certain functionality, provided: 
•	The service performs actual processing on external servers.
•	The functionality provided cannot be done locally by the plugin.
•	The service is clearly documented in your readme, including Terms of Use and Privacy Policy links.

For example: a "Spam checker" plugin that connects to a external service to check for spam (and thus uses it to provide that functionality) is generally acceptable. A plugin that simply checks a license key to unlock local features is not. 
✅ Ask yourself:
•	Does any function only work after a license check or payment?
•	Is any functionality in the plugin code disabled or limited until it’s unlocked?
•	Are there any limitations on the plugin after a certain amount of time or usage?

After excluding functionalities provided by legitimate external services, if the answer is yes to any of the above, the plugin does not comply. 
🔧 How to fix it:
•	Remove all license checks or other mechanisms that control access to features built in in the plugin code.
•	Remove or fully enable any built in features that are currently locked or limited.
•	Make sure external services are compliant and clearly documented.

ℹ️ Important clarification:
WordPress.org is not a marketplace. It's a repository for free, fully functional, GPL-compliant plugins. 

If you are not offering a service and want to offer additional features through a paid version, that code must be: 
•	Hosted elsewhere (e.g., your own website).
•	Not included in the plugin hosted on WordPress.org.
•	GPL compliant: Do not include any mechanisms that would prevent a plug-in from being used after a license has been checked.

✨ The plugin intentionally gates functionality already implemented in this codebase behind Pro/license checks via rp_can()/feature flags, including Kanban Board, email templates/history/sending, custom fields/form builder, API access/webhooks, and CSV export; the feature map marks them Pro-only while their pages/controllers/services/routes and DB support are present locally           


## The URL(s) declared in your plugin seems to be invalid or does not work.

From your plugin: 

Terms/Privacy URL: https://recruiting-playbook.com/terms/ - readme.txt - This URL replies us with a 404 HTTP code, meaning that it does not exists or it is not a public URL.
Terms/Privacy URL: https://recruiting-playbook.com/privacy/ - readme.txt - This URL replies us with a 404 HTTP code, meaning that it does not exists or it is not a public URL.

## Out of Date Libraries

At least one of the 3rd party libraries you're using is out of date. Please upgrade to the latest stable version for better support and security. We do not recommend you use beta releases. 

From your plugin: 
assets/dist/js/alpine.min.js:1 🔴  version:"3.15.4"
   # ↳ Possible URL: https://github.com/alpinejs/alpine



## Undocumented use of a 3rd Party / external service

Plugins are permitted to require the use of third party/external services as long as they are clearly documented. 

When your plugin reach out to external services, you must disclose it. This is true even if you are the one providing that service. 

You are required to document it in a clear and plain language, so users are aware of: what data is sent, why, where and under which conditions. 

To do this, you must update your readme file to clearly explain that your plugin relies on third party/external services, and include at least the following information for each third party/external service that this plugin uses: 
•	What the service is and what it is used for.
•	What data is sent and when.
•	Provide links to the service's terms of service and privacy policy.
Remember, this is for your own legal protection. Use of services must be upfront and well documented. This allows users to ensure that any legal issues with data transmissions are covered. 

Example: 
== External services ==

This plugin connects to an API to obtain weather information, it's needed to show the weather information and forecasts in the included widget.

It sends the user's location every time the widget is loaded (If the location isn't available and/or the user hasn't given their consent, it displays a configurable default location).
This service is provided by "PRT Weather INC": terms of use, privacy policy.

🔗 Please verify that the terms and privacy links exist and they have the proper content. We will check those links in the next review. 

Example(s) from your plugin: 
src/Integrations/Notifications/TeamsNotifier.php:370 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:201 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:331 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
src/Integrations/Notifications/TeamsNotifier.php:252 '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',

src/Integrations/Avada/Elements/AbstractElement.php:118 return 'https://developer.recruiting-playbook.dev/docs/shortcodes#' . $shortcode;
# ✨ Hardcoded Avada help URL points to developer.recruiting-playbook.dev, and while the readme documents developer documentation usage, it does not include Terms of Service or Privacy Policy links for that service  



## Check permission_callback in REST API Route

When using register_rest_route() or wp_register_ability() to define custom REST API endpoints, it is crucial to include a proper permission_callback . 

🔒 This callback function ensures that only authorized users can access or modify data through your endpoint. 

Code example, checking that the user can change options: 
register_rest_route( 'recruiting-playbook/v1', '/my-endpoint', array(
    'methods' => 'GET',
    'callback' => 'recruiting-playbook_callback_function',
    'permission_callback' => function() {
        return current_user_can( 'manage_options' );
    }
) );

Please check the register_rest_route() documentation and the current_user_can() documentation. 

✅ When a permission_callback is NOT Required: 

There are valid use cases for public endpoints, such as publicly available data (e.g., posts, public metadata) or endpoints designed for unauthenticated access (e.g., fetching public stats or information). 

In these cases, you should use __return_true as the permission_callback to indicate that the endpoint is intentionally public. 

🔒 When a permission_callback IS Required: 

For endpoints that involve sensitive data or actions (e.g., getting not public data, creating, updating, or deleting content). 

In these cases, you should always implement proper permission checks. 

Possible cases found on this plugin's code: 
src/Api/ApplicationController.php:206 register_rest_route($this->namespace, '/candidates/(?P<id>[\\d]+)/export', [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'export_candidate_data'], 'permission_callback' => [$this, 'get_items_permissions_check'], 'args' => ['id' => ['description' => __('Candidate ID', 'recruiting-playbook'), 'type' => 'integer', 'required' => true]]]]);
# ↳ Detected: get_items_permissions_check
# ✨ export endpoint only checks general application-view permission and does not enforce candidate/job-specific access before exporting sensitive data.


Remember to check everything

While our algorithms and AI are not yet able to make definitive judgments on this, they have flagged potential issues with the following: 
•	Our tools detected several elements (such as functions, classes, global variables, constants, data storage keys, and some WordPress hooks) that do not use a consistent and distinctive prefix of at least four characters. With more than 60,000 plugins in the directory, proper prefixing is essential to prevent compatibility conflicts, fatal errors, and other difficult-to-diagnose issues. Please review your codebase and ensure that all applicable elements are consistently and uniquely prefixed. 
This applies even if you have verified that no other plugins are currently using the same names. The directory does not operate on a first-come, first-served basis — every developer is responsible for properly namespacing and prefixing their own code.

Please review and correct these items as needed. 

If this is not done on your end, a volunteer will need to check everything manually. This slows down the process and places unnecessary burden on the team. Submissions may be rejected if your actions (or inaction) make it clear that you have chosen not to follow the instructions. 

If you already checked and are confident that everything is properly implemented, there's no need to worry. We will review it, and if no issues are found, we will not need to follow up regarding this matter. 
👉 Continue with the review process.

Read this email thoroughly.

Take the time to thoroughly review and understand the issues identified by our tools. Examine the provided examples, consult the relevant documentation, and conduct any additional research necessary. The goal of our review process is to help you clearly understand the reported issues so you can resolve them effectively and prevent similar problems in future updates to your plugin. 
Please note that false positives are possible. As an automated system, we may occasionally make mistakes, and we apologize if anything has been flagged incorrectly. If you have doubts you can ask us for clarification, when doing so, please be clear, concise, and include a specific example so we can assist you efficiently.
📋 Complete your checklist.

✔️ I fixed all the issues in my plugin based on the feedback I received and my own review, as I know that the Plugins Team may not share all cases of the same issue. I am familiar with tools such as Plugin Check, PHPCS + WPCS, and similar utilities to help me identify problems in my code. 
✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true.
⚠️ Do not skip this step. Testing is essential to make sure your fixes actually work and that you haven’t introduced new issues.

✔️ I acknowledge that this review will be rejected if I overlook the issues or fail to test my code. 
✔️ I went to "Add your plugin" and uploaded the updated version. I can continue updating the code there throughout the review process — the team will always check the latest version. 
✔️ I replied to this email. I was concise and shared any clarifications or important context that the team needed to know.
I didn't list all the changes, as the team will review the entire plugin again and that is not necessary at all.

ℹ️ To help speed up the review process, we kindly ask that you carefully verify and address all reported issues before resubmitting your code. 

We do our best to make these reviews as thorough as possible—but let’s be honest, I’m just a machine. If something looks off, it is probably my programmer’s fault (you’re welcome to direct your disappointment their way). On the other hand, if everything is spot-on, they’d certainly appreciate the gratitude. Either way, we truly value your patience, understanding and collaboration on making this process worthwhile and efficient. 

Review ID: AUTO recruiting-playbook/aimitsk/30Jan26/T5 19Mar26/3.9A7 (P0TDX278337HGN) 


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Thu, Mar 12, 2026 at 3:24 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.33.
Comment: Fix: ABSPATH check at very beginning of file for WordPress.org Plugin Checker

https://wordpress.org/plugins/files/2026/01/12_15-24-43_recruiting-playbook-free.1.2.33.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Thu, Mar 12, 2026 at 3:17 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.32.
Comment: WordPress.org Plugin Review - All issues resolved

- Split main plugin file to resolve namespace/ABSPATH conflict
- ABSPATH check in main file (recruiting-playbook.php) - Namespace code moved to src/bootstrap.php
- All 11 original review issues fixed (see changelog 1.2.26)

https://wordpress.org/plugins/files/2026/01/12_15-17-43_recruiting-playbook-free.1.2.32.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Thu, Mar 12, 2026 at 3:05 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.30.
Comment: fix: split main file to resolve Freemius namespace conflict

https://wordpress.org/plugins/files/2026/01/12_15-05-55_recruiting-playbook-free.1.2.30.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Thu, Mar 12, 2026 at 2:19 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.26.
Comment: WordPress.org Plugin Review Compliance

This release addresses all 11 issues identified in the WordPress.org plugin review:

Security & Code Quality
- Removed deprecated finfo_close() calls for PHP 8.1+ compatibility
- Added proper $_FILES sanitization using sanitize_file_name()
- Implemented output escaping with esc_attr() and esc_html() at all critical locations
- REST API endpoints now use documented permission callbacks instead of __return_true

WordPress.org Guidelines
- Replaced ob_start() output buffering with cleaner script_loader_tag filter
- Lowered admin menu position from 25 to 56 (below core WordPress menu items)
- Converted all inline <script> and <style> tags to wp_add_inline_script/style()
- Plugin constants now use RECPL_ prefix (minimum 4 characters as required)
- Enabled Freemius is_org_compliant mode

Documentation
- Added "External Services" section documenting AI API and Freemius data usage
- Added "Source Code" section with build instructions for minified assets
- Includes links to Terms of Service and Privacy Policy for all external services

Files Changed: 20 files across PHP, JavaScript, and documentation

https://wordpress.org/plugins/files/2026/01/12_14-19-28_recruiting-playbook-free.1.2.26.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Wed, Mar 11, 2026 at 3:57 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.25.
Comment: Security fix

https://wordpress.org/plugins/files/2026/01/11_15-57-33_recruiting-playbook-free.1.2.25.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
On Wed, Mar 11, 2026 at 2:13 PM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote: 
This is an automated message to confirm that we have received your updated plugin file.

File updated by aimitsk, version 1.2.24.
Comment: Upload

https://wordpress.org/plugins/files/2026/01/11_14-13-40_recruiting-playbook-free.1.2.24.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/ 
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/ 
