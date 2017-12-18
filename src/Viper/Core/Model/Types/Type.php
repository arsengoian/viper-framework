<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 15:56
 */

namespace Viper\Core\Model\Types;


use Viper\Core\Model\ModelConfigException;
use Viper\Support\Collection;
use Viper\Support\Validator;

abstract class  Type
{
    protected $sqlType;
    private $validator;


    /**
     * Type constructor.
     * @param $sqlType
     */
    public function __construct(string $sqlType) {
        $this -> sqlType = $sqlType;
    }

    /**
     * Checks if the type is correspondent
     */
    abstract public function validate($value): void;

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     */
    abstract public function convert($value);

    /**
     * Converts the value to the needed Mysql string
     * @param $value
     * @return mixed
     */
    abstract public function reverseConvert($value);

    /**
     * The list of SQL types supported
     * @return array
     */
    abstract public function availableTypes(): array;


    public function setValidator (string $validator)
    {
        $this->validator = $validator;
    }


    public function getValidator (): Validator
    {
        if (!$this -> validator)
            throw new ModelConfigException('Validator not set');
        return new $this->validator(new Collection());
    }

}