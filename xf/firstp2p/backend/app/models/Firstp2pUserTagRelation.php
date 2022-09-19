<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserTagRelation extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var integer
     */
    public $uid;


    /**
     *
     * @var integer
     */
    public $tag_id;


    /**
     *
     * @var date
     */
    public $created_at;


    /**
     *
     * @var date
     */
    public $updated_at;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->uid = '0';
        $this->tagId = '0';
        $this->createdAt = XDateTime::now();
        $this->updatedAt = '0000-00-00 00:00:00';
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
            'uid' => 'uid',
            'tag_id' => 'tagId',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_tag_relation";
    }
}