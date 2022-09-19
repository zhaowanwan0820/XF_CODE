<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAutoCache extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var string
     */
    public $cache_key;


    /**
     *
     * @var string
     */
    public $cache_type;


    /**
     *
     * @var string
     */
    public $cache_data;


    /**
     *
     * @var integer
     */
    public $cache_time;

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
            'cache_key' => 'cacheKey',
            'cache_type' => 'cacheType',
            'cache_data' => 'cacheData',
            'cache_time' => 'cacheTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_auto_cache";
    }
}