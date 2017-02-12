<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

class IntegerCheck
{

    /**
     * @param array $param
     * @return boolean
     */
    protected function check(array $param)
    {
        if (!is_int($param['value'])) {
            return false;
        }
        if (!isset($param['format'])) {
            return true;
        }

        if ($param['format'] === 'int32' && (
            $param['value'] < -2147483647 || $param['value'] > -2147483647
        )) {
            return false;
        }
        if ($param['format'] === 'int64' && (
            $param['value'] < -9223372036854775807 || $param['value'] > 9223372036854775807
        )) {
            return false;
        }

        return true;
    }
}
