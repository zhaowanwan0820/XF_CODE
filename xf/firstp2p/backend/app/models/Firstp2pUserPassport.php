<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserPassport extends ModelBaseNoTime
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
     * @var string
     */
    public $region;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $passportid;


    /**
     *
     * @var date
     */
    public $valid_date;


    /**
     *
     * @var integer
     */
    public $sex;


    /**
     *
     * @var date
     */
    public $birthday;


    /**
     *
     * @var string
     */
    public $idno;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $ctime;


    /**
     *
     * @var integer
     */
    public $utime;


    /**
     *
     * @var string
     */
    public $file;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->region = '';
        $this->name = '';
        $this->passportid = '';
        $this->validDate = '0000-00-00';
        $this->sex = '0';
        $this->birthday = '0000-00-00';
        $this->idno = '';
        $this->type = '1';
        $this->ctime = '0';
        $this->utime = '0';
        $this->file = '';
        $this->status = '0';
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
            'region' => 'region',
            'name' => 'name',
            'passportid' => 'passportid',
            'valid_date' => 'validDate',
            'sex' => 'sex',
            'birthday' => 'birthday',
            'idno' => 'idno',
            'type' => 'type',
            'ctime' => 'ctime',
            'utime' => 'utime',
            'file' => 'file',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_passport";
    }
}