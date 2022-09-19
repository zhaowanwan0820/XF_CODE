<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPreDealAuditCopy extends ModelBaseNoTime
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
    public $auser;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var string
     */
    public $log;


    /**
     *
     * @var integer
     */
    public $pic;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var string
     */
    public $note;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->auser = '';
        $this->dealId = '0';
        $this->pic = '0';
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
            'auser' => 'auser',
            'deal_id' => 'dealId',
            'log' => 'log',
            'pic' => 'pic',
            'create_time' => 'createTime',
            'note' => 'note',
        );
    }

    public function getSource()
    {
        return "firstp2p_pre_deal_audit_copy";
    }
}