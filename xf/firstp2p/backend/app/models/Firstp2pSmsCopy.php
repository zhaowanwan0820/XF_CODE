<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pSmsCopy extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var string
     */
    public $class_name;


    /**
     *
     * @var string
     */
    public $server_url;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var string
     */
    public $password;


    /**
     *
     * @var string
     */
    public $config;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $type;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->type = '0';
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
            'name' => 'name',
            'description' => 'description',
            'class_name' => 'className',
            'server_url' => 'serverUrl',
            'user_name' => 'userName',
            'password' => 'password',
            'config' => 'config',
            'is_effect' => 'isEffect',
            'type' => 'type',
        );
    }

    public function getSource()
    {
        return "firstp2p_sms_copy";
    }
}