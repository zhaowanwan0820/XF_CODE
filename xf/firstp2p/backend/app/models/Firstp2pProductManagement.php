<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pProductManagement extends ModelBaseNoTime
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
    public $product_id;


    /**
     *
     * @var string
     */
    public $product_name;


    /**
     *
     * @var integer
     */
    public $advisory_id;


    /**
     *
     * @var string
     */
    public $advisory_name;


    /**
     *
     * @var float
     */
    public $money_limit;


    /**
     *
     * @var float
     */
    public $use_money;


    /**
     *
     * @var integer
     */
    public $money_effect_term_start;


    /**
     *
     * @var integer
     */
    public $money_effect_term_end;


    /**
     *
     * @var string
     */
    public $operate_person;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $is_warning;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->productId = '0';
        $this->productName = '';
        $this->advisoryId = '0';
        $this->advisoryName = '';
        $this->moneyLimit = '0.00';
        $this->useMoney = '0';
        $this->moneyEffectTermStart = '0';
        $this->moneyEffectTermEnd = '0';
        $this->operatePerson = '';
        $this->isEffect = '0';
        $this->isDelete = '0';
        $this->isWarning = '0';
        $this->updateTime = '0';
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
            'product_id' => 'productId',
            'product_name' => 'productName',
            'advisory_id' => 'advisoryId',
            'advisory_name' => 'advisoryName',
            'money_limit' => 'moneyLimit',
            'use_money' => 'useMoney',
            'money_effect_term_start' => 'moneyEffectTermStart',
            'money_effect_term_end' => 'moneyEffectTermEnd',
            'operate_person' => 'operatePerson',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'is_warning' => 'isWarning',
            'update_time' => 'updateTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_product_management";
    }
}