<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * OpenAppInfo
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoOpenAppInfo extends ProtoBufferBase
{
    /**
     * client_id
     *
     * @var string
     * @required
     */
    private $client_id;

    /**
     * 是否查询站点邀请码用户ID
     *
     * @var int
     * @optional
     */
    private $need_invite = 0;

    /**
     * @return string
     */
    public function getClient_id()
    {
        return $this->client_id;
    }

    /**
     * @param string $client_id
     * @return ProtoOpenAppInfo
     */
    public function setClient_id($client_id)
    {
        \Assert\Assertion::string($client_id);

        $this->client_id = $client_id;

        return $this;
    }
    /**
     * @return int
     */
    public function getNeed_invite()
    {
        return $this->need_invite;
    }

    /**
     * @param int $need_invite
     * @return ProtoOpenAppInfo
     */
    public function setNeed_invite($need_invite = 0)
    {
        $this->need_invite = $need_invite;

        return $this;
    }

}