<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 11.02.2018
 * Time: 20:23
 */

namespace Viper\Core\Routing\Filters;


use Viper\Core\Filter;

/**
 * Class CustomModelConstructionFilter
 * Extend this and add custom models
 * @package Viper\Core\Routing\Filters
 */
abstract class CustomModelConstructionFilter extends Filter
{
    /**
     * List of classes representing custom models for which the data is to be updated
     * @return array
     */
    abstract protected function getCustomModels(): array;
    
    
    final public function proceed ()
    {
        foreach ($this -> getCustomModels() as $model)
            call_user_func("$model::checkCustomYAML");
    }

}