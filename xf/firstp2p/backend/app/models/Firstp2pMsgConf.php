<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMsgConf extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $mail_asked;


    /**
     *
     * @var integer
     */
    public $sms_asked;


    /**
     *
     * @var integer
     */
    public $mail_bid;


    /**
     *
     * @var integer
     */
    public $sms_bid;


    /**
     *
     * @var integer
     */
    public $mail_myfail;


    /**
     *
     * @var integer
     */
    public $sms_myfail;


    /**
     *
     * @var integer
     */
    public $mail_half;


    /**
     *
     * @var integer
     */
    public $sms_half;


    /**
     *
     * @var integer
     */
    public $mail_bidsuccess;


    /**
     *
     * @var integer
     */
    public $sms_bidsuccess;


    /**
     *
     * @var integer
     */
    public $mail_fail;


    /**
     *
     * @var integer
     */
    public $sms_fail;


    /**
     *
     * @var integer
     */
    public $mail_bidrepaid;


    /**
     *
     * @var integer
     */
    public $sms_bidrepaid;


    /**
     *
     * @var integer
     */
    public $mail_answer;


    /**
     *
     * @var integer
     */
    public $sms_answer;

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
            'user_id' => 'userId',
            'mail_asked' => 'mailAsked',
            'sms_asked' => 'smsAsked',
            'mail_bid' => 'mailBid',
            'sms_bid' => 'smsBid',
            'mail_myfail' => 'mailMyfail',
            'sms_myfail' => 'smsMyfail',
            'mail_half' => 'mailHalf',
            'sms_half' => 'smsHalf',
            'mail_bidsuccess' => 'mailBidsuccess',
            'sms_bidsuccess' => 'smsBidsuccess',
            'mail_fail' => 'mailFail',
            'sms_fail' => 'smsFail',
            'mail_bidrepaid' => 'mailBidrepaid',
            'sms_bidrepaid' => 'smsBidrepaid',
            'mail_answer' => 'mailAnswer',
            'sms_answer' => 'smsAnswer',
        );
    }

    public function getSource()
    {
        return "firstp2p_msg_conf";
    }
}