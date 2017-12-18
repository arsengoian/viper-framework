<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 13:19
 */

namespace Viper\Daemon;


abstract class Task implements \Serializable
{

    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    public function unserialize($serialized)
    {
        foreach(unserialize($serialized) as $field => $value)
            if (property_exists($this, $field))
                $this -> $field = $value;
    }

    /**
     * Perform action task
     */
    abstract public function run();

}