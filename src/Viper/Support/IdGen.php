<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:05
 */

namespace Viper\Support;


use Viper\Core\Model\DB\DB;
use Viper\Core\Model\DB\DBException;

class IdGen {

    const TABLE = '__IdGen';
    const COLUMNS = [
        ['len', 'tinyint NOT NULL'],
        ['table_key', 'varchar(63) NOT NULL'],
        ['p1', 'varchar(63) NOT NULL'],
        ['p2', 'varchar(63) NOT NULL'],
        ['k', 'varchar(63) NOT NULL'],
        ['id', 'varchar(63) NOT NULL'],
        ['CHECK', '(len>0)'],
        ['CHECK', '(len<33)']
    ];

    private $len;
    private $key;
    private $current;
    private $p1;
    private $p2;
    private $k;


    function __construct(int $len, string $key) {
        if ($len < 2 || $len > 32)
            throw new IdGenException('Length out of range');
        if (strlen($key) > 63)
            throw new IdGenException('Key must not exceed 63 characters');
        $this -> len = (string) $len;
        $this -> key = $key;
        $cdb = DB::instance();

        try {
            $dat = $cdb -> select(self::TABLE, 'id, p1, p2, k', "len = $len AND table_key = '$key'");
        } catch(DBException $e) {
            $cdb -> createTable(self::TABLE, self::COLUMNS);
            $dat = [];
        }
        if (count($dat) < 1) {
            $cdb -> insert(
                self::TABLE,
                [
                    'len' => $len,
                    'table_key' => $key,
                    'id' => $minid = $this -> minId(),
                    'p1' => $p1 = $this -> findP1(),
                    'p2' => $p2 = $this -> findp2(),
                    'k' => $k = $this -> findK()
                ]
            );
            $this -> current = $minid;
            $this -> p1 = $p1;
            $this -> p2 = $p2;
            $this -> k = $k;
        } else {
            $d = $dat[0];
            $this -> current = $d['id'];
            $this -> p1 = $d['p1'];
            $this -> p2 = $d['p2'];
            $this -> k = $d['k'];
        }
    }


    private function maxId() : string {
        return bcpow('16', $this -> len);
    }

    private function minId() : string {
        return bcpow('16', (string) (((int) $this -> len) - 1));
    }



    private static function validateInt(string $int) {
        if (bccomp($int, bcadd($int, '0'), 1) != 0)
            throw new IdGenException();
        return $int;
    }

    private static function validateRange(string $max) {
        try {
            $max = self::validateInt(bcdiv($max, '5', 1));
            $max = self::validateInt(bcdiv($max, '3', 1));
            $c = 0;
            while ($max != 2) {
                if (++$c > 128)
                    throw new IdGenException();
                $max = self::validateInt(bcdiv($max, '2', 1));
            }
        } catch (IdGenException $e) {
            throw new IdGenException('Wrong range number in result');
        }
    }

    private static function nextP(string $n, string $max) {
        self::validateRange($max);
        $nextp = 0;
        for ($nextP = bcadd($n, '1'); bcmod($nextP, '2') == 0 || bcmod($nextP, '3') == 0 || bcmod($nextP, '5') == 0; $nextP = bcadd($nextP, '1'))
            continue;
        if ($nextP >= $max)
            throw new IdGenException('Next number generation overflow');
        return $nextP;
    }

    private function findK() : string {
        $k = bcdiv(bcsub($this -> maxId(), $this -> minId()), '8');
        $endn = '';
        foreach (str_split(md5(sha1($this -> maxId()))) as $char)
            $endn .= (string) hexdec($char);
        $reduced = bcmod(bcmul($endn, 42), $k);
        return $reduced;
    }

    private function findP1() : string {
        return self::nextP(
            bcadd(
                bcdiv(bcsub($this -> maxId(), $this -> minId()), '3'),
                $this -> findK()
            ),
            bcsub($this -> maxId(), $this -> minId())
        );
    }

    private function findp2() : string {
        return self::nextP(
            bcadd(
                bcdiv(bcsub($this -> maxId(), $this -> minId()), '7'),
                $this -> findK()
            ),
            bcsub($this -> maxId(), $this -> minId())
        );
    }


    public function neu() {

        $max = $this -> maxId();
        $min = $this -> minId();

        $newseed = bcadd($this -> current, 1);
        if ($newseed >= $max)
            throw new IdGenException('Id overflow');
        if ($newseed < $min)
            throw new IdGenException('Id too small');
        else (DB::instance() -> update(self::TABLE, ['id' => $newseed], "len = {$this -> len} AND table_key = '{$this -> key}'"));

        $range = bcsub($max, $min);

        $a = bcsub($this -> current, $min);
        $b = bcmod(bcadd($this -> k, bcmul($this -> p1, $a)), $range);
        $c = bcmod(bcadd($this -> k, bcmul($this -> p1, $a)), $range);

        return strtoupper(self::bcdechex(bcadd($c, $min)));

    }


    private static function bcdechex($dec) {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);
        if($remain == 0) {
            return dechex($last);
        } else {
            return self::bcdechex($remain).strtoupper(dechex($last));
        }
    }

}


