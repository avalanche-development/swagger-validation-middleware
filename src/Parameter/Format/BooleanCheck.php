<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException;

class BooleanCheck
{

    /**
     * @param array $param
     */
    public function check(array $param)
    {
        if ($param['value'] !== 'true' && $param['value'] !== 'false') {
            throw new ValidationException('Value is not a boolean');
        }
    }
}
