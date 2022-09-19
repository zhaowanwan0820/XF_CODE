<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMailServer extends ModelBaseNoTime
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
    public $smtp_server;


    /**
     *
     * @var string
     */
    public $smtp_name;


    /**
     *
     * @var string
     */
    public $smtp_pwd;


    /**
     *
     * @var integer
     */
    public $is_ssl;


    /**
     *
     * @var string
     */
    public $smtp_port;


    /**
     *
     * @var integer
     */
    public $use_limit;


    /**
     *
     * @var integer
     */
    public $is_reset;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $total_use;


    /**
     *
     * @var integer
     */
    public $is_verify;

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
            'smtp_server' => 'smtpServer',
            'smtp_name' => 'smtpName',
            'smtp_pwd' => 'smtpPwd',
            'is_ssl' => 'isSsl',
            'smtp_port' => 'smtpPort',
            'use_limit' => 'useLimit',
            'is_reset' => 'isReset',
            'is_effect' => 'isEffect',
            'total_use' => 'totalUse',
            'is_verify' => 'isVerify',
        );
    }

    public function getSource()
    {
        return "firstp2p_mail_server";
    }
}