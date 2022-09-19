<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * vip信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class ResponseGetVipInfo extends ProtoBufferBase
{
    /**
     * 会员等级名称
     *
     * @var string
     * @required
     */
    private $gradeName;

    /**
     * 会员等级图标
     *
     * @var string
     * @required
     */
    private $imgUrl;

    /**
     * 会员积分
     *
     * @var int
     * @required
     */
    private $point;

    /**
     * 升级/降级描述
     *
     * @var string
     * @required
     */
    private $firstDesc;

    /**
     * 过期/保级积分描述
     *
     * @var string
     * @required
     */
    private $secondDesc;

    /**
     * @return string
     */
    public function getGradeName()
    {
        return $this->gradeName;
    }

    /**
     * @param string $gradeName
     * @return ResponseGetVipInfo
     */
    public function setGradeName($gradeName)
    {
        \Assert\Assertion::string($gradeName);

        $this->gradeName = $gradeName;

        return $this;
    }
    /**
     * @return string
     */
    public function getImgUrl()
    {
        return $this->imgUrl;
    }

    /**
     * @param string $imgUrl
     * @return ResponseGetVipInfo
     */
    public function setImgUrl($imgUrl)
    {
        \Assert\Assertion::string($imgUrl);

        $this->imgUrl = $imgUrl;

        return $this;
    }
    /**
     * @return int
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param int $point
     * @return ResponseGetVipInfo
     */
    public function setPoint($point)
    {
        \Assert\Assertion::integer($point);

        $this->point = $point;

        return $this;
    }
    /**
     * @return string
     */
    public function getFirstDesc()
    {
        return $this->firstDesc;
    }

    /**
     * @param string $firstDesc
     * @return ResponseGetVipInfo
     */
    public function setFirstDesc($firstDesc)
    {
        \Assert\Assertion::string($firstDesc);

        $this->firstDesc = $firstDesc;

        return $this;
    }
    /**
     * @return string
     */
    public function getSecondDesc()
    {
        return $this->secondDesc;
    }

    /**
     * @param string $secondDesc
     * @return ResponseGetVipInfo
     */
    public function setSecondDesc($secondDesc)
    {
        \Assert\Assertion::string($secondDesc);

        $this->secondDesc = $secondDesc;

        return $this;
    }

}