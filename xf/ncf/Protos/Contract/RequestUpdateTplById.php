<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据模板ID更新模板信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestUpdateTplById extends ProtoBufferBase
{
    /**
     * 模板id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 模板标题
     *
     * @var string
     * @required
     */
    private $contractTitle;

    /**
     * 模板名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 模板分类ID
     *
     * @var int
     * @required
     */
    private $contractCid;

    /**
     * 模板内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * 模板类型
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * 是否html模板
     *
     * @var int
     * @optional
     */
    private $isHtml = 1;

    /**
     * 合同版本号
     *
     * @var float
     * @optional
     */
    private $version = 1;

    /**
     * 关联模板标识 id
     *
     * @var int
     * @required
     */
    private $tplIdentifierId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestUpdateTplById
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
    public function getContractTitle()
    {
        return $this->contractTitle;
    }

    /**
     * @param string $contractTitle
     * @return RequestUpdateTplById
     */
    public function setContractTitle($contractTitle)
    {
        \Assert\Assertion::string($contractTitle);

        $this->contractTitle = $contractTitle;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RequestUpdateTplById
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractCid()
    {
        return $this->contractCid;
    }

    /**
     * @param int $contractCid
     * @return RequestUpdateTplById
     */
    public function setContractCid($contractCid)
    {
        \Assert\Assertion::integer($contractCid);

        $this->contractCid = $contractCid;

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
     * @return RequestUpdateTplById
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestUpdateTplById
     */
    public function setType($type = 1)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsHtml()
    {
        return $this->isHtml;
    }

    /**
     * @param int $isHtml
     * @return RequestUpdateTplById
     */
    public function setIsHtml($isHtml = 1)
    {
        $this->isHtml = $isHtml;

        return $this;
    }
    /**
     * @return float
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param float $version
     * @return RequestUpdateTplById
     */
    public function setVersion($version = 1)
    {
        $this->version = $version;

        return $this;
    }
    /**
     * @return int
     */
    public function getTplIdentifierId()
    {
        return $this->tplIdentifierId;
    }

    /**
     * @param int $tplIdentifierId
     * @return RequestUpdateTplById
     */
    public function setTplIdentifierId($tplIdentifierId)
    {
        \Assert\Assertion::integer($tplIdentifierId);

        $this->tplIdentifierId = $tplIdentifierId;

        return $this;
    }

}