<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoContract extends ProtoBufferBase
{
    /**
     * id
     *
     * @var int
     * @required
     */
    private $id;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoContract
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ProtoContract
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
     * @return ProtoContract
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}