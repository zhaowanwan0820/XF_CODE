<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserFrequented extends ModelBaseNoTime
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
     * @var string
     */
    public $title;


    /**
     *
     * @var string
     */
    public $addr;


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
     * @var float
     */
    public $latitude_top;


    /**
     *
     * @var float
     */
    public $latitude_bottom;


    /**
     *
     * @var float
     */
    public $longitude_left;


    /**
     *
     * @var float
     */
    public $longitude_right;


    /**
     *
     * @var integer
     */
    public $zoom_level;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

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
            'title' => 'title',
            'addr' => 'addr',
            'xpoint' => 'xpoint',
            'ypoint' => 'ypoint',
            'latitude_top' => 'latitudeTop',
            'latitude_bottom' => 'latitudeBottom',
            'longitude_left' => 'longitudeLeft',
            'longitude_right' => 'longitudeRight',
            'zoom_level' => 'zoomLevel',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_frequented";
    }
}