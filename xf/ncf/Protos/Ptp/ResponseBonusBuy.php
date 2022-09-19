<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 理财师买买红包结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class ResponseBonusBuy extends ResponseBase
{
    /**
     * 红包链接
     *
     * @var string
     * @required
     */
    private $bonusUrl;

    /**
     * @return string
     */
    public function getBonusUrl()
    {
        return $this->bonusUrl;
    }

    /**
     * @param string $bonusUrl
     * @return ResponseBonusBuy
     */
    public function setBonusUrl($bonusUrl)
    {
        \Assert\Assertion::string($bonusUrl);

        $this->bonusUrl = $bonusUrl;

        return $this;
    }

}