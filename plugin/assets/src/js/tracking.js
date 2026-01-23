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

    // Observer-Referenz für Cleanup (Memory Leak Prevention).
    var formObserver = null;

    /**
     * Tracking-Daten aus dem DOM lesen
     */
    function getJobData() {
        try {
            var container = document.querySelector('[data-rp-tracking]');
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
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in getJobData:', e);
            }
            return null;
        }
    }

    /**
     * DataLayer Event pushen
     */
    function pushEvent(eventName, data) {
        try {
            var eventData = {
                'event': eventName
            };

            // Daten manuell kopieren (Object.assign Fallback für ältere Browser).
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    eventData[key] = data[key];
                }
            }

            window.dataLayer.push(eventData);

            // Debug-Modus
            if (window.RP_DEBUG_TRACKING) {
                console.log('[RP Tracking]', eventName, eventData);
            }
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in pushEvent:', e);
            }
        }
    }

    /**
     * rp_job_viewed - Stellenanzeige angesehen
     */
    function trackJobViewed() {
        try {
            var jobData = getJobData();
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
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in trackJobViewed:', e);
            }
        }
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
     * Observer aufräumen (Memory Leak Prevention)
     */
    function cleanupObserver() {
        if (formObserver) {
            formObserver.disconnect();
            formObserver = null;
        }
    }

    /**
     * Formular-Interaktion tracken
     */
    function setupFormTracking() {
        try {
            var form = document.querySelector('[data-rp-application-form]');
            if (!form) {
                return;
            }

            var jobData = getJobData();
            if (!jobData) {
                return;
            }

            var formStarted = false;

            // Track wenn Formular sichtbar wird oder erstes Input
            var trackStart = function() {
                if (formStarted) {
                    return;
                }
                formStarted = true;

                // Observer aufräumen wenn nicht mehr benötigt.
                cleanupObserver();

                trackApplicationStarted(jobData.job_id, jobData.job_title);
            };

            // Bei erstem Fokus auf ein Formularfeld
            form.addEventListener('focusin', trackStart, { once: true });

            // Bei Scroll zum Formular (IntersectionObserver)
            if ('IntersectionObserver' in window) {
                formObserver = new IntersectionObserver(function(entries) {
                    for (var i = 0; i < entries.length; i++) {
                        if (entries[i].isIntersecting) {
                            trackStart();
                            // Observer wird in trackStart() aufgeräumt.
                            break;
                        }
                    }
                }, { threshold: 0.5 });

                formObserver.observe(form);
            }

            // Cleanup bei Page Unload (Memory Leak Prevention).
            window.addEventListener('beforeunload', cleanupObserver);
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in setupFormTracking:', e);
            }
        }
    }

    /**
     * Globale Funktion für Submit-Tracking (wird von application-form.js aufgerufen)
     */
    window.rpTrackApplicationSubmitted = function(data) {
        try {
            var jobData = getJobData();
            trackApplicationSubmitted({
                job_id: data.job_id || (jobData ? jobData.job_id : 0),
                job_title: data.job_title || (jobData ? jobData.job_title : ''),
                job_category: jobData ? jobData.job_category : '',
                job_location: jobData ? jobData.job_location : '',
                application_id: data.application_id
            });
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in rpTrackApplicationSubmitted:', e);
            }
        }
    };

    /**
     * Initialisierung
     */
    function init() {
        try {
            // Job View tracken
            trackJobViewed();

            // Form-Tracking Setup
            setupFormTracking();
        } catch (e) {
            if (window.RP_DEBUG_TRACKING) {
                console.error('[RP Tracking] Error in init:', e);
            }
        }
    }

    // Bei DOMContentLoaded initialisieren
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
