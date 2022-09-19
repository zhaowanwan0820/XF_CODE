<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMessage extends ModelBaseNoTime
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
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var string
     */
    public $admin_reply;


    /**
     *
     * @var integer
     */
    public $admin_id;


    /**
     *
     * @var string
     */
    public $rel_table;


    /**
     *
     * @var integer
     */
    public $rel_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $pid;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_buy;

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
            'title' => 'title',
            'content' => 'content',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'admin_reply' => 'adminReply',
            'admin_id' => 'adminId',
            'rel_table' => 'relTable',
            'rel_id' => 'relId',
            'user_id' => 'userId',
            'pid' => 'pid',
            'is_effect' => 'isEffect',
            'is_buy' => 'isBuy',
        );
    }

    public function getSource()
    {
        return "firstp2p_message";
    }
}