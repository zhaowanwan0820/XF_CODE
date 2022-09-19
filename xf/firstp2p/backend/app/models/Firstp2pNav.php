<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pNav extends ModelBaseNoTime
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
    public $url;


    /**
     *
     * @var integer
     */
    public $blank;


    /**
     *
     * @var integer
     */
    public $sort;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var string
     */
    public $u_module;


    /**
     *
     * @var string
     */
    public $u_action;


    /**
     *
     * @var integer
     */
    public $u_id;


    /**
     *
     * @var string
     */
    public $u_param;


    /**
     *
     * @var integer
     */
    public $is_shop;


    /**
     *
     * @var string
     */
    public $app_index;


    /**
     *
     * @var integer
     */
    public $pid;

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
            'url' => 'url',
            'blank' => 'blank',
            'sort' => 'sort',
            'is_effect' => 'isEffect',
            'u_module' => 'uModule',
            'u_action' => 'uAction',
            'u_id' => 'uId',
            'u_param' => 'uParam',
            'is_shop' => 'isShop',
            'app_index' => 'appIndex',
            'pid' => 'pid',
        );
    }

    public function getSource()
    {
        return "firstp2p_nav";
    }
}