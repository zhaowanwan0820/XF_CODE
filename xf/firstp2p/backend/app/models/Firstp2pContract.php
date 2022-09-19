<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pContract extends ModelBaseNoTime
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
    public $title;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var string
     */
    public $number;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $is_send;


    /**
     *
     * @var integer
     */
    public $agency_id;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $is_needsign;


    /**
     *
     * @var integer
     */
    public $attach_id;


    /**
     *
     * @var integer
     */
    public $sign_time;


    /**
     *
     * @var integer
     */
    public $resign_status;


    /**
     *
     * @var integer
     */
    public $resign_time;


    /**
     *
     * @var integer
     */
    public $deal_load_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->title = '';
        $this->type = '0';
        $this->number = '';
        $this->userId = '0';
        $this->dealId = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->isSend = '0';
        $this->status = '0';
        $this->isNeedsign = '0';
        $this->attachId = '0';
        $this->signTime = '0';
        $this->resignStatus = '0';
        $this->resignTime = '0';
        $this->dealLoadId = '0';
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
            'title' => 'title',
            'type' => 'type',
            'number' => 'number',
            'user_id' => 'userId',
            'deal_id' => 'dealId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'is_send' => 'isSend',
            'agency_id' => 'agencyId',
            'status' => 'status',
            'is_needsign' => 'isNeedsign',
            'attach_id' => 'attachId',
            'sign_time' => 'signTime',
            'resign_status' => 'resignStatus',
            'resign_time' => 'resignTime',
            'deal_load_id' => 'dealLoadId',
        );
    }

    public function getSource()
    {
        return "firstp2p_contract";
    }
}