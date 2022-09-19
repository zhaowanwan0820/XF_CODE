<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserProve extends ModelBaseNoTime
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
    public $real_name;


    /**
     *
     * @var integer
     */
    public $province_id;


    /**
     *
     * @var integer
     */
    public $city_id;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $postcode;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $phone;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->realName = '';
        $this->provinceId = '0';
        $this->cityId = '0';
        $this->address = '';
        $this->postcode = '';
        $this->mobile = '';
        $this->phone = '';
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
            'user_id' => 'userId',
            'real_name' => 'realName',
            'province_id' => 'provinceId',
            'city_id' => 'cityId',
            'address' => 'address',
            'postcode' => 'postcode',
            'mobile' => 'mobile',
            'phone' => 'phone',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_prove";
    }
}