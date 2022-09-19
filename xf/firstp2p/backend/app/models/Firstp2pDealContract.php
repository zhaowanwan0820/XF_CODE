<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealContract extends ModelBaseNoTime
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
     * @var string
     */
    public $contract_tpl_type;


    /**
     *
     * @var integer
     */
    public $agency_id;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $sign_time;

    /**
     *
     * @var integer
     */
    public $adm_id;


    /**
     *
     * @var integer
     */
    public $deal_type;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->status = '0';
        $this->signTime = '0';
        $this->admId = '0';
        $this->dealType = '0';
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
            'deal_id' => 'dealId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'contract_tpl_type' => 'contractTplType',
            'agency_id' => 'agencyId',
            'status' => 'status',
            'sign_time' => 'signTime',
            'adm_id' => 'admId',
            'deal_type' => 'dealType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_contract";
    }
}