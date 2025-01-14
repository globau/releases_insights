<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

// Historical data from Product Details
$firefox_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];

// Number of dot releases
$dot_release_count = count((array) array_filter(
    $firefox_releases,
    fn($key) => str_starts_with($key, 'firefox-' . $requested_version . '.'),
    ARRAY_FILTER_USE_KEY
));
