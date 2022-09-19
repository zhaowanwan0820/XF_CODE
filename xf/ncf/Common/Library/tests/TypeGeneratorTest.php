<?php
use NCFGroup\Common\Library\CodeGenerator\TypeGenerator;

require '/home/dev/git/phalcon-common/Library/CodeGenerator/TypeGenerator.php';

/**
 * TypeGeneratorTest
 *
 * @author jingxu
 */
class TypeGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function testIsInt()
    {
        $testCorrectTypeStrArr = array('int', 'integer', 'Int', 'int ', ' int');
        foreach ($testCorrectTypeStrArr as $testTypeStr) {
            $typeGenerator = new TypeGenerator($testTypeStr);
            $this->assertTrue($typeGenerator->isInt() == true);
        }

        $testFailingTypeStrArr = array('inta', 'aint');
        foreach ($testFailingTypeStrArr as $testTypeStr) {
            $typeGenerator = new TypeGenerator($testTypeStr);
            $this->assertTrue($typeGenerator->isInt() == false);
        }
    }

    public function testIsArray()
    {
        $testCorrectTypeStrArr = array('array', 'Array', 'array<abcde>');
        foreach ($testCorrectTypeStrArr as $typeStr) {
            $typeGenerator = new typeGenerator($typeStr);
            $this->assertTrue($typeGenerator->isArray() == true);
        }

        $testFailingTypeStrArr = array('array<', 'array2', 'array<>');
        foreach ($testFailingTypeStrArr as $testTypeStr) {
            $typeGenerator = new TypeGenerator($testTypeStr);
            $this->assertTrue($typeGenerator->isArray() == false);
        }
    }

    public function testIsObj()
    {
        $testCorrectTypeStrArr = array('Abc', 'CDD', 'JXsb');
        foreach ($testCorrectTypeStrArr as $typeStr) {
            $typeGenerator = new typeGenerator($typeStr);
            $this->assertTrue($typeGenerator->isObj() == true);
        }

        $testFailingTypeStrArr = array('array', 'int', 'bool', 'abcadf');
        foreach ($testFailingTypeStrArr as $testTypeStr) {
            $typeGenerator = new TypeGenerator($testTypeStr);
            $this->assertTrue($typeGenerator->isObj() == false);
        }
    }
}
