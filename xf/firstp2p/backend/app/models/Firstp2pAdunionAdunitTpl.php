<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdunionAdunitTpl extends ModelBaseNoTime
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
     * @var string
     */
    public $size;


    /**
     *
     * @var string
     */
    public $color;


    /**
     *
     * @var string
     */
    public $rows;


    /**
     *
     * @var string
     */
    public $content;


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
    public $is_delete;

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
            'name' => 'name',
            'size' => 'size',
            'color' => 'color',
            'rows' => 'rows',
            'content' => 'content',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'is_delete' => 'isDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_adunion_adunit_tpl";
    }
}