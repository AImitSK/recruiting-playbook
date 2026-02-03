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

        // Form data - initialized from config or defaults
        formData: {},

        // Validation rules from config
        validationRules: {},

        // i18n strings from config
        i18n: {},

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
            // Load configuration from PHP-generated window.rpFormConfig
            const config = window.rpFormConfig || {};

            // Set total steps from config
            this.totalSteps = config.steps || 3;

            // Initialize form data from config or use defaults
            this.formData = config.formData || {
                job_id: 0,
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                message: '',
                privacy_consent: false
            };

            // Load validation rules
            this.validationRules = config.validation || {};

            // Load i18n strings
            this.i18n = config.i18n || {};

            // Fallback: Get job_id from data attribute if not in config
            if (!this.formData.job_id) {
                const form = this.$el.closest('[data-job-id]');
                if (form) {
                    this.formData.job_id = parseInt(form.dataset.jobId, 10);
                }
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
         *
         * Uses dynamic validation based on fields visible in the current step.
         */
        validateCurrentStep() {
            this.errors = {};
            let valid = true;

            // Get fields for current step from DOM
            const stepContainer = this.$el.querySelector(`[x-show="step === ${this.step}"]`);
            if (!stepContainer) {
                return true;
            }

            // Find all field containers in current step
            const fieldContainers = stepContainer.querySelectorAll('[data-field]');

            fieldContainers.forEach(container => {
                const fieldKey = container.dataset.field;
                const rules = this.validationRules[fieldKey] || {};

                if (!this.validateField(fieldKey, rules)) {
                    valid = false;
                }
            });

            return valid;
        },

        /**
         * Validate a single field against its rules
         *
         * @param {string} fieldKey - Field identifier
         * @param {object} rules - Validation rules for the field
         * @returns {boolean} - Validation result
         */
        validateField(fieldKey, rules) {
            const value = this.formData[fieldKey];

            // Required check
            if (rules.required) {
                if (typeof value === 'boolean') {
                    if (!value) {
                        this.errors[fieldKey] = this.i18n.required || 'Dieses Feld ist erforderlich';
                        return false;
                    }
                } else if (typeof value === 'string') {
                    if (!value.trim()) {
                        this.errors[fieldKey] = this.i18n.required || 'Dieses Feld ist erforderlich';
                        return false;
                    }
                } else if (Array.isArray(value)) {
                    if (value.length === 0) {
                        this.errors[fieldKey] = this.i18n.required || 'Dieses Feld ist erforderlich';
                        return false;
                    }
                } else if (value === null || value === undefined) {
                    this.errors[fieldKey] = this.i18n.required || 'Dieses Feld ist erforderlich';
                    return false;
                }
            }

            // Skip further validation if empty and not required
            if (!value || (typeof value === 'string' && !value.trim())) {
                return true;
            }

            // Email validation
            if (rules.email && !this.isValidEmail(value)) {
                this.errors[fieldKey] = this.i18n.invalidEmail || 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
                return false;
            }

            // Phone validation
            if (rules.phone && !this.isValidPhone(value)) {
                this.errors[fieldKey] = this.i18n.invalidPhone || 'Bitte geben Sie eine gültige Telefonnummer ein';
                return false;
            }

            // URL validation
            if (rules.url && !this.isValidUrl(value)) {
                this.errors[fieldKey] = this.i18n.invalidUrl || 'Bitte geben Sie eine gültige URL ein';
                return false;
            }

            // Min length
            if (rules.minLength && typeof value === 'string' && value.length < rules.minLength) {
                this.errors[fieldKey] = (this.i18n.minLength || 'Mindestens %d Zeichen erforderlich').replace('%d', rules.minLength);
                return false;
            }

            // Max length
            if (rules.maxLength && typeof value === 'string' && value.length > rules.maxLength) {
                this.errors[fieldKey] = (this.i18n.maxLength || 'Maximal %d Zeichen erlaubt').replace('%d', rules.maxLength);
                return false;
            }

            return true;
        },

        /**
         * Check if field has error
         *
         * @param {string} fieldKey - Field identifier
         * @returns {boolean}
         */
        hasError(fieldKey) {
            return !!this.errors[fieldKey];
        },

        /**
         * Get error message for field
         *
         * @param {string} fieldKey - Field identifier
         * @returns {string}
         */
        getError(fieldKey) {
            return this.errors[fieldKey] || '';
        },

        /**
         * Email validation
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * Phone validation
         */
        isValidPhone(phone) {
            // Allow digits, spaces, dashes, parentheses, and + sign
            const regex = /^[+]?[\d\s\-().]{6,20}$/;
            return regex.test(phone);
        },

        /**
         * URL validation
         */
        isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
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
                    message: this.getI18nString('fileTooLarge', 'Die Datei ist zu groß (max. 10 MB)')
                };
            }

            if (!this.allowedTypes.includes(file.type)) {
                return {
                    valid: false,
                    message: this.getI18nString('invalidFileType', 'Dateityp nicht erlaubt. Erlaubt: PDF, DOC, DOCX, JPG, PNG')
                };
            }

            return { valid: true };
        },

        /**
         * Get i18n string with fallback
         *
         * Checks rpFormConfig.i18n first, then rpForm.i18n, then uses default.
         *
         * @param {string} key - Translation key
         * @param {string} defaultValue - Default value if not found
         * @returns {string}
         */
        getI18nString(key, defaultValue) {
            return this.i18n[key] || window.rpForm?.i18n?.[key] || defaultValue;
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
         * Validate all steps before submit
         */
        validateAllSteps() {
            this.errors = {};
            let valid = true;
            let firstErrorStep = null;

            // Validate all fields from all steps
            for (const [fieldKey, rules] of Object.entries(this.validationRules)) {
                if (!this.validateField(fieldKey, rules)) {
                    valid = false;
                    // Find which step this field is in
                    if (firstErrorStep === null) {
                        for (let s = 1; s <= this.totalSteps; s++) {
                            const stepContainer = this.$el.querySelector(`[x-show="step === ${s}"]`);
                            if (stepContainer && stepContainer.querySelector(`[data-field="${fieldKey}"]`)) {
                                firstErrorStep = s;
                                break;
                            }
                        }
                    }
                }
            }

            // Navigate to first step with error
            if (!valid && firstErrorStep !== null) {
                this.step = firstErrorStep;
            }

            return valid;
        },

        /**
         * Submit form
         */
        async submit() {
            if (!this.validateAllSteps()) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();

                // System-Felder, die direkt gesendet werden (nicht als custom_fields)
                const systemFields = [
                    'job_id', 'salutation', 'first_name', 'last_name', 'email', 'phone',
                    'cover_letter', 'message', 'privacy_consent', 'resume',
                    '_hp_field', '_form_timestamp'
                ];

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

                // Collect custom fields separately
                const customFieldsData = {};

                // Add form fields
                for (const [key, value] of Object.entries(this.formData)) {
                    // Check if this is a system field or custom field
                    const isSystemField = systemFields.includes(key) || key.startsWith('_');

                    if (isSystemField) {
                        // System fields go directly into FormData
                        if (typeof value === 'boolean') {
                            formData.append(key, value ? 'true' : 'false');
                        } else {
                            formData.append(key, value);
                        }
                    } else {
                        // Non-system fields are custom fields (e.g., field_123456789)
                        if (typeof value === 'boolean') {
                            customFieldsData[key] = value;
                        } else if (Array.isArray(value)) {
                            customFieldsData[key] = value;
                        } else {
                            customFieldsData[key] = value;
                        }
                    }
                }

                // Add custom_fields as JSON object if there are any
                if (Object.keys(customFieldsData).length > 0) {
                    formData.append('custom_fields', JSON.stringify(customFieldsData));
                }

                // Add files
                if (this.files.resume) {
                    formData.append('resume', this.files.resume);
                }

                for (const file of this.files.documents) {
                    formData.append('documents[]', file);
                }

                // Send request
                // WICHTIG: Keine Nonce für öffentliche Bewerbungen senden!
                // WordPress REST API gibt 403 zurück wenn Nonce ungültig ist (z.B. durch Caching).
                // Spam-Schutz wird stattdessen durch Honeypot und Timestamp gewährleistet.
                const response = await fetch(window.rpForm.apiUrl + 'applications', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    // Handle field-specific validation errors from backend.
                    if (data.data?.field_errors) {
                        this.errors = { ...this.errors, ...data.data.field_errors };
                    }
                    throw new Error(data.message || data.error?.message || 'Ein Fehler ist aufgetreten');
                }

                this.submitted = true;

                // Conversion Tracking: Bewerbung erfolgreich abgeschickt
                if (typeof window.rpTrackApplicationSubmitted === 'function') {
                    window.rpTrackApplicationSubmitted({
                        job_id: this.formData.job_id,
                        job_title: data.job_title || '',
                        application_id: data.application_id || data.id || 0
                    });
                }

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
