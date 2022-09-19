<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealProjectCompound extends ModelBaseNoTime
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
    public $project_id;


    /**
     *
     * @var integer
     */
    public $lock_period;


    /**
     *
     * @var integer
     */
    public $redemption_period;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->lockPeriod = '0';
        $this->redemptionPeriod = '0';
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
            'project_id' => 'projectId',
            'lock_period' => 'lockPeriod',
            'redemption_period' => 'redemptionPeriod',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_project_compound";
    }
}