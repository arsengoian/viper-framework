<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 19:52
 */

namespace Viper\Core;


use Viper\Core\Viewable;
use Viper\Models\Product;

class JSONView implements Viewable
{
    private $data;

    function __construct (array $data)
    {
        $this -> data = $data;
    }

    public function flush (): string
    {
        return json_encode($this -> data);
    }

}