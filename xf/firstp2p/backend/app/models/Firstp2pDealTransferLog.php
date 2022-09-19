<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealTransferLog extends ModelBaseNoTime
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
    public $ownerid;


    /**
     *
     * @var string
     */
    public $ownertype;


    /**
     *
     * @var integer
     */
    public $fromuserid;


    /**
     *
     * @var integer
     */
    public $touserid;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var date
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->createTime = '0000-00-00 00:00:00';
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
            'ownerid' => 'ownerid',
            'ownertype' => 'ownertype',
            'fromuserid' => 'fromuserid',
            'touserid' => 'touserid',
            'money' => 'money',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_transfer_log";
    }
}