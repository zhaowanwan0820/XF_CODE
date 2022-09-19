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
class ResponseMedalProgress extends ProtoBufferBase
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
    private $beginnerMedalCount = 0;

    /**
     * 
     *
     * @var int
     * @optional
     */
    private $userBeginnerMedalCount = 0;

    /**
     * 
     *
     * @var array
     * @optional
     */
    private $unawardedBeginnerMedals = NULL;

    /**
     * 未领奖的勋章列表
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
     * @return ResponseMedalProgress
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
    public function getBeginnerMedalCount()
    {
        return $this->beginnerMedalCount;
    }

    /**
     * @param int $beginnerMedalCount
     * @return ResponseMedalProgress
     */
    public function setBeginnerMedalCount($beginnerMedalCount = 0)
    {
        $this->beginnerMedalCount = $beginnerMedalCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserBeginnerMedalCount()
    {
        return $this->userBeginnerMedalCount;
    }

    /**
     * @param int $userBeginnerMedalCount
     * @return ResponseMedalProgress
     */
    public function setUserBeginnerMedalCount($userBeginnerMedalCount = 0)
    {
        $this->userBeginnerMedalCount = $userBeginnerMedalCount;

        return $this;
    }
    /**
     * @return array
     */
    public function getUnawardedBeginnerMedals()
    {
        return $this->unawardedBeginnerMedals;
    }

    /**
     * @param array $unawardedBeginnerMedals
     * @return ResponseMedalProgress
     */
    public function setUnawardedBeginnerMedals(array $unawardedBeginnerMedals = NULL)
    {
        $this->unawardedBeginnerMedals = $unawardedBeginnerMedals;

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
     * @return ResponseMedalProgress
     */
    public function setUnawardedMedals(array $unawardedMedals = NULL)
    {
        $this->unawardedMedals = $unawardedMedals;

        return $this;
    }

}