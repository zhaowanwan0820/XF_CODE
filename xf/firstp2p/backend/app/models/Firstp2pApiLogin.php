<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pApiLogin extends ModelBaseNoTime
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
    public $config;


    /**
     *
     * @var string
     */
    public $class_name;


    /**
     *
     * @var string
     */
    public $icon;


    /**
     *
     * @var string
     */
    public $bicon;


    /**
     *
     * @var integer
     */
    public $is_weibo;

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
            'config' => 'config',
            'class_name' => 'className',
            'icon' => 'icon',
            'bicon' => 'bicon',
            'is_weibo' => 'isWeibo',
        );
    }

    public function getSource()
    {
        return "firstp2p_api_login";
    }
}