<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAgencyContractCopy extends ModelBaseNoTime
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
    public $user_name;


    /**
     *
     * @var integer
     */
    public $agency_id;


    /**
     *
     * @var integer
     */
    public $contract_id;


    /**
     *
     * @var integer
     */
    public $pass;


    /**
     *
     * @var integer
     */
    public $deal_id;


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
    public $sign_pass;


    /**
     *
     * @var integer
     */
    public $sign_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->signPass = '0';
        $this->signTime = '0';
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
            'user_name' => 'userName',
            'agency_id' => 'agencyId',
            'contract_id' => 'contractId',
            'pass' => 'pass',
            'deal_id' => 'dealId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'sign_pass' => 'signPass',
            'sign_time' => 'signTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_agency_contract_copy";
    }
}