<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pLogRegLogin extends ModelBaseNoTime
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
    public $ip;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var datetime
     */
    public $time;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $ua;


    /**
     *
     * @var string
     */
    public $referer;


    /**
     *
     * @var integer
     */
    public $is_register;


    /**
     *
     * @var integer
     */
    public $is_login;


    /**
     *
     * @var integer
     */
    public $from_platform;


    /**
     *
     * @var string
     */
    public $invitation_code;


    /**
     *
     * @var integer
     */
    public $time_stamp;

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
            'ip' => 'ip',
            'user_name' => 'userName',
            'time' => 'time',
            'user_id' => 'userId',
            'ua' => 'ua',
            'referer' => 'referer',
            'is_register' => 'isRegister',
            'is_login' => 'isLogin',
            'from_platform' => 'fromPlatform',
            'invitation_code' => 'invitationCode',
            'time_stamp' => 'timeStamp',
        );
    }

    public function getSource()
    {
        return "firstp2p_log_reg_login";
    }
}