/**
 * Recruiting Playbook - Custom Fields Form (Alpine.js)
 *
 * Handles dynamic forms with custom fields, validation, and conditional logic.
 *
 * @package RecruitingPlaybook
 */

document.addEventListener('alpine:init', () => {
    /**
     * Custom Fields Form Component
     *
     * Usage: x-data="rpCustomFieldsForm()"
     * Config via: window.rpCustomFieldsConfig
     */
    Alpine.data('rpCustomFieldsForm', () => ({
        // Form state
        step: 1,
        totalSteps: 1,
        loading: false,
        submitted: false,
        error: null,

        // Form data (dynamic, populated from config)
        formData: {},

        // Files storage (keyed by field_key)
        files: {},

        // Validation errors
        errors: {},

        // Field definitions from server
        fields: [],

        // Steps configuration
        steps: [],

        // Conditional logic cache
        conditionCache: {},

        /**
         * Initialize form
         */
        init() {
            const config = window.rpCustomFieldsConfig || {};

            // Load field definitions
            this.fields = config.fields || [];
            this.steps = config.steps || [];
            this.totalSteps = this.steps.length || 1;

            // Initialize form data from field definitions
            this.initFormData(config.initialData || {});

            // Get job_id from data attribute
            const form = this.$el.closest('[data-job-id]');
            if (form) {
                this.formData.job_id = parseInt(form.dataset.jobId, 10);
            }

            // Initialize honeypot and timestamp
            this.formData._hp_field = '';
            this.formData._form_timestamp = 0;

            // Watch for conditional logic updates
            this.$watch('formData', () => {
                this.conditionCache = {};
            });
        },

        /**
         * Initialize form data from field definitions
         */
        initFormData(initialData) {
            this.formData = { ...initialData };

            for (const field of this.fields) {
                const key = field.field_key;

                if (this.formData[key] !== undefined) {
                    continue;
                }

                // Set default values based on field type
                switch (field.type) {
                    case 'checkbox':
                        if (field.settings?.mode === 'multi') {
                            this.formData[key] = [];
                        } else {
                            this.formData[key] = false;
                        }
                        break;

                    case 'number':
                        this.formData[key] = field.settings?.default_value || '';
                        break;

                    case 'file':
                        this.files[key] = [];
                        this.formData[key] = [];
                        break;

                    default:
                        this.formData[key] = field.settings?.default_value || '';
                }
            }
        },

        /**
         * Get field by key
         */
        getField(fieldKey) {
            return this.fields.find(f => f.field_key === fieldKey);
        },

        /**
         * Get fields for current step
         */
        getCurrentStepFields() {
            if (this.steps.length === 0) {
                return this.fields;
            }

            const currentStepId = this.steps[this.step - 1]?.id;
            return this.fields.filter(f => f.step_id === currentStepId);
        },

        /**
         * Check if field is visible (conditional logic)
         */
        isFieldVisible(fieldKey) {
            const cacheKey = `visible_${fieldKey}`;
            if (this.conditionCache[cacheKey] !== undefined) {
                return this.conditionCache[cacheKey];
            }

            const field = this.getField(fieldKey);
            if (!field || !field.conditional_logic?.enabled) {
                this.conditionCache[cacheKey] = true;
                return true;
            }

            const logic = field.conditional_logic;
            const conditions = logic.conditions || [];

            if (conditions.length === 0) {
                this.conditionCache[cacheKey] = true;
                return true;
            }

            const results = conditions.map(condition => this.evaluateCondition(condition));

            let result;
            if (logic.match === 'all') {
                result = results.every(r => r);
            } else {
                result = results.some(r => r);
            }

            // Apply action
            if (logic.action === 'hide') {
                result = !result;
            }

            this.conditionCache[cacheKey] = result;
            return result;
        },

        /**
         * Evaluate a single condition
         */
        evaluateCondition(condition) {
            const { field, operator, value } = condition;
            const fieldValue = this.formData[field];

            switch (operator) {
                case 'equals':
                    return fieldValue == value;

                case 'not_equals':
                    return fieldValue != value;

                case 'contains':
                    if (Array.isArray(fieldValue)) {
                        return fieldValue.includes(value);
                    }
                    return String(fieldValue).includes(value);

                case 'not_contains':
                    if (Array.isArray(fieldValue)) {
                        return !fieldValue.includes(value);
                    }
                    return !String(fieldValue).includes(value);

                case 'greater_than':
                    return parseFloat(fieldValue) > parseFloat(value);

                case 'less_than':
                    return parseFloat(fieldValue) < parseFloat(value);

                case 'is_empty':
                    return !fieldValue || fieldValue.length === 0;

                case 'is_not_empty':
                    return fieldValue && fieldValue.length > 0;

                case 'starts_with':
                    return String(fieldValue).startsWith(value);

                case 'ends_with':
                    return String(fieldValue).endsWith(value);

                default:
                    return true;
            }
        },

        /**
         * Navigate to next step
         */
        nextStep() {
            if (this.validateCurrentStep()) {
                this.step = Math.min(this.step + 1, this.totalSteps);
                this.scrollToTop();
            }
        },

        /**
         * Navigate to previous step
         */
        prevStep() {
            this.step = Math.max(this.step - 1, 1);
            this.scrollToTop();
        },

        /**
         * Go to specific step
         */
        goToStep(stepNumber) {
            if (stepNumber < this.step) {
                this.step = stepNumber;
                this.scrollToTop();
            } else if (stepNumber > this.step) {
                // Validate all steps up to target
                for (let i = this.step; i < stepNumber; i++) {
                    this.step = i;
                    if (!this.validateCurrentStep()) {
                        return;
                    }
                }
                this.step = stepNumber;
                this.scrollToTop();
            }
        },

        /**
         * Scroll to form top
         */
        scrollToTop() {
            this.$nextTick(() => {
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },

        /**
         * Validate current step
         */
        validateCurrentStep() {
            this.errors = {};
            const stepFields = this.getCurrentStepFields();

            let valid = true;

            for (const field of stepFields) {
                if (!this.isFieldVisible(field.field_key)) {
                    continue;
                }

                if (!this.validateField(field)) {
                    valid = false;
                }
            }

            return valid;
        },

        /**
         * Validate entire form
         */
        validateForm() {
            this.errors = {};
            let valid = true;

            for (const field of this.fields) {
                if (!this.isFieldVisible(field.field_key)) {
                    continue;
                }

                if (!this.validateField(field)) {
                    valid = false;
                }
            }

            return valid;
        },

        /**
         * Validate a single field
         */
        validateField(field) {
            const key = field.field_key;
            const value = this.formData[key];
            const validation = field.validation || {};
            const i18n = window.rpForm?.i18n || {};

            // Required check
            if (field.is_required) {
                if (!this.hasValue(value, field.type)) {
                    this.errors[key] = validation.custom_error || i18n.required || 'Dieses Feld ist erforderlich';
                    return false;
                }
            }

            // Skip further validation if empty and not required
            if (!this.hasValue(value, field.type)) {
                return true;
            }

            // Type-specific validation
            switch (field.type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        this.errors[key] = i18n.invalidEmail || 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
                        return false;
                    }
                    break;

                case 'url':
                    if (!this.isValidUrl(value)) {
                        this.errors[key] = i18n.invalidUrl || 'Bitte geben Sie eine gültige URL ein';
                        return false;
                    }
                    break;

                case 'phone':
                    if (!this.isValidPhone(value)) {
                        this.errors[key] = i18n.invalidPhone || 'Bitte geben Sie eine gültige Telefonnummer ein';
                        return false;
                    }
                    break;

                case 'number':
                    const num = parseFloat(value);
                    if (isNaN(num)) {
                        this.errors[key] = i18n.invalidNumber || 'Bitte geben Sie eine gültige Zahl ein';
                        return false;
                    }
                    if (validation.min !== undefined && num < validation.min) {
                        this.errors[key] = i18n.numberMin?.replace('{min}', validation.min) ||
                            `Mindestwert: ${validation.min}`;
                        return false;
                    }
                    if (validation.max !== undefined && num > validation.max) {
                        this.errors[key] = i18n.numberMax?.replace('{max}', validation.max) ||
                            `Maximalwert: ${validation.max}`;
                        return false;
                    }
                    break;

                case 'text':
                case 'textarea':
                    if (validation.min_length && value.length < validation.min_length) {
                        this.errors[key] = i18n.minLength?.replace('{min}', validation.min_length) ||
                            `Mindestens ${validation.min_length} Zeichen erforderlich`;
                        return false;
                    }
                    if (validation.max_length && value.length > validation.max_length) {
                        this.errors[key] = i18n.maxLength?.replace('{max}', validation.max_length) ||
                            `Maximal ${validation.max_length} Zeichen erlaubt`;
                        return false;
                    }
                    if (validation.pattern) {
                        const regex = new RegExp(validation.pattern);
                        if (!regex.test(value)) {
                            this.errors[key] = validation.custom_error || i18n.patternMismatch ||
                                'Das Format ist ungültig';
                            return false;
                        }
                    }
                    break;

                case 'date':
                    if (!this.isValidDate(value)) {
                        this.errors[key] = i18n.invalidDate || 'Bitte geben Sie ein gültiges Datum ein';
                        return false;
                    }
                    if (validation.min_date && value < validation.min_date) {
                        this.errors[key] = i18n.dateMin?.replace('{date}', validation.min_date) ||
                            `Datum muss nach ${validation.min_date} liegen`;
                        return false;
                    }
                    if (validation.max_date && value > validation.max_date) {
                        this.errors[key] = i18n.dateMax?.replace('{date}', validation.max_date) ||
                            `Datum muss vor ${validation.max_date} liegen`;
                        return false;
                    }
                    break;

                case 'file':
                    const files = this.files[key] || [];
                    if (field.is_required && files.length === 0) {
                        this.errors[key] = i18n.fileRequired || 'Bitte laden Sie eine Datei hoch';
                        return false;
                    }
                    break;

                case 'checkbox':
                    if (field.settings?.mode === 'multi' && validation.min_selections) {
                        if (value.length < validation.min_selections) {
                            this.errors[key] = i18n.minSelections?.replace('{min}', validation.min_selections) ||
                                `Bitte wählen Sie mindestens ${validation.min_selections} Optionen`;
                            return false;
                        }
                    }
                    if (field.settings?.mode === 'multi' && validation.max_selections) {
                        if (value.length > validation.max_selections) {
                            this.errors[key] = i18n.maxSelections?.replace('{max}', validation.max_selections) ||
                                `Bitte wählen Sie maximal ${validation.max_selections} Optionen`;
                            return false;
                        }
                    }
                    break;
            }

            return true;
        },

        /**
         * Check if value is considered "filled"
         */
        hasValue(value, fieldType) {
            if (value === null || value === undefined) {
                return false;
            }

            if (Array.isArray(value)) {
                return value.length > 0;
            }

            if (typeof value === 'boolean') {
                return value === true;
            }

            if (typeof value === 'string') {
                return value.trim() !== '';
            }

            return true;
        },

        /**
         * Email validation
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
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
         * Phone validation (basic)
         */
        isValidPhone(phone) {
            // Allow digits, spaces, dashes, parentheses, and plus sign
            const regex = /^[\d\s\-()+ ]{6,}$/;
            return regex.test(phone);
        },

        /**
         * Date validation
         */
        isValidDate(date) {
            const parsed = new Date(date);
            return !isNaN(parsed.getTime());
        },

        /**
         * Submit form
         */
        async submit() {
            if (!this.validateForm()) {
                // Find first error and scroll to it
                const firstError = Object.keys(this.errors)[0];
                if (firstError) {
                    const errorElement = this.$el.querySelector(`[name="${firstError}"]`);
                    if (errorElement) {
                        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        errorElement.focus();
                    }
                }
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const submitData = new FormData();

                // Read honeypot field from DOM
                const honeypotField = this.$el.querySelector('input[name="_hp_field"]');
                if (honeypotField) {
                    this.formData._hp_field = honeypotField.value;
                }

                // Read timestamp from DOM
                const timestampField = this.$el.querySelector('input[name="_form_timestamp"]');
                if (timestampField && timestampField.value) {
                    this.formData._form_timestamp = parseInt(timestampField.value, 10);
                }

                // Add regular form fields
                for (const [key, value] of Object.entries(this.formData)) {
                    // Skip file fields (handled separately)
                    if (this.files[key] !== undefined) {
                        continue;
                    }

                    // Only include visible fields
                    const field = this.getField(key);
                    if (field && !this.isFieldVisible(key)) {
                        continue;
                    }

                    if (typeof value === 'boolean') {
                        submitData.append(key, value ? 'true' : 'false');
                    } else if (Array.isArray(value)) {
                        submitData.append(key, JSON.stringify(value));
                    } else {
                        submitData.append(key, value);
                    }
                }

                // Add file fields
                for (const [key, fileList] of Object.entries(this.files)) {
                    // Only include visible fields
                    if (!this.isFieldVisible(key)) {
                        continue;
                    }

                    for (const file of fileList) {
                        submitData.append(`${key}[]`, file);
                    }
                }

                // Add custom fields metadata
                submitData.append('_custom_fields', JSON.stringify(
                    this.fields
                        .filter(f => this.isFieldVisible(f.field_key))
                        .map(f => f.field_key)
                ));

                // Send request
                const response = await fetch(window.rpForm.apiUrl + 'applications', {
                    method: 'POST',
                    body: submitData,
                    headers: {
                        'X-WP-Nonce': window.rpForm.nonce
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error?.message || 'Ein Fehler ist aufgetreten');
                }

                this.submitted = true;

                // Conversion Tracking
                if (typeof window.rpTrackApplicationSubmitted === 'function') {
                    window.rpTrackApplicationSubmitted({
                        job_id: this.formData.job_id,
                        job_title: data.job_title || '',
                        application_id: data.application_id || data.id || 0
                    });
                }

                // Scroll to success message
                this.scrollToTop();

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
        },

        /**
         * Get current step info
         */
        get currentStep() {
            return this.steps[this.step - 1] || null;
        }
    }));

    /**
     * File Upload Component
     *
     * Usage: x-data="rpFileUpload('field_key', { maxFiles: 5, maxSize: 10, multiple: true })"
     */
    Alpine.data('rpFileUpload', (fieldKey, options = {}) => ({
        fieldKey,
        maxFiles: options.maxFiles || 5,
        maxSize: (options.maxSize || 10) * 1024 * 1024, // Convert MB to bytes
        multiple: options.multiple !== false,
        allowedTypes: options.allowedTypes || null,

        files: [],
        isDragging: false,
        error: null,

        /**
         * Initialize
         */
        init() {
            // Sync with parent form if available
            const parentForm = Alpine.$data(this.$el.closest('[x-data*="rpCustomFieldsForm"]'));
            if (parentForm && parentForm.files) {
                this.files = parentForm.files[this.fieldKey] || [];

                // Watch for changes
                this.$watch('files', (value) => {
                    if (parentForm.files) {
                        parentForm.files[this.fieldKey] = value;
                    }
                });
            }
        },

        /**
         * Handle file selection
         */
        handleSelect(event) {
            const fileList = event.target.files;
            this.addFiles(fileList);
            event.target.value = ''; // Reset input
        },

        /**
         * Handle drag & drop
         */
        handleDrop(event) {
            this.isDragging = false;
            const fileList = event.dataTransfer.files;
            this.addFiles(fileList);
        },

        /**
         * Add files with validation
         */
        addFiles(fileList) {
            this.error = null;

            for (const file of fileList) {
                // Check max files
                if (this.files.length >= this.maxFiles) {
                    this.error = `Maximal ${this.maxFiles} Dateien erlaubt`;
                    break;
                }

                // Validate file
                const validation = this.validateFile(file);
                if (!validation.valid) {
                    this.error = validation.message;
                    continue;
                }

                // Add file
                if (this.multiple) {
                    this.files.push(file);
                } else {
                    this.files = [file];
                }
            }

            // Clear error in parent form
            this.clearParentError();
        },

        /**
         * Validate single file
         */
        validateFile(file) {
            const i18n = window.rpForm?.i18n || {};

            // Size check
            if (file.size > this.maxSize) {
                return {
                    valid: false,
                    message: i18n.fileTooLarge ||
                        `Die Datei ist zu groß (max. ${this.maxSize / 1024 / 1024} MB)`
                };
            }

            // Type check (if allowedTypes specified)
            if (this.allowedTypes) {
                const extension = '.' + file.name.split('.').pop().toLowerCase();
                const mimeType = file.type;

                const allowed = this.allowedTypes.split(',').map(t => t.trim().toLowerCase());
                const isAllowed = allowed.some(t =>
                    t === extension ||
                    t === mimeType ||
                    (t.endsWith('/*') && mimeType.startsWith(t.slice(0, -1)))
                );

                if (!isAllowed) {
                    return {
                        valid: false,
                        message: i18n.invalidFileType ||
                            `Dateityp nicht erlaubt. Erlaubt: ${this.allowedTypes}`
                    };
                }
            }

            return { valid: true };
        },

        /**
         * Remove file
         */
        removeFile(index) {
            this.files.splice(index, 1);
            this.error = null;
            this.clearParentError();
        },

        /**
         * Clear parent form error for this field
         */
        clearParentError() {
            const parentForm = Alpine.$data(this.$el.closest('[x-data*="rpCustomFieldsForm"]'));
            if (parentForm && parentForm.errors) {
                delete parentForm.errors[this.fieldKey];
            }
        },

        /**
         * Format file size
         */
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }));

    /**
     * Conditional Field Component
     *
     * Usage: x-data="rpConditionalField('field_key')"
     */
    Alpine.data('rpConditionalField', (fieldKey) => ({
        fieldKey,

        /**
         * Check if field is visible
         */
        get isVisible() {
            const parentForm = Alpine.$data(this.$el.closest('[x-data*="rpCustomFieldsForm"]'));
            if (parentForm && typeof parentForm.isFieldVisible === 'function') {
                return parentForm.isFieldVisible(this.fieldKey);
            }
            return true;
        }
    }));
});
