<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pP2bDealAttachment extends ModelBaseNoTime
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
    public $bank_deal_id;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->bankDealId = '0';
        $this->title = '';
        $this->filename = '';
        $this->type = '';
        $this->order = '0';
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
            'bank_deal_id' => 'bankDealId',
            'title' => 'title',
            'filename' => 'filename',
            'type' => 'type',
            'description' => 'description',
            'order' => 'order',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_p2b_deal_attachment";
    }
}