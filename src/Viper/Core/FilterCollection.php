<?php

namespace Viper\Core;

use Viper\Support\Collection;
class FilterCollection extends Collection {

    public function __construct (array $arr = [], $clone = FALSE)
    {
        foreach ($arr as $item)
            if (!class_exists($item))
                throw new AppLogicException('Must be Filter');
        parent::__construct($arr, $clone);
    }

    public function offsetSet ($offset, $value)
    {
        if (!class_exists($value))
            throw new AppLogicException('Must be Filter');
        return parent::offsetSet($offset, $value);
    }

}