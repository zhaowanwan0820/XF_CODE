<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 进度查询
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetSchedule extends ResponseBase
{
    /**
     * 身份证名称
     *
     * @var string
     * @optional
     */
    private $customerName = '';

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $idCardNumber = '';

    /**
     * 沪A
     *
     * @var int
     * @optional
     */
    private $stockSh = 0;

    /**
     * 深A
     *
     * @var int
     * @optional
     */
    private $stockSz = 0;

    /**
     * 银行
     *
     * @var string
     * @optional
     */
    private $bankName = '';

    /**
     * 银行卡
     *
     * @var string
     * @optional
     */
    private $bankCard = '';

    /**
     * 状态
     *
     * @var int
     * @optional
     */
    private $status = 0;

    /**
     * 银行状态
     *
     * @var int
     * @optional
     */
    private $bankStatus = 0;

    /**
     * 客户号
     *
     * @var string
     * @optional
     */
    private $account = '';

    /**
     * 提示信息
     *
     * @var string
     * @optional
     */
    private $message = '';

    /**
     * 上基
     *
     * @var int
     * @optional
     */
    private $fundSh = 0;

    /**
     * 深基
     *
     * @var int
     * @optional
     */
    private $fundSz = 0;

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return ResponseGetSchedule
     */
    public function setCustomerName($customerName = '')
    {
        $this->customerName = $customerName;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardNumber()
    {
        return $this->idCardNumber;
    }

    /**
     * @param string $idCardNumber
     * @return ResponseGetSchedule
     */
    public function setIdCardNumber($idCardNumber = '')
    {
        $this->idCardNumber = $idCardNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getStockSh()
    {
        return $this->stockSh;
    }

    /**
     * @param int $stockSh
     * @return ResponseGetSchedule
     */
    public function setStockSh($stockSh = 0)
    {
        $this->stockSh = $stockSh;

        return $this;
    }
    /**
     * @return int
     */
    public function getStockSz()
    {
        return $this->stockSz;
    }

    /**
     * @param int $stockSz
     * @return ResponseGetSchedule
     */
    public function setStockSz($stockSz = 0)
    {
        $this->stockSz = $stockSz;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * @param string $bankName
     * @return ResponseGetSchedule
     */
    public function setBankName($bankName = '')
    {
        $this->bankName = $bankName;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankCard()
    {
        return $this->bankCard;
    }

    /**
     * @param string $bankCard
     * @return ResponseGetSchedule
     */
    public function setBankCard($bankCard = '')
    {
        $this->bankCard = $bankCard;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ResponseGetSchedule
     */
    public function setStatus($status = 0)
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getBankStatus()
    {
        return $this->bankStatus;
    }

    /**
     * @param int $bankStatus
     * @return ResponseGetSchedule
     */
    public function setBankStatus($bankStatus = 0)
    {
        $this->bankStatus = $bankStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param string $account
     * @return ResponseGetSchedule
     */
    public function setAccount($account = '')
    {
        $this->account = $account;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ResponseGetSchedule
     */
    public function setMessage($message = '')
    {
        $this->message = $message;

        return $this;
    }
    /**
     * @return int
     */
    public function getFundSh()
    {
        return $this->fundSh;
    }

    /**
     * @param int $fundSh
     * @return ResponseGetSchedule
     */
    public function setFundSh($fundSh = 0)
    {
        $this->fundSh = $fundSh;

        return $this;
    }
    /**
     * @return int
     */
    public function getFundSz()
    {
        return $this->fundSz;
    }

    /**
     * @param int $fundSz
     * @return ResponseGetSchedule
     */
    public function setFundSz($fundSz = 0)
    {
        $this->fundSz = $fundSz;

        return $this;
    }

}