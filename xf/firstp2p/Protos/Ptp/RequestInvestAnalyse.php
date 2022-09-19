<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取客户列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class RequestInvestAnalyse extends AbstractRequestBase
{
    /**
     * 投资分析类别，0当前分析，1总体分析
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 客户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestInvestAnalyse
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestInvestAnalyse
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestInvestAnalyse
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}