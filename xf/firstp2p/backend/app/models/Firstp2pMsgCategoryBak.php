<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMsgCategoryBak extends ModelBaseNoTime
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
    public $type_name;


    /**
     *
     * @var integer
     */
    public $is_contract;


    /**
     *
     * @var string
     */
    public $type_tag;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $contract_type;


    /**
     *
     * @var integer
     */
    public $use_status;


    /**
     *
     * @var integer
     */
    public $parent_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->typeName = '';
        $this->isContract = '0';
        $this->typeTag = '';
        $this->createTime = '0';
        $this->isDelete = '0';
        $this->contractType = '0';
        $this->useStatus = '1';
        $this->parentId = '0';
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
            'type_name' => 'typeName',
            'is_contract' => 'isContract',
            'type_tag' => 'typeTag',
            'create_time' => 'createTime',
            'is_delete' => 'isDelete',
            'contract_type' => 'contractType',
            'use_status' => 'useStatus',
            'parent_id' => 'parentId',
        );
    }

    public function getSource()
    {
        return "firstp2p_msg_category_bak";
    }
}