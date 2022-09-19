<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * zzz
 *
 * 由代码生成器生成, 不可人为修改
 * @author zzz
 */
class RequestTest extends AbstractRequestBase
{
    /**
     * test
     *
     * @var string
     * @required
     */
    private $test;

    /**
     * @return string
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param string $test
     * @return RequestTest
     */
    public function setTest($test)
    {
        \Assert\Assertion::string($test);

        $this->test = $test;

        return $this;
    }

}