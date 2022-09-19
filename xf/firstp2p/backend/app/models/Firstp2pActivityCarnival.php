<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pActivityCarnival extends ModelBaseNoTime
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
    public $user_name;


    /**
     *
     * @var string
     */
    public $gift_virtual;


    /**
     *
     * @var string
     */
    public $gift_practical;


    /**
     *
     * @var integer
     */
    public $is_commit;


    /**
     *
     * @var string
     */
    public $gift_choose;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $last_changed_time;


    /**
     *
     * @var integer
     */
    public $expire_time;


    /**
     *
     * @var string
     */
    public $recipient_name;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $province;


    /**
     *
     * @var string
     */
    public $city;


    /**
     *
     * @var string
     */
    public $country;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $coupon;

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
            'user_id' => 'userId',
            'user_name' => 'userName',
            'gift_virtual' => 'giftVirtual',
            'gift_practical' => 'giftPractical',
            'is_commit' => 'isCommit',
            'gift_choose' => 'giftChoose',
            'create_time' => 'createTime',
            'last_changed_time' => 'lastChangedTime',
            'expire_time' => 'expireTime',
            'recipient_name' => 'recipientName',
            'mobile' => 'mobile',
            'province' => 'province',
            'city' => 'city',
            'country' => 'country',
            'address' => 'address',
            'coupon' => 'coupon',
        );
    }

    public function getSource()
    {
        return "firstp2p_activity_carnival";
    }
}