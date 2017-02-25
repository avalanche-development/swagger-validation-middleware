<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException;

class StringCheck
{

    /**
     * @param array $param
     */
    public function check(array $param)
    {
        return true;
    }
}
