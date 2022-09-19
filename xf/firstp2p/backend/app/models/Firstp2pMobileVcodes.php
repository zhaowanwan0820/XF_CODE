<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMobileVcodes extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $mobile_phone;


    /**
     *
     * @var string
     */
    public $mobile_vcode;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->mobilePhone = '';
        $this->mobileVcode = '';
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
            'mobile_phone' => 'mobilePhone',
            'mobile_vcode' => 'mobileVcode',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_mobile_vcodes";
    }
}