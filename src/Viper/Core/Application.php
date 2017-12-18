<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 05.11.2017
 * Time: 18:12
 */

namespace Viper\Core;


use Viper\Core\Routing\Filters\HttpsFilter;
use Viper\Core\Routing\Filters\ModelDestructionFilter;
use Viper\Core\Routing\App;

class Application extends App
{

    final protected function systemOnLoad (): void
    {
        // After all filters are passed
    }

    final protected function declareSystemFilters (): FilterCollection
    {
        return new FilterCollection([
            HttpsFilter::class
        ]);
    }

    final protected function declareSystemDyingFilters (): FilterCollection
    {
        return new FilterCollection([
            ModelDestructionFilter::class
        ]);
    }

    protected function onLoad (): void
    {

    }

    protected function declareFilters (): FilterCollection
    {
        return new FilterCollection([]);
    }

    protected function declareDyingFilters (): FilterCollection
    {
        return new FilterCollection([]);
    }
}