<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 展示合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestShowContract extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $user_id;

    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $deal_id;

    /**
     * 机构ID
     *
     * @var int
     * @required
     */
    private $agency_id;

    /**
     * 投资ID
     *
     * @var int
     * @required
     */
    private $deal_load_id;

    /**
     * @return int
     */
    public function getUser_id()
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return RequestShowContract
     */
    public function setUser_id($user_id)
    {
        \Assert\Assertion::integer($user_id);

        $this->user_id = $user_id;

        return $this;
    }
    /**
     * @return int
     */
    public function getDeal_id()
    {
        return $this->deal_id;
    }

    /**
     * @param int $deal_id
     * @return RequestShowContract
     */
    public function setDeal_id($deal_id)
    {
        \Assert\Assertion::integer($deal_id);

        $this->deal_id = $deal_id;

        return $this;
    }
    /**
     * @return int
     */
    public function getAgency_id()
    {
        return $this->agency_id;
    }

    /**
     * @param int $agency_id
     * @return RequestShowContract
     */
    public function setAgency_id($agency_id)
    {
        \Assert\Assertion::integer($agency_id);

        $this->agency_id = $agency_id;

        return $this;
    }
    /**
     * @return int
     */
    public function getDeal_load_id()
    {
        return $this->deal_load_id;
    }

    /**
     * @param int $deal_load_id
     * @return RequestShowContract
     */
    public function setDeal_load_id($deal_load_id)
    {
        \Assert\Assertion::integer($deal_load_id);

        $this->deal_load_id = $deal_load_id;

        return $this;
    }

}