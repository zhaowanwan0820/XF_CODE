<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusConf extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var string
     */
    public $value;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_conf;


    /**
     *
     * @var integer
     */
    public $start_time;


    /**
     *
     * @var integer
     */
    public $end_time;


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
    public $version;


    /**
     *
     * @var string
     */
    public $tip;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->title = '';
        $this->name = '';
        $this->value = '';
        $this->isEffect = '1';
        $this->isConf = '1';
        $this->startTime = '0';
        $this->endTime = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->version = '1';
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
            'name' => 'name',
            'value' => 'value',
            'is_effect' => 'isEffect',
            'is_conf' => 'isConf',
            'start_time' => 'startTime',
            'end_time' => 'endTime',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'version' => 'version',
            'tip' => 'tip',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_conf";
    }
}