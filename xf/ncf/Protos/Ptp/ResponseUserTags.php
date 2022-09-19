<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 设置用户Tag返回结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhaohui
 */
class ResponseUserTags extends AbstractRequestBase
{
    /**
     * 返回结果
     *
     * @var boolean
     * @optional
     */
    private $result = NULL;

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param boolean $result
     * @return ResponseUserTags
     */
    public function setResult($result = NULL)
    {
        $this->result = $result;

        return $this;
    }

}