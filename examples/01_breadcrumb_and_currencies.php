<?php

declare(strict_types=1);

/**
 * Example: Breadcrumb and currency formatting in Zen Cart.
 *
 * These are two of the most commonly used utility classes in Zen Cart.
 * In production both are instantiated during application_top.php.
 *
 * Note: These classes require the full Zen Cart bootstrap (IS_ADMIN_FLAG,
 * global $db, TABLE_CURRENCIES, etc.) to function. This file is illustrative.
 */

// --- Breadcrumb ---

// In production: global $breadcrumb is available after bootstrap.
// require_once DIR_FS_CATALOG . 'includes/classes/breadcrumb.php';

$breadcrumb = new breadcrumb();

// Build a typical product-page trail
$breadcrumb->add(HEADER_TITLE_CATALOG, zen_href_link(FILENAME_DEFAULT));
$breadcrumb->add('Clothing', zen_href_link(FILENAME_DEFAULT, 'cPath=10'));
$breadcrumb->add('T-Shirts',  zen_href_link(FILENAME_DEFAULT, 'cPath=10_12'));
$breadcrumb->add('Classic White Tee'); // current page — no link

// Get the number of items
echo 'Trail depth: ' . $breadcrumb->count() . "\n"; // 4

// Render as HTML (the last item is shown without a link by default when
// DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM is 'true')
echo $breadcrumb->trail(' &raquo; ');

// Replace the last entry (useful when a module adjusts the page title)
$breadcrumb->replace_last('Classic White Tee — Size M');

// Check if trail is non-empty
if (!$breadcrumb->is_empty()) {
    $lastTitle = $breadcrumb->last();
    echo "Current page: {$lastTitle}\n";
}

// --- Currency formatting ---

// In production: global $currencies is available after bootstrap.
// require_once DIR_FS_CATALOG . 'includes/classes/currencies.php';

/** @var currencies $currencies */
$currencies = new currencies(); // loads from DB

// Format a price in the session currency
echo $currencies->format(29.99) . "\n"; // e.g. "$29.99" or "€27.50"

// Format with explicit exchange rate
echo $currencies->format(29.99, true, 'EUR') . "\n";

// Get the raw rate-adjusted value (without formatting)
$adjusted = $currencies->rate_adjusted(29.99, true, 'GBP');
echo "Adjusted: {$adjusted}\n";

// Check if a currency is available
if ($currencies->is_set('JPY')) {
    echo 'JPY exchange rate: ' . $currencies->get_value('JPY') . "\n";
}

// Display a product price including tax and quantity
echo $currencies->display_price(24.99, 20.0, 3) . "\n"; // 3 × $24.99 + 20% tax
