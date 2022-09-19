<?php
namespace core\dao;

class CouponBaseModel extends BaseModel {

    public static $_is_use_slave = true;

    protected static $module_name = '';

    protected static $instance;

    protected $dataType = 0;

    public function __construct(){
        parent::__construct();
       // $this->db = \libs\db\Db::getInstance('coupon');
    }

    public static function getInstance($module_name = '', $dataType = 0)
    {
        self::$module_name = $module_name;
        if ($module_name == 'p2p'){
            $module_name = '';
        }
        $className = get_called_class();
        if(!empty($module_name)){
            $className  = str_replace('Model', ucfirst($module_name).'Model', $className);
        }
        if (! isset(self::$instance[$className])) {
            self::$instance[$className] = new $className();
        }
        self::$instance[$className]->dataType = $dataType;
        return self::$instance[$className];
    }

     /*
     * 获取投资记录id范围,区分名字改叫服务奖励还是邀请奖励
     */
    public function setDealLoadIdCond($dataType = 0,$item='ncfph'){

        $dealLoadId = $this->getSplitDealLoadId($item);

        if($dataType == 1){
            return ' AND deal_load_id <= '.intval($dealLoadId);
        }

        if($dataType == 2){
            return ' AND deal_load_id > '.intval($dealLoadId);
        }

        return '';
    }

    /**
     * 获取分割投资记录id
     */
    public function getSplitDealLoadId($item='ncfph'){
        switch ($item) {
            case 'ncfph':
                $dealLoadId = app_conf("COUPON_NCFPH_SPLIT_DEAL_LOAD_ID");
                break;
            case 'p2p':
                $dealLoadId = app_conf("COUPON_P2P_SPLIT_DEAL_LOAD_ID");
                break;
            case 'duotou':
                $dealLoadId = app_conf("COUPON_DUOTOU_SPLIT_DEAL_LOAD_ID");
                break;
            default:
                $dealLoadId = 0;
                break;
        }

        return $dealLoadId;
    }
}