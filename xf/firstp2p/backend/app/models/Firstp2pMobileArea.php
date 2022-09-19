<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMobileArea extends ModelBaseNoTime
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
    public $mobile;


    /**
     *
     * @var string
     */
    public $provice;


    /**
     *
     * @var string
     */
    public $city;


    /**
     *
     * @var string
     */
    public $mobile_type;


    /**
     *
     * @var string
     */
    public $area_code;


    /**
     *
     * @var string
     */
    public $post_code;

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
            'mobile' => 'mobile',
            'provice' => 'provice',
            'city' => 'city',
            'mobile_type' => 'mobileType',
            'area_code' => 'areaCode',
            'post_code' => 'postCode',
        );
    }

    public function getSource()
    {
        return "firstp2p_mobile_area";
    }
}