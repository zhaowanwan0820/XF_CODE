<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;
use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;

class Firstp2pThirdpartyInvest0 extends ModelBaseNoTime
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
    public $merchantNo;


    /**
     *
     * @var integer
     */
    public $merchantId;


    /**
     *
     * @var string
     */
    public $outOrderId;


    /**
     *
     * @var integer
     */
    public $userId;


    /**
     *
     * @var integer
     */
    public $amount;


    /**
     *
     * @var string
     */
    public $orderStatus;


    /**
     *
     * @var string
     */
    public $paymentId;


    /**
     *
     * @var integer
     */
    public $createTime;


    /**
     *
     * @var integer
     */
    public $updateTime;

    //END PROPERTY

    /**
     * 按该字段进行拆分数据表
     * @var string
     */
    const SHARDING_FIELD_KEY = 'merchantId';

    /**
     * 要拆分的数据表个数
     * @var int
     */
    const SHARDING_TABLE_NUM = 32;

    /**
     * 按该值进行拆分数据表，查询操作时需要指定
     * @var int
     */
    public static $shardFieldValue = null;

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->merchantNo = '';
        $this->userId = '0';
        $this->amount = '0';
        $this->orderStatus = 'N';
        $this->paymentId = '';
        $this->createTime = '0';
        $this->updateTime = '0';
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
            'merchantNo' => 'merchantNo',
            'merchantId' => 'merchantId',
            'outOrderId' => 'outOrderId',
            'userId' => 'userId',
            'amount' => 'amount',
            'orderStatus' => 'orderStatus',
            'paymentId' => 'paymentId',
            'createTime' => 'createTime',
            'updateTime' => 'updateTime',
        );
    }

    public function getSource()
    {
        return sprintf('firstp2p_thirdparty_invest_%d', fmod(floatval(!is_null(self::$shardFieldValue) ? self::$shardFieldValue : $this->{self::SHARDING_FIELD_KEY}), self::SHARDING_TABLE_NUM));
    }

    /**
     * 使用Pageable分页查询
     * @param Pageable $pageable
     * @param array $condition
     * @return Page
     */
    public static function findByPageableList(Pageable $pageable, array $condition = [], array $searchFields = [], $isCleanModels = true)
    {
        $totalCnt = self::count($condition);
        $condition['limit'] = self::_limit($pageable);
        $models = self::find($condition);
        $cleanModels = array();
        if ($isCleanModels) {
            if ($models) {
                foreach($models as $model){
                    $cleanModels[] = self::reorganizeData($searchFields, $model);
                }
            }
        } else {
            $cleanModels =& $models;
        }

        return new Page($pageable, $totalCnt, $cleanModels);
    }

    /**
     * 整理数据
     * @param array $data
     */
    public static function reorganizeData(array $searchFields = [], &$modelData)
    {
        if ($searchFields && !empty($modelData)) {
            $data = array();
            foreach ($searchFields as $field) {
                if (strlen($field) <= 0) continue;
                if (false !== strpos($field, '|')) {
                    list($field, $newField, $format) = explode('|', $field);
                }
                $data[(isset($newField) ? $newField : $field)] = (!empty($modelData->$field)
                    ? (!empty($format) ? self::_toDate($modelData->$field, $format)
                    : $modelData->$field) : '');
            }
            return $data;
        }
        return !empty($modelData) ? $modelData->toArray() : array();
    }

    private static function _limit(Pageable $pageable)
    {
        return array('offset' => ($pageable->getPageNo() - 1) * $pageable->getPageSize(),
            'number' => $pageable->getPageSize());
    }

    /**
     * 转换时间或时间戳
     * @param int/string $dateString    需要转换的时间或时间戳
     * @param string $format    时间格式
     */
    private static function _toDate($dateString, $format = 'Y-m-d H:i:s')
    {
        switch ($format) {
            case 'timestamp':
                return strtotime($dateString);
                break;
            default:
                return is_numeric($dateString) ? date($format, $dateString) : $dateString;
                break;
        }
        return $dateString;
    }
}