<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserFeedback extends ModelBaseNoTime
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
     * @var string
     */
    public $content;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $sysver;


    /**
     *
     * @var string
     */
    public $softver;


    /**
     *
     * @var string
     */
    public $models;


    /**
     *
     * @var string
     */
    public $imei;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->mobile = '';
        $this->sysver = '';
        $this->softver = '';
        $this->models = '';
        $this->imei = '';
        $this->createTime = '0';
        $this->isDelete = '0';
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
            'content' => 'content',
            'mobile' => 'mobile',
            'sysver' => 'sysver',
            'softver' => 'softver',
            'models' => 'models',
            'imei' => 'imei',
            'create_time' => 'createTime',
            'is_delete' => 'isDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_feedback";
    }
}