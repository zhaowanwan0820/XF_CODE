<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMonthBill extends ModelBaseNoTime
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
    public $attachment_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $year;


    /**
     *
     * @var integer
     */
    public $month;


    /**
     *
     * @var string
     */
    public $html_content;


    /**
     *
     * @var string
     */
    public $email;


    /**
     *
     * @var string
     */
    public $idno;


    /**
     *
     * @var string
     */
    public $rs_upload;


    /**
     *
     * @var date
     */
    public $create_time;


    /**
     *
     * @var date
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $is_send;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->idno = '';
        $this->createTime = '0000-00-00 00:00:00';
        $this->updateTime = '0000-00-00 00:00:00';
        $this->isSend = '0';
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
            'attachment_id' => 'attachmentId',
            'user_id' => 'userId',
            'year' => 'year',
            'month' => 'month',
            'html_content' => 'htmlContent',
            'email' => 'email',
            'idno' => 'idno',
            'rs_upload' => 'rsUpload',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'is_send' => 'isSend',
        );
    }

    public function getSource()
    {
        return "firstp2p_month_bill";
    }
}