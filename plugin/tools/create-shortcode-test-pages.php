<?php
/**
 * Shortcode Test Pages Creator
 *
 * Erstellt Testseiten für alle öffentlichen Shortcodes mit Navigation.
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
<p>Diese Seiten dienen zum Testen aller öffentlichen Shortcodes und ihrer Kompatibilität mit den Design & Branding Einstellungen.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Job-Listen Shortcodes</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><a href="/shortcode-test-rp-jobs/">rp_jobs</a> - Job-Liste mit Grid</li>
<li><a href="/shortcode-test-rp-job-search/">rp_job_search</a> - Suchformular mit Filtern</li>
<li><a href="/shortcode-test-rp-job-count/">rp_job_count</a> - Stellen-Zähler</li>
<li><a href="/shortcode-test-rp-featured-jobs/">rp_featured_jobs</a> - Hervorgehobene Stellen</li>
<li><a href="/shortcode-test-rp-latest-jobs/">rp_latest_jobs</a> - Neueste Stellen</li>
<li><a href="/shortcode-test-rp-job-categories/">rp_job_categories</a> - Kategorie-Übersicht</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>Bewerbungsformular</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><a href="/shortcode-test-rp-application-form/">rp_application_form</a> - Bewerbungsformular (Auto-Detection Form Builder)</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>KI-Features (AI-Addon)</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder</a> - KI-Job-Finder</li>
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
<h2>[[rp_jobs]] - Job-Liste</h2>
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
<h3>Nur Featured Jobs</h3>
<!-- /wp:heading -->

[rp_jobs limit="3" featured="true"]

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
<li>[ ] Link-Farbe</li>
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
<p><a href="/shortcode-test-rp-jobs/">← rp_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-job-count/">rp_job_count →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_job_search]] - Suchformular</h2>
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
<h3>Nur Suchfeld (keine Dropdowns)</h3>
<!-- /wp:heading -->

[rp_job_search show_category="false" show_location="false" show_type="false"]

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
<li>[ ] Ergebnis-Cards</li>
<li>[ ] "Keine Ergebnisse" Meldung</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-jobs/">← rp_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-job-count/">rp_job_count →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-job-count' => [
        'title'   => 'Test: rp_job_count',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-search/">← rp_job_search</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-featured-jobs/">rp_featured_jobs →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_job_count]] - Stellen-Zähler</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Standard</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Aktuell: [rp_job_count]</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>In einer Überschrift</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":2} -->
<h2>Wir haben [rp_job_count] für Sie!</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Mit eigenem Format</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ergebnis: [rp_job_count format="Entdecken Sie {count} Karrieremöglichkeiten"]</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Mit Singular-Form</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ergebnis: [rp_job_count singular="{count} offene Stelle" format="{count} offene Stellen"]</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Mit Zero-Text</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ergebnis: [rp_job_count zero="Aktuell keine offenen Stellen - schauen Sie bald wieder vorbei!"]</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Gefiltert nach Kategorie</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>IT-Stellen: [rp_job_count category="it"]</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Text erbt Farbe vom Kontext</li>
<li>[ ] Zero-State hat gedämpfte Farbe</li>
<li>[ ] Font-Weight ist bold</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-search/">← rp_job_search</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-featured-jobs/">rp_featured_jobs →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-featured-jobs' => [
        'title'   => 'Test: rp_featured_jobs',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-count/">← rp_job_count</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-latest-jobs/">rp_latest_jobs →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_featured_jobs]] - Hervorgehobene Stellen</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-pink"} -->
<p class="has-pale-pink-background-color has-background"><strong>Hinweis:</strong> Stellen müssen im Job-Editor als "Featured" markiert werden (Meta-Feld _rp_featured).</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Standard (3 Stellen, 3 Spalten)</h3>
<!-- /wp:heading -->

[rp_featured_jobs]

<!-- wp:heading {"level":3} -->
<h3>Mit Überschrift</h3>
<!-- /wp:heading -->

[rp_featured_jobs title="Unsere Top-Stellenangebote"]

<!-- wp:heading {"level":3} -->
<h3>4 Stellen, 2 Spalten</h3>
<!-- /wp:heading -->

[rp_featured_jobs limit="4" columns="2"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Auszug</h3>
<!-- /wp:heading -->

[rp_featured_jobs show_excerpt="false"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Titel-Styling</li>
<li>[ ] Card-Design (wie rp_jobs)</li>
<li>[ ] Grid-Layout</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-count/">← rp_job_count</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-latest-jobs/">rp_latest_jobs →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-latest-jobs' => [
        'title'   => 'Test: rp_latest_jobs',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-featured-jobs/">← rp_featured_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-job-categories/">rp_job_categories →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_latest_jobs]] - Neueste Stellen</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Standard (5 Stellen als Liste)</h3>
<!-- /wp:heading -->

[rp_latest_jobs]

<!-- wp:heading {"level":3} -->
<h3>Mit Überschrift</h3>
<!-- /wp:heading -->

[rp_latest_jobs title="Neu bei uns"]

<!-- wp:heading {"level":3} -->
<h3>3 Stellen, 3 Spalten</h3>
<!-- /wp:heading -->

[rp_latest_jobs limit="3" columns="3"]

<!-- wp:heading {"level":3} -->
<h3>Mit Auszug</h3>
<!-- /wp:heading -->

[rp_latest_jobs limit="3" show_excerpt="true"]

<!-- wp:heading {"level":3} -->
<h3>Gefiltert nach Kategorie</h3>
<!-- /wp:heading -->

[rp_latest_jobs category="it" limit="3"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Titel-Styling</li>
<li>[ ] Card-Design</li>
<li>[ ] Datum-Anzeige</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-featured-jobs/">← rp_featured_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-job-categories/">rp_job_categories →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-job-categories' => [
        'title'   => 'Test: rp_job_categories',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-latest-jobs/">← rp_latest_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-application-form/">rp_application_form →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_job_categories]] - Kategorie-Übersicht</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Standard (4 Spalten)</h3>
<!-- /wp:heading -->

[rp_job_categories]

<!-- wp:heading {"level":3} -->
<h3>3 Spalten</h3>
<!-- /wp:heading -->

[rp_job_categories columns="3"]

<!-- wp:heading {"level":3} -->
<h3>2 Spalten</h3>
<!-- /wp:heading -->

[rp_job_categories columns="2"]

<!-- wp:heading {"level":3} -->
<h3>Nach Anzahl sortiert</h3>
<!-- /wp:heading -->

[rp_job_categories orderby="count"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Anzahl-Badge</h3>
<!-- /wp:heading -->

[rp_job_categories show_count="false"]

<!-- wp:heading {"level":3} -->
<h3>Mit leeren Kategorien</h3>
<!-- /wp:heading -->

[rp_job_categories hide_empty="false"]

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Design-Prüfung</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>[ ] Card-Styling</li>
<li>[ ] Hover-Effekt (Border-Farbe)</li>
<li>[ ] Badge-Styling (Anzahl)</li>
<li>[ ] Grid-Layout responsive</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-latest-jobs/">← rp_latest_jobs</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-application-form/">rp_application_form →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-application-form' => [
        'title'   => 'Test: rp_application_form',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-categories/">← rp_job_categories</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder →</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_application_form]] - Bewerbungsformular</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-cyan-blue"} -->
<p class="has-pale-cyan-blue-background-color has-background"><strong>Auto-Detection:</strong> Erkennt automatisch ob der Form Builder (Pro) aktiv ist und verwendet dann das konfigurierte Multi-Step Formular.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Ohne job_id (Fehlermeldung erwartet)</h3>
<!-- /wp:heading -->

[rp_application_form]

<!-- wp:heading {"level":3} -->
<h3>Mit job_id</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID"]

<!-- wp:heading {"level":3} -->
<h3>Mit custom title</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID" title="Ihre Bewerbung für diese Stelle"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Stellentitel</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID" show_job_title="false"]

<!-- wp:heading {"level":3} -->
<h3>Ohne Fortschrittsanzeige</h3>
<!-- /wp:heading -->

[rp_application_form job_id="REPLACE_WITH_JOB_ID" show_progress="false"]

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
<li>[ ] Fortschrittsbalken (Primärfarbe)</li>
<li>[ ] Submit-Button (Primärfarbe/Custom)</li>
<li>[ ] Validierungs-Fehlermeldungen</li>
<li>[ ] Erfolgs-Meldung</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-job-categories/">← rp_job_categories</a> | <a href="/shortcode-tests/">Übersicht</a> | <a href="/shortcode-test-rp-ai-job-finder/">rp_ai_job_finder →</a></p>
<!-- /wp:paragraph -->',
    ],

    'shortcode-test-rp-ai-job-finder' => [
        'title'   => 'Test: rp_ai_job_finder',
        'content' => '<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-application-form/">← rp_application_form</a> | <a href="/shortcode-tests/">Übersicht</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>[[rp_ai_job_finder]] - KI-Job-Finder</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"backgroundColor":"pale-cyan-blue"} -->
<p class="has-pale-cyan-blue-background-color has-background"><strong>AI-Addon Feature:</strong> Benötigt AI-Addon Lizenz. Ohne Lizenz wird ein Upgrade-Hinweis angezeigt.</p>
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
<h3>Max 3 Vorschläge</h3>
<!-- /wp:heading -->

[rp_ai_job_finder limit="3"]

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
<li>[ ] "Jetzt bewerben" Buttons</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="/shortcode-test-rp-application-form/">← rp_application_form</a> | <a href="/shortcode-tests/">Übersicht</a></p>
<!-- /wp:paragraph -->',
    ],
];

/**
 * Alte Testseiten die gelöscht werden sollen
 */
