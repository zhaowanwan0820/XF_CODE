<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * gearman jobserver 总信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestJobServerInfo extends AbstractRequestBase
{
    /**
     * 查查询的job server ip
     *
     * @var string
     * @required
     */
    private $ip;

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return RequestJobServerInfo
     */
    public function setIp($ip)
    {
        \Assert\Assertion::string($ip);

        $this->ip = $ip;

        return $this;
    }

}