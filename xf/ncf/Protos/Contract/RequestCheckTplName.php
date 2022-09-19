<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据模板标识判断模板是否存在
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestCheckTplName extends ProtoBufferBase
{
    /**
     * 模板标题
     *
     * @var string
     * @required
     */
    private $tplName;

    /**
     * 合同版本号
     *
     * @var float
     * @required
     */
    private $version;

    /**
     * 关联模板标识 id
     *
     * @var int
     * @required
     */
    private $tplIdentifierId;

    /**
     * @return string
     */
    public function getTplName()
    {
        return $this->tplName;
    }

    /**
     * @param string $tplName
     * @return RequestCheckTplName
     */
    public function setTplName($tplName)
    {
        \Assert\Assertion::string($tplName);

        $this->tplName = $tplName;

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
     * @return RequestCheckTplName
     */
    public function setVersion($version)
    {
        \Assert\Assertion::float($version);

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
     * @return RequestCheckTplName
     */
    public function setTplIdentifierId($tplIdentifierId)
    {
        \Assert\Assertion::integer($tplIdentifierId);

        $this->tplIdentifierId = $tplIdentifierId;

        return $this;
    }

}