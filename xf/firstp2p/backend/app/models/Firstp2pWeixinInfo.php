<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pWeixinInfo extends ModelBaseNoTime
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
    public $token_info;


    /**
     *
     * @var string
     */
    public $user_info;


    /**
     *
     * @var integer
     */
    public $user_id;


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
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
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
            'token_info' => 'tokenInfo',
            'user_info' => 'userInfo',
            'user_id' => 'userId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_weixin_info";
    }
}