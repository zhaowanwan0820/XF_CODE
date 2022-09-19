<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserWork extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $office;


    /**
     *
     * @var string
     */
    public $jobtype;


    /**
     *
     * @var integer
     */
    public $province_id;


    /**
     *
     * @var integer
     */
    public $city_id;


    /**
     *
     * @var string
     */
    public $officetype;


    /**
     *
     * @var string
     */
    public $officedomain;


    /**
     *
     * @var string
     */
    public $officecale;


    /**
     *
     * @var string
     */
    public $position;


    /**
     *
     * @var string
     */
    public $salary;


    /**
     *
     * @var string
     */
    public $workyears;


    /**
     *
     * @var string
     */
    public $workphone;


    /**
     *
     * @var string
     */
    public $workemail;


    /**
     *
     * @var string
     */
    public $officeaddress;


    /**
     *
     * @var string
     */
    public $urgentcontact;


    /**
     *
     * @var string
     */
    public $urgentrelation;


    /**
     *
     * @var string
     */
    public $urgentmobile;


    /**
     *
     * @var string
     */
    public $urgentcontact2;


    /**
     *
     * @var string
     */
    public $urgentrelation2;


    /**
     *
     * @var string
     */
    public $urgentmobile2;

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
            'user_id' => 'userId',
            'office' => 'office',
            'jobtype' => 'jobtype',
            'province_id' => 'provinceId',
            'city_id' => 'cityId',
            'officetype' => 'officetype',
            'officedomain' => 'officedomain',
            'officecale' => 'officecale',
            'position' => 'position',
            'salary' => 'salary',
            'workyears' => 'workyears',
            'workphone' => 'workphone',
            'workemail' => 'workemail',
            'officeaddress' => 'officeaddress',
            'urgentcontact' => 'urgentcontact',
            'urgentrelation' => 'urgentrelation',
            'urgentmobile' => 'urgentmobile',
            'urgentcontact2' => 'urgentcontact2',
            'urgentrelation2' => 'urgentrelation2',
            'urgentmobile2' => 'urgentmobile2',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_work";
    }
}