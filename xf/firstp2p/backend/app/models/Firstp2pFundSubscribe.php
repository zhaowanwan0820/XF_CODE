<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pFundSubscribe extends ModelBaseNoTime
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
    public $fund_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $realname;


    /**
     *
     * @var integer
     */
    public $sex;


    /**
     *
     * @var string
     */
    public $phone;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var string
     */
    public $comment;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $platform;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->sex = '0';
        $this->platform = '0';
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
            'fund_id' => 'fundId',
            'user_id' => 'userId',
            'realname' => 'realname',
            'sex' => 'sex',
            'phone' => 'phone',
            'money' => 'money',
            'comment' => 'comment',
            'create_time' => 'createTime',
            'platform' => 'platform',
        );
    }

    public function getSource()
    {
        return "firstp2p_fund_subscribe";
    }
}