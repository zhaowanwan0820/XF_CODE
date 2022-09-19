<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;
use NCFGroup\Protos\Medal\ProtoMedalInfo;
use NCFGroup\Protos\Medal\ProtoMedalRule;
use NCFGroup\Protos\Medal\ProtoMedalAward;

/**
 * 获取勋章接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ResponseGetMedal extends ProtoBufferBase
{
    /**
     * medal基本信息
     *
     * @var ProtoMedalInfo
     * @required
     */
    private $medalInfo;

    /**
     * medal规则
     *
     * @var array<ProtoMedalRule>
     * @required
     */
    private $medalRules;

    /**
     * medal奖励
     *
     * @var array<ProtoMedalAward>
     * @required
     */
    private $medalAwards;

    /**
     * @return ProtoMedalInfo
     */
    public function getMedalInfo()
    {
        return $this->medalInfo;
    }

    /**
     * @param ProtoMedalInfo $medalInfo
     * @return ResponseGetMedal
     */
    public function setMedalInfo(ProtoMedalInfo $medalInfo)
    {
        $this->medalInfo = $medalInfo;

        return $this;
    }
    /**
     * @return array<ProtoMedalRule>
     */
    public function getMedalRules()
    {
        return $this->medalRules;
    }

    /**
     * @param array<ProtoMedalRule> $medalRules
     * @return ResponseGetMedal
     */
    public function setMedalRules(array $medalRules)
    {
        $this->medalRules = $medalRules;

        return $this;
    }
    /**
     * @return array<ProtoMedalAward>
     */
    public function getMedalAwards()
    {
        return $this->medalAwards;
    }

    /**
     * @param array<ProtoMedalAward> $medalAwards
     * @return ResponseGetMedal
     */
    public function setMedalAwards(array $medalAwards)
    {
        $this->medalAwards = $medalAwards;

        return $this;
    }

}