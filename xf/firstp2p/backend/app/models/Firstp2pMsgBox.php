<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMsgBox extends ModelBaseNoTime
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
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $from_user_id;


    /**
     *
     * @var integer
     */
    public $to_user_id;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_read;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $system_msg_id;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var string
     */
    public $group_key;


    /**
     *
     * @var integer
     */
    public $is_notice;


    /**
     *
     * @var integer
     */
    public $read_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->readTime = '0';
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
            'content' => 'content',
            'from_user_id' => 'fromUserId',
            'to_user_id' => 'toUserId',
            'create_time' => 'createTime',
            'is_read' => 'isRead',
            'is_delete' => 'isDelete',
            'system_msg_id' => 'systemMsgId',
            'type' => 'type',
            'group_key' => 'groupKey',
            'is_notice' => 'isNotice',
            'read_time' => 'readTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_msg_box";
    }
}