<?php
/**
 * Job-Finder Template
 *
 * Template für den KI-Job-Finder (Mode B).
 * Ermöglicht Multi-Job-Matching gegen alle aktiven Stellen.
 *
 * @var array  $atts         Shortcode Attribute
 * @var int    $job_count    Anzahl aktiver Jobs
 * @var bool   $show_profile Profil anzeigen
 * @var bool   $show_skills  Skills anzeigen
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="rp-plugin">
    <div class="rp-job-finder" x-data="jobFinder" x-cloak>

        <!-- Header -->
        <div class="rp-job-finder__header">
            <h2 class="rp-job-finder__title">
                <?php echo esc_html( $atts['title'] ); ?>
            </h2>
            <p class="rp-job-finder__subtitle">
                <?php echo esc_html( $atts['subtitle'] ); ?>
            </p>
            <p class="rp-job-finder__job-count">
                <?php
                printf(
                    esc_html(
                        _n(
                            '%d offene Stelle wird analysiert',
                            '%d offene Stellen werden analysiert',
                            $job_count,
                            'recruiting-playbook'
                        )
                    ),
                    $job_count
                );
                ?>
            </p>
        </div>

        <!-- ===== STATUS: IDLE (Upload) ===== -->
        <template x-if="status === 'idle'">
            <div>
                <!-- Datenschutz-Hinweis -->
                <div class="rp-match-info-box rp-mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <div>
                        <strong><?php esc_html_e( 'Datenschutz', 'recruiting-playbook' ); ?></strong>
                        <p class="rp-text-sm rp-text-gray-600 rp-mt-1">
                            <?php esc_html_e( 'Ihre persönlichen Daten werden automatisch entfernt. Nur Ihre beruflichen Qualifikationen werden analysiert.', 'recruiting-playbook' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Upload Zone -->
                <div
                    class="rp-match-upload-zone"
                    :class="{ 'rp-match-upload-zone--dragging': isDragging }"
                    @dragover.prevent="handleDragOver"
                    @dragleave.prevent="handleDragLeave"
                    @drop.prevent="handleDrop"
                    @click="$refs.fileInput.click()"
                >
                    <template x-if="!file">
                        <div>
                            <svg class="rp-w-12 rp-h-12 rp-mx-auto rp-text-gray-400 rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="rp-text-gray-600 rp-font-medium">
                                <?php esc_html_e( 'Lebenslauf hier ablegen', 'recruiting-playbook' ); ?>
                            </p>
                            <p class="rp-text-sm rp-text-gray-500 rp-mt-1">
                                <?php esc_html_e( 'oder klicken zum Auswählen', 'recruiting-playbook' ); ?>
                            </p>
                            <p class="rp-text-xs rp-text-gray-400 rp-mt-2">
                                PDF, JPG, PNG, DOCX (max. 10 MB)
                            </p>
                        </div>
                    </template>

                    <template x-if="file">
                        <div class="rp-flex rp-items-center rp-justify-center rp-gap-3">
                            <svg class="rp-w-8 rp-h-8 rp-text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="rp-text-left">
                                <p class="rp-font-medium rp-text-gray-900" x-text="fileName"></p>
                                <p class="rp-text-sm rp-text-gray-500" x-text="fileSize"></p>
                            </div>
                            <button
                                type="button"
                                class="rp-ml-2 rp-text-gray-400 hover:rp-text-red-500"
                                @click.stop="removeFile()"
                            >
                                <svg class="rp-w-5 rp-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>

                    <input
                        type="file"
                        x-ref="fileInput"
                        class="rp-hidden"
                        accept=".pdf,.jpg,.jpeg,.png,.docx"
                        @change="handleFileSelect"
                    >
                </div>

                <!-- Submit Button -->
                <button
                    type="button"
                    class="rp-job-finder__submit"
                    :disabled="!file"
                    @click="startAnalysis()"
                >
                    <?php esc_html_e( 'Passende Jobs finden', 'recruiting-playbook' ); ?>
                </button>
            </div>
        </template>

        <!-- ===== STATUS: PROCESSING ===== -->
        <template x-if="status === 'uploading' || status === 'processing'">
            <div class="rp-job-finder__processing">
                <div class="rp-job-finder__spinner"></div>
                <p class="rp-text-gray-600 rp-font-medium" x-text="statusMessage"></p>
                <div class="rp-job-finder__progress">
                    <div class="rp-job-finder__progress-bar" :style="'width: ' + progress + '%'"></div>
                </div>
                <p class="rp-text-sm rp-text-gray-500 rp-mt-2">
                    <?php
                    printf(
                        esc_html__( 'Analysiere %d Stellen...', 'recruiting-playbook' ),
                        $job_count
                    );
                    ?>
                </p>
            </div>
        </template>

        <!-- ===== STATUS: COMPLETED (mit Ergebnissen) ===== -->
        <template x-if="hasResults">
            <div class="rp-job-finder-results">

                <!-- Profil-Zusammenfassung -->
                <?php if ( $show_profile ) : ?>
                <template x-if="result.profile">
                    <div class="rp-job-finder-profile">
                        <h3 class="rp-job-finder-profile__title">
                            <?php esc_html_e( 'Erkanntes Profil', 'recruiting-playbook' ); ?>
                        </h3>
                        <div class="rp-job-finder-profile__skills">
                            <template x-for="skill in result.profile.extractedSkills" :key="skill">
                                <span class="rp-job-finder-profile__skill" x-text="skill"></span>
                            </template>
                        </div>
                        <template x-if="result.profile.experienceYears">
                            <p class="rp-text-sm rp-text-gray-600 rp-mt-2">
                                <span x-text="result.profile.experienceYears"></span>
                                <?php esc_html_e( 'Jahre Berufserfahrung', 'recruiting-playbook' ); ?>
                            </p>
                        </template>
                    </div>
                </template>
                <?php endif; ?>

                <!-- Results Header -->
                <div class="rp-job-finder-results__header">
                    <h3 class="rp-job-finder-results__title">
                        <?php esc_html_e( 'Deine Top-Matches', 'recruiting-playbook' ); ?>
                    </h3>
                    <span class="rp-job-finder-results__count">
                        <span x-text="result.matches.length"></span> von
                        <span x-text="result.totalJobsAnalyzed"></span> Stellen
                    </span>
                </div>

                <!-- Match Cards -->
                <div class="rp-job-finder-matches">
                    <template x-for="(match, index) in result.matches" :key="match.jobId">
                        <div class="rp-job-finder-match" :class="getCategoryClass(match.category)">

                            <!-- Header -->
                            <div class="rp-job-finder-match__header">
                                <div>
                                    <h4 class="rp-job-finder-match__title" x-text="match.jobTitle"></h4>
                                    <span class="rp-job-finder-match__category" x-text="getCategoryLabel(match.category)"></span>
                                </div>
                                <div class="rp-job-finder-match__score" :class="getScoreClass(match.category)">
                                    <span x-text="match.score + '%'"></span>
                                </div>
                            </div>

                            <!-- Message -->
                            <p class="rp-job-finder-match__message" x-text="match.message"></p>

                            <!-- Skills -->
                            <?php if ( $show_skills ) : ?>
                            <div class="rp-job-finder-match__skills">
                                <template x-for="skill in match.matchedSkills.slice(0, 5)" :key="'matched-' + skill">
                                    <span class="rp-job-finder-match__skill rp-job-finder-match__skill--matched" x-text="skill"></span>
                                </template>
                                <template x-for="skill in match.missingSkills.slice(0, 3)" :key="'missing-' + skill">
                                    <span class="rp-job-finder-match__skill rp-job-finder-match__skill--missing" x-text="skill"></span>
                                </template>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="rp-job-finder-match__actions">
                                <a
                                    :href="match.jobUrl"
                                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                                >
                                    <?php esc_html_e( 'Stelle ansehen', 'recruiting-playbook' ); ?>
                                </a>
                                <a
                                    :href="match.applyUrl"
                                    class="rp-job-finder-match__btn rp-job-finder-match__btn--primary"
                                >
                                    <?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Reset -->
                <div class="rp-job-finder__reset">
                    <button
                        type="button"
                        class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                        @click="reset()"
                    >
                        <?php esc_html_e( 'Neue Analyse starten', 'recruiting-playbook' ); ?>
                    </button>
                </div>
            </div>
        </template>

        <!-- ===== STATUS: NO MATCHES ===== -->
        <template x-if="noMatches">
            <div class="rp-job-finder__no-matches">
                <svg class="rp-job-finder__no-matches-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <p class="rp-job-finder__no-matches-text">
                    <?php esc_html_e( 'Leider haben wir keine passenden Stellen gefunden.', 'recruiting-playbook' ); ?>
                </p>
                <a
                    href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>"
                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                >
                    <?php esc_html_e( 'Alle Stellen ansehen', 'recruiting-playbook' ); ?>
                </a>
            </div>
        </template>

        <!-- ===== STATUS: ERROR ===== -->
        <template x-if="status === 'error'">
            <div class="rp-job-finder__error">
                <p class="rp-job-finder__error-text" x-text="error"></p>
                <button
                    type="button"
                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary rp-mt-4"
                    @click="reset()"
                >
                    <?php esc_html_e( 'Erneut versuchen', 'recruiting-playbook' ); ?>
                </button>
            </div>
        </template>

    </div>
</div>
