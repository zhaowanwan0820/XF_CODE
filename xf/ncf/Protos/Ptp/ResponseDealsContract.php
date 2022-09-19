<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 已投项目查看合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class ResponseDealsContract extends ResponseBase
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
     * @return ResponseDealsContract
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}