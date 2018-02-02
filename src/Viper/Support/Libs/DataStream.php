<?php

/**
 * stream - Handle raw input stream
 *
 * LICENSE: This source file is subject to version 3.01 of the GPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/licenses/gpl.html. If you did not receive a copy of
 * the GPL License and are unable to obtain it through the web, please
 *
 * @author jason.gerfen@gmail.com
 * @license http://www.gnu.org/licenses/gpl.html GPL License 3
 */

namespace Viper\Support\Libs;

use Viper\Support\Collection;

class DataStream extends Collection
{
    /**
     * @abstract Raw input stream
     */
    protected $input;

    /**
     * @function __construct
     */
    public function __construct(string $input)
    {
        $this->input = $input;

        $boundary = $this->boundary();

        if (!strlen($boundary)) {
            $data = [
                'post' => $this->parse(),
                'file' => []
            ];
        } else {
            $blocks = $this->split($boundary);

            $data = $this->blocks($blocks);
        }

        // Fix for App
        // @author Arsen Goian
        parent::__construct($data);
    }

    /**
     * @function boundary
     * @returns string
     */
    private function boundary()
    {
        if(!isset($_SERVER['CONTENT_TYPE'])) {
            return null;
        }

        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);

        $m = isset($matches[1])? $matches[1] : '';
        return $m;
    }

    /**
     * @function parse
     * @returns array
     */
    private function parse()
    {
        parse_str(urldecode($this->input), $result);
        return $result;
    }

    /**
     * @function split
     * @param $boundary string
     * @returns array
     */
    private function split($boundary)
    {
        $result = preg_split("/-+$boundary/", $this->input);
        array_pop($result);
        return $result;
    }

    /**
     * @function blocks
     * @param $array array
     * @returns array
     */
    private function blocks($array)
    {
        $results = [
            'post' => new DataCollection(),
            'file' => new DataCollection()
        ];

        foreach($array as $key => $value)
        {
            if (empty($value))
                continue;

            $block = $this->decide($value);

            if (count($block['post']) > 0)
               $results['post'] -> push($block['post']);

            if (count($block['file']) > 0)
               $results['file'] -> push($block['file']);
        }

        return $this->mergeData($results);
    }

    /**
     * @function decide
     * @param $string string
     * @returns array
     */
    private function decide($string)
    {
        if (strpos($string, 'application/octet-stream') !== FALSE)
        {
            return [
                'post' => $this->file($string),
                'file' => []
            ];
        }

        if (strpos($string, 'filename') !== FALSE)
        {
            return [
                'post' => [],
                'file' => $this->file_stream($string)
            ];
        }

        return [
            'post' => $this->post($string),
            'file' => []
        ];
    }

    /**
     * @function file
     *
     * @param $string
     *
     * @return array
     */
    private function file($string)
    {
        preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $string, $match);
        return [
            $match[1] => (!empty($match[2]) ? $match[2] : '')
        ];
    }

    /**
     * @function file_stream
     *
     * @param $string
     *
     * @return array
     */
    private function file_stream($string)
    {
        $data = [];

        preg_match('/name=\"([^\"]*)\"; filename=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $string, $match);
        preg_match('/Content-Type: (.*)?/', $match[3], $mime);

        $image = preg_replace('/Content-Type: (.*)[^\n\r]/', '', $match[3]);

        $path = sys_get_temp_dir().'/php'.substr(sha1(rand()), 0, 6);

        $err = file_put_contents($path, ltrim($image));

        if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
            $index = $tmp[1];
        } else {
            $index = $match[1];
        }

        $data[$index]['name'][] = $match[2];
        $data[$index]['type'][] = $mime[1];
        $data[$index]['tmp_name'][] = $path;
        $data[$index]['error'][] = ($err === FALSE) ? $err : 0;
        $data[$index]['size'][] = filesize($path);

        return $data;
    }

    /**
     * @function post
     *
     * @param $string
     *
     * @return array
     */
    private function post($string)
    {
        $data = [];

        preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $string, $match);

        if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
            $data[$tmp[1]][] = (!empty($match[2]) ? $match[2] : '');
        } else {
            $data[$match[1]] = (!empty($match[2]) ? $match[2] : '');
        }

        return $data;
    }

    /**
     * @function mergeData
     * @param $array array
     *
     * Ugly ugly ugly
     *
     * @returns array
     */
    private function mergeData($array)
    {
        $results = [
            'post' => new DataCollection(),
            'file' => new DataCollection()
        ];

        if (count($array['post']) > 0) {
            foreach($array['post'] as $key => $value) {
                foreach($value as $k => $v) {
                    if (is_array($v)) {
                        foreach($v as $kk => $vv) {
                            $results['post'][$k][] = $vv;
                        }
                    } else {
                        $results['post'][$k] = $v;
                    }
                }
            }
        }

        if (count($array['file']) > 0) {
            foreach($array['file'] as $key => $value) {
                foreach($value as $k => $v) {
                    if (is_array($v)) {
                        foreach($v as $kk => $vv) {
                            if(is_array($vv) && (count($vv) === 1)) {
                                $results['file'][$k][$kk] = $vv[0];
                            } else {
                                $results['file'][$k][$kk][] = $vv[0];
                            }
                        }
                    } else {
                        $results['file'][$k][$key] = $v;
                    }
                }
            }
        }

        return $results;
    }
}