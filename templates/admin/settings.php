<?php
/**
 * Settings page: four steps (connection, source, import, display) in one form with one save bar.
 * Actions that are not "save" post their own hidden form at the bottom through the button's form attribute,
 * because forms cannot nest.
 *
 * @var array<string, mixed> $view  See SettingsPage::view().
 * @var Notices $notices
 */

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\Notices;
use YourStoryz\Admin\PreviewPage;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Settings;

defined('ABSPATH') || exit;

$connected = $view['connected'];
$source = $view['source'];
$urls = $view['urls'];
$statusLabels = ['draft' => __('Concept', 'yourstoryz'), 'publish' => __('Gepubliceerd', 'yourstoryz')];

$companyName = '';
$departmentName = __('Hele company', 'yourstoryz');

foreach ($source['companies'] ?? [] as $company) {
    $companyName = $company['id'] === $source['company'] ? $company['name'] : $companyName;
}

foreach ($source['departments'] ?? [] as $department) {
    $departmentName = $department['id'] === $source['department'] ? $department['name'] : $departmentName;
}

$sourceDone = $companyName !== '' && $source['notice'] === null;

$steps = [
    'verbinding' => [
        __('Verbinding', 'yourstoryz'),
        $connected ? ($view['account'] !== '' ? $view['account'] : __('Token ingesteld', 'yourstoryz')) : __('Token invoeren', 'yourstoryz'),
        $connected ? 'done' : 'active',
    ],
    'bron' => [
        __('Bron', 'yourstoryz'),
        $connected ? ($sourceDone ? $companyName.' · '.$departmentName : __('Nog niet gekozen', 'yourstoryz')) : __('Na verbinden', 'yourstoryz'),
        $sourceDone ? 'done' : ($connected ? 'active' : 'todo'),
    ],
    'import' => [
        __('Import', 'yourstoryz'),
        $connected ? ($view['auto_import'] ? $view['intervals'][$view['interval']] : __('Handmatig', 'yourstoryz')).' · '.strtolower($statusLabels[$view['import_status']]) : __('Na verbinden', 'yourstoryz'),
        $connected ? 'done' : 'todo',
    ],
    'weergave' => [
        __('Weergave', 'yourstoryz'),
        $connected ? '/'.$view['slug'].'/ · '.($view['autoplay'] ? __('autoplay aan', 'yourstoryz') : __('autoplay uit', 'yourstoryz')) : __('Na verbinden', 'yourstoryz'),
        $connected ? 'done' : 'todo',
    ],
];
?>
<div class="wrap yourstoryz-settings">
    <header class="ys-card ys-header">
        <img class="ys-header__logo" src="<?php echo esc_url($urls['logo']); ?>" alt="YourStoryz" width="143" height="32">
        <h1 class="ys-header__title"><?php esc_html_e('Instellingen', 'yourstoryz'); ?></h1>
        <?php if (! $connected) { ?>
            <span class="ys-badge ys-badge--neutral"><?php esc_html_e('Nog niet verbonden', 'yourstoryz'); ?></span>
        <?php } elseif ($view['account'] !== '') { ?>
            <span class="ys-badge ys-badge--success"><?php echo esc_html(sprintf(__('Verbonden als %s', 'yourstoryz'), $view['account'])); ?></span>
        <?php } else { ?>
            <span class="ys-badge ys-badge--warning"><?php esc_html_e('Verbinding niet bevestigd', 'yourstoryz'); ?></span>
        <?php } ?>
        <nav class="ys-header__links">
            <a href="<?php echo esc_url($urls['preview']); ?>"><?php esc_html_e('Voorbeeld', 'yourstoryz'); ?></a>
            <a href="<?php echo esc_url($urls['contact']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Support', 'yourstoryz'); ?></a>
        </nav>
    </header>
    <hr class="wp-header-end">
    <?php $notices->render(); ?>

    <div class="ys-layout">
        <nav class="ys-card ys-steps" aria-label="<?php esc_attr_e('Overzicht', 'yourstoryz'); ?>">
            <p class="ys-steps__heading"><?php esc_html_e('Overzicht', 'yourstoryz'); ?></p>
            <ol>
                <?php $number = 0; ?>
                <?php foreach ($steps as $anchor => [$title, $meta, $state]) { ?>
                    <li>
                        <a class="ys-step ys-step--<?php echo esc_attr($state); ?>" href="#ys-<?php echo esc_attr($anchor); ?>">
                            <span class="ys-step__number" aria-hidden="true"><?php echo esc_html((string) ++$number); ?></span>
                            <span class="ys-step__text">
                                <span class="ys-step__title"><?php echo esc_html($title); ?></span>
                                <span class="ys-step__meta"><?php echo esc_html($meta); ?></span>
                            </span>
                        </a>
                    </li>
                <?php } ?>
            </ol>
            <?php if ($view['last_import'] !== null) { ?>
                <p class="ys-steps__footer">
                    <?php echo esc_html(sprintf(__('Laatste import: %s', 'yourstoryz'), $view['last_import']['date'])); ?><br>
                    <?php echo esc_html(sprintf(__('%1$d stories · %2$d nieuw', 'yourstoryz'), $view['last_import']['total'], $view['last_import']['created'])); ?>
                </p>
            <?php } ?>
        </nav>

        <form class="ys-sections" id="ys-settings-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(SettingsPage::ACTION_SAVE); ?>">
            <?php wp_nonce_field(SettingsPage::ACTION_SAVE); ?>

            <?php if (! $connected) { ?>
                <section class="ys-card ys-card--focus ys-onboarding" id="ys-verbinding">
                    <div class="ys-onboarding__intro">
                        <h2><?php esc_html_e('Koppel je YourStoryz-account', 'yourstoryz'); ?></h2>
                        <p><?php esc_html_e('Binnen een paar minuten staan je video-stories op deze site. Je hebt alleen een API-token nodig.', 'yourstoryz'); ?></p>
                    </div>
                    <div class="ys-onboarding__body">
                        <ol class="ys-onboarding__steps">
                            <li>
                                <strong><?php esc_html_e('Maak een account aan', 'yourstoryz'); ?></strong>
                                <span><?php esc_html_e('Op de website of in de app.', 'yourstoryz'); ?> <a href="<?php echo esc_url($urls['site']); ?>" target="_blank" rel="noopener noreferrer">yourstoryz.com</a></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Maak een API-token aan', 'yourstoryz'); ?></strong>
                                <span><?php esc_html_e('Log in op het dashboard en ga naar My Companies → Tokens → API Tokens.', 'yourstoryz'); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Plak het token hiernaast', 'yourstoryz'); ?></strong>
                                <span><?php esc_html_e('Daarna kies je welke stories je importeert.', 'yourstoryz'); ?></span>
                            </li>
                        </ol>
                        <div class="ys-onboarding__form">
                            <div class="ys-field">
                                <label class="ys-field__label" for="yourstoryz_token"><?php esc_html_e('API-token', 'yourstoryz'); ?></label>
                                <input class="ys-input" type="password" id="yourstoryz_token" name="yourstoryz_token" value="" autocomplete="off" spellcheck="false" required>
                                <p class="ys-field__hint"><?php esc_html_e('We testen de verbinding direct na het opslaan.', 'yourstoryz'); ?></p>
                            </div>
                            <button type="submit" class="button button-primary ys-button"><?php esc_html_e('Verbinden', 'yourstoryz'); ?></button>
                        </div>
                    </div>
                    <div class="ys-onboarding__footer">
                        <span><?php esc_html_e('Nog geen app?', 'yourstoryz'); ?></span>
                        <a href="<?php echo esc_url($urls['app_store']); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url($urls['app_store_badge']); ?>" alt="<?php esc_attr_e('Download in de App Store', 'yourstoryz'); ?>" height="34"></a>
                        <a href="<?php echo esc_url($urls['play_store']); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url($urls['play_store_badge']); ?>" alt="<?php esc_attr_e('Ontdek het op Google Play', 'yourstoryz'); ?>" height="34"></a>
                        <a class="ys-onboarding__help" href="<?php echo esc_url($urls['contact']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Hulp nodig? Neem contact op', 'yourstoryz'); ?></a>
                    </div>
                </section>

                <?php foreach ([['bron', __('2. Bron', 'yourstoryz'), __('Kies company en afdeling zodra je verbonden bent.', 'yourstoryz')], ['import', __('3. Import', 'yourstoryz'), __('Status, automatisch importeren en interval.', 'yourstoryz')], ['weergave', __('4. Weergave', 'yourstoryz'), __('Autoplay en url-slug.', 'yourstoryz')]] as [$anchor, $title, $description]) { ?>
                    <section class="ys-card ys-card--locked" id="ys-<?php echo esc_attr($anchor); ?>">
                        <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                        <div>
                            <h2 class="ys-card__title"><?php echo esc_html($title); ?></h2>
                            <p class="ys-card__description"><?php echo esc_html($description); ?></p>
                        </div>
                    </section>
                <?php } ?>
                <p class="ys-footnote"><?php esc_html_e('Thumbnails worden opgeslagen in de mediabibliotheek. Video\'s worden niet lokaal opgeslagen en spelen af vanaf YourStoryz.', 'yourstoryz'); ?></p>
            <?php } else { ?>
                <section class="ys-card" id="ys-verbinding">
                    <div class="ys-card__head">
                        <div>
                            <h2 class="ys-card__title"><?php esc_html_e('1. Verbinding', 'yourstoryz'); ?></h2>
                            <p class="ys-card__description"><?php esc_html_e('Het API-token koppelt deze site aan je YourStoryz-account.', 'yourstoryz'); ?></p>
                        </div>
                    </div>
                    <div class="ys-row ys-row--end">
                        <div class="ys-field ys-grow">
                            <label class="ys-field__label" for="yourstoryz_token"><?php esc_html_e('API-token', 'yourstoryz'); ?></label>
                            <input class="ys-input" type="password" id="yourstoryz_token" name="yourstoryz_token" value="" placeholder="<?php echo esc_attr($view['masked']); ?>" autocomplete="off" spellcheck="false">
                            <p class="ys-field__hint"><?php esc_html_e('Opgeslagen token. Plak een nieuw token om het te vervangen.', 'yourstoryz'); ?></p>
                        </div>
                        <button type="submit" class="button ys-button ys-button--secondary" form="ys-test-form"><?php esc_html_e('Verbinding testen', 'yourstoryz'); ?></button>
                    </div>
                    <div class="ys-row ys-card__foot">
                        <span class="ys-muted"><?php esc_html_e('Token kwijt of verlopen? Maak een nieuw token aan op het YourStoryz-dashboard.', 'yourstoryz'); ?></span>
                        <button type="submit" class="ys-link-danger" form="ys-disconnect-form" data-ys-confirm><?php esc_html_e('Verbinding verbreken', 'yourstoryz'); ?></button>
                    </div>
                </section>

                <section class="ys-card" id="ys-bron">
                    <div class="ys-card__head">
                        <div>
                            <h2 class="ys-card__title"><?php esc_html_e('2. Bron', 'yourstoryz'); ?></h2>
                            <p class="ys-card__description"><?php esc_html_e('Welke stories worden geïmporteerd.', 'yourstoryz'); ?></p>
                        </div>
                        <button type="submit" class="ys-link" form="ys-refresh-form"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('Lijsten vernieuwen', 'yourstoryz'); ?></button>
                    </div>
                    <?php if ($source['notice'] !== null) { ?>
                        <div class="notice notice-<?php echo esc_attr($source['notice']['type']); ?> inline"><p><?php echo esc_html($source['notice']['message']); ?></p></div>
                    <?php } ?>
                    <?php if ($source['companies'] !== []) { ?>
                        <div class="ys-grid-2">
                            <div class="ys-field">
                                <label class="ys-field__label" for="yourstoryz_company_id"><?php esc_html_e('Company', 'yourstoryz'); ?></label>
                                <select class="ys-input" id="yourstoryz_company_id" name="yourstoryz_company_id">
                                    <?php if ($source['company'] === 0) { ?>
                                        <option value=""><?php esc_html_e('Kies een company', 'yourstoryz'); ?></option>
                                    <?php } ?>
                                    <?php foreach ($source['companies'] as $company) { ?>
                                        <option value="<?php echo esc_attr((string) $company['id']); ?>"<?php selected($company['id'], $source['company']); ?>><?php echo esc_html($company['name']); ?></option>
                                    <?php } ?>
                                </select>
                                <p class="ys-field__hint"><?php esc_html_e('De afdelingen laden direct na het kiezen.', 'yourstoryz'); ?></p>
                            </div>
                            <div class="ys-field">
                                <label class="ys-field__label" for="yourstoryz_department_id"><?php esc_html_e('Afdeling', 'yourstoryz'); ?></label>
                                <select class="ys-input" id="yourstoryz_department_id" name="yourstoryz_department_id">
                                    <option value=""><?php esc_html_e('Hele company', 'yourstoryz'); ?></option>
                                    <?php foreach ($source['departments'] as $department) { ?>
                                        <option value="<?php echo esc_attr((string) $department['id']); ?>"<?php selected($department['id'], $source['department']); ?>><?php echo esc_html($department['name']); ?></option>
                                    <?php } ?>
                                </select>
                                <p class="ys-field__hint" data-ys-departments-hint><?php esc_html_e('Kies "Hele company" voor alle stories.', 'yourstoryz'); ?></p>
                            </div>
                        </div>
                    <?php } ?>
                </section>

                <section class="ys-card" id="ys-import">
                    <div class="ys-card__head">
                        <div>
                            <h2 class="ys-card__title"><?php esc_html_e('3. Import', 'yourstoryz'); ?></h2>
                            <p class="ys-card__description"><?php esc_html_e('Hoe en wanneer nieuwe stories binnenkomen.', 'yourstoryz'); ?></p>
                        </div>
                    </div>
                    <fieldset class="ys-field">
                        <legend class="ys-field__label"><?php esc_html_e('Status van nieuwe stories', 'yourstoryz'); ?></legend>
                        <div class="ys-segmented">
                            <?php foreach (['draft' => __('Concept, eerst nakijken', 'yourstoryz'), 'publish' => __('Direct publiceren', 'yourstoryz')] as $value => $label) { ?>
                                <label>
                                    <input type="radio" name="yourstoryz_import_status" value="<?php echo esc_attr($value); ?>"<?php checked($view['import_status'], $value); ?>>
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                            <?php } ?>
                        </div>
                        <p class="ys-field__hint"><?php esc_html_e('Als concept kun je elke story controleren voordat hij live gaat.', 'yourstoryz'); ?></p>
                    </fieldset>
                    <div class="ys-divider"></div>
                    <label class="ys-toggle">
                        <span class="ys-toggle__text">
                            <span class="ys-toggle__label"><?php esc_html_e('Automatisch importeren', 'yourstoryz'); ?></span>
                            <span class="ys-toggle__hint"><?php esc_html_e('Nieuwe stories worden op de achtergrond opgehaald.', 'yourstoryz'); ?></span>
                        </span>
                        <input type="checkbox" class="ys-toggle__input" id="yourstoryz_auto_import" name="yourstoryz_auto_import" value="1"<?php checked($view['auto_import']); ?>>
                        <span class="ys-toggle__switch" aria-hidden="true"></span>
                    </label>
                    <div class="ys-inset" data-ys-interval<?php echo $view['auto_import'] ? '' : ' hidden'; ?>>
                        <div class="ys-field">
                            <label class="ys-field__label" for="yourstoryz_import_interval"><?php esc_html_e('Hoe vaak', 'yourstoryz'); ?></label>
                            <select class="ys-input" id="yourstoryz_import_interval" name="yourstoryz_import_interval">
                                <?php foreach ($view['intervals'] as $value => $label) { ?>
                                    <option value="<?php echo esc_attr($value); ?>"<?php selected($view['interval'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php if ($view['next_import'] !== null) { ?>
                            <p class="ys-muted"><?php esc_html_e('Volgende import:', 'yourstoryz'); ?> <strong><?php echo esc_html($view['next_import']); ?></strong></p>
                        <?php } ?>
                    </div>
                    <div class="ys-row ys-card__foot">
                        <button type="submit" class="button ys-button ys-button--secondary" form="ys-import-form"><?php esc_html_e('Nu importeren', 'yourstoryz'); ?></button>
                        <span class="ys-muted"><?php esc_html_e('Haalt direct nieuwe stories op, los van het schema.', 'yourstoryz'); ?></span>
                    </div>
                </section>

                <section class="ys-card" id="ys-weergave">
                    <div class="ys-card__head">
                        <div>
                            <h2 class="ys-card__title"><?php esc_html_e('4. Weergave', 'yourstoryz'); ?></h2>
                            <p class="ys-card__description"><?php esc_html_e('Hoe stories op je site verschijnen.', 'yourstoryz'); ?></p>
                        </div>
                    </div>
                    <label class="ys-toggle">
                        <span class="ys-toggle__text">
                            <span class="ys-toggle__label"><?php esc_html_e('Video automatisch afspelen', 'yourstoryz'); ?></span>
                            <span class="ys-toggle__hint"><?php esc_html_e('Video\'s starten zonder geluid zodra de story in beeld komt.', 'yourstoryz'); ?></span>
                        </span>
                        <input type="checkbox" class="ys-toggle__input" id="yourstoryz_autoplay" name="yourstoryz_autoplay" value="1"<?php checked($view['autoplay']); ?>>
                        <span class="ys-toggle__switch" aria-hidden="true"></span>
                    </label>
                    <div class="ys-divider"></div>
                    <div class="ys-field">
                        <label class="ys-field__label" for="yourstoryz_slug"><?php esc_html_e('Url-slug', 'yourstoryz'); ?></label>
                        <div class="ys-affix">
                            <span class="ys-affix__prefix"><?php echo esc_html(preg_replace('#^https?://#', '', $view['home_url'])); ?></span>
                            <input class="ys-affix__input" type="text" id="yourstoryz_slug" name="yourstoryz_slug" value="<?php echo esc_attr($view['slug']); ?>" placeholder="<?php echo esc_attr(Settings::DEFAULT_SLUG); ?>" spellcheck="false">
                            <span class="ys-affix__suffix">/</span>
                        </div>
                        <p class="ys-field__hint">
                            <?php esc_html_e('Stories staan op', 'yourstoryz'); ?>
                            <a href="<?php echo esc_url($view['home_url'].$view['slug'].'/'); ?>" target="_blank" rel="noopener noreferrer" data-ys-slug-preview data-ys-home="<?php echo esc_attr($view['home_url']); ?>"><?php echo esc_html($view['home_url'].$view['slug'].'/'); ?></a>.
                            <?php esc_html_e('Links naar de oude slug werken niet meer na wijzigen.', 'yourstoryz'); ?>
                        </p>
                    </div>
                </section>
            <?php } ?>

            <?php if ($connected) { ?>
                <div class="ys-savebar">
                    <span class="ys-badge ys-badge--warning ys-savebar__dirty"><?php esc_html_e('Niet-opgeslagen wijzigingen', 'yourstoryz'); ?></span>
                    <a class="ys-savebar__cancel" href="<?php echo esc_url(add_query_arg('page', Menu::SLUG, admin_url('admin.php'))); ?>"><?php esc_html_e('Annuleren', 'yourstoryz'); ?></a>
                    <button type="submit" class="button button-primary ys-button"><?php esc_html_e('Wijzigingen opslaan', 'yourstoryz'); ?></button>
                </div>
            <?php } ?>
        </form>
    </div>

    <?php if ($connected) { ?>
        <div hidden>
            <?php foreach (['ys-test-form' => SettingsPage::ACTION_TEST, 'ys-disconnect-form' => SettingsPage::ACTION_DISCONNECT, 'ys-refresh-form' => SettingsPage::ACTION_REFRESH, 'ys-import-form' => PreviewPage::ACTION_IMPORT] as $formId => $action) { ?>
                <form id="<?php echo esc_attr($formId); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
                    <input type="hidden" name="yourstoryz_return" value="<?php echo esc_attr(Menu::SLUG); ?>">
                    <?php wp_nonce_field($action, '_wpnonce', true, true); ?>
                </form>
            <?php } ?>
        </div>
    <?php } ?>
</div>
