<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class PushUser extends ModelBaseNoTime
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
    public $appId;


    /**
     *
     * @var integer
     */
    public $appUserId;


    /**
     *
     * @var string
     */
    public $appVersion;


    /**
     *
     * @var string
     */
    public $baiduUserId;


    /**
     *
     * @var string
     */
    public $baiduChannelId;


    /**
     *
     * @var integer
     */
    public $osType;


    /**
     *
     * @var string
     */
    public $osVersion;


    /**
     *
     * @var string
     */
    public $model;


    /**
     *
     * @var string
     */
    public $apnsToken;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $createtime;


    /**
     *
     * @var integer
     */
    public $updatetime;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->appId = '0';
        $this->appUserId = '0';
        $this->appVersion = '';
        $this->baiduUserId = '';
        $this->baiduChannelId = '';
        $this->osType = '0';
        $this->osVersion = '';
        $this->model = '';
        $this->apnsToken = '';
        $this->status = '0';
        $this->createtime = '0';
        $this->updatetime = '0';
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
            'appId' => 'appId',
            'appUserId' => 'appUserId',
            'appVersion' => 'appVersion',
            'baiduUserId' => 'baiduUserId',
            'baiduChannelId' => 'baiduChannelId',
            'osType' => 'osType',
            'osVersion' => 'osVersion',
            'model' => 'model',
            'apnsToken' => 'apnsToken',
            'status' => 'status',
            'createtime' => 'createtime',
            'updatetime' => 'updatetime',
        );
    }

    public function getSource()
    {
        return "push_user";
    }
}