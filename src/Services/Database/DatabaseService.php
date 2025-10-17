<?php

namespace Lyre\Strings\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service class for database operations.
 * 
 * This service provides methods for database-related operations
 * including table introspection and foreign key detection.
 * 
 * @package Lyre\Strings\Services\Database
 */
class DatabaseService
{
    /**
     * Get all tables in the database.
     *
     * @return array
     * @throws \Exception
     */
    public function getAllTables(): array
    {
        $database = config('database.default');
        switch ($database) {
            case 'mysql':
                $tables = DB::select('SHOW TABLES');
                return array_map(function ($table) {
                    return array_values((array) $table)[0];
                }, $tables);
            case 'pgsql':
                $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'');
                return array_map(function ($table) {
                    return $table->table_name;
                }, $tables);
            case 'sqlite':
                $tables = DB::select('SELECT name FROM sqlite_master WHERE type = \'table\'');
                return array_map(function ($table) {
                    return $table->name;
                }, $tables);
            default:
                throw new \Exception('Unsupported database driver');
        }
    }

    /**
     * Get foreign key columns for a table.
     *
     * @param string $table
     * @param string|null $schema
     * @return array
     * @throws \Exception
     */
    public function getTableForeignColumns($table, $schema = null): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $schema = $schema ?? 'public';

            $foreignKeys = DB::select("
                SELECT DISTINCT
                    kcu.column_name,
                    ccu.table_name AS foreign_table,
                    ccu.column_name AS foreign_column
                FROM
                    information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                 AND tc.constraint_schema = kcu.constraint_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                 AND ccu.constraint_schema = tc.constraint_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND tc.table_name = ?
                  AND tc.table_schema = ?
            ", [$table, $schema]);
        } elseif ($driver === 'mysql') {
            $schema = $schema ?? DB::getDatabaseName();

            $foreignKeys = DB::select("
                SELECT DISTINCT
                    kcu.COLUMN_NAME AS column_name,
                    kcu.REFERENCED_TABLE_NAME AS foreign_table,
                    kcu.REFERENCED_COLUMN_NAME AS foreign_column
                FROM
                    information_schema.KEY_COLUMN_USAGE kcu
                WHERE
                    kcu.TABLE_NAME = ?
                    AND kcu.TABLE_SCHEMA = ?
                    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $schema]);
        } else {
            throw new \Exception("Unsupported driver: {$driver}");
        }

        $columns = [];
        foreach ($foreignKeys as $fk) {
            $columns[] = $fk->column_name;
        }

        return array_values(array_unique($columns));
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function columnExists($table, $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Get join details for a relationship.
     *
     * @param string $relationName
     * @param mixed $model
     * @return array|null
     */
    public function getJoinDetails($relationName, $model): ?array
    {
        $relation = $model->$relationName();

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $relatedModel = $relation->getRelated();
            return [
                'foreignKey' => $relation->getForeignKeyName(),
                'relatedKey' => $relation->getOwnerKeyName(),
                'relatedTable' => $relatedModel->getTable(),
            ];
        } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $relatedModel = $relation->getRelated();
            return [
                'foreignKey' => $relation->getForeignKeyName(),
                'relatedKey' => $relation->getLocalKeyName(),
                'relatedTable' => $relatedModel->getTable(),
            ];
        }

        return null;
    }
}
