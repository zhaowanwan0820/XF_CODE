<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealQueueInfo extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var integer
     */
    public $queue_id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $sort_num;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->queueId = '0';
        $this->dealId = '0';
        $this->sortNum = '0';
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
            'queue_id' => 'queueId',
            'deal_id' => 'dealId',
            'sort_num' => 'sortNum',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_queue_info";
    }
}