<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 17:38
 */

namespace Viper\Core\Model\DB\Types;


abstract class SizedType extends Type
{
    protected $size;

    const TYPES = [];

    /**
     * @param int $size
     */
    public function setSize (int $size)
    {
        $this->size = $size;
    }

    /**
     * The list of SQL types supported
     * @return array
     */
    public function availableTypes (): array
    {
        return array_keys(static::TYPES);
    }
}