<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pRegisterBatchLog extends ModelBaseNoTime
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
    public $user;


    /**
     *
     * @var string
     */
    public $passwd;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $email;


    /**
     *
     * @var integer
     */
    public $idtype;


    /**
     *
     * @var string
     */
    public $idno;


    /**
     *
     * @var string
     */
    public $bank;


    /**
     *
     * @var string
     */
    public $branch;


    /**
     *
     * @var string
     */
    public $acno;


    /**
     *
     * @var integer
     */
    public $group_id;


    /**
     *
     * @var string
     */
    public $transfer_ac;


    /**
     *
     * @var float
     */
    public $transfer_money;


    /**
     *
     * @var string
     */
    public $transfer_comment;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $batchno;


    /**
     *
     * @var integer
     */
    public $payment_user_id;


    /**
     *
     * @var date
     */
    public $created_at;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->user = '';
        $this->passwd = '';
        $this->mobile = '';
        $this->name = '';
        $this->email = '';
        $this->idtype = '1';
        $this->idno = '';
        $this->bank = '';
        $this->branch = '';
        $this->acno = '';
        $this->groupId = '0';
        $this->transferAc = '';
        $this->transferMoney = '0.00';
        $this->transferComment = '';
        $this->status = '1000';
        $this->batchno = '0';
        $this->paymentUserId = '0';
        $this->createdAt = XDateTime::now();
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
            'user' => 'user',
            'passwd' => 'passwd',
            'mobile' => 'mobile',
            'name' => 'name',
            'email' => 'email',
            'idtype' => 'idtype',
            'idno' => 'idno',
            'bank' => 'bank',
            'branch' => 'branch',
            'acno' => 'acno',
            'group_id' => 'groupId',
            'transfer_ac' => 'transferAc',
            'transfer_money' => 'transferMoney',
            'transfer_comment' => 'transferComment',
            'status' => 'status',
            'batchno' => 'batchno',
            'payment_user_id' => 'paymentUserId',
            'created_at' => 'createdAt',
        );
    }

    public function getSource()
    {
        return "firstp2p_register_batch_log";
    }
}