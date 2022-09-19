<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMsgTemplate extends ModelBaseNoTime
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
    public $msg_title;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $contract_title;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $is_html;


    /**
     *
     * @var integer
     */
    public $msg_typeid;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->msgTitle = '';
        $this->contractTitle = '';
        $this->msgTypeid = '0';
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
            'msg_title' => 'msgTitle',
            'name' => 'name',
            'contract_title' => 'contractTitle',
            'content' => 'content',
            'type' => 'type',
            'is_html' => 'isHtml',
            'msg_typeid' => 'msgTypeid',
        );
    }

    public function getSource()
    {
        return "firstp2p_msg_template";
    }
}