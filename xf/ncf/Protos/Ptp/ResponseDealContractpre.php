<?php
namespace NCFGroup\Protos\Ptp;

use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 查看合同协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class ResponseDealContractpre extends ResponseBase
{
    /**
     * 合同内容,html
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
     * @return ResponseDealContractpre
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}