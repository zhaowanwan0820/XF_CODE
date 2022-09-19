<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 签署前置合同-临时表
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestSignBeforeBorrowContract extends ProtoBufferBase
{
    /**
     * 合同ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 签署时间
     *
     * @var int
     * @required
     */
    private $borrowerSignTime;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestSignBeforeBorrowContract
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
    public function getBorrowerSignTime()
    {
        return $this->borrowerSignTime;
    }

    /**
     * @param int $borrowerSignTime
     * @return RequestSignBeforeBorrowContract
     */
    public function setBorrowerSignTime($borrowerSignTime)
    {
        \Assert\Assertion::integer($borrowerSignTime);

        $this->borrowerSignTime = $borrowerSignTime;

        return $this;
    }

}