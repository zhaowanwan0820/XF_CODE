<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPushCopy extends ModelBaseNoTime
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
    public $title;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var string
     */
    public $platform;


    /**
     *
     * @var integer
     */
    public $send_type;


    /**
     *
     * @var date
     */
    public $send_time;


    /**
     *
     * @var integer
     */
    public $send_status;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $send_cnt;


    /**
     *
     * @var string
     */
    public $return;


    /**
     *
     * @var string
     */
    public $message_key;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->title = '';
        $this->content = '';
        $this->dealId = '0';
        $this->platform = 'ANDROID_TEST';
        $this->sendType = '1';
        $this->sendTime = XDateTime::now();
        $this->sendStatus = '0';
        $this->isDelete = '0';
        $this->sendCnt = '1';
        $this->messageKey = '';
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
            'title' => 'title',
            'content' => 'content',
            'deal_id' => 'dealId',
            'platform' => 'platform',
            'send_type' => 'sendType',
            'send_time' => 'sendTime',
            'send_status' => 'sendStatus',
            'is_delete' => 'isDelete',
            'send_cnt' => 'sendCnt',
            'return' => 'return',
            'message_key' => 'messageKey',
        );
    }

    public function getSource()
    {
        return "firstp2p_push_copy";
    }
}