<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserField extends ModelBaseNoTime
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
    public $field_name;


    /**
     *
     * @var string
     */
    public $field_show_name;


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
    public $is_must;


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
            'field_name' => 'fieldName',
            'field_show_name' => 'fieldShowName',
            'input_type' => 'inputType',
            'value_scope' => 'valueScope',
            'is_must' => 'isMust',
            'sort' => 'sort',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_field";
    }
}