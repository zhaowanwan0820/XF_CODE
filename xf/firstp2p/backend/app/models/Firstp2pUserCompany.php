<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserCompany extends ModelBaseNoTime
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
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $domicile;


    /**
     *
     * @var string
     */
    public $legal_person;


    /**
     *
     * @var string
     */
    public $tel;


    /**
     *
     * @var string
     */
    public $license;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


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
     * @var string
     */
    public $project_area;


    /**
     *
     * @var string
     */
    public $project_condition;


    /**
     *
     * @var float
     */
    public $top_credit;


    /**
     *
     * @var string
     */
    public $is_important_enterprise;


    /**
     *
     * @var string
     */
    public $mangage_condition;


    /**
     *
     * @var string
     */
    public $complain_condition;


    /**
     *
     * @var string
     */
    public $trustworthiness;


    /**
     *
     * @var string
     */
    public $repayment_source;


    /**
     *
     * @var string
     */
    public $policy;


    /**
     *
     * @var string
     */
    public $marketplace;


    /**
     *
     * @var integer
     */
    public $licence_image;


    /**
     *
     * @var integer
     */
    public $organization_iamge;


    /**
     *
     * @var integer
     */
    public $taxation_image;


    /**
     *
     * @var integer
     */
    public $bank_iamge;


    /**
     *
     * @var integer
     */
    public $is_html;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->name = '';
        $this->address = '';
        $this->domicile = '';
        $this->legalPerson = '';
        $this->tel = '';
        $this->license = '';
        $this->isEffect = '1';
        $this->isDelete = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->projectArea = '';
        $this->topCredit = '0.0000';
        $this->isImportantEnterprise = '';
        $this->complainCondition = '';
        $this->licenceImage = '0';
        $this->organizationIamge = '0';
        $this->taxationImage = '0';
        $this->bankIamge = '0';
        $this->isHtml = '0';
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
            'name' => 'name',
            'address' => 'address',
            'domicile' => 'domicile',
            'legal_person' => 'legalPerson',
            'tel' => 'tel',
            'license' => 'license',
            'description' => 'description',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'project_area' => 'projectArea',
            'project_condition' => 'projectCondition',
            'top_credit' => 'topCredit',
            'is_important_enterprise' => 'isImportantEnterprise',
            'mangage_condition' => 'mangageCondition',
            'complain_condition' => 'complainCondition',
            'trustworthiness' => 'trustworthiness',
            'repayment_source' => 'repaymentSource',
            'policy' => 'policy',
            'marketplace' => 'marketplace',
            'licence_image' => 'licenceImage',
            'organization_iamge' => 'organizationIamge',
            'taxation_image' => 'taxationImage',
            'bank_iamge' => 'bankIamge',
            'is_html' => 'isHtml',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_company";
    }
}