<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 合作方表单
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class ResponseGetPartnerInterface extends ResponseBase
{
    /**
     * id
     *
     * @var string
     * @required
     */
    private $id;

    /**
     * 合作方标识
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * 公共配置
     *
     * @var string
     * @required
     */
    private $commonConf;

    /**
     * 短信配置
     *
     * @var string
     * @required
     */
    private $mapConf;

    /**
     * 接口描述
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return ResponseGetPartnerInterface
     */
    public function setId($id)
    {
        \Assert\Assertion::string($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return ResponseGetPartnerInterface
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }
    /**
     * @return string
     */
    public function getCommonConf()
    {
        return $this->commonConf;
    }

    /**
     * @param string $commonConf
     * @return ResponseGetPartnerInterface
     */
    public function setCommonConf($commonConf)
    {
        \Assert\Assertion::string($commonConf);

        $this->commonConf = $commonConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getMapConf()
    {
        return $this->mapConf;
    }

    /**
     * @param string $mapConf
     * @return ResponseGetPartnerInterface
     */
    public function setMapConf($mapConf)
    {
        \Assert\Assertion::string($mapConf);

        $this->mapConf = $mapConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return ResponseGetPartnerInterface
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }

}