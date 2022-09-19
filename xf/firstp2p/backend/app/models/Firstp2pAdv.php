<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdv extends ModelBaseNoTime
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
    public $tmpl;


    /**
     *
     * @var string
     */
    public $adv_id;


    /**
     *
     * @var string
     */
    public $code;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $city_ids;


    /**
     *
     * @var integer
     */
    public $rel_id;


    /**
     *
     * @var string
     */
    public $rel_table;

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
            'id' => 'id',
            'tmpl' => 'tmpl',
            'adv_id' => 'advId',
            'code' => 'code',
            'is_effect' => 'isEffect',
            'name' => 'name',
            'city_ids' => 'cityIds',
            'rel_id' => 'relId',
            'rel_table' => 'relTable',
        );
    }

    public function getSource()
    {
        return "firstp2p_adv";
    }
}