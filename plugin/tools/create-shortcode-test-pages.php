<?php
/**
 * Shortcode Test Pages Creator
 *
 * Erstellt Testseiten für alle Shortcodes mit Navigation.
 *
 * Ausführen:
 *   wp eval-file plugin/tools/create-shortcode-test-pages.php
 *
 * Löschen:
 *   wp eval-file plugin/tools/create-shortcode-test-pages.php --delete
 *
 * @package RecruitingPlaybook
 */

// Prüfen ob WP geladen ist
if ( ! defined( 'ABSPATH' ) ) {
    // Versuche WP zu laden (für direkten Aufruf)
    $wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
    if ( file_exists( $wp_load ) ) {
        require_once $wp_load;
    } else {
        die( "WordPress nicht gefunden. Bitte via WP-CLI ausführen:\nwp eval-file plugin/tools/create-shortcode-test-pages.php\n" );
    }
}

// Delete-Modus prüfen
$delete_mode = in_array( '--delete', $argv ?? [], true );

/**
 * Alle Testseiten-Definitionen
 */
$test_pages = [
    'shortcode-tests' => [
        'title'   => 'Shortcode Tests - Übersicht',
        'content' => '<!-- wp:heading -->
<h2>Recruiting Playbook - Shortcode Tests</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Diese Seiten dienen zum Testen aller Shortcodes und ihrer Kompatibilität mit den Design & Branding Einstellungen.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>✅ Implementierte Shortcodes</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><a href="/shortcode-test-rp-jobs/">rp_jobs</a> - Job-Liste mit Grid</li>
<li><a href="/shortcode-test-rp-job-search/">rp_job_search</a> - Suchformular mit Filtern</li>
<li><a href="/shortcode-test-rp-application-form/">rp_application_form</a> - Bewerbungsformular</li>
<li><a href="/shortcode-test-rp-custom-application-form/">rp_custom_application_form</a> - Custom Formular (Form Builder)</li>
<li><a href="/shortcode-test-rp-ai-job-match/">rp_ai_job_match</a> - KI-Matching Button</li>
<li><a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder</a> - KI-Job-Finder</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>❌ Nicht implementierte Shortcodes (geplant)</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>rp_jobs_tabs - Gefilterte Tabs</li>
<li>rp_jobs_slider - Karussell</li>
<li>rp_job_card - Einzelne Job-Karte</li>
<li>rp_featured_jobs - Top-Stellen</li>
<li>rp_latest_jobs - Neueste Stellen</li>
<li>rp_job_count - Stellen-Counter</li>
<li>rp_job_categories - Kategorie-Übersicht</li>
<li>rp_ai_chance_check - Einstellungschancen</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>Design & Branding Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Für jeden Shortcode prüfen:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li>✅ Primärfarbe wird angewendet</li>
<li>✅ Button-Styles (Theme/Custom)</li>
<li>✅ Card-Design (Radius, Schatten, Rahmen)</li>
<li>✅ Typografie (Schriftgrößen)</li>
<li>✅ Badge-Farben</li>
<li>✅ Hover-Effekte</li>
</ul>
<!-- /wp:list -->',
    ],

    'shortcode-test-rp-jobs' => [
        'title'   => 'Test: rp_jobs',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-tests/">← Zurück zur Übersicht</a> | <a href="/shortcode-test-rp-job-search/">Weiter: rp_job_search →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_jobs] - Job-Liste</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Standard (keine Parameter)</h3>
<!-- /wp:heading -->

[rp_jobs]

<!-- wp:heading {"level":3} -->
<h3>Mit limit="3"</h3>
<!-- /wp:heading -->

[rp_jobs limit="3"]

<!-- wp:heading {"level":3} -->
<h3>2 Spalten</h3>
<!-- /wp:heading -->

[rp_jobs limit="4" columns="2"]

<!-- wp:heading {"level":3} -->
<h3>3 Spalten</h3>
<!-- /wp:heading -->

[rp_jobs limit="6" columns="3"]

<!-- wp:heading {"level":3} -->
<h3>4 Spalten</h3>
<!-- /wp:heading -->

[rp_jobs limit="8" columns="4"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Excerpt</h3>
<!-- /wp:heading -->

[rp_jobs limit="3" show_excerpt="false"]

<!-- wp:heading {"level":3} -->
<h3>Sortiert nach Titel ASC</h3>
<!-- /wp:heading -->

[rp_jobs limit="3" orderby="title" order="ASC"]

<!-- wp:heading {"level":3} -->
<h3>Zufällige Reihenfolge</h3>
<!-- /wp:heading -->

[rp_jobs limit="3" orderby="rand"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Card-Hintergrund</li>
<li>[ ] Card-Rahmen (Farbe, Stärke)</li>
<li>[ ] Card-Schatten</li>
<li>[ ] Card-Eckenradius</li>
<li>[ ] Hover-Effekt</li>
<li>[ ] Badge-Farben (Neu, Remote, Kategorie)</li>
<li>[ ] Badge-Stil (Hell/Ausgefüllt)</li>
<li>[ ] Link-Farbe ("mehr Informationen")</li>
<li>[ ] Schriftgrößen</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-tests/">← Zurück zur Übersicht</a> | <a href="/shortcode-test-rp-job-search/">Weiter: rp_job_search →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-job-search' => [
        'title'   => 'Test: rp_job_search',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-jobs/">← rp_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-application-form/">rp_application_form →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_job_search] - Suchformular</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Standard (alle Filter)</h3>
<!-- /wp:heading -->

[rp_job_search]

<!-- wp:heading {"level":3} -->
<h3>2 Spalten Ergebnisse</h3>
<!-- /wp:heading -->

[rp_job_search columns="2"]

<!-- wp:heading {"level":3} -->
<h3>3 Spalten Ergebnisse</h3>
<!-- /wp:heading -->

[rp_job_search columns="3"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Kategorie-Filter</h3>
<!-- /wp:heading -->

[rp_job_search show_category="false"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Standort-Filter</h3>
<!-- /wp:heading -->

[rp_job_search show_location="false"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Beschäftigungsart-Filter</h3>
<!-- /wp:heading -->

[rp_job_search show_type="false"]

<!-- wp:heading {"level":3} -->
<h3>Nur Suchfeld (keine Dropdowns)</h3>
<!-- /wp:heading -->

[rp_job_search show_category="false" show_location="false" show_type="false"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Suchfeld (nur Dropdowns)</h3>
<!-- /wp:heading -->

[rp_job_search show_search="false"]

<!-- wp:heading {"level":3} -->
<h3>Limit 5 pro Seite</h3>
<!-- /wp:heading -->

[rp_job_search limit="5"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Suchfeld-Styling</li>
<li>[ ] Dropdown-Styling</li>
<li>[ ] Such-Button (Primärfarbe)</li>
<li>[ ] Filter-Tags</li>
<li>[ ] Ergebnis-Cards (siehe rp_jobs)</li>
<li>[ ] Pagination</li>
<li>[ ] "Keine Ergebnisse" Meldung</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-jobs/">← rp_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-application-form/">rp_application_form →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-application-form' => [
        'title'   => 'Test: rp_application_form',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-search/">← rp_job_search</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-custom-application-form/">rp_custom_application_form →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_application_form] - Bewerbungsformular</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-pink"} -->
<p class="has-pale-pink-background-color has-background"><strong>Hinweis:</strong> Dieser Shortcode benötigt eine job_id. Ohne ID erscheint eine Fehlermeldung (außer auf Job-Einzelseiten).</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Ohne job_id (Fehlermeldung erwartet)</h3>
<!-- /wp:heading -->

[rp_application_form]

<!-- wp:heading {"level":3} -->
<h3>Mit job_id (erste Stelle)</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><em>Die job_id wird dynamisch eingefügt wenn Jobs existieren.</em></p>
<!-- /wp:paragraph -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID"]

<!-- wp:heading {"level":3} -->
<h3>Mit custom title</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID" title="Ihre Bewerbung für diese Stelle"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Stellentitel</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID" show_job_title="false"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Formular-Container (Card-Design)</li>
<li>[ ] Input-Felder Styling</li>
<li>[ ] Label-Styling</li>
<li>[ ] Pflichtfeld-Markierung (*)</li>
<li>[ ] Datei-Upload Bereich</li>
<li>[ ] Checkbox-Styling (DSGVO)</li>
<li>[ ] Submit-Button (Primärfarbe/Custom)</li>
<li>[ ] Validierungs-Fehlermeldungen</li>
<li>[ ] Erfolgs-Meldung</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-search/">← rp_job_search</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-custom-application-form/">rp_custom_application_form →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-custom-application-form' => [
        'title'   => 'Test: rp_custom_application_form',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-application-form/">← rp_application_form</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-match/">rp_ai_job_match →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_custom_application_form] - Custom Form Builder Formular</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-pink"} -->
<p class="has-pale-pink-background-color has-background"><strong>Hinweis:</strong> Dieser Shortcode zeigt das im Form Builder konfigurierte Formular. Benötigt eine job_id.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Custom Formular mit job_id</h3>
<!-- /wp:heading -->

[rp_custom_application_form job_id="REPLACE_WITH_JOB_ID"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Formular-Container (Card-Design)</li>
<li>[ ] Multi-Step Navigation (wenn aktiviert)</li>
<li>[ ] Alle Feldtypen korrekt gestylt</li>
<li>[ ] Custom Fields aus Form Builder</li>
<li>[ ] Datei-Upload Bereich</li>
<li>[ ] Submit-Button</li>
<li>[ ] Step-Indicator (bei Multi-Step)</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-application-form/">← rp_application_form</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-match/">rp_ai_job_match →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-ai-job-match' => [
        'title'   => 'Test: rp_ai_job_match',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-custom-application-form/">← rp_custom_application_form</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_ai_job_match] - KI-Matching Button</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-cyan-blue"} -->
<p class="has-pale-cyan-blue-background-color has-background"><strong>Pro-Feature:</strong> Benötigt Pro-Lizenz und AI-Addon. Ohne Lizenz wird ein Upgrade-Hinweis angezeigt.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Standard (auf Job-Seite)</h3>
<!-- /wp:heading -->

[rp_ai_job_match]

<!-- wp:heading {"level":3} -->
<h3>Mit job_id</h3>
<!-- /wp:heading -->

[rp_ai_job_match job_id="REPLACE_WITH_JOB_ID"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung (KI-Button Tab in Design & Branding)</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Button-Stil: Theme-Modus</li>
<li>[ ] Button-Stil: Preset-Modus (Gradient, Outline, etc.)</li>
<li>[ ] Button-Stil: Manueller Modus</li>
<li>[ ] Icon wird angezeigt</li>
<li>[ ] Button-Text aus Einstellungen</li>
<li>[ ] Button-Höhe gleich wie normale Buttons</li>
<li>[ ] Hover-Effekt</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-custom-application-form/">← rp_custom_application_form</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-ai-job-finder' => [
        'title'   => 'Test: rp_ai_job_finder',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-ai-job-match/">← rp_ai_job_match</a> | <a href="/shortcode-tests/">Übersicht</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[rp_ai_job_finder] - KI-Job-Finder</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-cyan-blue"} -->
<p class="has-pale-cyan-blue-background-color has-background"><strong>Pro-Feature:</strong> Benötigt Pro-Lizenz und AI-Addon. Ohne Lizenz wird ein Upgrade-Hinweis angezeigt.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Standard</h3>
<!-- /wp:heading -->

[rp_ai_job_finder]

<!-- wp:heading {"level":3} -->
<h3>Mit custom title</h3>
<!-- /wp:heading -->

[rp_ai_job_finder title="Welcher Job passt zu dir?"]

<!-- wp:heading {"level":3} -->
<h3>Max 5 Matches</h3>
<!-- /wp:heading -->

[rp_ai_job_finder max_matches="5"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Profil-Anzeige</h3>
<!-- /wp:heading -->

[rp_ai_job_finder show_profile="false"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Upload-Container (Card-Design)</li>
<li>[ ] Datei-Upload Bereich</li>
<li>[ ] "Analysieren" Button (KI-Button Stil)</li>
<li>[ ] Lade-Animation</li>
<li>[ ] Ergebnis-Cards (Match-Score)</li>
<li>[ ] Match-Percentage Balken</li>
<li>[ ] Profil-Zusammenfassung</li>
<li>[ ] "Jetzt bewerben" Buttons</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-ai-job-match/">← rp_ai_job_match</a> | <a href="/shortcode-tests/">Übersicht</a></p>
<!-- /wp:paragraph -->',
    ],
];

/**
 * Erste Job-ID finden für Platzhalter
 */
function get_first_job_id(): int {
    $jobs = get_posts( [
        'post_type'      => 'job_listing',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ] );

    return $jobs[0] ?? 0;
}

/**
 * Testseiten löschen
 */
function delete_test_pages( array $pages ): void {
    foreach ( $pages as $slug => $page ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            wp_delete_post( $existing->ID, true );
            echo "Gelöscht: {$page['title']} (/{$slug}/)\n";
        }
    }
    echo "\nAlle Testseiten wurden gelöscht.\n";
}

/**
 * Testseiten erstellen
 */
function create_test_pages( array $pages ): void {
    $job_id = get_first_job_id();

    if ( ! $job_id ) {
        echo "⚠️  Warnung: Keine Jobs gefunden. Platzhalter 'REPLACE_WITH_JOB_ID' muss manuell ersetzt werden.\n\n";
    } else {
        echo "✅ Erste Job-ID gefunden: {$job_id}\n\n";
    }

    $created = 0;
    $updated = 0;

    foreach ( $pages as $slug => $page ) {
        $content = $page['content'];

        // Job-ID Platzhalter ersetzen
        if ( $job_id ) {
            $content = str_replace( 'REPLACE_WITH_JOB_ID', (string) $job_id, $content );
        }

        // Prüfen ob Seite existiert
        $existing = get_page_by_path( $slug );

        if ( $existing ) {
            // Update
            wp_update_post( [
                'ID'           => $existing->ID,
                'post_title'   => $page['title'],
                'post_content' => $content,
                'post_status'  => 'publish',
            ] );
            echo "Aktualisiert: {$page['title']} (/{$slug}/)\n";
            $updated++;
        } else {
            // Create
            $post_id = wp_insert_post( [
                'post_title'   => $page['title'],
                'post_name'    => $slug,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ] );

            if ( is_wp_error( $post_id ) ) {
                echo "❌ Fehler bei: {$page['title']} - {$post_id->get_error_message()}\n";
            } else {
                echo "Erstellt: {$page['title']} (/{$slug}/)\n";
                $created++;
            }
        }
    }

    echo "\n";
    echo "=================================\n";
    echo "Erstellt: {$created} | Aktualisiert: {$updated}\n";
    echo "=================================\n";
    echo "\n";
    echo "Öffne die Übersicht:\n";
    echo home_url( '/shortcode-tests/' ) . "\n";
}

// Ausführen
if ( $delete_mode ) {
    delete_test_pages( $test_pages );
} else {
    create_test_pages( $test_pages );
}
