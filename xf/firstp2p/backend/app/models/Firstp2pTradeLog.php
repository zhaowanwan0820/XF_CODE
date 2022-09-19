<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pTradeLog extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $bCode;


    /**
     *
     * @var string
     */
    public $merchantNo;


    /**
     *
     * @var integer
     */
    public $merchantId;


    /**
     *
     * @var string
     */
    public $outOrderId;


    /**
     *
     * @var string
     */
    public $orderStatus;


    /**
     *
     * @var integer
     */
    public $billId;


    /**
     *
     * @var integer
     */
    public $createTime;


    /**
     *
     * @var integer
     */
    public $updateTime;


    /**
     *
     * @var integer
     */
    public $payerId;


    /**
     *
     * @var integer
     */
    public $amount;

    //END PROPERTY

    /**
     * 业务代码-第三方投资业务
     * @var string
     */
    const BUSINESS_CODE_THIRDPARTY_INVEST = 'A1';

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->bCode = '';
        $this->merchantNo = '';
        $this->merchantId = '0';
        $this->outOrderId = '';
        $this->billId = '0';
        $this->payerId = '0';
        $this->amount = '0';
        //END DEFAULT_VALUE
    }

    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('firstp2p_r');
        $this->setWriteConnectionService('firstp2p');
    }

    public function columnMap()
    {
        return array(
            'id' => 'id',
            'bCode' => 'bCode',
            'merchantNo' => 'merchantNo',
            'merchantId' => 'merchantId',
            'outOrderId' => 'outOrderId',
            'orderStatus' => 'orderStatus',
            'billId' => 'billId',
            'createTime' => 'createTime',
            'updateTime' => 'updateTime',
            'payerId' => 'payerId',
            'amount' => 'amount',
        );
    }

    public function getSource()
    {
        return "firstp2p_trade_log";
    }
}