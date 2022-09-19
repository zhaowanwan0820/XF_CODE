<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMoneyApply extends ModelBaseNoTime
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
    public $parent_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $admin_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $orderid;


    /**
     *
     * @var string
     */
    public $note;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->parentId = '0';
        $this->status = '0';
        $this->orderid = '';
        $this->note = '';
        $this->createTime = '0';
        $this->updateTime = '0';
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
            'parent_id' => 'parentId',
            'money' => 'money',
            'admin_id' => 'adminId',
            'user_id' => 'userId',
            'type' => 'type',
            'time' => 'time',
            'status' => 'status',
            'orderid' => 'orderid',
            'note' => 'note',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_money_apply";
    }
}