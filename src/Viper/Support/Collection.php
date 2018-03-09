<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 16:15
 */

namespace Viper\Support;


use Viper\Support\Libs\Util;
use ArrayIterator;
use Traversable;

class Collection implements \ArrayAccess, \Serializable, \IteratorAggregate, \Countable {

    protected $data;
    private $position = 0;

    public function __construct(array &$arr = [], bool $clone = FALSE) {
        if (!$clone)
            $this -> data = &$arr;
        else $this -> data = $arr;
    }

    public static function fromArray(array $arr): Collection {
        return new Collection($arr);
    }

    public function length() : int {
        return count($this -> data);
    }

    public function toArray() {
        return $this -> data;
    }


    // Countable interface

    public function count ()
    {
        return count($this -> data);
    }



    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     */
    public function getIterator (): Traversable
    {
        return new ArrayIterator($this->data);
    }


    // ArrayAccess realization

    public function offsetSet($offset, $value) {
        if (is_null($offset))
            $this -> data[] = $value;
        else
            $this -> data[$offset] = $value;
        return $value;
    }

    public function offsetExists($offset) : bool {
        if (!is_string($offset) && !is_numeric($offset) && !is_null($offset))
            throw new \Exception('Illegal offset type');
        return isset($this -> data[$offset]);
    }

    public function offsetGet($offset) {
        return $this -> offsetExists($offset) ? $this -> data[$offset] : NULL;
    }

    public function offsetUnset($offset) {
        unset($this -> data[$offset]);
    }

    // Serializable realization

    public function serialize() : string {
        return serialize($this -> data);
    }

    public function unserialize($data) {
        $this -> data = unserialize($data);
    }

    // Array functions

    public function push(...$values) {
        call_user_func_array('array_push', array_merge([&$this -> data], $values));
    }

    public function merge(Collection $b): Collection {
        $this -> data = array_merge($this -> data, $b -> toArray());
        return $this;
    }

    public function mergeArray(array $b): Collection {
        $this -> data = array_merge($this -> data, $b);
        return $this;
    }

    public function usort(callable $callback): void {
        usort($this -> data, $callback);
    }

    public function toJson() {
        return json_encode($this -> data);
    }

    public function hasKey(string $key) {
        return array_key_exists($key, $this -> data);
    }

    public function contains($value) {
        return in_array($value, $this -> data);
    }


    public static function slice(Collection $els, int $start, int $end): Collection {
        if (!isset($els[$start]) || !isset($els[$end - 1]))
            throw new \Exception("Start or end point do not exist in collection");
        $clname = get_class($els);
        $ret = new $clname();
        for ($i = $start; $i < $end; $i++)
            $ret[] = $els[$i];
        return $ret;
    }
}