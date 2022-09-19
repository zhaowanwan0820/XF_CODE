<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照分类id取得模板列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetTplByCid extends ProtoBufferBase
{
    /**
     * 分类ID
     *
     * @var int
     * @required
     */
    private $categoryId;

    /**
     * 合同版本号
     *
     * @var float
     * @required
     */
    private $contractVersion;

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return RequestGetTplByCid
     */
    public function setCategoryId($categoryId)
    {
        \Assert\Assertion::integer($categoryId);

        $this->categoryId = $categoryId;

        return $this;
    }
    /**
     * @return float
     */
    public function getContractVersion()
    {
        return $this->contractVersion;
    }

    /**
     * @param float $contractVersion
     * @return RequestGetTplByCid
     */
    public function setContractVersion($contractVersion)
    {
        \Assert\Assertion::float($contractVersion);

        $this->contractVersion = $contractVersion;

        return $this;
    }

}