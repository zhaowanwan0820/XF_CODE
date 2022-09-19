<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdmin extends ModelBaseNoTime
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
    public $adm_name;


    /**
     *
     * @var string
     */
    public $adm_password;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $role_id;


    /**
     *
     * @var integer
     */
    public $login_time;


    /**
     *
     * @var string
     */
    public $login_ip;

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
            'adm_name' => 'admName',
            'adm_password' => 'admPassword',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'role_id' => 'roleId',
            'login_time' => 'loginTime',
            'login_ip' => 'loginIp',
        );
    }

    public function getSource()
    {
        return "firstp2p_admin";
    }
}