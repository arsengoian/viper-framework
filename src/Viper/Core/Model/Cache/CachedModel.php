<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 05.11.2017
 * Time: 17:46
 */

namespace Viper\Core\Model\Cache;

use Viper\Core\Model\Model;
use Viper\Support\Collection;
use Viper\Support\Libs\Util;
use ArrayAccess;

class CachedModel implements ArrayAccess
{
    private $data;
    private $insert;
    private $doomed = FALSE;
    private $sets = [];

    public function __construct (Model $model, bool $insert = FALSE)
    {
        $this -> data = serialize($model);
        $this -> insert = $insert;
    }

    public function model() {
        $model = unserialize($this -> data);
        return $model;
    }

    public function offsetExists ($offset)
    {
        return unserialize($this -> data) -> offsetExists($offset);
    }

    public function offsetGet ($offset)
    {
        if (isset($this -> sets[$offset]))
            return $this -> sets[$offset];
        return unserialize($this -> data) -> baseGet($offset);
    }

    public function offsetSet ($offset, $value)
    {
        $this -> sets[$offset] = $value;
        return $value;
    }

    public function offsetUnset ($offset)
    {
        return unserialize($this -> data) -> offsetUnset($offset);
    }

    public function isInsert (): bool
    {
        return $this->insert;
    }

    public function isDoomed (): bool
    {
        return $this->doomed;
    }

    public function doom ()
    {
        $this->doomed = TRUE;
    }

    public function undoom()
    {
        $this->doomed = FALSE;
    }

    public function getSets (): array
    {
        return $this->sets;
    }
}