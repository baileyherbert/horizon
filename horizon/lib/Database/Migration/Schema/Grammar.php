<?php

namespace Horizon\Database\Migration\Schema;

/**
 * Utility class to assist with building SQL statements.
 */
class Grammar
{

    /**
     * Compiles a name for use in a query. Works with names for tables, columns, and keys.
     *
     * @param string $columnName
     * @return string
     */
    public static function compileName($columnName)
    {
        $columnName = trim($columnName, '`');
        return '`' . $columnName . '`';
    }

    /**
     * Compiles a string with surrounding quotes.
     *
     * @param string $value
     * @return string
     */
    public static function compileString($value)
    {
        $value = str_replace(chr(39), chr(92) . chr(39), $value);
        return "'{$value}'";
    }

    /**
     * Compiles an array of column names into a formatted SQL list, such as (`col`, `col`).
     *
     * @param array $columns
     * @return string
     */
    public static function compileColumnList(array $columns)
    {
        $compiled = array();

        foreach ($columns as $column) {
            $compiled[] = static::compileName($column);
        }

        return '(' . implode(', ', $compiled) . ')';
    }

    /**
     * Compiles a default value into a string that can be inserted directly into an SQL query without any further
     * escaping. Returns false for invalid values.
     *
     * @param mixed $value
     * @return string|false
     */
    public static function compileDefault($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_numeric($value)) {
            return "'{$value}'";
        }

        if (is_string($value)) {
            if ($value === 'CURRENT_TIMESTAMP') {
                return $value;
            }

            $value = str_replace(chr(39), chr(92) . chr(39), $value);
            return "'{$value}'";
        }

        return false;
    }

    /**
     * Compiles a comment into an escaped, quoted string.
     *
     * @param string $comment
     * @return string
     */
    public static function compileComment($comment)
    {
        $comment = str_replace(chr(39), chr(92) . chr(39), $comment);
        return "'{$comment}'";
    }

    /**
     * Gets the category of a column (textual, numeric, date) given its blueprinted type name.
     *
     * @param string $type
     * @return string|null
     */
    public static function getColumnCategory($type)
    {
        foreach (static::getColumns() as $category => $columns) {
            foreach ($columns as $columnType) {
                if ($columnType === $type) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Returns true if the column type is numeric.
     *
     * @param string $type
     * @return bool
     */
    public static function isNumeric($type)
    {
        foreach (static::getColumns() as $category => $columns) {
            foreach ($columns as $columnType) {
                if ($columnType === $type) {
                    return $category == 'numeric';
                }
            }
        }

        return false;
    }

    /**
     * Returns true if the column type is a floating point column (meaning it has a precision parameter).
     *
     * @param string $type
     * @return bool
     */
    public static function isFloatingPoint($type)
    {
        return ($type == 'float' || $type == 'double' || $type == 'decimal');
    }

    /**
     * Returns true if the column type is a variable-length string of some kind.
     *
     * @param string $type
     * @return bool
     */
    public static function isVariableLength($type)
    {
        return ($type == 'char' || $type == 'string');
    }

    /**
     * Gets the SQL type of a column from the blueprinted type name.
     *
     * @param string $type
     * @return string|null
     */
    public static function getColumnType($type)
    {
        foreach (static::getColumnTypes() as $blueprintType => $sqlType) {
            if ($blueprintType === $type) {
                return $sqlType;
            }
        }

        return null;
    }

    /**
     * Gets the SQL key statement for a given type (primary, index, unique, foreign).
     *
     * @param string $type
     * @return string
     */
    public static function getKey($type)
    {
        $type = strtolower(str_replace('drop', '', $type));

        if ($type === 'primary') return 'PRIMARY KEY';
        if ($type === 'index') return 'INDEX';
        if ($type === 'unique') return 'UNIQUE';

        return 'FOREIGN KEY';
    }

    /**
     * Gets an array of categories and the column types that fall under them.
     *
     * @return array
     */
    public static function getColumns()
    {
        static $columns = array(
            'textual' => array('char', 'string', 'text', 'mediumText', 'longText', 'binary', 'json'),
            'numeric' => array('integer', 'tinyInteger', 'smallInteger', 'mediumInteger', 'bigInteger', 'float', 'double', 'decimal', 'boolean'),
            'date' => array('date', 'dateTime', 'time', 'timestamp', 'year')
        );

        return $columns;
    }

    /**
     * Gets an array of categories and the column types that fall under them.
     *
     * @return array
     */
    public static function getColumnTypes()
    {
        static $types = array(
            'char' => 'CHAR',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'mediumText' => 'MEDIUMTEXT',
            'longText' => 'LONGTEXT',
            'binary' => 'BINARY',
            'json' => 'JSON',
            'integer' => 'INT',
            'tinyInteger' => 'TINYINT',
            'smallInteger' => 'SMALLINT',
            'mediumInteger' => 'MEDIUMINT',
            'bigInteger' => 'BIGINT',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'decimal' => 'DECIMAL',
            'boolean' => 'BOOLEAN',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'year' => 'YEAR'
        );

        return $types;
    }

}
