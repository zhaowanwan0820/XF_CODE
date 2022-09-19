<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAttachment extends ModelBaseNoTime
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
    public $app_name;


    /**
     *
     * @var string
     */
    public $filename;


    /**
     *
     * @var integer
     */
    public $filesize;


    /**
     *
     * @var string
     */
    public $attachment;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $isimage;


    /**
     *
     * @var string
     */
    public $other;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_priv;


    /**
     *
     * @var integer
     */
    public $is_delete;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->appName = '';
        $this->filename = '';
        $this->filesize = '0';
        $this->attachment = '';
        $this->description = '';
        $this->isimage = '0';
        $this->other = '';
        $this->createTime = '0';
        $this->isPriv = '0';
        $this->isDelete = '0';
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
            'app_name' => 'appName',
            'filename' => 'filename',
            'filesize' => 'filesize',
            'attachment' => 'attachment',
            'description' => 'description',
            'isimage' => 'isimage',
            'other' => 'other',
            'create_time' => 'createTime',
            'is_priv' => 'isPriv',
            'is_delete' => 'isDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_attachment";
    }
}