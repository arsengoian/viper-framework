<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 05.11.2017
 * Time: 18:16
 */

namespace Viper\Core\Routing\Filters;


use Viper\Core\Filter;
use Viper\Core\Model\AsyncModel;
use Viper\Support\Libs\Util;

class ModelDestructionFilter extends Filter
{

    public function proceed ()
    {
        // Get all models
        $models = Util::RAM('cached.models', function() {});

        // Call each model's destruction
        if ($models)
            foreach ($models as $model)
                call_user_func($model.'::destruction');
    }

}