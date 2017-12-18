<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 20:21
 */

namespace Viper\Core\Model;


class DBObjectException extends ModelException {

    function __construct() {
        parent::__construct('Arguments for model construction must be a pair of strings or array');
    }

}