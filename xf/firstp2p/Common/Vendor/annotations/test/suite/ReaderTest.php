<?php
namespace Minime\Annotations;

use Minime\Annotations\Fixtures\AnnotationsFixture;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    private $fixture;

    public function setUp()
    {
        $this->fixture = new AnnotationsFixture;
    }

    /**
     * @return Reader
     */
    private function getReader()
    {
        return new Reader(new Parser);
    }

    public function testGetReader()
    {
        $this->assertInstanceOf('Minime\Annotations\Interfaces\ParserInterface', $this->getReader()->getParser());
    }

    public function testGetAnnotations()
    {
        $reflectionClass = new \ReflectionClass($this->fixture);
        $annotations = $this->getReader()->getAnnotations($reflectionClass);
        $this->assertTrue($annotations->get('get'));
    }

    public function testReadClassAnnotations()
    {
        $annotations = $this->getReader()->getClassAnnotations($this->fixture);
        $this->assertTrue($annotations->get('get'));
    }

    public function testReadPropertyAnnotations()
    {
        $annotations = $this->getReader()->getPropertyAnnotations($this->fixture, 'single_values_fixture');
        $this->assertEquals('foo', $annotations['param_a']);
        $this->assertEquals('bar', $annotations['param_b']);
    }

    public function testReadMethodAnnotations()
    {
        $annotations = $this->getReader()->getMethodAnnotations($this->fixture, 'method_fixture');
        $this->assertTrue($annotations->get('post'));
    }

    public function testCreateFromDefaults()
    {
        $this->assertInstanceOf('Minime\Annotations\Reader', Reader::createFromDefaults());
    }
}
