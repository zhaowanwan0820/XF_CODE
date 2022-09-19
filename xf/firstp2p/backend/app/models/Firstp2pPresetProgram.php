<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPresetProgram extends ModelBaseNoTime
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
    public $program_name;


    /**
     *
     * @var string
     */
    public $program_url;


    /**
     *
     * @var string
     */
    public $program_html;


    /**
     *
     * @var string
     */
    public $program_content;


    /**
     *
     * @var integer
     */
    public $program_status;


    /**
     *
     * @var integer
     */
    public $program_default;


    /**
     *
     * @var string
     */
    public $program_pic;


    /**
     *
     * @var string
     */
    public $program_img;


    /**
     *
     * @var string
     */
    public $program_desc;


    /**
     *
     * @var string
     */
    public $program_deals;


    /**
     *
     * @var integer
     */
    public $program_is_login;


    /**
     *
     * @var string
     */
    public $program_area;


    /**
     *
     * @var integer
     */
    public $program_create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->programName = '';
        $this->programUrl = '';
        $this->programStatus = '0';
        $this->programDefault = '0';
        $this->programPic = '';
        $this->programImg = '';
        $this->programDeals = '';
        $this->programIsLogin = '0';
        $this->programCreateTime = '0';
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
            'program_name' => 'programName',
            'program_url' => 'programUrl',
            'program_html' => 'programHtml',
            'program_content' => 'programContent',
            'program_status' => 'programStatus',
            'program_default' => 'programDefault',
            'program_pic' => 'programPic',
            'program_img' => 'programImg',
            'program_desc' => 'programDesc',
            'program_deals' => 'programDeals',
            'program_is_login' => 'programIsLogin',
            'program_area' => 'programArea',
            'program_create_time' => 'programCreateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_preset_program";
    }
}