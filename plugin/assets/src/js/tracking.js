/**
 * Recruiting Playbook - Conversion Tracking (FREE)
 *
 * DataLayer Events für Google Tag Manager Kompatibilität.
 *
 * @package RecruitingPlaybook
 */

(function() {
    'use strict';

    // DataLayer initialisieren falls nicht vorhanden.
    window.dataLayer = window.dataLayer || [];

    /**
     * Tracking-Daten aus dem DOM lesen
     */
    function getJobData() {
        const container = document.querySelector('[data-rp-tracking]');
        if (!container) {
            return null;
        }

        return {
            job_id: parseInt(container.dataset.rpJobId, 10) || 0,
            job_title: container.dataset.rpJobTitle || '',
            job_category: container.dataset.rpJobCategory || '',
            job_location: container.dataset.rpJobLocation || '',
            employment_type: container.dataset.rpEmploymentType || ''
        };
    }

    /**
     * DataLayer Event pushen
     */
    function pushEvent(eventName, data) {
        const eventData = {
            'event': eventName,
            ...data
        };

        window.dataLayer.push(eventData);

        // Debug-Modus
        if (window.RP_DEBUG_TRACKING) {
            console.log('[RP Tracking]', eventName, eventData);
        }
    }

    /**
     * rp_job_viewed - Stellenanzeige angesehen
     */
    function trackJobViewed() {
        const jobData = getJobData();
        if (!jobData || !jobData.job_id) {
            return;
        }

        pushEvent('rp_job_viewed', {
            'rp_job_id': jobData.job_id,
            'rp_job_title': jobData.job_title,
            'rp_job_category': jobData.job_category,
            'rp_job_location': jobData.job_location,
            'rp_employment_type': jobData.employment_type
        });
    }

    /**
     * rp_application_started - Bewerbungsformular geöffnet
     */
    function trackApplicationStarted(jobId, jobTitle) {
        pushEvent('rp_application_started', {
            'rp_job_id': jobId,
            'rp_job_title': jobTitle
        });
    }

    /**
     * rp_application_submitted - Bewerbung abgeschickt
     */
    function trackApplicationSubmitted(data) {
        pushEvent('rp_application_submitted', {
            'rp_job_id': data.job_id,
            'rp_job_title': data.job_title,
            'rp_job_category': data.job_category || '',
            'rp_job_location': data.job_location || '',
            'rp_application_id': data.application_id || 0
        });
    }

    /**
     * Formular-Interaktion tracken
     */
    function setupFormTracking() {
        const form = document.querySelector('[data-rp-application-form]');
        if (!form) {
            return;
        }

        const jobData = getJobData();
        if (!jobData) {
            return;
        }

        let formStarted = false;

        // Track wenn Formular sichtbar wird oder erstes Input
        const trackStart = function() {
            if (formStarted) {
                return;
            }
            formStarted = true;
            trackApplicationStarted(jobData.job_id, jobData.job_title);
        };

        // Bei erstem Fokus auf ein Formularfeld
        form.addEventListener('focusin', trackStart, { once: true });

        // Bei Scroll zum Formular (IntersectionObserver)
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        trackStart();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.5 });

            observer.observe(form);
        }
    }

    /**
     * Globale Funktion für Submit-Tracking (wird von application-form.js aufgerufen)
     */
    window.rpTrackApplicationSubmitted = function(data) {
        const jobData = getJobData();
        trackApplicationSubmitted({
            job_id: data.job_id || jobData?.job_id,
            job_title: data.job_title || jobData?.job_title,
            job_category: jobData?.job_category,
            job_location: jobData?.job_location,
            application_id: data.application_id
        });
    };

    /**
     * Initialisierung
     */
    function init() {
        // Job View tracken
        trackJobViewed();

        // Form-Tracking Setup
        setupFormTracking();
    }

    // Bei DOMContentLoaded initialisieren
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
