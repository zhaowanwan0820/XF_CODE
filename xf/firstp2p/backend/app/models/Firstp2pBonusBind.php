<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusBind extends ModelBaseNoTime
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
    public $openid;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $delete_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->status = '1';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->deleteTime = '0';
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
            'openid' => 'openid',
            'mobile' => 'mobile',
            'status' => 'status',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'delete_time' => 'deleteTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_bind";
    }
}