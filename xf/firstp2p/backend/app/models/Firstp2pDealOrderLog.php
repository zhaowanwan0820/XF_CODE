<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealOrderLog extends ModelBaseNoTime
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
    public $log_info;


    /**
     *
     * @var integer
     */
    public $log_time;


    /**
     *
     * @var integer
     */
    public $order_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

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
            'log_info' => 'logInfo',
            'log_time' => 'logTime',
            'order_id' => 'orderId',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_order_log";
    }
}