$old_pages_to_delete = [
    'shortcode-test-rp-custom-application-form',
    'shortcode-test-rp-ai-job-match',
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
function delete_test_pages( array $pages, array $old_pages = [] ): void {
    $all_slugs = array_merge( array_keys( $pages ), $old_pages );

    foreach ( $all_slugs as $slug ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            wp_delete_post( $existing->ID, true );
            echo "Gelöscht: /{$slug}/\n";
        }
    }
    echo "\nAlle Testseiten wurden gelöscht.\n";
}

/**
 * Testseiten erstellen
 */
function create_test_pages( array $pages, array $old_pages = [] ): void {
    // Zuerst alte Seiten löschen
    foreach ( $old_pages as $slug ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            wp_delete_post( $existing->ID, true );
            echo "Alte Seite gelöscht: /{$slug}/\n";
        }
    }

    $job_id = get_first_job_id();

    if ( ! $job_id ) {
        echo "\n⚠️  Warnung: Keine Jobs gefunden. Platzhalter 'REPLACE_WITH_JOB_ID' muss manuell ersetzt werden.\n\n";
    } else {
        echo "\n✅ Erste Job-ID gefunden: {$job_id}\n\n";
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
    delete_test_pages( $test_pages, $old_pages_to_delete );
} else {
    create_test_pages( $test_pages, $old_pages_to_delete );
}
