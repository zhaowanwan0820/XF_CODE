<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pWorldCup2 extends ModelBaseNoTime
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
    public $mobile;


    /**
     *
     * @var string
     */
    public $team;


    /**
     *
     * @var string
     */
    public $player;


    /**
     *
     * @var integer
     */
    public $created;

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
            'mobile' => 'mobile',
            'team' => 'team',
            'player' => 'player',
            'created' => 'created',
        );
    }

    public function getSource()
    {
        return "firstp2p_world_cup2";
    }
}