<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pTopic extends ModelBaseNoTime
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
    public $fav_id;


    /**
     *
     * @var string
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var integer
     */
    public $l_user_id;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $create_time;

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
            'fav_id' => 'favId',
            'type' => 'type',
            'user_id' => 'userId',
            'user_name' => 'userName',
            'l_user_id' => 'lUserId',
            'is_effect' => 'isEffect',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_topic";
    }
}