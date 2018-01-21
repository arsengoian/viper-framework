<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 14:13
 */

namespace Viper\Core\Model;


use Viper\Core\Model\DB\Types\Type;
use Viper\Support\Libs\Util;

class DBField
{
    private $query;
    private $name;
    private $type;
    private $text_type;
    private $sql_type;
    private $size;
    private $default_val;
    private $auto_increment;
    private $not_null;

    public function __construct (string $name, string $column, array $sqlTypeClasses)
    {
        $this -> query = $column;
        $this -> name = $name;

        $this -> not_null = (bool) preg_match( "/NOT[\s\t\r]+NULL/i", $column);
        $this -> auto_increment = Util::contains(strtoupper($column), 'AUTO_INCREMENT');

        $this -> type = preg_replace('/^\s*([^\s]+)\s+(\([^\s]+\))?.*$/', '$1$2', $column);
        if (preg_match('/^.+\((.+)\)$/', $this -> type))
            $this -> size = (int) preg_replace('/^.+\((.+)\)$/', '$1', $this -> type);
        $this -> text_type = preg_replace('/^(.+)\(.*$/', '$1', $this -> type);

        foreach($sqlTypeClasses as $type) {
            $specimen = new $type($this -> text_type);
            /** @noinspection PhpUndefinedMethodInspection */
            if (in_array($this -> text_type, $specimen -> availableTypes())) {
                $this -> sql_type = $specimen;
                break;
            }
        }
        if (!$this -> sql_type)
            throw new ModelConfigException('Type "'.$this -> sql_type.'" not supported');

        if (Util::contains(strtoupper($column), 'DEFAULT'))
            $this -> default_val = preg_replace('/^.+DEFAULT\s+(.*)\s*.*$/i', '$1', $column);
    }


    public function getQuery (): string
    {
        return $this->query;
    }

    public function getName (): string
    {
        return $this->name;
    }

    public function getNotNull (): bool
    {
        return $this->not_null;
    }

    public function getAutoIncrement (): bool
    {
        return $this->auto_increment;
    }

    public function getDefaultVal (): ?string
    {
        return $this->default_val;
    }

    public function getType (): string
    {
        return $this->type;
    }

    public function getTextType (): string
    {
        return $this->text_type;
    }

    public function getSQLType (): Type
    {
        return $this->sql_type;
    }

    public function getSize (): ?int
    {
        return $this->size;
    }
}