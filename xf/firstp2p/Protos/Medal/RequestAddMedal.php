<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;
use NCFGroup\Protos\Medal\ProtoMedalInfo;
use NCFGroup\Protos\Medal\ProtoMedalRule;
use NCFGroup\Protos\Medal\ProtoMedalAward;

/**
 * 勋章创建接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class RequestAddMedal extends ProtoBufferBase
{
    /**
     * medal基本信息
     *
     * @var ProtoMedalInfo
     * @required
     */
    private $medalInfo;

    /**
     * medal规则列表
     *
     * @var array<ProtoMedalRule>
     * @required
     */
    private $medalRules;

    /**
     * medal奖励列表
     *
     * @var array<ProtoMedalAward>
     * @optional
     */
    private $medalAwards = NULL;

    /**
     * @return ProtoMedalInfo
     */
    public function getMedalInfo()
    {
        return $this->medalInfo;
    }

    /**
     * @param ProtoMedalInfo $medalInfo
     * @return RequestAddMedal
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
     * @return RequestAddMedal
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
     * @return RequestAddMedal
     */
    public function setMedalAwards(array $medalAwards = NULL)
    {
        $this->medalAwards = $medalAwards;

        return $this;
    }

}