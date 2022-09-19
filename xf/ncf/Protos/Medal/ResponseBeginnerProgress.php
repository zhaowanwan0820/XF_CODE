<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 新手任务进度
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi
 */
class ResponseBeginnerProgress extends ProtoBufferBase
{
    /**
     * 是否是新手
     *
     * @var bool
     * @required
     */
    private $isBeginner;

    /**
     * 
     *
     * @var int
     * @optional
     */
    private $medalCount = 0;

    /**
     * 
     *
     * @var int
     * @optional
     */
    private $userMedalCount = 0;

    /**
     * 
     *
     * @var array
     * @optional
     */
    private $unawardedMedals = NULL;

    /**
     * @return bool
     */
    public function getIsBeginner()
    {
        return $this->isBeginner;
    }

    /**
     * @param bool $isBeginner
     * @return ResponseBeginnerProgress
     */
    public function setIsBeginner($isBeginner)
    {
        \Assert\Assertion::boolean($isBeginner);

        $this->isBeginner = $isBeginner;

        return $this;
    }
    /**
     * @return int
     */
    public function getMedalCount()
    {
        return $this->medalCount;
    }

    /**
     * @param int $medalCount
     * @return ResponseBeginnerProgress
     */
    public function setMedalCount($medalCount = 0)
    {
        $this->medalCount = $medalCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserMedalCount()
    {
        return $this->userMedalCount;
    }

    /**
     * @param int $userMedalCount
     * @return ResponseBeginnerProgress
     */
    public function setUserMedalCount($userMedalCount = 0)
    {
        $this->userMedalCount = $userMedalCount;

        return $this;
    }
    /**
     * @return array
     */
    public function getUnawardedMedals()
    {
        return $this->unawardedMedals;
    }

    /**
     * @param array $unawardedMedals
     * @return ResponseBeginnerProgress
     */
    public function setUnawardedMedals(array $unawardedMedals = NULL)
    {
        $this->unawardedMedals = $unawardedMedals;

        return $this;
    }

}