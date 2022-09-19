<?php
namespace NCFGroup\Protos\Ptp;

use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 查看合同协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequstDealContractpre extends AbstractRequestBase
{
    /**
     * 投资金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 合同类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 借款id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequstDealContractpre
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequstDealContractpre
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequstDealContractpre
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequstDealContractpre
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}