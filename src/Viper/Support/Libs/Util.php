<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 22.07.2017
 * Time: 23:56
 */

namespace Viper\Support\Libs;

use Viper\Core\Config;
use ReflectionFunction;

class Util
{
    // String functions
    public static function match(string $switch, array $fields, callable $default = NULL): bool {
        foreach ($fields as $key => $call) {
            if (self::contains($switch, $key)){
                return $call();
            }
        }
        if ($default)
            $default();
        return FALSE;
    }

    public static function contains(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) !== FALSE;
    }

    public static function trimLines(string $mutiLineText): string {
        return self::eachLine($mutiLineText, function($line): string  {
            return trim($line);
        });
    }

    public static function eachLine(string &$mutiLineText, callable $run): string {
        if ((new ReflectionFunction($run)) -> getNumberOfRequiredParameters() != 1)
            throw new UtilException('Run callback must contain 1 string parameter $line and return string');
        $lines = [];
        foreach(self::expodeGenerator($mutiLineText, "\n")() as $line)
            $lines[] = $run($line);
        return implode("\n", $lines);
    }

    public static function expodeGenerator(string $str, string $separator = ','): callable {
        if (strlen($separator) != 1)
            throw new UtilException('Separator must be a single character: '.$separator);
        return function() use ($str, $separator) {
            $last = 0;
            for ($i = 0; $i < strlen($str); $i++) {
                if ($str{$i} === $separator) {
                    if ($last == 0)
                        yield substr($str, $last, $i - $last);
                    else yield substr($str, $last + 1, $i - $last - 1);
                    $last = $i;
                }
            }
            if ($last == 0)
                yield substr($str, $last);
            else yield substr($str, $last + 1);
        };
    }

    public static function partsGenerator(string $str, int $len): callable {
        return function() use ($str, $len) {
            $num = strlen($str) / $len;
            for($i = 0; $i < $num; $i++)
                yield substr($str, $len*$i, $len);
        };
    }

    public static function put(string $file, string $content, int $FPCflags = 0) {
        if (!file_exists($dir = dirname($file)))
            self::recursiveMkdir($dir);
        $ex = file_exists($file);
        file_put_contents($file, $content, $FPCflags);
        if (!$ex)
            chmod($file, 0777);
    }

    // Warning! Ignoring warnings
    public static function recursiveMkdir(string $dir, int $loops = 0) {
        if ($loops > 20)
            throw new UtilException('No more than 20 directories in a row');
        if(!file_exists($newdir = dirname($dir)))
            self::recursiveMkdir($newdir);
        @mkdir($dir);
        @chmod($dir, 0777);
    }
    
    // Warning! Ignoring warnings
    public static function recursiveRmdir($dir) {
        if (!file_exists($dir))
            return true;
        if (!is_dir($dir))
            return @unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..')
                continue;
            if (!self::recursiveRmdir($dir . DIRECTORY_SEPARATOR . $item))
                return false;
        }
        return @rmdir($dir);
    }



    private const TERMINATOR = '...';

    private static function terminate(string $str): string {
        while (mb_strlen($str) > 1 && in_array($str{mb_strlen($str) - 1}, [',', ':', '-', '.', '"', "'"])) {
            $str = mb_substr($str, 0, mb_strlen($str) - 1);
        }
        if (mb_strlen($str) <= 1)
            return '...';
        return $str . self::TERMINATOR;
    }

    public static function shorten(string $text, int $len, ?int $limit = NULL): string {
        $text = trim($text);
        if ($limit === NULL)
            $limit = $len;
        if (mb_strlen($text) < $len)
            return $text;
        $spaceN = mb_strrpos(mb_substr($text, 0, $len), ' ');
        if ($spaceN === NULL) {
            return self::terminate(mb_substr($text, 0, $len - mb_strlen(self::TERMINATOR)));
        } else {
            if ($spaceN < $limit - 3)
                return self::terminate(mb_substr($text, 0, $spaceN));
            else {
                return self::shorten(mb_substr($text, 0, $spaceN), $len - mb_strlen(self::TERMINATOR), $limit);
            }
        }
    }

    public static function explodeLast(string $separator, string $string): string {
        if (strlen($separator) != 1)
            throw new UtilException('Separator must be a single character: '.$separator);
        $arr = explode($separator, $string);
        $len = count($arr);
        return $arr[$len - 1];
    }

    public static function fromYaml(string $file): array {
        if (!file_exists($file))
            throw new UtilException('File missing: '.$file);
        return self::cache($file, file_get_contents($file), 'yaml', function($data) use ($file) {
            return (new Yaml()) -> load($file);
        });
    }

    public static function RAM(string $key, callable $get = NULL) {
        $k = Config::get('RAM_KEY').$key;
        if (!isset($GLOBALS[$k]))
            $GLOBALS[$k] = $get ? $get() : NULL;
        return $GLOBALS[$k];
    }


    public static function cache(string $key, string $dataKey, string $namespace = 'system',
                                 callable $dataHandler = NULL, callable $serialize = NULL, callable $unserialize = NULL) {
        if (!$dataHandler || (new ReflectionFunction($dataHandler)) -> getNumberOfParameters() != 1)
            throw new UtilException('Data handler must have one data parameter');
        if ($serialize && (new ReflectionFunction($serialize)) -> getNumberOfParameters() != 1)
            throw new UtilException('Serialize must have one data parameter');
        if ($unserialize && (new ReflectionFunction($unserialize)) -> getNumberOfParameters() != 1)
            throw new UtilException('Serialize must have one data parameter');

        $dir = root().'/storage/cache/'.$namespace.'/'.md5($key);
        $file = $dir.'/'.md5($dataKey).'.cache';
        if (file_exists($file)) {

            $data = file_get_contents($file);
            if ($unserialize)
                return $unserialize($data);
            return unserialize($data);

        } else {

            $data = $dataHandler($dataKey);
            if (is_dir($dir))
                self::recursiveRmdir($dir);
            if ($serialize)
                self::put($file, $serialize($data));
            self::put($file, serialize($data));
            return $data;

        }

    }

    public static function clearCache(string $namespace) {
        return self::recursiveRmdir(root().'/storage/cache/'.$namespace);
    }
}