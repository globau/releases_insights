<?php

declare(strict_types=1);

use ReleaseInsights\ESR;
use ReleaseInsights\Version;

$requested_version = Version::get();

if ($requested_version == '0.0') {
    header('Location: /404/');
    exit;
}

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';
$release_owners    = include DATA .'release_owners.php';

$template_data = [
        'css_files'             => ['base.css'],
        'css_page_id'           => 'release',
        'page_title'            => 'Milestones and key data for Firefox ' . (int) $requested_version,
        'release'               => (int) $requested_version,
        'release_owner'         => $release_owners[$requested_version] ?? 'TBD',
        'fallback_content'      => '',
];

// Releases before version 4 were handled completely differently
if ((int) $requested_version < 4) {
    require_once MODELS . 'pre4_release.php';
    $template_file = 'pre4_release.html.twig';
    $template_data += ['dot_release_count' => $dot_release_count];
    print $twig->render($template_file, $template_data);
    return;
}

$template_data = array_merge($template_data, [
        'ESR'          => ReleaseInsights\ESR::getVersion((int) $requested_version),
        'PREVIOUS_ESR' => ReleaseInsights\ESR::getOlderSupportedVersion((int) $requested_version),
]);

// If this is a release we already shipped, display stats for the release
if ((int) $requested_version <= (int) FIREFOX_RELEASE) {
    require_once MODELS . 'past_release.php';
    $template_file = 'past_release.html.twig';
    $template_data = array_merge($template_data, [
        'release_date'          => $last_release_date,
        'previous_release_date' => $previous_release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'nightly_fixes'         => $nightly_fixes,
        'beta_changelog'        => $beta_changelog,
        'beta_uplifts'          => $beta_uplifts,
        'rc_uplifts'            => $rc_uplifts,
        'rc_changelog'          => $rc_changelog,
        'rc_uplifts_url'        => $rc_uplifts_url,
        'rc_backouts_url'       => $rc_backouts_url,
        'beta_uplifts_url'      => $beta_uplifts_url,
        'beta_backouts_url'     => $beta_backouts_url,
        'rc_count'              => $rc_count,
        'beta_count'            => $beta_count,
        'dot_release_count'     => $dot_release_count,
        ]);
} elseif ((int) $requested_version > (int) FIREFOX_RELEASE
    && array_key_exists($requested_version, $upcoming_releases)) {
    require_once MODELS . 'future_release.php';
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'release_date'          => $release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'nightly_fixes'         => $nightly_fixes,
        'nightly_updates'       => $nightly_updates,
        'cycle_dates'           => $cycle_dates,
    ]);
} else {
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'page_title'   => 'No information yet for this release',
        'fallback_content' => '<p class="alert alert-warning text-center w-50 mx-auto">The release date for this version is not yet available.</p>',
    ]);
}

print $twig->render($template_file, $template_data);
