<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBankCharge extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var string
     */
    public $value;


    /**
     *
     * @var integer
     */
    public $img;


    /**
     *
     * @var integer
     */
    public $faster;


    /**
     *
     * @var integer
     */
    public $auxiliary_id;


    /**
     *
     * @var integer
     */
    public $admin_id;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var string
     */
    public $short_name;


    /**
     *
     * @var integer
     */
    public $payment_id;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->value = '';
        $this->img = '0';
        $this->faster = '0';
        $this->auxiliaryId = '0';
        $this->adminId = '0';
        $this->updateTime = '0';
        $this->shortName = '';
        $this->paymentId = '0';
        $this->status = '0';
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
            'name' => 'name',
            'value' => 'value',
            'img' => 'img',
            'faster' => 'faster',
            'auxiliary_id' => 'auxiliaryId',
            'admin_id' => 'adminId',
            'update_time' => 'updateTime',
            'short_name' => 'shortName',
            'payment_id' => 'paymentId',
            'status' => 'status',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_bank_charge";
    }
}