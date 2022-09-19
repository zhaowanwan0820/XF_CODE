<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhang
 */
class RequestAddContract extends AbstractRequestBase
{
    /**
     * 协议标题
     *
     * @var string
     * @required
     */
    private $title;

    /**
     * 协议内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return RequestAddContract
     */
    public function setTitle($title)
    {
        \Assert\Assertion::string($title);

        $this->title = $title;

        return $this;
    }
    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return RequestAddContract
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}