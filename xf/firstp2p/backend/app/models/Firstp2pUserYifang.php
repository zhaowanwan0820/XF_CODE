<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserYifang extends ModelBaseNoTime
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
    public $user_name;


    /**
     *
     * @var string
     */
    public $real_name;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var integer
     */
    public $old_groupid;


    /**
     *
     * @var integer
     */
    public $old_levelid;


    /**
     *
     * @var integer
     */
    public $new_groupid;


    /**
     *
     * @var integer
     */
    public $new_levelid;


    /**
     *
     * @var date
     */
    public $update_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userName = '';
        $this->realName = '';
        $this->mobile = '';
        $this->oldGroupid = '0';
        $this->oldLevelid = '0';
        $this->newGroupid = '0';
        $this->newLevelid = '0';
        $this->updateTime = XDateTime::now();
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
            'user_name' => 'userName',
            'real_name' => 'realName',
            'mobile' => 'mobile',
            'old_groupid' => 'oldGroupid',
            'old_levelid' => 'oldLevelid',
            'new_groupid' => 'newGroupid',
            'new_levelid' => 'newLevelid',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_yifang";
    }
}