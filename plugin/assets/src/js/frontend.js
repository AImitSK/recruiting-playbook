/**
 * Recruiting Playbook - Frontend JavaScript
 * Verwendet Alpine.js für Interaktivität
 */

document.addEventListener('alpine:init', () => {
    // Job Filter Store
    Alpine.store('jobFilter', {
        category: '',
        location: '',
        employmentType: '',
        search: '',

        reset() {
            this.category = '';
            this.location = '';
            this.employmentType = '';
            this.search = '';
        },
    });

    // Notification Store (für Erfolgsmeldungen etc.)
    Alpine.store('notifications', {
        items: [],

        add(message, type = 'info', duration = 5000) {
            const id = Date.now();
            this.items.push({ id, message, type });

            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }

            return id;
        },

        remove(id) {
            this.items = this.items.filter(item => item.id !== id);
        },

        success(message) {
            return this.add(message, 'success');
        },

        error(message) {
            return this.add(message, 'error');
        },
    });

    // File Upload Component
    Alpine.data('rpFileUpload', (config = {}) => ({
        files: [],
        dragging: false,
        error: null,
        maxFiles: config.maxFiles || 5,
        maxSize: (config.maxSize || 10) * 1024 * 1024,
        allowedTypes: config.allowedTypes || ['pdf', 'doc', 'docx'],

        init() {
            // Sync mit Parent-Formular über Dispatch
            this.$watch('files', (newFiles) => {
                this.$dispatch('files-updated', { files: [...newFiles] });
            });
        },

        handleSelect(event) {
            this.addFiles(event.target.files);
            event.target.value = '';
        },

        handleDrop(event) {
            this.dragging = false;
            this.addFiles(event.dataTransfer.files);
        },

        isValidType(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            return this.allowedTypes.includes(ext);
        },

        addFiles(fileList) {
            this.error = null;

            for (const file of fileList) {
                if (this.files.length >= this.maxFiles) {
                    this.error = config.errorMaxFiles || 'Maximal ' + this.maxFiles + ' Dateien erlaubt';
                    break;
                }

                if (!this.isValidType(file)) {
                    this.error = config.errorInvalidType || 'Ungültiger Dateityp';
                    continue;
                }

                if (file.size > this.maxSize) {
                    this.error = config.errorTooLarge || 'Datei zu groß';
                    continue;
                }

                this.files.push(file);
            }
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        }
    }));
});

// Alpine wird über CDN/Bundle separat geladen
console.log('Recruiting Playbook Frontend loaded');
