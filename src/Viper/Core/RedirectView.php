<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 03.11.2017
 * Time: 19:09
 */

namespace Viper\Core;


class RedirectView implements Viewable
{
    private $url;

    public function __construct (string $url)
    {
        $this -> url = $url;
        header('Location: '.$this -> url);
    }

    public function flush (): string
    {
        return 'Redirecting...';
    }
}