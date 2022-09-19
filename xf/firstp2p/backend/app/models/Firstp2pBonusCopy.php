<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusCopy extends ModelBaseNoTime
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
    public $group_id;


    /**
     *
     * @var integer
     */
    public $sender_uid;


    /**
     *
     * @var integer
     */
    public $owner_uid;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $openid;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $created_at;


    /**
     *
     * @var integer
     */
    public $expired_at;


    /**
     *
     * @var integer
     */
    public $rebate_status;


    /**
     *
     * @var string
     */
    public $refer_mobile;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $task_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->groupId = '0';
        $this->senderUid = '0';
        $this->ownerUid = '0';
        $this->mobile = '';
        $this->rebateStatus = '0';
        $this->type = '0';
        $this->taskId = '0';
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
            'group_id' => 'groupId',
            'sender_uid' => 'senderUid',
            'owner_uid' => 'ownerUid',
            'mobile' => 'mobile',
            'openid' => 'openid',
            'status' => 'status',
            'money' => 'money',
            'created_at' => 'createdAt',
            'expired_at' => 'expiredAt',
            'rebate_status' => 'rebateStatus',
            'refer_mobile' => 'referMobile',
            'type' => 'type',
            'task_id' => 'taskId',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_copy";
    }
}