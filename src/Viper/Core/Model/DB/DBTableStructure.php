<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:02
 */

namespace Viper\Core\Model\DB;


use Viper\Core\Model\DBField;

abstract class DBTableStructure
{
    abstract protected function idQuery (): string;

    abstract protected function fieldExists(string $field): bool;

    abstract public function getField(string $field): DBField;

    abstract protected function validateChecks(string $field, $value);

    abstract protected function validateUnique(string $field, $value, string $className);

    abstract protected function createColumn(DBField $field);

    abstract protected function changeColumn(string $old, DBField $new);

    abstract protected function dropColumn(string $colName);

    abstract protected static function DB(): RDBMS;

    abstract protected function checkColumnTypes();

    abstract protected function getTableStructure(): array;

    abstract protected function getCreateQuery(): string;
}