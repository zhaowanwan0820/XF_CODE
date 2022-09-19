<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealAttachment extends ModelBaseNoTime
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
    public $deal_id;


    /**
     *
     * @var string
     */
    public $title;


    /**
     *
     * @var string
     */
    public $filename;


    /**
     *
     * @var string
     */
    public $type;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $order;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $admin_user_id;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->title = '';
        $this->filename = '';
        $this->type = '';
        $this->order = '0';
        $this->createTime = '0';
        $this->adminUserId = '0';
        $this->status = '0';
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
            'deal_id' => 'dealId',
            'title' => 'title',
            'filename' => 'filename',
            'type' => 'type',
            'description' => 'description',
            'order' => 'order',
            'create_time' => 'createTime',
            'admin_user_id' => 'adminUserId',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_attachment";
    }
}