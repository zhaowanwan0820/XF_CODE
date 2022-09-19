<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealAgency extends ModelBaseNoTime
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
    public $header;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $agency_user_id;


    /**
     *
     * @var string
     */
    public $brief;


    /**
     *
     * @var string
     */
    public $company_brief;


    /**
     *
     * @var string
     */
    public $history;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $is_effect;


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
    public $sort;


    /**
     *
     * @var string
     */
    public $short_name;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $realname;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $postcode;


    /**
     *
     * @var string
     */
    public $fax;


    /**
     *
     * @var string
     */
    public $email;


    /**
     *
     * @var float
     */
    public $review;


    /**
     *
     * @var float
     */
    public $premium;


    /**
     *
     * @var float
     */
    public $caution_money;


    /**
     *
     * @var string
     */
    public $agreement;


    /**
     *
     * @var string
     */
    public $bankzone;


    /**
     *
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var string
     */
    public $mechanism;


    /**
     *
     * @var string
     */
    public $license;


    /**
     *
     * @var string
     */
    public $repay_inform_email;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->type = '1';
        $this->userId = '0';
        $this->agencyUserId = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->bankzone = '';
        $this->bankcard = '';
        $this->license = '';
        $this->repayInformEmail = '';
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
            'header' => 'header',
            'type' => 'type',
            'name' => 'name',
            'user_id' => 'userId',
            'agency_user_id' => 'agencyUserId',
            'brief' => 'brief',
            'company_brief' => 'companyBrief',
            'history' => 'history',
            'content' => 'content',
            'is_effect' => 'isEffect',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'sort' => 'sort',
            'short_name' => 'shortName',
            'address' => 'address',
            'realname' => 'realname',
            'mobile' => 'mobile',
            'postcode' => 'postcode',
            'fax' => 'fax',
            'email' => 'email',
            'review' => 'review',
            'premium' => 'premium',
            'caution_money' => 'cautionMoney',
            'agreement' => 'agreement',
            'bankzone' => 'bankzone',
            'bankcard' => 'bankcard',
            'mechanism' => 'mechanism',
            'license' => 'license',
            'repay_inform_email' => 'repayInformEmail',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_agency";
    }
}