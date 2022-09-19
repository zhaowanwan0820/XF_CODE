<?php
namespace NCFGroup\Common\Library\CodeGenerator;

include "TypeGenerator.php";

/**
 * AttrGenerator 属性及其get set方法生成器
 *
 * @author jingxu
 */
class AttrGenerator
{
    public $name;
    public $type;
    public $comment;
    public $required;
    public $default;
    public $annotationArr;
    public $classGenerator;

    public function __construct($classGenerator, $name, $type, $comment, $annotationArr, $required, $default = null)
    {
        $this->classGenerator = $classGenerator;
        $this->name = $name;
        $this->type = new TypeGenerator($type);
        $this->comment = $comment;
        $this->annotationArr = $annotationArr;
        $this->required = $required;
        $this->default = var_export($default, true);
    }

    public function generateAttrCode()
    {
        $content = <<<CCC
    /**
     * {$this->comment}
     *
     * @var {$this->type->typeStr}

CCC;

        foreach ($this->annotationArr as $annotation) {
            $content .= <<<CCC
     * @{$annotation}

CCC;
        }

        if ($this->required) {
            $content .= <<<CCC
     * @required

CCC;
        } else {
            $content .= <<<CCC
     * @optional

CCC;
        }

        if($this->required == false) {
            $content .= <<<CCC
     */
    private \${$this->name} = {$this->default};


CCC;
        } else {
            $content .= <<<CCC
     */
    private \${$this->name};


CCC;
        }

        return $content;
    }

    public function generateFunctionCode()
    {
        return <<<CCC
{$this->generateGetFunctionCode()}{$this->generateSetFunctionCode()}
CCC;
    }

    public function generateGetFunctionCode()
    {
        $fun = ucfirst($this->name);

        return <<<CCC
    /**
     * @return {$this->type->typeStr}
     */
    public function get{$fun}()
    {
        return \$this->{$this->name};
    }


CCC;
    }

    public function generateSetFunctionCode()
    {
        $defaultParamStr = '';
        if (!$this->required) {
            $defaultParamStr = " = {$this->default}";
        }
        $fun = ucfirst($this->name);

        return <<<CCC
    /**
     * @param {$this->type->typeStr} \${$this->name}
     * @return {$this->classGenerator->className}
     */
    public function set{$fun}({$this->type->getType4Param()}\${$this->name}{$defaultParamStr})
    {
{$this->getAssert()}        \$this->{$this->name} = \${$this->name};

        return \$this;
    }

CCC;
    }

    private function getAssert()
    {
        if (!$this->required) {
            return '';
        }

        $assertFunction = $this->type->getAssertFunction4Set();
        if (!$assertFunction) {
            return '';
        }

        return <<<CCC
        \Assert\Assertion::{$assertFunction}(\${$this->name});


CCC;
    }
}
