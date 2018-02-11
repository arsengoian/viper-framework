<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:11
 */

namespace Viper\Core\Model\DB\Common;


use Viper\Core\Model\DB\DB;
use Viper\Core\Model\DB\RDBMS;
use Viper\Core\Model\DBField;
use Viper\Core\Model\ModelConfig;
use Viper\Core\Model\ModelConfigException;

abstract class SQLTable extends ModelConfig
{
    protected static function DB(): RDBMS {
        return DB::instance();
    }

    public function getField(string $field): DBField {
        if (isset($this -> columns[$field]))
            return $this -> columns[$field];
        else throw new ModelConfigException('Field '.$field.' not found');
    }

    protected function fieldExists(string $field): bool {
        foreach ($this -> getTableStructure() as $column)
            if ($column['Field'] == $field)
                return TRUE;
        return FALSE;
    }


}