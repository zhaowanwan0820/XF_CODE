<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCashPresent extends ModelBaseNoTime
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
     * @var integer
     */
    public $amount;


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
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var string
     */
    public $bank_short_name;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->amount = '0';
        $this->realName = '';
        $this->mobile = '';
        $this->bankcard = '';
        $this->bankShortName = '';
        $this->status = '0';
        $this->updateTime = '0';
        $this->createTime = '0';
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
            'amount' => 'amount',
            'real_name' => 'realName',
            'mobile' => 'mobile',
            'bankcard' => 'bankcard',
            'bank_short_name' => 'bankShortName',
            'status' => 'status',
            'update_time' => 'updateTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_cash_present";
    }
}