<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMedal extends ModelBaseNoTime
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
    public $class_name;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var string
     */
    public $config;


    /**
     *
     * @var string
     */
    public $icon;


    /**
     *
     * @var string
     */
    public $image;


    /**
     *
     * @var string
     */
    public $route;


    /**
     *
     * @var integer
     */
    public $allow_check;

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
            'class_name' => 'className',
            'name' => 'name',
            'description' => 'description',
            'is_effect' => 'isEffect',
            'config' => 'config',
            'icon' => 'icon',
            'image' => 'image',
            'route' => 'route',
            'allow_check' => 'allowCheck',
        );
    }

    public function getSource()
    {
        return "firstp2p_medal";
    }
}