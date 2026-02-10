/**
 * CV Matching Modal Component
 *
 * Alpine.js Komponente für das KI-Matching Feature.
 * Ermöglicht Bewerbern, ihren Lebenslauf hochzuladen und
 * eine KI-basierte Einschätzung der Passgenauigkeit zu erhalten.
 */

// Komponenten-Definition
const matchModalComponent = () => ({
        // Modal State
        isOpen: false,
        jobId: null,
        jobTitle: '',

        // Upload State
        file: null,
        isDragging: false,

        // Process State
        status: 'idle', // idle, uploading, processing, completed, error
        jobRequestId: null,
        pollInterval: null,
        progress: 0,

        // Result
        result: null,
        error: null,

        // Init
        init() {
            console.log('[RP] matchModal component initialized');
            // ESC zum Schließen
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        // Modal öffnen
        open(jobId, jobTitle) {
            console.log('[RP] matchModal.open called', { jobId, jobTitle });
            this.reset();
            this.jobId = jobId;
            this.jobTitle = jobTitle;
            this.isOpen = true;
            document.body.classList.add('rp-modal-open');
        },

        // Modal schließen
        close() {
            console.log('[RP] matchModal.close() called, isOpen was:', this.isOpen);
            this.isOpen = false;
            document.body.classList.remove('rp-modal-open');
            this.stopPolling();
        },

        // State zurücksetzen
        reset() {
            this.file = null;
            this.status = 'idle';
            this.jobRequestId = null;
            this.progress = 0;
            this.result = null;
            this.error = null;
            this.stopPolling();
        },

        // Drag & Drop Handler
        handleDragOver(e) {
            e.preventDefault();
            this.isDragging = true;
        },

        handleDragLeave() {
            this.isDragging = false;
        },

        handleDrop(e) {
            e.preventDefault();
            this.isDragging = false;

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },

        // File Input Handler
        handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },

        // File validieren
        handleFile(file) {
            // Erlaubte Typen
            const allowedTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!allowedTypes.includes(file.type)) {
                this.error = rpMatchConfig.i18n.invalidFileType || 'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.';
                return;
            }

            // Max 10MB
            if (file.size > 10 * 1024 * 1024) {
                this.error = rpMatchConfig.i18n.fileTooLarge || 'Die Datei ist zu groß. Maximum: 10 MB.';
                return;
            }

            this.file = file;
            this.error = null;
        },

        // Datei entfernen
        removeFile() {
            this.file = null;
        },

        // Analyse starten
        async startAnalysis() {
            if (!this.file || !this.jobId) return;

            this.status = 'uploading';
            this.error = null;
            this.progress = 10;

            try {
                const formData = new FormData();
                formData.append('file', this.file);
                formData.append('job_id', this.jobId);

                const response = await fetch(rpMatchConfig.endpoints.analyze, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-WP-Nonce': rpMatchConfig.nonce,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || rpMatchConfig.i18n.analysisFailed || 'Analyse fehlgeschlagen');
                }

                this.jobRequestId = data.job_id;
                this.status = 'processing';
                this.progress = 30;

                // Polling starten
                this.startPolling();

            } catch (e) {
                this.status = 'error';
                this.error = e.message || rpMatchConfig.i18n.error || 'Ein Fehler ist aufgetreten';
            }
        },

        // Polling für Ergebnis
        startPolling() {
            this.pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(
                        `${rpMatchConfig.endpoints.status}/${this.jobRequestId}`,
                        {
                            headers: {
                                'X-WP-Nonce': rpMatchConfig.nonce,
                            },
                        }
                    );

                    const data = await response.json();

                    // Progress simulieren
                    if (this.progress < 90) {
                        this.progress += 10;
                    }

                    if (data.status === 'completed') {
                        this.result = data.result;
                        this.status = 'completed';
                        this.progress = 100;
                        this.stopPolling();
                    } else if (data.status === 'failed') {
                        this.error = data.error || rpMatchConfig.i18n.analysisFailed || 'Analyse fehlgeschlagen';
                        this.status = 'error';
                        this.stopPolling();
                    }

                } catch (e) {
                    // Bei Netzwerkfehler weiter versuchen
                    console.error('Polling error:', e);
                }
            }, 2000); // Alle 2 Sekunden

            // Timeout nach 2 Minuten
            setTimeout(() => {
                if (this.status === 'processing') {
                    this.error = rpMatchConfig.i18n.timeout || 'Die Analyse dauert zu lange. Bitte versuchen Sie es später erneut.';
                    this.status = 'error';
                    this.stopPolling();
                }
            }, 120000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        // Ergebnis-Farbe
        get resultColor() {
            if (!this.result) return '';
            const colors = {
                low: 'rp-result--low',
                medium: 'rp-result--medium',
                high: 'rp-result--high',
            };
            return colors[this.result.category] || '';
        },

        // Ergebnis-Label
        get resultLabel() {
            if (!this.result) return '';
            const labels = {
                low: rpMatchConfig.i18n.resultLow || 'Eher nicht passend',
                medium: rpMatchConfig.i18n.resultMedium || 'Teilweise passend',
                high: rpMatchConfig.i18n.resultHigh || 'Gute Übereinstimmung',
            };
            return labels[this.result.category] || '';
        },

        // Formatierter Dateiname
        get fileName() {
            if (!this.file) return '';
            const name = this.file.name;
            if (name.length > 30) {
                return name.substring(0, 27) + '...';
            }
            return name;
        },

    // Formatierte Dateigröße
    get fileSize() {
        if (!this.file) return '';
        const kb = this.file.size / 1024;
        if (kb < 1024) {
            return Math.round(kb) + ' KB';
        }
        return (kb / 1024).toFixed(1) + ' MB';
    },
});

// Komponente registrieren - mit Fallback für verschiedene Ladereihenfolgen
function registerMatchModalComponent() {
    if (typeof Alpine !== 'undefined' && Alpine.data) {
        console.log('[RP] Registering matchModal component');
        Alpine.data('matchModal', matchModalComponent);
    } else {
        console.warn('[RP] Alpine not available for matchModal registration');
    }
}

// Versuche sofortige Registrierung (falls Alpine schon geladen)
if (typeof Alpine !== 'undefined') {
    registerMatchModalComponent();
} else {
    // Warte auf Alpine via alpine:init Event
    document.addEventListener('alpine:init', registerMatchModalComponent);
}
