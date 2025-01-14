<?php

declare(strict_types=1);

use ReleaseInsights\Utils;
use ReleaseInsights\Version;

// Get the schedule for the current nightly
$requested_version = Version::get(FIREFOX_NIGHTLY);
$nightly_cycle_dates = include MODELS . 'api/release_schedule.php';

// Get the schedule for the current beta
$requested_version = Version::get(FIREFOX_BETA);
$beta_cycle_dates = include MODELS . 'api/release_schedule.php';

// Historical data from Product Details, cache an hour
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 3600);
$upcoming_releases = include DATA .'upcoming_releases.php';

$all_releases = array_merge($shipped_releases, $upcoming_releases);

$today_is_release_day = false;

$today = date('Y-m-d');

if (in_array($today, $all_releases)) {
    $today_is_release_day = true;
}

$aus_url = 'https://aus-api.mozilla.org/api/v1/';

// Calculation of rc_week interval
$is_rc_week = false;
$today = new DateTime();
$rc_week_start = new DateTime($beta_cycle_dates['rc_gtb']);
$rc_week_end = new DateTime($nightly_cycle_dates['merge_day']);
$rc_build = FIREFOX_BETA;

if ((int) FIREFOX_BETA !== (int) FIREFOX_RELEASE) {
    if (Utils::isDateBetweenDates($today, $rc_week_start, $rc_week_end)) {
        $is_rc_week = true;
        // Check if we have already shipped a Release Candidate build to the beta channel
        $rc_build = Utils::getJson($aus_url . 'rules/firefox-beta', 3600)['mapping'];
        $rc_build = explode('-', $rc_build)[1];
        $rc_build = str_contains($rc_build, 'b') ? FIREFOX_BETA : $main_beta . ' RC';
    }
    if ($today_is_release_day) {
        $is_rc_week = false;
    }
}

// Get the latest nightly build ID, used as a tooltip on the nightly version number
$latest_nightly = Utils::getJson(
    $aus_url . 'releases/Firefox-mozilla-central-nightly-latest',
    3600
);

$latest_nightly = $latest_nightly['platforms']['WINNT_x86_64-msvc']['locales']['en-US']['buildID'];
