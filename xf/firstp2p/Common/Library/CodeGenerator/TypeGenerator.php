<?php
namespace NCFGroup\Common\Library\CodeGenerator;

/**
 * TypeGenerator 类型生成
 *
 * @author jingxu
 */
class TypeGenerator
{
    const INT_PREG = '/^(int|integer)$/i';
    const FLOAT_PREG = '/^(float|double)$/i';
    const STR_PREG = '/^(str|string)$/i';
    const BOOL_PREG = '/^(bool|boolean)$/i';
    const ARRAY_PREG = '/^(array|array<\w+>)$/i';
    const OBJ_PREG = '/^.+$/';
    const TRUE_OBJ_PREG = '/^(?<trueObj>.*?)(<\w+>)?$/';

    public $typeStr;

    public function __construct($typeStr)
    {
        $this->typeStr = trim($typeStr);
    }

    public function getType4Param()
    {
        if ($this->isArray()) {
            return 'array ';
        }

        if ($this->isObj()) {
            preg_match(self::TRUE_OBJ_PREG, $this->typeStr, $matches);
            return $matches['trueObj'].' ';
        }

        return '';
    }

    public function getAssertFunction4Set()
    {
        if ($this->isInt()) {
            return 'integer';
        }

        if ($this->isFloat()) {
            return 'float';
        }

        if ($this->isBool()) {
            return 'boolean';
        }

        if ($this->isStr()) {
            return 'string';
        }

        return '';
    }

    private function isArray()
    {
        return self::_isArray($this->typeStr);
    }

    public static function _isArray($typeStr)
    {
        return preg_match(self::ARRAY_PREG, $typeStr);
    }

    private function isInt()
    {
        return self::_isInt($this->typeStr);
    }

    public static function _isInt($typeStr)
    {
        return preg_match(self::INT_PREG, $typeStr);
    }

    private function isFloat()
    {
        return self::_isFloat($this->typeStr);
    }

    public static function _isFloat($typeStr)
    {
        return preg_match(self::FLOAT_PREG, $typeStr);
    }

    private function isStr()
    {
        return self::_isStr($this->typeStr);
    }

    public static function _isStr($typeStr)
    {
        return preg_match(self::STR_PREG, $typeStr);
    }

    private function isBool()
    {
        return self::_isBool($this->typeStr);
    }

    public static function _isBool($typeStr)
    {
        return preg_match(self::BOOL_PREG, $typeStr);
    }

    private function isObj()
    {
        return self::_isObj($this->typeStr);
    }

    public static function _isObj($typeStr)
    {
        if (self::_isInt($typeStr) || self::_isFloat($typeStr) || self::_isStr($typeStr) || self::_isBool($typeStr) || self::_isArray($typeStr)) {
            return false;
        }

        return preg_match(self::OBJ_PREG, $typeStr);
    }

}
