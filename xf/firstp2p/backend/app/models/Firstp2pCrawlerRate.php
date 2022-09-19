<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCrawlerRate extends ModelBaseNoTime
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
    public $point;


    /**
     *
     * @var string
     */
    public $source;


    /**
     *
     * @var string
     */
    public $ratetime;


    /**
     *
     * @var integer
     */
    public $updatetime;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->point = '0';
        $this->source = '0';
        $this->ratetime = '0';
        $this->updatetime = '0';
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
            'point' => 'point',
            'source' => 'source',
            'ratetime' => 'ratetime',
            'updatetime' => 'updatetime',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_crawler_rate";
    }
}