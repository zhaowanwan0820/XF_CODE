<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserBankcardAudit extends ModelBaseNoTime
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
    public $bank_id;


    /**
     *
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var string
     */
    public $bankzone;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $card_name;


    /**
     *
     * @var integer
     */
    public $card_type;


    /**
     *
     * @var string
     */
    public $admin;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $audit_time;


    /**
     *
     * @var integer
     */
    public $region_lv1;


    /**
     *
     * @var integer
     */
    public $region_lv2;


    /**
     *
     * @var integer
     */
    public $region_lv3;


    /**
     *
     * @var integer
     */
    public $region_lv4;


    /**
     *
     * @var integer
     */
    public $user_bank_id;


    /**
     *
     * @var integer
     */
    public $image_id;


    /**
     *
     * @var string
     */
    public $description;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->bankId = '0';
        $this->bankcard = '';
        $this->bankzone = '';
        $this->userId = '0';
        $this->status = '0';
        $this->cardName = '';
        $this->cardType = '0';
        $this->admin = '';
        $this->createTime = '0';
        $this->regionLv1 = '0';
        $this->regionLv2 = '0';
        $this->regionLv3 = '0';
        $this->regionLv4 = '0';
        $this->userBankId = '0';
        $this->imageId = '0';
        $this->description = '';
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
            'bank_id' => 'bankId',
            'bankcard' => 'bankcard',
            'bankzone' => 'bankzone',
            'user_id' => 'userId',
            'status' => 'status',
            'card_name' => 'cardName',
            'card_type' => 'cardType',
            'admin' => 'admin',
            'create_time' => 'createTime',
            'audit_time' => 'auditTime',
            'region_lv1' => 'regionLv1',
            'region_lv2' => 'regionLv2',
            'region_lv3' => 'regionLv3',
            'region_lv4' => 'regionLv4',
            'user_bank_id' => 'userBankId',
            'image_id' => 'imageId',
            'description' => 'description',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_bankcard_audit";
    }
}