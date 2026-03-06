<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

/**
 * @since ZC v1.5.6
 */
function zen_get_banner_data_recent($banner_id, $days): array
{
    global $db;
    $set1 = $set2 = $stats = [];

    $result = $db->Execute('select dayofmonth(banners_history_date) as source,
                                       banners_shown as impressions, banners_clicked as clicks
                     from ' . TABLE_BANNERS_HISTORY . "
                     where banners_id = '" . (int)$banner_id . "'
                     and to_days(now()) - to_days(banners_history_date) < " . zen_db_input($days) . '
                     order by banners_history_date');

    while (!$result->EOF) {
        $set1[] = [$result->fields['source'], $result->fields['impressions']];
        $set2[] = [$result->fields['source'], $result->fields['clicks']];
        $stats[] = [$result->fields['source'], $result->fields['impressions'], $result->fields['clicks']];
        $result->MoveNext();
    }
    if (sizeof($set1) < 1) {
        $set1 = $set2 = [[date('j'), 0]];
    }

    return [$set1, $set2, $stats];
}

/**
 * @since ZC v1.5.6
 */
function zen_get_banner_data_yearly($banner_id): array
{
    global $db;
    $set1 = $set2 = [[0, 0]];
    $years = [0 => ''];
    $stats = [];
    $result = $db->Execute('select year(banners_history_date) as year,
                                       sum(banners_shown) as impressions, sum(banners_clicked) as clicks
                     from ' . TABLE_BANNERS_HISTORY . "
                     where banners_id = '" . (int)$banner_id . "'
                     group by year order by year");

    while (!$result->EOF) {
        $set1[] = [(int)$result->fields['year'], (int)$result->fields['impressions']];
        $set2[] = [(int)$result->fields['year'], (int)$result->fields['clicks']];
        $stats[] = [$result->fields['year'], $result->fields['impressions'], $result->fields['clicks']];
        $years[] = (string)$result->fields['year'];
        $result->MoveNext();
    }

    return [$set1, $set2, $stats, $years];
}

/**
 * @since ZC v1.5.6
 */
function zen_get_banner_data_monthly($banner_id, $year = ''): array
{
    global $db, $zcDate;
    if ((int)$year == 0) {
        $year = date('Y');
    }
    $set1 = $set2 = $stats = $months = [];
    for ($i = 1; $i < 13; $i++) {
        $m = $zcDate->output('%b', mktime(0, 0, 0, $i, 1));
        $months[] = [$i, $m];
        $set1[] = $set2[] = $stats[] = [$i, 0];
    }

    $result = $db->Execute('select month(banners_history_date) as banner_month, sum(banners_shown) as impressions,
                                sum(banners_clicked) as clicks
                  from ' . TABLE_BANNERS_HISTORY . "
                  where banners_id = '" . (int)$banner_id . "'
                  and year(banners_history_date) = '" . zen_db_input($year) . "'
                  group by banner_month order by banner_month");

    while (!$result->EOF) {
        $set1[($result->fields['banner_month'] - 1)] = [(int)$result->fields['banner_month'], (int)$result->fields['impressions']];
        $set2[($result->fields['banner_month'] - 1)] = [(int)$result->fields['banner_month'], (int)$result->fields['clicks']];
        $stats[($result->fields['banner_month'] - 1)] = [(int)$result->fields['banner_month'], (int)$result->fields['impressions'], (int)$result->fields['clicks']];
        $result->MoveNext();
    }

    return [$set1, $set2, $stats, $months];
}

/**
 * @since ZC v1.5.6
 */
function zen_get_banner_data_daily($banner_id, $year = '', $month = ''): array
{
    global $db;
    if ((int)$year == 0) {
        $year = date('Y');
    }
    if ((int)$month == 0) {
        $month = date('n');
    }

    $set1 = $set2 = [];

    $days = (date('t', mktime(0, 0, 0, $month)) + 1);
    for ($i = 1; $i < $days; $i++) {
        $set1[] = $set2[] = $stats[] = [$i, 0];
    }

    $result = $db->Execute('select dayofmonth(banners_history_date) as banner_day,
                                       banners_shown as impressions, banners_clicked as clicks
                     from ' . TABLE_BANNERS_HISTORY . "
                     where banners_id = '" . (int)$banner_id . "'
                     and month(banners_history_date) = '" . zen_db_input($month) . "'
                     and year(banners_history_date) = '" . zen_db_input($year) . "' order by banner_day");

    while (!$result->EOF) {
        $set1[($result->fields['banner_day'] - 1)] = [(int)$result->fields['banner_day'], (int)$result->fields['impressions']];
        $set2[($result->fields['banner_day'] - 1)] = [(int)$result->fields['banner_day'], (int)$result->fields['clicks']];
        $stats[($result->fields['banner_day'] - 1)] = [(int)$result->fields['banner_day'], (int)$result->fields['impressions'], (int)$result->fields['clicks']];
        $result->MoveNext();
    }

    return [$set1, $set2, $stats];
}
