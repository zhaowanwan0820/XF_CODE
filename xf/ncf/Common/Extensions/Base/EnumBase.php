<?php
namespace NCFGroup\Common\Extensions\Base;

class EnumBase
{
    public static function isInEnum($var) {
        $reflectionClass = new \ReflectionClass(get_called_class());
        $enumValues = $reflectionClass->getConstants();
        return in_array($var, $enumValues);
    }

    public static function getConstants() {
        $reflectionClass = new \ReflectionClass(get_called_class());
        return $reflectionClass->getConstants();
    }
}