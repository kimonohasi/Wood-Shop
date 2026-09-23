<?php
/**
 * WoodCon - Base Model
 * Lớp nền: wrapper PDO Prepared Statement cho mọi model.
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

abstract class Base
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::connect();
    }

    public static function all(string $columns = '*'): array
    {
        return static::where('1=1 AND status = 1', [], $columns);
    }

    public static function find(int $id): ?array
    {
        return static::first('SELECT * FROM `' . static::$table . '` WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        return static::first(
            'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` = ? LIMIT 1',
            [$value]
        );
    }

    public static function where(
        string $condition,
        array $params = [],
        string $columns = '*',
        string $orderBy = 'id DESC',
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $sql = "SELECT {$columns} FROM `" . static::$table . "` WHERE {$condition} ORDER BY {$orderBy}";
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(string $condition = '1=1', array $params = []): int
    {
        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM `" . static::$table . "` WHERE {$condition}");
        $stmt->execute($params);
        return (int)$stmt->fetch()['c'];
    }

    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`,`', $cols) . '`) VALUES (' .
            implode(',', array_fill(0, count($cols), '?')) . ')';
        $stmt = static::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        return static::updateWhere('id = ?', $data, [$id]);
    }

    public static function updateWhere(string $condition, array $data, array $whereParams = []): bool
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "`{$col}` = ?";
        }
        $sql = 'UPDATE `' . static::$table . '` SET ' . implode(', ', $sets) . " WHERE {$condition}";
        $stmt = static::db()->prepare($sql);
        return $stmt->execute(array_merge(array_values($data), $whereParams));
    }

    public static function delete(int $id): bool
    {
        $stmt = static::db()->prepare('DELETE FROM `' . static::$table . '` WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function deleteWhere(string $condition, array $params = []): bool
    {
        $stmt = static::db()->prepare('DELETE FROM `' . static::$table . '` WHERE ' . $condition);
        return $stmt->execute($params);
    }
}