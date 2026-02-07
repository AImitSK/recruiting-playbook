<?php
/**
 * Freemius SDK Bridge
 *
 * Leitet zum Composer-installierten Freemius SDK weiter.
 * Wird von Add-ons benötigt, die das SDK über den Standard-Pfad
 * (freemius/start.php) aus dem Parent-Plugin laden.
 *
 * @package RecruitingPlaybook
 */

require_once dirname( __DIR__ ) . '/vendor/freemius/wordpress-sdk/start.php';
