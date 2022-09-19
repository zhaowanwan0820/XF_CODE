<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserBankcard extends ModelBaseNoTime
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
    public $bank_id;


    /**
     *
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var string
     */
    public $bankzone;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $card_name;


    /**
     *
     * @var integer
     */
    public $card_type;


    /**
     *
     * @var integer
     */
    public $region_lv1;


    /**
     *
     * @var integer
     */
    public $region_lv2;


    /**
     *
     * @var integer
     */
    public $region_lv3;


    /**
     *
     * @var integer
     */
    public $region_lv4;


    /**
     *
     * @var integer
     */
    public $image_id;


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
     * @var integer
     */
    public $verify_status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->status = '0';
        $this->cardType = '0';
        $this->imageId = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->verifyStatus = '0';
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
            'bank_id' => 'bankId',
            'bankcard' => 'bankcard',
            'bankzone' => 'bankzone',
            'user_id' => 'userId',
            'status' => 'status',
            'card_name' => 'cardName',
            'card_type' => 'cardType',
            'region_lv1' => 'regionLv1',
            'region_lv2' => 'regionLv2',
            'region_lv3' => 'regionLv3',
            'region_lv4' => 'regionLv4',
            'image_id' => 'imageId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'verify_status' => 'verifyStatus',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_bankcard";
    }
}