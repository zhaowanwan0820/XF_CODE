<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pWeshareInfoDisclosure extends ModelBaseNoTime
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
    public $project_type;


    /**
     *
     * @var integer
     */
    public $product_type;


    /**
     *
     * @var integer
     */
    public $invest_term;


    /**
     *
     * @var integer
     */
    public $invest_unit;


    /**
     *
     * @var string
     */
    public $repay_guarantee_measur;


    /**
     *
     * @var string
     */
    public $loan_usage;


    /**
     *
     * @var string
     */
    public $expect_intrerst_date;


    /**
     *
     * @var string
     */
    public $limit_manage;


    /**
     *
     * @var string
     */
    public $project_risk_tip;


    /**
     *
     * @var integer
     */
    public $is_effect;


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
        $this->projectType = '1';
        $this->productType = '0';
        $this->investTerm = '0';
        $this->investUnit = '0';
        $this->isEffect = '0';
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
            'project_type' => 'projectType',
            'product_type' => 'productType',
            'invest_term' => 'investTerm',
            'invest_unit' => 'investUnit',
            'repay_guarantee_measur' => 'repayGuaranteeMeasur',
            'loan_usage' => 'loanUsage',
            'expect_intrerst_date' => 'expectIntrerstDate',
            'limit_manage' => 'limitManage',
            'project_risk_tip' => 'projectRiskTip',
            'is_effect' => 'isEffect',
            'update_time' => 'updateTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_weshare_info_disclosure";
    }
}