<?php

declare(strict_types=1);


namespace RecruitingPlaybook\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin-Deaktivierung
 */
class Deactivator {

    /**
     * Bei Deaktivierung ausführen
     */
    public static function deactivate(): void {
        // Rewrite Rules flushen
        flush_rewrite_rules();

        // Geplante Tasks entfernen
        self::clearScheduledTasks();
    }

    /**
     * Geplante WP-Cron Tasks entfernen
     */
    private static function clearScheduledTasks(): void {
        $hooks = [
            'rp_daily_cleanup',
            'rp_license_check',
        ];

        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }
}
