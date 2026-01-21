/**
 * Setup-Wizard JavaScript
 *
 * @package RecruitingPlaybook
 */

(function($) {
    'use strict';

    /**
     * Wizard Handler
     */
    const RPWizard = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind Events
         */
        bindEvents: function() {
            // Form submissions
            $(document).on('submit', '.rp-wizard-form', this.handleFormSubmit.bind(this));

            // Skip wizard button
            $(document).on('click', '.rp-skip-wizard', this.handleSkipWizard.bind(this));

            // Skip job button
            $(document).on('click', '#rp-skip-job', this.handleSkipJob.bind(this));

            // Test email button
            $(document).on('click', '#rp-send-test-email', this.handleTestEmail.bind(this));
        },

        /**
         * Handle form submission
         *
         * @param {Event} e
         */
        handleFormSubmit: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $button = $form.find('button[type="submit"]');

            // Disable button
            $button.prop('disabled', true).text(rpWizard.i18n.saving);

            // Collect form data
            const formData = new FormData($form[0]);
            formData.append('action', 'rp_wizard_save_step');

            // Send AJAX request
            $.ajax({
                url: rpWizard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success && response.data.next_url) {
                        window.location.href = response.data.next_url;
                    } else {
                        alert(response.data?.message || rpWizard.i18n.error);
                        $button.prop('disabled', false).text($button.data('original-text') || 'Weiter');
                    }
                },
                error: function() {
                    alert(rpWizard.i18n.error);
                    $button.prop('disabled', false).text($button.data('original-text') || 'Weiter');
                }
            });
        },

        /**
         * Handle skip wizard
         *
         * @param {Event} e
         */
        handleSkipWizard: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            $button.prop('disabled', true);

            $.ajax({
                url: rpWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rp_wizard_skip',
                    nonce: rpWizard.nonce
                },
                success: function(response) {
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data?.message || rpWizard.i18n.error);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(rpWizard.i18n.error);
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Handle skip job step
         *
         * @param {Event} e
         */
        handleSkipJob: function(e) {
            e.preventDefault();

            // Set skip flag
            $('#skip_job').val('1');

            // Submit form
            $('#rp-wizard-job-form').submit();
        },

        /**
         * Handle test email
         *
         * @param {Event} e
         */
        handleTestEmail: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $input = $('#test_email_address');
            const $result = $('#rp-test-email-result');
            const email = $input.val();

            if (!email) {
                $result.removeClass('success').addClass('error')
                    .text(rpWizard.i18n.error).show();
                return;
            }

            // Disable button
            $button.prop('disabled', true).text(rpWizard.i18n.sendingEmail);
            $result.hide();

            $.ajax({
                url: rpWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rp_send_test_email',
                    nonce: rpWizard.nonce,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success')
                            .text(response.data.message).show();
                    } else {
                        $result.removeClass('success').addClass('error')
                            .text(response.data?.message || rpWizard.i18n.emailFailed).show();
                    }
                    $button.prop('disabled', false).text('Test senden');
                },
                error: function() {
                    $result.removeClass('success').addClass('error')
                        .text(rpWizard.i18n.emailFailed).show();
                    $button.prop('disabled', false).text('Test senden');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        RPWizard.init();
    });

})(jQuery);
