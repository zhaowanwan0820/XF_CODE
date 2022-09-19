<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserFocus extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $focus_user_id;


    /**
     *
     * @var integer
     */
    public $focused_user_id;


    /**
     *
     * @var string
     */
    public $focus_user_name;


    /**
     *
     * @var string
     */
    public $focused_user_name;

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
            'focus_user_id' => 'focusUserId',
            'focused_user_id' => 'focusedUserId',
            'focus_user_name' => 'focusUserName',
            'focused_user_name' => 'focusedUserName',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_focus";
    }
}