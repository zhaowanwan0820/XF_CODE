<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 电子合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetContractBook extends ResponseBase
{
    /**
     * 合同内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ResponseGetContractBook
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}