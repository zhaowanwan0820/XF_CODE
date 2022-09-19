<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealQueue extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var string
     */
    public $note;


    /**
     *
     * @var integer
     */
    public $first_deal_id;


    /**
     *
     * @var string
     */
    public $deal_id_queue;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $site_id;


    /**
     *
     * @var integer
     */
    public $type_id;


    /**
     *
     * @var integer
     */
    public $start_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->firstDealId = '0';
        $this->isEffect = '1';
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
            'name' => 'name',
            'create_time' => 'createTime',
            'note' => 'note',
            'first_deal_id' => 'firstDealId',
            'deal_id_queue' => 'dealIdQueue',
            'is_effect' => 'isEffect',
            'site_id' => 'siteId',
            'type_id' => 'typeId',
            'start_time' => 'startTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_queue";
    }
}