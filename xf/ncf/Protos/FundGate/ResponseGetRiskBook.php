<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 风险揭示书
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetRiskBook extends ResponseBase
{
    /**
     * 内容
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
     * @return ResponseGetRiskBook
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}