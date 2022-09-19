<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pActivityIphone extends ModelBaseNoTime
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
    public $user_lottery_num;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var integer
     */
    public $is_win;


    /**
     *
     * @var integer
     */
    public $deal_time;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $stat_time;

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
            'user_lottery_num' => 'userLotteryNum',
            'user_id' => 'userId',
            'user_name' => 'userName',
            'is_win' => 'isWin',
            'deal_time' => 'dealTime',
            'create_time' => 'createTime',
            'stat_time' => 'statTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_activity_iphone";
    }
}