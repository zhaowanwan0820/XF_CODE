<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pP2bDeal extends ModelBaseNoTime
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
    public $title;


    /**
     *
     * @var float
     */
    public $borrow_amount;


    /**
     *
     * @var string
     */
    public $repay_type;


    /**
     *
     * @var string
     */
    public $repay_period;


    /**
     *
     * @var float
     */
    public $rate;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $accept_user_id;


    /**
     *
     * @var integer
     */
    public $accept_time;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->title = '';
        $this->borrowAmount = '0';
        $this->repayType = '';
        $this->rate = '0';
        $this->userId = '0';
        $this->acceptUserId = '0';
        $this->acceptTime = '0';
        $this->createTime = '0';
        $this->status = '0';
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
            'title' => 'title',
            'borrow_amount' => 'borrowAmount',
            'repay_type' => 'repayType',
            'repay_period' => 'repayPeriod',
            'rate' => 'rate',
            'description' => 'description',
            'user_id' => 'userId',
            'accept_user_id' => 'acceptUserId',
            'accept_time' => 'acceptTime',
            'create_time' => 'createTime',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_p2b_deal";
    }
}