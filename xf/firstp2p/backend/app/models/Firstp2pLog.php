<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pLog extends ModelBaseNoTime
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
    public $log_info;


    /**
     *
     * @var integer
     */
    public $log_time;


    /**
     *
     * @var integer
     */
    public $log_admin;


    /**
     *
     * @var string
     */
    public $log_ip;


    /**
     *
     * @var integer
     */
    public $log_status;


    /**
     *
     * @var string
     */
    public $module;


    /**
     *
     * @var string
     */
    public $action;


    /**
     *
     * @var string
     */
    public $extra_info;


    /**
     *
     * @var string
     */
    public $new_info;

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
            'log_info' => 'logInfo',
            'log_time' => 'logTime',
            'log_admin' => 'logAdmin',
            'log_ip' => 'logIp',
            'log_status' => 'logStatus',
            'module' => 'module',
            'action' => 'action',
            'extra_info' => 'extraInfo',
            'new_info' => 'newInfo',
        );
    }

    public function getSource()
    {
        return "firstp2p_log";
    }
}