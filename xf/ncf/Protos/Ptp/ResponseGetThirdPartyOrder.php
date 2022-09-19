<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 第三方交互-单笔订单查询接口返回定义
 *
 * 由代码生成器生成, 不可人为修改
 * @author 郭峰 <guofeng3@ucfgroup.com>
 */
class ResponseGetThirdPartyOrder extends ProtoBufferBase
{
    /**
     * 交易总笔数
     *
     * @var int
     * @required
     */
    private $tradeCount;

    /**
     * 交易总金额
     *
     * @var string
     * @required
     */
    private $tradeSum;

    /**
     * 交易列表
     *
     * @var string
     * @required
     */
    private $tradeList;

    /**
     * 当前页号
     *
     * @var int
     * @required
     */
    private $pageNo;

    /**
     * 消息提示码
     *
     * @var string
     * @required
     */
    private $respCode;

    /**
     * 消息提示信息
     *
     * @var string
     * @optional
     */
    private $respMsg = '';

    /**
     * @return int
     */
    public function getTradeCount()
    {
        return $this->tradeCount;
    }

    /**
     * @param int $tradeCount
     * @return ResponseGetThirdPartyOrder
     */
    public function setTradeCount($tradeCount)
    {
        \Assert\Assertion::integer($tradeCount);

        $this->tradeCount = $tradeCount;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeSum()
    {
        return $this->tradeSum;
    }

    /**
     * @param string $tradeSum
     * @return ResponseGetThirdPartyOrder
     */
    public function setTradeSum($tradeSum)
    {
        \Assert\Assertion::string($tradeSum);

        $this->tradeSum = $tradeSum;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeList()
    {
        return $this->tradeList;
    }

    /**
     * @param string $tradeList
     * @return ResponseGetThirdPartyOrder
     */
    public function setTradeList($tradeList)
    {
        \Assert\Assertion::string($tradeList);

        $this->tradeList = $tradeList;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return ResponseGetThirdPartyOrder
     */
    public function setPageNo($pageNo)
    {
        \Assert\Assertion::integer($pageNo);

        $this->pageNo = $pageNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespCode()
    {
        return $this->respCode;
    }

    /**
     * @param string $respCode
     * @return ResponseGetThirdPartyOrder
     */
    public function setRespCode($respCode)
    {
        \Assert\Assertion::string($respCode);

        $this->respCode = $respCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespMsg()
    {
        return $this->respMsg;
    }

    /**
     * @param string $respMsg
     * @return ResponseGetThirdPartyOrder
     */
    public function setRespMsg($respMsg = '')
    {
        $this->respMsg = $respMsg;

        return $this;
    }

}