<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pVoteAsk extends ModelBaseNoTime
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
    public $type;


    /**
     *
     * @var integer
     */
    public $sort;


    /**
     *
     * @var integer
     */
    public $vote_id;


    /**
     *
     * @var string
     */
    public $val_scope;

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
            'type' => 'type',
            'sort' => 'sort',
            'vote_id' => 'voteId',
            'val_scope' => 'valScope',
        );
    }

    public function getSource()
    {
        return "firstp2p_vote_ask";
    }
}