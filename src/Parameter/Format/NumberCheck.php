<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException;

class NumberCheck
{

    /**
     * @param array $param
     */
    public function check(array $param)
    {
        if (!is_int($param['value']) && !is_float($param['value'])) {
            throw new ValidationException('Value is not a number');
        }

        $this->checkRange($param);
    }

    /**
     * @param array $param
     */
    protected function checkRange(array $param)
    {
        if (isset($param['maximum']) && $param['value'] > $param['maximum']) {
            throw new ValidationException('Value exceeds maximum');
        }
        if (isset($param['exclusiveMaximum']) && $param['value'] >= $param['exclusiveMaximum']) {
            throw new ValidationException('Value exceeds exclusiveMaximum');
        }
        if (isset($param['minimum']) && $param['value'] < $param['minimum']) {
            throw new ValidationException('Value exceeds minimum');
        }
        if (isset($param['exclusiveMinimum']) && $param['value'] <= $param['exclusiveMinimum']) {
            throw new ValidationException('Value exceeds exclusiveMinimum');
        }
    }
}
