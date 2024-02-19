<?php

namespace App\Traits;

/**
 * ConstantExport Trait implements getConstants() method which allows
 * to return class constant as an associative array
 */
trait ConstantExport
{
    /**
     * @return array [const_name => 'value', ...]
     */
    static function getConstants(): array
    {
        $refl = new \ReflectionClass(__CLASS__);

        return $refl->getConstants();
    }
}
