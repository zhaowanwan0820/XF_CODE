<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * ProtoOpenDelP2pDealProject
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoOpenDelP2pDealProject extends ProtoBufferBase
{
    /**
     * dealId
     *
     * @var int
     * @optional
     */
    private $dealId = 0;

    /**
     * projectId
     *
     * @var int
     * @optional
     */
    private $projectId = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ProtoOpenDelP2pDealProject
     */
    public function setDealId($dealId = 0)
    {
        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return ProtoOpenDelP2pDealProject
     */
    public function setProjectId($projectId = 0)
    {
        $this->projectId = $projectId;

        return $this;
    }

}