<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCompanyFinance extends ModelBaseNoTime
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
    public $cid;


    /**
     *
     * @var integer
     */
    public $year;


    /**
     *
     * @var float
     */
    public $master_income;


    /**
     *
     * @var float
     */
    public $gross_profit;


    /**
     *
     * @var float
     */
    public $total_assets;


    /**
     *
     * @var float
     */
    public $net_asset;


    /**
     *
     * @var string
     */
    public $remarks;


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
        $this->cid = '0';
        $this->year = '0';
        $this->masterIncome = '0.00';
        $this->grossProfit = '0.00';
        $this->totalAssets = '0.00';
        $this->netAsset = '0.00';
        $this->remarks = '';
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
            'cid' => 'cid',
            'year' => 'year',
            'master_income' => 'masterIncome',
            'gross_profit' => 'grossProfit',
            'total_assets' => 'totalAssets',
            'net_asset' => 'netAsset',
            'remarks' => 'remarks',
            'status' => 'status',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_company_finance";
    }
}