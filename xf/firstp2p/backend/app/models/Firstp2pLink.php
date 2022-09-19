<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pLink extends ModelBaseNoTime
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
     * @var integer
     */
    public $group_id;


    /**
     *
     * @var string
     */
    public $url;


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
    public $img_gray;


    /**
     *
     * @var string
     */
    public $img;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $count;


    /**
     *
     * @var integer
     */
    public $show_index;

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
            'group_id' => 'groupId',
            'url' => 'url',
            'is_effect' => 'isEffect',
            'sort' => 'sort',
            'img_gray' => 'imgGray',
            'img' => 'img',
            'description' => 'description',
            'count' => 'count',
            'show_index' => 'showIndex',
        );
    }

    public function getSource()
    {
        return "firstp2p_link";
    }
}