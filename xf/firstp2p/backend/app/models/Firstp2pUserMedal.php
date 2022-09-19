<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserMedal extends ModelBaseNoTime
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
    public $user_id;


    /**
     *
     * @var integer
     */
    public $medal_id;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var string
     */
    public $icon;

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
            'user_id' => 'userId',
            'medal_id' => 'medalId',
            'name' => 'name',
            'create_time' => 'createTime',
            'is_delete' => 'isDelete',
            'icon' => 'icon',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_medal";
    }
}