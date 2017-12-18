<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 13:19
 */

namespace Viper\Daemon;


use Viper\Support\Collection;

interface Queue
{
    /**
     * Add new task to queue top
     * @param Task $task
     */
    public function add(Task $task) : void;

    /**
     * Get the oldest items from the queue
     * @param int $num the number of items to pop
     * @return Collection
     */
    public function pop(int $num) : Collection;

    /**
     * Number of items in the queue
     * @return int
     */
    public function count() : int;

    /**
     * Approximate time until the queue is over
     * @return int
     */
    public function eta(): int;

}