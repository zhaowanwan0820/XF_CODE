<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pArticleCate extends ModelBaseNoTime
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
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $type_id;


    /**
     *
     * @var integer
     */
    public $sort;


    /**
     *
     * @var integer
     */
    public $site_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
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
            'brief' => 'brief',
            'pid' => 'pid',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'type_id' => 'typeId',
            'sort' => 'sort',
            'site_id' => 'siteId',
        );
    }

    public function getSource()
    {
        return "firstp2p_article_cate";
    }
}