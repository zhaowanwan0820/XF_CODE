<?php

namespace NCFGroup\Ptp\models\cfp;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class CfpCustomerMap extends ModelBaseNoTime
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
    public $cfp_id;


    /**
     *
     * @var integer
     */
    public $customer_id;


    /**
     *
     * @var string
     */
    public $memo;


    /**
     *
     * @var date
     */
    public $ctime;


    /**
     *
     * @var date
     */
    public $mtime;

    //END PROPERTY

    public function initialize()
    {
        //BEGIN DEFAULT_VALUE
        $this->memo = '';
        $this->ctime = '0000-00-00 00:00:00';
        $this->mtime = XDateTime::now();
        //END DEFAULT_VALUE
        parent::initialize();
        $this->setConnectionService('cfp');
    }

    public function columnMap()
    {
        return array(
            'id' => 'id',
            'cfp_id' => 'cfpId',
            'customer_id' => 'customerId',
            'memo' => 'memo',
            'ctime' => 'ctime',
            'mtime' => 'mtime',
        );
    }

    public function getSource()
    {
        return "cfp_customer_map";
    }
}