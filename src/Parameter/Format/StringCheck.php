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
        if (!is_string($param['value'])) {
            throw new ValidationException('Value is not a string');
        }
        if (!isset($param['format'])) {
            return;
        }

        if ($param['format'] === 'byte' &&
            preg_match('/^[a-z0-9\/+]*={0,2}$/i', $param['value']) !== 1
        ) {
            throw new ValidationException('Value is not a byte');
        }
        if ($param['format'] === 'binary' && (
            (strlen($param['value']) % 8 !== 0) || preg_match('/[^01]/', $param['value']) === 1
        )) {
            throw new ValidationException('Value is not a binary');
        }
        if ($param['format'] === 'date' &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $param['value']) !== 1
        ) {
            throw new ValidationException('Value is not a date');
        }
        if ($param['format'] === 'datetime' &&
            preg_match(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[zZ]|(?:[+-]\d{2}:\d{2}))$/',
                $param['value']
            ) !== 1
        ) {
            throw new ValidationException('Value is not a datetime');
        }

        $this->checkLength($param);
        $this->checkPattern($param);
    }

    /**
     * @param array $param
     */
    protected function checkLength(array $param)
    {
        if (isset($param['maxLength']) && strlen($param['value']) > $param['maxLength']) {
            throw new ValidationException('Value exceeds maxLength');
        }
        if (isset($param['minLength']) && strlen($param['value']) < $param['minLength']) {
            throw new ValidationException('Value exceeds minLength');
        }
    }

    /**
     * @param array $param
     */
    protected function checkPattern(array $param)
    {
        if (!isset($param['pattern'])) {
            return;
        }

        if (preg_match("/{$param['pattern']}/", $param['value']) !== 1) {
            throw new ValidationException('Value does not match pattern');
        }
    }
}
