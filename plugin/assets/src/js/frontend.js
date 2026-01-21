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
});

// Alpine wird über CDN/Bundle separat geladen
console.log('Recruiting Playbook Frontend loaded');
