<?php

namespace Horizon\Database\QueryBuilder;

use Horizon\Support\Str;



class ColumnDefinition
{

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @var bool
     */
    protected $unsigned = false;

    /**
     * @var bool
     */
    protected $zeroFill = false;

    /**
     * @var string
     */
    protected $charset;

    /**
     * @var string
     */
    protected $collate;

    /**
     * @var bool
     */
    protected $isNull = false;

    /**
     * @var string|int|null
     */
    protected $default;

    /**
     * @var bool
     */
    protected $autoIncrements = false;

    /**
     * @var string|null
     */
    protected $comment;

    /**
     * Constructs a new ColumnDefinition instance.
     *
     * @param string $type
     * @param array $parameter
     */
    public function __construct($type, $name, array $parameters = array())
    {
        $this->type = $type;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function compile($includeName = true)
    {
        return Str::join(
            ($includeName ? $this->compileName() : ''),
            $this->compileDataType(),
            $this->compileOptions()
        );
    }

    protected function compileName()
    {
        return StringBuilder::formatColumnName($this->name);
    }

    protected function compileDataType()
    {
        $type = $this->type;
        $compiled = array();

        if (!empty($this->parameters)) {
            $type .= sprintf('(%s)', implode(', ', $this->parameters));
        }

        $compiled[] = $type;

        if ($this->isNumeric()) {
            if ($this->unsigned) {
                $compiled[] = 'UNSIGNED';
            }

            if ($this->zeroFill) {
                $compiled[] = 'ZEROFILL';
            }
        }
        elseif ($this->isTextual()) {
            if ($this->charset) {
                $compiled[] = sprintf('CHARACTER SET %s', $this->charset);
            }

            if ($this->collate) {
                $compiled[] = sprintf('COLLATE %s', $this->collate);
            }
        }

        return Str::join($compiled);
    }

    protected function compileOptions()
    {
        $compiled = array();

        // Null
        $compiled[] = ($this->isNull) ? 'NULL' : 'NOT NULL';

        // Default
        if (isset($this->default)) {
            $compiled[] = 'DEFAULT ' . StringBuilder::escapeEnumValue($this->default);
        }

        // Auto increment
        if ($this->autoIncrements) {
            $compiled[] = 'AUTO_INCREMENT';
        }

        // Comment
        if (isset($this->comment)) {
            $compiled[] = sprintf('COMMENT \'%s\'', trim(StringBuilder::escapeEnumValue($this->comment), '\''));
        }

        return Str::join($compiled);
    }

    /**
     * Sets whether the column is UNSIGNED (only applies to numeric columns).
     *
     * @param bool $bool
     * @return ColumnDefinition $this
     */
    public function unsigned($bool = true)
    {
        $this->unsigned = $bool;
        return $this;
    }

    /**
     * Sets whether the column is ZEROFILL (only applies to numeric columns).
     *
     * @param bool $bool
     * @return ColumnDefinition $this
     */
    public function zeroFill($bool = true)
    {
        $this->zeroFill = $bool;
        return $this;
    }

    /**
     * Sets the character set for the column (only applies to textual columns).
     *
     * @param string $charset
     * @return ColumnDefinition $this
     */
    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Sets the character collation for the column (only applies to textual columns and enum/sets).
     *
     * @param string $collate
     * @return ColumnDefinition $this
     */
    public function collate($collate)
    {
        $this->collate = $collate;
        return $this;
    }

    /**
     * Sets whether the column is null.
     *
     * @param bool $bool
     * @return ColumnDefinition $this
     */
    public function isNull($bool = true)
    {
        $this->isNull = $bool;
        return $this;
    }

    /**
     * Sets the default value for the column.
     *
     * @param mixed $default
     * @return ColumnDefinition $this
     */
    public function defaults($default)
    {
        if (is_null($default)) {
            $default = 'NULL';
        }
        elseif (!is_numeric($default)) {
            $default = $default;
        }

        $this->default = $default;
        return $this;
    }

    /**
     * Sets whether the column is auto incrementing.
     *
     * @param bool $bool
     * @return ColumnDefinition $this
     */
    public function autoIncrements($bool = true)
    {
        $this->autoIncrements = $bool;
        return $this;
    }

    /**
     * Sets the comment for the column.
     *
     * @param string $text
     * @return ColumnDefinition $this
     */
    public function comment($text)
    {
        $this->comment = $text;
        return $this;
    }

    /**
     * Determines whether the current column type can have a CHARSET or COLLATE.
     *
     * @return bool
     */
    public function isTextual()
    {
        static $columns = array(
            'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'ENUM', 'SET'
        );

        return in_array($this->type, $columns);
    }

    /**
     * Determines whether the current column type can have an UNSIGNED or ZEROFILL property.
     *
     * @return bool
     */
    public function isNumeric()
    {
        static $columns = array(
            'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC'
        );

        return in_array($this->type, $columns);
    }


    /**
     * Creates a new BIT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function bit($name, $length)
    {
        return new ColumnDefinition('BIT', $name, array($length));
    }

    /**
     * Creates a new TINYINT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function tinyInteger($name, $length)
    {
        return new ColumnDefinition('TINYINT', $name, array($length));
    }

    /**
     * Creates a new SMALLINT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function smallInteger($name, $length)
    {
        return new ColumnDefinition('SMALLINT', $name, array($length));
    }

    /**
     * Creates a new MEDIUMINT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function mediumInteger($name, $length)
    {
        return new ColumnDefinition('MEDIUMINT', $name, array($length));
    }

    /**
     * Creates a new INT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function integer($name, $length)
    {
        return new ColumnDefinition('INT', $name, array($length));
    }

    /**
     * Creates a new BIGINT(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function bigInteger($name, $length)
    {
        return new ColumnDefinition('BIGINT', $name, array($length));
    }

    /**
     * Creates a new REAL(length, decimals) column.
     *
     * @param string $name
     * @param int $length
     * @param int $decimals
     * @return ColumnDefinition
     */
    public static function real($name, $length, $decimals)
    {
        return new ColumnDefinition('REAL', $name, array($length, $decimals));
    }

    /**
     * Creates a new DOUBLE(length, decimals) column.
     *
     * @param string $name
     * @param int $length
     * @param int $decimals
     * @return ColumnDefinition
     */
    public static function double($name, $length, $decimals)
    {
        return new ColumnDefinition('DOUBLE', $name, array($length, $decimals));
    }

    /**
     * Creates a new FLOAT(length, decimals) column.
     *
     * @param string $name
     * @param int $length
     * @param int $decimals
     * @return ColumnDefinition
     */
    public static function float($name, $length, $decimals)
    {
        return new ColumnDefinition('FLOAT', $name, array($length, $decimals));
    }

    /**
     * Creates a new DECIMAL(length[, decimals]) column.
     *
     * @param string $name
     * @param int $length
     * @param int $decimals
     * @return ColumnDefinition
     */
    public static function decimal($name, $length, $decimals = null)
    {
        return new ColumnDefinition('DECIMAL', $name, array($length, $decimals));
    }

    /**
     * Creates a new NUMERIC(length[, decimals]) column.
     *
     * @param string $name
     * @param int $length
     * @param int $decimals
     * @return ColumnDefinition
     */
    public static function numeric($name, $length, $decimals = null)
    {
        return new ColumnDefinition('NUMERIC', $name, array($length, $decimals));
    }

    /**
     * Creates a new DATE() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function date($name)
    {
        return new ColumnDefinition('DATE', $name);
    }

    /**
     * Creates a new TIME() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function time($name)
    {
        return new ColumnDefinition('TIME', $name);
    }

    /**
     * Creates a new TIMESTAMP() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function timestamp($name)
    {
        return new ColumnDefinition('TIMESTAMP', $name);
    }

    /**
     * Creates a new DATETIME() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function dateTime($name)
    {
        return new ColumnDefinition('DATETIME', $name);
    }

    /**
     * Creates a new YEAR() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function year($name)
    {
        return new ColumnDefinition('YEAR', $name);
    }

    /**
     * Creates a new CHAR(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function char($name, $length)
    {
        return new ColumnDefinition('CHAR', $name, array($length));
    }

    /**
     * Creates a new VARCHAR(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function varChar($name, $length)
    {
        return new ColumnDefinition('VARCHAR', $name, array($length));
    }

    /**
     * Creates a new BINARY(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function binary($name, $length)
    {
        return new ColumnDefinition('BINARY', $name, array($length));
    }

    /**
     * Creates a new VARBINARY(length) column.
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public static function varBinary($name, $length)
    {
        return new ColumnDefinition('VARBINARY', $name, array($length));
    }

    /**
     * Creates a new TINYBLOB() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function tinyBlob($name)
    {
        return new ColumnDefinition('TINYBLOB', $name);
    }

    /**
     * Creates a new BLOB() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function blob($name)
    {
        return new ColumnDefinition('BLOB', $name);
    }

    /**
     * Creates a new MEDIUMBLOB() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function mediumBlob($name)
    {
        return new ColumnDefinition('MEDIUMBLOB', $name);
    }

    /**
     * Creates a new LONGBLOB() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function longBlob($name)
    {
        return new ColumnDefinition('LONGBLOB', $name);
    }

    /**
     * Creates a new TINYTEXT() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function tinyText($name)
    {
        return new ColumnDefinition('TINYTEXT', $name);
    }

    /**
     * Creates a new TEXT() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function text($name)
    {
        return new ColumnDefinition('TEXT', $name);
    }

    /**
     * Creates a new MEDIUMTEXT() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function mediumText($name)
    {
        return new ColumnDefinition('MEDIUMTEXT', $name);
    }

    /**
     * Creates a new LONGTEXT() column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function longText($name)
    {
        return new ColumnDefinition('LONGTEXT', $name);
    }

    /**
     * Creates a new ENUM(...) column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function enum($name)
    {
        $args = func_get_args();
        $escaped = array();

        foreach ($args as $arg) {
            $escaped[] = StringBuilder::escapeEnumValue($arg);
        }

        return new ColumnDefinition('ENUM', $name, $escaped);
    }

    /**
     * Creates a new SET(...) column.
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public static function set($name)
    {
        $args = func_get_args();
        $escaped = array();

        foreach ($args as $arg) {
            $escaped[] = StringBuilder::escapeEnumValue($arg);
        }

        return new ColumnDefinition('SET', $name, $escaped);
    }

}
