<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserXYPoint extends ModelBaseNoTime
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
    public $uid;


    /**
     *
     * @var float
     */
    public $xpoint;


    /**
     *
     * @var float
     */
    public $ypoint;


    /**
     *
     * @var integer
     */
    public $locate_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->xpoint = '0.000000';
        $this->ypoint = '0.000000';
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
            'uid' => 'uid',
            'xpoint' => 'xpoint',
            'ypoint' => 'ypoint',
            'locate_time' => 'locateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_x_y_point";
    }
}