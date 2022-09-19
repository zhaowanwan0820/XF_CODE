<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获得地区银行列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestRegionBankList extends ProtoBufferBase
{
    /**
     * 城市
     *
     * @var string
     * @required
     */
    private $city;

    /**
     * 省份
     *
     * @var string
     * @optional
     */
    private $province = '';

    /**
     * 银行
     *
     * @var string
     * @required
     */
    private $bank;

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return RequestRegionBankList
     */
    public function setCity($city)
    {
        \Assert\Assertion::string($city);

        $this->city = $city;

        return $this;
    }
    /**
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param string $province
     * @return RequestRegionBankList
     */
    public function setProvince($province = '')
    {
        $this->province = $province;

        return $this;
    }
    /**
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param string $bank
     * @return RequestRegionBankList
     */
    public function setBank($bank)
    {
        \Assert\Assertion::string($bank);

        $this->bank = $bank;

        return $this;
    }

}