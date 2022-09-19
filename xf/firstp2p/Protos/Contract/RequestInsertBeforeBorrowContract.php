<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 新增前置合同记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestInsertBeforeBorrowContract extends ProtoBufferBase
{
    /**
     * 放款审批单号
     *
     * @var string
     * @required
     */
    private $approveNumber;

    /**
     * 分类ID
     *
     * @var int
     * @required
     */
    private $categoryId;

    /**
     * 借款人用户ID
     *
     * @var int
     * @required
     */
    private $borrowUserId;

    /**
     * 用于渲染合同的变量(json化)
     *
     * @var string
     * @required
     */
    private $params;

    /**
     * @return string
     */
    public function getApproveNumber()
    {
        return $this->approveNumber;
    }

    /**
     * @param string $approveNumber
     * @return RequestInsertBeforeBorrowContract
     */
    public function setApproveNumber($approveNumber)
    {
        \Assert\Assertion::string($approveNumber);

        $this->approveNumber = $approveNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return RequestInsertBeforeBorrowContract
     */
    public function setCategoryId($categoryId)
    {
        \Assert\Assertion::integer($categoryId);

        $this->categoryId = $categoryId;

        return $this;
    }
    /**
     * @return int
     */
    public function getBorrowUserId()
    {
        return $this->borrowUserId;
    }

    /**
     * @param int $borrowUserId
     * @return RequestInsertBeforeBorrowContract
     */
    public function setBorrowUserId($borrowUserId)
    {
        \Assert\Assertion::integer($borrowUserId);

        $this->borrowUserId = $borrowUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $params
     * @return RequestInsertBeforeBorrowContract
     */
    public function setParams($params)
    {
        \Assert\Assertion::string($params);

        $this->params = $params;

        return $this;
    }

}