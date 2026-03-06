<?php

declare(strict_types=1);
/**
 * database functions and aliases into the $db queryFactory class
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

/**
 * Alias to $db->insert_ID() to get id of last inserted record
 * @return int
 * @since ZC v1.0.3
 */
function zen_db_insert_id()
{
    global $db;
    return $db->insert_ID();
}

/**
 * Alias to $db->prepare_input() for sanitizing db inserts
 * @param string $string
 * @return string
 * @since ZC v1.0.3
 */
function zen_db_input($string)
{
    global $db;
    return (empty($string) ? $string : $db->prepare_input($string));
}

/**
 * @deprecated use zen_output_string_protected() instead
 * @since ZC v1.0.3
 */
function zen_db_output(string $string): string
{
    trigger_error('Call to deprecated function zen_db_output. Use zen_output_string_protected() ' . (IS_ADMIN_FLAG ? 'for single encoding or consider htmlspecialchars() to support original double encoding ' : '') . 'instead', E_USER_DEPRECATED);

    if (IS_ADMIN_FLAG) {
        return htmlspecialchars($string, ENT_COMPAT, CHARSET, true);
    }

    return zen_output_string_protected($string);
}

/**
 * Rudimentary input sanitizer
 * NOTE: SHOULD NOT BE USED FOR DB QUERIES!!!  Use $db->prepare_input() or zen_db_input() instead
 *
 * @since ZC v1.0.3
 */
function zen_db_prepare_input(array|string|int|float|null $input, bool $trimspace = true): array|string|int|float|null
{
    if (is_string($input)) {
        $input = zen_sanitize_string($input);
    }
    if (is_string($input)) {
        if ($trimspace === true) {
            return trim(stripslashes($input));
        }
        return stripslashes($input);
    }

    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = zen_db_prepare_input($value);
        }
    }

    return $input;
}

/**
 * Performs an INSERT or UPDATE based on a supplied array of field data.
 * (Similar to $db->perform() but with only a 2D array.
 *  If type-cast binding is required, use $db->perform instead.)
 *
 * @param string $tableName table on which to perform the insert/update
 * @param array $tableData key-value pairs -- all will be treated as strings, and will be escaped
 * @param string $performType INSERT or UPDATE
 * @param string $whereCondition condition for UPDATE (exclude the word "WHERE")
 * @since ZC v1.0.3
 */
function zen_db_perform(string $tableName, array $tableData, $performType = 'INSERT', string $whereCondition = ''): queryFactoryResult
{
    global $db;
    if (strtolower($performType) === 'insert') {
        $query = 'INSERT INTO ' . $tableName . ' (';
        foreach ($tableData as $columns => $value) {
            $query .= $columns . ', ';
        }
        $query = substr($query, 0, -2) . ') VALUES (';
        foreach ($tableData as $value) {
            $value = (string)$value;
            match ($value) {
                'now()' => $query .= 'now(), ',
                'NULL', 'null' => $query .= 'null, ',
                default => $query .= '\'' . $db->prepare_input($value) . '\', ',
            };
        }
        $query = substr($query, 0, -2) . ')';
    } elseif (strtolower($performType) === 'update') {
        $query = 'UPDATE ' . $tableName . ' SET ';
        foreach ($tableData as $columns => $value) {
            $value = (string)$value;
            match ($value) {
                'now()' => $query .= $columns . ' = now(), ',
                'NULL', 'null' => $query .= $columns . ' = null, ',
                default => $query .= $columns . ' = \'' . $db->prepare_input($value) . '\', ',
            };
        }
        $query = substr($query, 0, -2) . ' WHERE ' . $whereCondition;
    }

    return $db->Execute($query);
}

/**
 * zen_db_perform equiv for language-specific inserts
 *
 * @return queryFactoryResult
 * @since ZC v1.5.3
 */
function zen_db_perform_language(string $tableName, array $tableData, string $keyIdName, int $keyId, int $languageId)
{
    global $db;
    $sql = 'INSERT INTO ' . $tableName . '(' . $db->prepare_input($keyIdName) . ', languages_id, ';
    foreach ($tableData as $columns => $value) {
        $sql .= $columns . ', ';
    }
    $sql = substr($sql, 0, -2) . ') values (' . $keyId . ', ' . $languageId . ', ';
    foreach ($tableData as $value) {
        match ((string)$value) {
            'now()' => $sql .= 'now(), ',
            'null' => $sql .= 'null, ',
            default => $sql .= '\'' . $db->prepare_input($value) . '\', ',
        };
    }
    $sql = substr($sql, 0, -2) . ')';
    $sql .= ' ON DUPLICATE KEY UPDATE ';
    foreach ($tableData as $columns => $value) {
        match ((string)$value) {
            'now()' => $sql .= $columns . ' = now(), ',
            'null' => $sql .= $columns .= ' = null, ',
            default => $sql .= $columns . ' = \'' . $db->prepare_input($value) . '\', ',
        };
    }
    $sql = substr($sql, 0, -2);
    return $db->Execute($sql);
}

/** @deprecated
 * Return a random row from a database query
 * @since ZC v1.0.3
 */
function zen_random_select($query)
{
    trigger_error('Call to deprecated function zen_random_select. Use $db->ExecuteRandomMulti() instead', E_USER_DEPRECATED);

    global $db;
    $random_query = $db->Execute($query);
    $num_rows = $random_query->RecordCount();
    if ($num_rows > 1) {
        $random_row = zen_rand(0, ($num_rows - 1));
        $random_query->Move($random_row);
    }
    return $random_query;
}
