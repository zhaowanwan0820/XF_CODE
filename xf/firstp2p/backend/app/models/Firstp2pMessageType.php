<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMessageType extends ModelBaseNoTime
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
    public $type_name;


    /**
     *
     * @var integer
     */
    public $is_fix;


    /**
     *
     * @var string
     */
    public $show_name;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $sort;

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
            'type_name' => 'typeName',
            'is_fix' => 'isFix',
            'show_name' => 'showName',
            'is_effect' => 'isEffect',
            'sort' => 'sort',
        );
    }

    public function getSource()
    {
        return "firstp2p_message_type";
    }
}