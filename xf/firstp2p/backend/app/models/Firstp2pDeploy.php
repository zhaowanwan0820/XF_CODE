<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDeploy extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var float
     */
    public $threeperiod;


    /**
     *
     * @var float
     */
    public $sixperiod;


    /**
     *
     * @var float
     */
    public $nineperiod;


    /**
     *
     * @var float
     */
    public $twelveperiod;


    /**
     *
     * @var float
     */
    public $total;


    /**
     *
     * @var string
     */
    public $process;


    /**
     *
     * @var integer
     */
    public $dateline;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->threeperiod = '0';
        $this->sixperiod = '0';
        $this->nineperiod = '0';
        $this->twelveperiod = '0';
        $this->total = '0';
        $this->process = '——';
        $this->dateline = '0';
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
            'threeperiod' => 'threeperiod',
            'sixperiod' => 'sixperiod',
            'nineperiod' => 'nineperiod',
            'twelveperiod' => 'twelveperiod',
            'total' => 'total',
            'process' => 'process',
            'dateline' => 'dateline',
        );
    }

    public function getSource()
    {
        return "firstp2p_deploy";
    }
}