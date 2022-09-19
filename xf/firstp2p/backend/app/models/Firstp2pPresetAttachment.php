<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPresetAttachment extends ModelBaseNoTime
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
    public $program_id;


    /**
     *
     * @var string
     */
    public $title;


    /**
     *
     * @var string
     */
    public $filename;


    /**
     *
     * @var string
     */
    public $type;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->programId = '0';
        $this->title = '';
        $this->filename = '';
        $this->type = '';
        $this->createTime = '0';
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
            'program_id' => 'programId',
            'title' => 'title',
            'filename' => 'filename',
            'type' => 'type',
            'description' => 'description',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_preset_attachment";
    }
}