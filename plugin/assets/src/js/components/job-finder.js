/**
 * KI-Job-Finder Alpine.js Component
 *
 * Ermöglicht Multi-Job-Matching: CV gegen alle aktiven Jobs analysieren.
 * Mode B des KI-Matching Features.
 */

const rpJobFinderComponent = (options = {}) => ({
    // ===== CONFIG =====
    limit: options.limit || 5,
    jobCount: options.jobCount || 0,

    // ===== STATE =====
    file: null,
    fileName: '',
    fileSize: '',
    isDragging: false,
    status: 'idle', // idle | uploading | processing | completed | error
    jobRequestId: null,
    pollInterval: null,
    progress: 0,

    // Ergebnisse
    result: null,
    error: null,

    // Config (von wp_localize_script)
    config: window.rpJobFinderConfig || {},

    // ===== INIT =====
    init() {
        console.log('[RP] Job-Finder Component initialized', { limit: this.limit, jobCount: this.jobCount });
    },

    // ===== FILE HANDLING =====
    handleDragOver(e) {
        e.preventDefault();
        this.isDragging = true;
    },

    handleDragLeave(e) {
        e.preventDefault();
        this.isDragging = false;
    },

    handleDrop(e) {
        e.preventDefault();
        this.isDragging = false;

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    },

    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    },

    processFile(file) {
        // Validierung
        const allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.error = this.config.i18n?.invalidFileType ||
                'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.';
            this.status = 'error';
            return;
        }

        // Max 10 MB
        if (file.size > 10 * 1024 * 1024) {
            this.error = this.config.i18n?.fileTooLarge ||
                'Die Datei ist zu groß. Maximum: 10 MB.';
            this.status = 'error';
            return;
        }

        this.file = file;
        this.fileName = file.name;
        this.fileSize = this.formatFileSize(file.size);
        this.error = null;
        this.status = 'idle';
    },

    removeFile() {
        this.file = null;
        this.fileName = '';
        this.fileSize = '';
    },

    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },

    // ===== ANALYSIS =====
    async startAnalysis() {
        if (!this.file) return;

        this.status = 'uploading';
        this.progress = 10;
        this.error = null;

        try {
            const formData = new FormData();
            formData.append('file', this.file);
            formData.append('limit', this.limit);

            const response = await fetch(this.config.endpoints.analyze, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-WP-Nonce': this.config.nonce
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Analyse fehlgeschlagen');
            }

            this.jobRequestId = data.job_id;
            this.status = 'processing';
            this.progress = 30;

            this.startPolling();

        } catch (e) {
            console.error('[RP] Job-Finder Error:', e);
            this.status = 'error';
            this.error = e.message;
        }
    },

    // ===== POLLING =====
    startPolling() {
        this.pollInterval = setInterval(() => this.checkStatus(), 2000);

        // Simulierter Progress
        this.simulateProgress();
    },

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    async checkStatus() {
        if (!this.jobRequestId) return;

        try {
            const response = await fetch(
                `${this.config.endpoints.status}/${this.jobRequestId}`,
                {
                    headers: {
                        'X-WP-Nonce': this.config.nonce
                    }
                }
            );

            const data = await response.json();

            if (data.status === 'completed') {
                this.stopPolling();
                // Parse result if it's a string (result_message contains JSON)
                if (data.result && typeof data.result.message === 'string') {
                    try {
                        this.result = JSON.parse(data.result.message);
                    } catch {
                        this.result = data.result;
                    }
                } else if (typeof data.result_message === 'string') {
                    this.result = JSON.parse(data.result_message);
                } else {
                    this.result = data.result || data;
                }
                this.status = 'completed';
                this.progress = 100;
            } else if (data.status === 'failed') {
                this.stopPolling();
                this.status = 'error';
                this.error = data.error_message || data.error || 'Analyse fehlgeschlagen';
            }

        } catch (e) {
            console.error('[RP] Status Check Error:', e);
        }
    },

    simulateProgress() {
        const steps = [40, 55, 70, 85];
        let i = 0;

        const interval = setInterval(() => {
            if (this.status !== 'processing' || i >= steps.length) {
                clearInterval(interval);
                return;
            }
            this.progress = steps[i];
            i++;
        }, 3000);
    },

    // ===== HELPERS =====
    getCategoryClass(category) {
        const classes = {
            high: 'rp-job-finder-match--high',
            medium: 'rp-job-finder-match--medium',
            low: 'rp-job-finder-match--low'
        };
        return classes[category] || '';
    },

    getCategoryLabel(category) {
        const labels = {
            high: this.config.i18n?.resultHigh || 'Gute Übereinstimmung',
            medium: this.config.i18n?.resultMedium || 'Teilweise passend',
            low: this.config.i18n?.resultLow || 'Weniger passend'
        };
        return labels[category] || '';
    },

    getScoreClass(category) {
        const classes = {
            high: 'rp-text-success',
            medium: 'rp-text-warning',
            low: 'rp-text-error'
        };
        return classes[category] || '';
    },

    // ===== COMPUTED =====
    get hasResults() {
        return this.status === 'completed' &&
               this.result?.matches?.length > 0;
    },

    get noMatches() {
        return this.status === 'completed' &&
               (!this.result?.matches || this.result.matches.length === 0);
    },

    get statusMessage() {
        const messages = {
            uploading: this.config.i18n?.uploading || 'Dokument wird hochgeladen...',
            processing: this.config.i18n?.analyzing || 'Analysiere Lebenslauf gegen alle Stellen...'
        };
        return messages[this.status] || '';
    },

    // ===== ACTIONS =====
    reset() {
        this.file = null;
        this.fileName = '';
        this.fileSize = '';
        this.status = 'idle';
        this.result = null;
        this.error = null;
        this.progress = 0;
        this.jobRequestId = null;
        this.stopPolling();
    }
});

// Registrierung
function registerJobFinderComponent() {
    if (typeof Alpine !== 'undefined' && Alpine.data) {
        console.log('[RP] Registering rpJobFinder component');
        Alpine.data('rpJobFinder', rpJobFinderComponent);
    }
}

if (typeof Alpine !== 'undefined') {
    registerJobFinderComponent();
} else {
    document.addEventListener('alpine:init', registerJobFinderComponent);
}
