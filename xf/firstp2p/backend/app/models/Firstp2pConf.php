<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pConf extends ModelBaseNoTime
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
    public $site_id;


    /**
     *
     * @var integer
     */
    public $group_id;


    /**
     *
     * @var integer
     */
    public $input_type;


    /**
     *
     * @var string
     */
    public $value_scope;


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
    public $sort;


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
        $this->siteId = '0';
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
            'site_id' => 'siteId',
            'group_id' => 'groupId',
            'input_type' => 'inputType',
            'value_scope' => 'valueScope',
            'is_effect' => 'isEffect',
            'is_conf' => 'isConf',
            'sort' => 'sort',
            'tip' => 'tip',
        );
    }

    public function getSource()
    {
        return "firstp2p_conf";
    }
}