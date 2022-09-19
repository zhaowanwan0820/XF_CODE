<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealMsgListCopy extends ModelBaseNoTime
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
    public $dest;


    /**
     *
     * @var integer
     */
    public $send_type;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $send_time;


    /**
     *
     * @var integer
     */
    public $is_send;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $result;


    /**
     *
     * @var integer
     */
    public $is_success;


    /**
     *
     * @var integer
     */
    public $is_html;


    /**
     *
     * @var string
     */
    public $title;


    /**
     *
     * @var integer
     */
    public $is_youhui;


    /**
     *
     * @var integer
     */
    public $youhui_id;


    /**
     *
     * @var string
     */
    public $attachment;


    /**
     *
     * @var integer
     */
    public $is_contract;


    /**
     *
     * @var integer
     */
    public $sms_template_id;


    /**
     *
     * @var string
     */
    public $sms_content;


    /**
     *
     * @var string
     */
    public $sc_id;


    /**
     *
     * @var integer
     */
    public $open_time;


    /**
     *
     * @var integer
     */
    public $receive_time;


    /**
     *
     * @var integer
     */
    public $is_opened;


    /**
     *
     * @var integer
     */
    public $is_received;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->isContract = '0';
        $this->smsTemplateId = '0';
        $this->smsContent = '';
        $this->scId = '';
        $this->openTime = '0';
        $this->receiveTime = '0';
        $this->isOpened = '0';
        $this->isReceived = '0';
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
            'dest' => 'dest',
            'send_type' => 'sendType',
            'content' => 'content',
            'send_time' => 'sendTime',
            'is_send' => 'isSend',
            'create_time' => 'createTime',
            'user_id' => 'userId',
            'result' => 'result',
            'is_success' => 'isSuccess',
            'is_html' => 'isHtml',
            'title' => 'title',
            'is_youhui' => 'isYouhui',
            'youhui_id' => 'youhuiId',
            'attachment' => 'attachment',
            'is_contract' => 'isContract',
            'sms_template_id' => 'smsTemplateId',
            'sms_content' => 'smsContent',
            'sc_id' => 'scId',
            'open_time' => 'openTime',
            'receive_time' => 'receiveTime',
            'is_opened' => 'isOpened',
            'is_received' => 'isReceived',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_msg_list_copy";
    }
}