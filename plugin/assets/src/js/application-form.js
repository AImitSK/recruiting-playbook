/**
 * Recruiting Playbook - Application Form (Alpine.js)
 *
 * @package RecruitingPlaybook
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('applicationForm', () => ({
        // Form state
        step: 1,
        totalSteps: 3,
        loading: false,
        submitted: false,
        error: null,

        // Form data
        formData: {
            job_id: 0,
            salutation: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            cover_letter: '',
            privacy_consent: false,
            _hp_field: '', // Honeypot
            _form_timestamp: 0
        },

        // Files
        files: {
            resume: null,
            documents: []
        },

        // Validation errors
        errors: {},

        // File upload constraints
        maxFileSize: 10 * 1024 * 1024, // 10 MB
        allowedTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'],
        maxFiles: 5,

        /**
         * Initialize form
         */
        init() {
            // Get job_id from data attribute
            const form = this.$el.closest('[data-job-id]');
            if (form) {
                this.formData.job_id = parseInt(form.dataset.jobId, 10);
            }

            // Note: Timestamp is read from PHP-generated hidden field on submit
            // This ensures we measure time from page load, not Alpine init
        },

        /**
         * Go to next step
         */
        nextStep() {
            if (this.validateCurrentStep()) {
                this.step = Math.min(this.step + 1, this.totalSteps);
            }
        },

        /**
         * Go to previous step
         */
        prevStep() {
            this.step = Math.max(this.step - 1, 1);
        },

        /**
         * Validate current step
         */
        validateCurrentStep() {
            this.errors = {};

            switch (this.step) {
                case 1:
                    return this.validatePersonalData();
                case 2:
                    return this.validateDocuments();
                case 3:
                    return this.validateConsent();
                default:
                    return true;
            }
        },

        /**
         * Validate personal data (step 1)
         */
        validatePersonalData() {
            let valid = true;

            if (!this.formData.first_name.trim()) {
                this.errors.first_name = window.rpForm?.i18n?.required || 'Dieses Feld ist erforderlich';
                valid = false;
            }

            if (!this.formData.last_name.trim()) {
                this.errors.last_name = window.rpForm?.i18n?.required || 'Dieses Feld ist erforderlich';
                valid = false;
            }

            if (!this.formData.email.trim()) {
                this.errors.email = window.rpForm?.i18n?.required || 'Dieses Feld ist erforderlich';
                valid = false;
            } else if (!this.isValidEmail(this.formData.email)) {
                this.errors.email = window.rpForm?.i18n?.invalidEmail || 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
                valid = false;
            }

            return valid;
        },

        /**
         * Validate documents (step 2)
         */
        validateDocuments() {
            // Documents are optional, but if provided, must be valid
            return true;
        },

        /**
         * Validate consent (step 3)
         */
        validateConsent() {
            let valid = true;

            if (!this.formData.privacy_consent) {
                this.errors.privacy_consent = window.rpForm?.i18n?.privacyRequired || 'Bitte stimmen Sie der Datenschutzerklärung zu';
                valid = false;
            }

            return valid;
        },

        /**
         * Email validation
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * Handle file select
         */
        handleFileSelect(event, type) {
            const fileList = event.target.files;

            if (type === 'resume') {
                if (fileList.length > 0) {
                    const file = fileList[0];
                    const validation = this.validateFile(file);

                    if (validation.valid) {
                        this.files.resume = file;
                        delete this.errors.resume;
                    } else {
                        this.errors.resume = validation.message;
                        event.target.value = '';
                    }
                }
            } else {
                // Multiple documents
                for (const file of fileList) {
                    if (this.files.documents.length >= this.maxFiles) {
                        this.errors.documents = `Maximal ${this.maxFiles} Dateien erlaubt`;
                        break;
                    }

                    const validation = this.validateFile(file);
                    if (validation.valid) {
                        this.files.documents.push(file);
                    } else {
                        this.errors.documents = validation.message;
                    }
                }
                event.target.value = '';
            }
        },

        /**
         * Handle drag and drop
         */
        handleDrop(event, type) {
            event.preventDefault();
            const fileList = event.dataTransfer.files;

            if (type === 'resume' && fileList.length > 0) {
                const file = fileList[0];
                const validation = this.validateFile(file);

                if (validation.valid) {
                    this.files.resume = file;
                    delete this.errors.resume;
                } else {
                    this.errors.resume = validation.message;
                }
            } else {
                for (const file of fileList) {
                    if (this.files.documents.length >= this.maxFiles) break;

                    const validation = this.validateFile(file);
                    if (validation.valid) {
                        this.files.documents.push(file);
                    }
                }
            }
        },

        /**
         * Validate single file
         */
        validateFile(file) {
            if (file.size > this.maxFileSize) {
                return {
                    valid: false,
                    message: window.rpForm?.i18n?.fileTooLarge || 'Die Datei ist zu groß (max. 10 MB)'
                };
            }

            if (!this.allowedTypes.includes(file.type)) {
                return {
                    valid: false,
                    message: window.rpForm?.i18n?.invalidFileType || 'Dateityp nicht erlaubt. Erlaubt: PDF, DOC, DOCX, JPG, PNG'
                };
            }

            return { valid: true };
        },

        /**
         * Remove file
         */
        removeFile(type, index = null) {
            if (type === 'resume') {
                this.files.resume = null;
            } else if (index !== null) {
                this.files.documents.splice(index, 1);
            }
        },

        /**
         * Format file size
         */
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },

        /**
         * Submit form
         */
        async submit() {
            if (!this.validateCurrentStep()) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();

                // Read honeypot field from DOM (bot detection)
                const honeypotField = this.$el.querySelector('input[name="_hp_field"]');
                if (honeypotField) {
                    this.formData._hp_field = honeypotField.value;
                }

                // Read timestamp from DOM (set by PHP on page load)
                const timestampField = this.$el.querySelector('input[name="_form_timestamp"]');
                if (timestampField && timestampField.value) {
                    this.formData._form_timestamp = parseInt(timestampField.value, 10);
                }

                // Add form fields
                for (const [key, value] of Object.entries(this.formData)) {
                    if (typeof value === 'boolean') {
                        formData.append(key, value ? '1' : '0');
                    } else {
                        formData.append(key, value);
                    }
                }

                // Add files
                if (this.files.resume) {
                    formData.append('resume', this.files.resume);
                }

                for (const file of this.files.documents) {
                    formData.append('documents[]', file);
                }

                // Send request
                const response = await fetch(window.rpForm.apiUrl + 'applications', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-WP-Nonce': window.rpForm.nonce
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error?.message || 'Ein Fehler ist aufgetreten');
                }

                this.submitted = true;

                // Scroll to success message
                this.$nextTick(() => {
                    this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

            } catch (err) {
                this.error = err.message;
                console.error('Application submit error:', err);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Get progress percentage
         */
        get progress() {
            return Math.round((this.step / this.totalSteps) * 100);
        }
    }));
});
