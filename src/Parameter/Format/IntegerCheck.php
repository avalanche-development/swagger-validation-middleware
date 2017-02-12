<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException;

class IntegerCheck
{

    /**
     * @param array $param
     */
    public function check(array $param)
    {
        if (!is_int($param['value'])) {
            throw new ValidationException('Value is not an integer');
        }
        if (!isset($param['format'])) {
            return;
        }

        if ($param['format'] === 'int32' && (
            $param['value'] < -2147483647 || $param['value'] > 2147483647
        )) {
            throw new ValidationException('Value exceeds int32 bounds');
        }
        if ($param['format'] === 'int64' && (
            $param['value'] < -9223372036854775807 || $param['value'] > 9223372036854775807
        )) {
            throw new ValidationException('Value exceeds int64 bounds');
        }
    }
}
