<?php

declare(strict_types=1);

namespace Tests\Support\Database;

use PDO;
use RuntimeException;

class TestDb
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        throw new RuntimeException('Unsupported DB_TYPE for test framework: ' . DB_TYPE);
    }

    public static function resetConnection(): void
    {
        self::$pdo = null;
    }

    public static function truncate(string $table): void
    {
        self::pdo()->exec('TRUNCATE TABLE ' . self::quoteIdentifier($table));
    }

    public static function insert(string $table, array $values): int
    {
        $columns = array_keys($values);
        $columnSql = implode(', ', array_map(self::quoteIdentifier(...), $columns));
        $placeholders = implode(', ', array_map(static fn (int|string $column): string => ':' . $column, $columns));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quoteIdentifier($table),
            $columnSql,
            $placeholders
        );

        $statement = self::pdo()->prepare($sql);
        foreach ($values as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }
        $statement->execute();

        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $values, string $whereSql, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($values) as $column) {
            $set[] = self::quoteIdentifier($column) . ' = :set_' . $column;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::quoteIdentifier($table),
            implode(', ', $set),
            $whereSql
        );

        $statement = self::pdo()->prepare($sql);
        foreach ($values as $column => $value) {
            $statement->bindValue(':set_' . $column, $value);
        }
        foreach ($whereParams as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->execute();

        return $statement->rowCount();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->execute();
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    public static function selectValue(string $sql, array $params = []): mixed
    {
        $row = self::selectOne($sql, $params);
        if ($row === null) {
            return null;
        }

        return reset($row);
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
