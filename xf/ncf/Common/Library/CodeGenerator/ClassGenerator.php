<?php
namespace NCFGroup\Common\Library\CodeGenerator;

include "AttrGenerator.php";

/**
 * ClassGenerator 类生成器
 *
 * @author jingxu
 */
class ClassGenerator
{
    public $className;
    public $namespace;
    public $comment;
    public $author;
    public $useArr;
    public $extend;
    public $attrGeneratorArr = array();
    public $consts = array();

    public static function parseByJson($jsonStr, $className)
    {
        $classInfoArr = json_decode($jsonStr, true);
        return self::parseByArray($classInfoArr, $className);
    }

    public static function parseByArray($classInfoArr, $className)
    {
        $self = new self();
        $self->className = $className;
        $self->namespace = $classInfoArr['namespace'];
        $self->comment = $classInfoArr['comment'];
        $self->author = $classInfoArr['author'];
        $self->useArr = isset($classInfoArr['use']) ? $classInfoArr['use'] : array();
        $self->extend = isset($classInfoArr['extend']) ? $classInfoArr['extend'] : '';

        if (isset($classInfoArr['consts'])) {
            foreach ($classInfoArr['consts']  as $constKey => $constVal) {
                $self->consts[] = "const $constKey = ".var_export($constVal, true).";";
            }
        }

        foreach ($classInfoArr['properties'] as $propertyInfo) {
            if (!$propertyInfo['required'] && !array_key_exists('default', $propertyInfo)) {
                die("optional的属性, 必须给出default值\n");
            }

            $self->attrGeneratorArr[] = new AttrGenerator($self,
                $propertyInfo['name'],
                $propertyInfo['type'],
                isset($propertyInfo['comment']) ? $propertyInfo['comment'] : '',
                isset($propertyInfo['annotation']) ? $propertyInfo['annotation'] : array(),
                $propertyInfo['required'],
                isset($propertyInfo['default']) ? $propertyInfo['default'] : null);
        }

        return $self;
    }

    public static function getPrettyJsonByArray(array $classInfoArr)
    {
        return json_encode($classInfoArr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
    }


    public function generate($classPath)
    {
        file_put_contents("{$classPath}/{$this->className}.php", $this->getClassContent());
    }

    public function getClassContent()
    {
        return <<<CCC
<?php
namespace {$this->namespace};

{$this->getUseStr()}
{$this->getClassDoc()}
class {$this->className}{$this->getExtendStr()}
{
{$this->getConsts()}{$this->getAttrCodes()}{$this->getFunctionCodes()}
}
CCC;
    }

    public function getClassDoc()
    {
        return <<<CCC
/**
 * {$this->comment}
 *
 * 由代码生成器生成, 不可人为修改
 * @author {$this->author}
 */
CCC;
    }

    public function getConsts()
    {
        if (!empty($this->consts)) {
            return "    ".implode("\n    ", $this->consts)."\n\n";
        } else {
            return "";
        }
    }

    public function getAttrCodes()
    {
        $content = '';
        foreach ($this->attrGeneratorArr as $attrGenerator) {
            $content .= $attrGenerator->generateAttrCode();
        }

        return $content;
    }

    public function getFunctionCodes()
    {
        $content = '';
        foreach ($this->attrGeneratorArr as $attrGenerator) {
            $content .= $attrGenerator->generateFunctionCode();
        }

        return $content;

    }

    public function getUseStr()
    {
        $content = '';
        foreach ($this->useArr as $use) {
            $content .= <<<CCC
use {$use};

CCC;
        }

        return $content;
    }

    public function getExtendStr()
    {
        if ($this->extend === '') {
            return '';
        }

        return " extends {$this->extend}";
    }
}
