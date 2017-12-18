<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 19:22
 */

namespace Viper\Daemon;

use Viper\Support\Collection;
use Viper\Support\Util;
use FilesystemIterator;
use GlobIterator;

abstract class QueueRouter extends Router implements Queue
{
    private $index;
    private $iterator;

    const STORAGE = ROOT.'/storage/daemon/queue/';
    const ONES_PER_TIME = 1;

    public function __construct($id)
    {
        parent::__construct($id);

        if (!is_dir($this -> storage())) {
            mkdir($this->storage());
        }

        if (!file_exists($this -> storage().'_index')) {
            Util::put($this->storage() . '_index', 0);
        }

        $this->index = (int) file_get_contents($this -> storage().'_index') ?? 0;
        $this -> updateIterator();
    }

    private function storage(): string {
        return self::STORAGE.$this -> getName().'/';
    }

    private function updateIterator(): void {
        $this -> iterator = new GlobIterator($this -> storage().'*.queue', FilesystemIterator::SKIP_DOTS);
    }

    /**
     * Add new task to queue top
     * @param Task $task
     */
    public function add(Task $task) : void
    {
        $this->index++;
        Util::put($this -> storage().$this->index.'.queue', serialize($task));
        $this -> updateIterator();
        Util::put($this -> storage().'_index', $this -> index);
    }

    /**
     * Get the oldest items from the queue
     * @param int $num the number of items to pop
     * @return Collection
     * TODO fix situation when crash is immediately after pop;
     */
    public function pop(int $num) : Collection
    {
        $ret = new Collection();
        foreach ($this -> iterator as $item) {
            if (!$num--)
                return $ret;
            $ret[] = unserialize(file_get_contents($item));
            unlink($item);
        }
        $this -> updateIterator();
        return $ret;
    }

    /**
     * Number of items in the queue
     * @return int
     */
    public function count() : int
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this -> iterator -> count();
    }

    /**
     * Approximate time until the queue is over (in seconds)
     * @return int
     */
    public function eta(): int
    {
        return floor($this -> count() * $this -> getSleep() / static::ONES_PER_TIME);
    }



}