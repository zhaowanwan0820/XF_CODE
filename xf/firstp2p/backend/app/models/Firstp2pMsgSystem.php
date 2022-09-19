<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMsgSystem extends ModelBaseNoTime
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
     * @var string
     */
    public $user_names;


    /**
     *
     * @var string
     */
    public $user_ids;


    /**
     *
     * @var integer
     */
    public $end_time;

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
            'user_names' => 'userNames',
            'user_ids' => 'userIds',
            'end_time' => 'endTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_msg_system";
    }
}