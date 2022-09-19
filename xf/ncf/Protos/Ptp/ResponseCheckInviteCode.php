<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 邀请码信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangge
 */
class ResponseCheckInviteCode extends ResponseBase
{
    /**
     * 返利点
     *
     * @var float
     * @required
     */
    private $rebateRatio;

    /**
     * 邀请者Id
     *
     * @var int
     * @required
     */
    private $referUserId;

    /**
     * 开始时间
     *
     * @var string
     * @required
     */
    private $validBegin;

    /**
     * 结束时间
     *
     * @var string
     * @required
     */
    private $validEnd;

    /**
     * @return float
     */
    public function getRebateRatio()
    {
        return $this->rebateRatio;
    }

    /**
     * @param float $rebateRatio
     * @return ResponseCheckInviteCode
     */
    public function setRebateRatio($rebateRatio)
    {
        \Assert\Assertion::float($rebateRatio);

        $this->rebateRatio = $rebateRatio;

        return $this;
    }
    /**
     * @return int
     */
    public function getReferUserId()
    {
        return $this->referUserId;
    }

    /**
     * @param int $referUserId
     * @return ResponseCheckInviteCode
     */
    public function setReferUserId($referUserId)
    {
        \Assert\Assertion::integer($referUserId);

        $this->referUserId = $referUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getValidBegin()
    {
        return $this->validBegin;
    }

    /**
     * @param string $validBegin
     * @return ResponseCheckInviteCode
     */
    public function setValidBegin($validBegin)
    {
        \Assert\Assertion::string($validBegin);

        $this->validBegin = $validBegin;

        return $this;
    }
    /**
     * @return string
     */
    public function getValidEnd()
    {
        return $this->validEnd;
    }

    /**
     * @param string $validEnd
     * @return ResponseCheckInviteCode
     */
    public function setValidEnd($validEnd)
    {
        \Assert\Assertion::string($validEnd);

        $this->validEnd = $validEnd;

        return $this;
    }

}