<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealCate extends ModelBaseNoTime
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
    public $brief;


    /**
     *
     * @var integer
     */
    public $pid;


    /**
     *
     * @var integer
     */
    public $is_delete;


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


    /**
     *
     * @var string
     */
    public $uname;


    /**
     *
     * @var string
     */
    public $icon;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->icon = '';
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
            'brief' => 'brief',
            'pid' => 'pid',
            'is_delete' => 'isDelete',
            'is_effect' => 'isEffect',
            'sort' => 'sort',
            'uname' => 'uname',
            'icon' => 'icon',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_cate";
    }
}