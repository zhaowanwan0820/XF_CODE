<?php
/**
 * Deal class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\DealAgencyModel;
use core\dao\DealCateModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealLoadModel;
use core\dao\DealPrepayModel;
use core\dao\DealRepayModel;
use core\dao\DealExtModel;
use core\dao\DealSiteModel;
use core\dao\DealQueueModel;
use core\dao\DealGuarantorModel;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\service\EarningService;
use libs\web\Url;
use libs\utils\Finance;
use api\controllers\user\Auth;
use core\dao\FinanceQueueModel;
use core\service\BonusService;
use libs\utils\PaymentApi;
use core\service\deal\FailState;
use core\service\ContractService;
use core\service\CouponService;
use core\service\DealService;
use core\service\ChannelFeeService;
use core\dao\BonusAccountModel;
use core\dao\BonusGroupModel;
use core\service\MsgBoxService;
use core\service\TransferService;
use core\service\ReservationMatchService;
use core\service\ReservationConfService;
use core\service\DealProjectService;
use libs\payment\supervision\Supervision;

use core\service\DtDealService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\DealLoansMsgEvent;
use core\event\ReserveDealLoansCacheEvent;
use libs\utils\Aes;
use core\dao\DealProjectModel;
use core\dao\DealContractModel;
use core\dao\UserCompanyModel;
use core\dao\UserCarryModel;

use NCFGroup\Protos\Contract\RequestSetDealCId;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use libs\utils\Rpc;
use libs\utils\XDateTime;
use libs\utils\Logger;
use core\dao\DealCustomUserModel;
use core\service\BwlistService;
use core\service\CouponBindService;

/**
 * Deal class
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class DealModel extends BaseModel {
    const DAY = "天";
    const HOUR = "时";
    const MINUTE = "分";

    const DAY_OF_YEAR = 360;    //金融计算通常将一年作为360天计算
    const DAY_OF_MONTH = 30;    //一月作为30天计算
    const MONTH_OF_YEAR = 12;   //一年中的月数
    const RATE_DIGIT = 5; //利率位数

    //投资人群
    const DEAL_CROWD_ALL = 0; //全部用户
    const DEAL_CROWD_NEW = 1; //新手专享
    const DEAL_CROWD_SPEC = 2; //专享
    const DEAL_CROWD_MOBILE = 4; //手机专享
    const DEAL_CROWD_MOBILE_NEW = 8; //手机新手标
    const DEAL_CROWD_SPECIFY_USER = 16; // 指定用户可投
    const DEAL_CROWD_OLD_USER = 32; // 老用户专享
    const DEAL_CROWD_VIP = 33; // vip用户专享
    const DEAL_CROWD_CUSTORM = 34; // 批量导入用户定制
    const DEAL_CROWD_GROUP = 35; // 指定用户组可投

    // 标类型
    const DEAL_TYPE_GENERAL = 0; //普通标
    const DEAL_TYPE_COMPOUND = 1;  //通知贷
    const DEAL_TYPE_EXCHANGE = 2;  //交易所
    const DEAL_TYPE_EXCLUSIVE = 3;  //专享
    const DEAL_TYPE_ALL_P2P = "0,1";  //所有p2p包含通知贷和普通标

    // 此类型为虚拟类型，deal表中不存在类型为4的记录
    const DEAL_TYPE_SUPERVISION = 4; // 走存管标的类型不含通知贷
    const DEAL_TYPE_PETTYLOAN = 5;//小贷

    const DEAL_REPORT_TYPE_YES = 1; // 需要到存管行报备
    const DEAL_REPORT_TYPE_NO = 0; // 不需要到存管行报备
    const DEAL_REPORT_STATUS_YES = 1; // 已经报备到存管行
    const DEAL_REPORT_STATUS_NO = 0; // 未报备到存管行
    // 黄金项目标中的类型，p2p deal中不做记录
    const DEAL_TYPE_GOLD = 100;

    const DEAL_FLOAT_MIN_LOAN_MONEY_YES = 1; // 启用标的浮动起投金额
    const DEAL_FLOAT_MIN_LOAN_MONEY_NO = 0; // 不启用标的浮动起投金额

    const DEAL_MIN_LOAN_UNIT = 1000; // 标的最小投资单位
    const DEAL_MAX_LOAN_COUNT = 200; /// 标的最大投资次数
    const DEAL_MIN_LOAN_MONEY = 1000; // 最小投资钱数

    public $is_slave = 1;  // 默认走从库

    /**
     * 借款状态
     *
     * @var string
     **/
    public static $DEAL_STATUS = array(
        'waiting'     => 0, //等待材料
        'progressing' => 1, //进行中
        'full'        => 2, //满标
        'failed'      => 3, //流标
        'repaying'    => 4, //还款中
        'repaid'      => 5, //已还清
        'reserving'   => 6, //预约投标中
    );

    const DURING_REPAY = 1;//正在还款中
    const NOT_DURING_REPAY = 0;//未处于还款中

    // dealagency与deal表中的机构类型对照表
    static $agencyKey = array(
        DealAgencyModel::TYPE_GUARANTEE => 'agency_id', // 担保
        DealAgencyModel::TYPE_CONSULT   => 'advisory_id', // 咨询
        // DealAgencyModel::TYPE_PLATFORM  => 'site_id', // 平台机构在deal_site表中与deal关联
        DealAgencyModel::TYPE_PAYMENT   => 'pay_agency_id', // 支付
        DealAgencyModel::TYPE_MANAGEMENT=> 'management_agency_id',
        DealAgencyModel::TYPE_ADVANCE   => 'advance_agency_id', // 垫付
    );

    /**
     * 根据参数拼装查询条件
     * @param $type string 类型分类（tab标签）
     * @param $sort array() 排序规则
     * @param $is_all_site bool
     * @param $is_display bool
     * @param $site_id int
     * @param $option array() 排序规则
     * @param bool $is_real_site 是true的话，site_id 不读配置，只读传过来的site_id,false 不做任何处理，默认是false
     * @param bool $is_bxt 默认为false，如果为False的话，原先的逻辑，如果为true只显示变现通标的
     * @return array
     */
    protected function buildCondQuery($type, $sort, $is_all_site=false, $is_display=false, $site_id=0, $option=array(), $is_real_site=false, $is_bxt=false) {
        $condition = " AND `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0";

        if ($is_display == true) {
            $condition .= " AND `is_visible`='1'";
        }

        /*
        //变现通类型
        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        if ( $is_bxt == false && !empty($bxtTypeId) ){
            $condition .= " AND `type_id`!=':bxtTypeId' ";
        }
        */

        if ($type) {
            $condition .= " AND `type_id` IN (:type)";
        }

        if(isset($option['show_crowd_specific']) && $option['show_crowd_specific'] == 0){
            $condition .= " AND `deal_crowd` != 2";
        }

        // 存管abtest 不在白名单用户不能看到存管标的
        if(!isset($option['isHitSupervision']) || $option['isHitSupervision'] !== true){
            $condition .= " AND `report_status` !=1";
        }

        // 产品类型二级分类ID
        if(isset($option['product_class_type']) && $option['product_class_type'] != 0){
            $condition .= " AND `product_class_type` = ".intval($option['product_class_type']);
        }

        // 借款客群
        if(isset($option['loan_user_customer_type']) && $option['loan_user_customer_type'] != 0){
            $condition .= " AND `loan_user_customer_type` = ".intval($option['loan_user_customer_type']);
        }

        if ($is_real_site === false && $is_all_site === false) {
            $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
            $site_id = $site_id==0 ? 0 : $site_id;
        }

        // JIRA#2994 特定标的不展示
        if (app_conf('DEAL_ID_FORBIDDEN_LIST')) {
            $ids_forbidden = explode(',', app_conf('DEAL_ID_FORBIDDEN_LIST'));
            $condition .= " AND `id` NOT IN (" . implode(",", $ids_forbidden) . ")";
        }

        //deal_type 标类型 是不是利滚利
        if (isset($option['deal_type']) && $option['deal_type'] !== ''){
            $condition .= " AND `deal_type` IN ({$option['deal_type']}) ";
        }
        // 用户未登录只有siteid的情况
        if (isset($option['not_deal_type']) && $option['not_deal_type'] !== ''){
            $condition .= " AND `deal_type` NOT IN ({$option['not_deal_type']}) ";
        }
        //$dealCustomUserModel = new DealCustomUserModel();
        //$dealCustomStr = $dealCustomUserModel->getCommaSeparatedDealId();

        $dealCustomStr = $this->getListBatchImportByDealCrowd(2);

        if (!empty($dealCustomStr)) {
            // 是否读取定制标
            if (isset($option['is_read_deal_custom_user']) && $option['is_read_deal_custom_user'] == true) {
                $condition .= " AND `id` IN ($dealCustomStr)";
            } else {
                $condition .= " AND `id` NOT IN ($dealCustomStr)";
            }
        }
        // deal_tag_name,根据标签类型获取
        if (isset($option['deal_tag_name']) && is_array($option['deal_tag_name']) && !empty($option['deal_tag_name'])){
            $tmp = array();
            foreach( $option['deal_tag_name'] as $oneTag){
                $tmp[] = "deal_tag_name='{$oneTag}'";
            }
            $condition .= sprintf(' AND (%s)',implode(' OR ',$tmp));
        }

        //专享(资产管理计划)类型
        $zxTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_GLJH);

        $page = 1;
        $page_size = 0;
        $params = array(
            ":siteIds" => $site_id,
            ":zxTypeId" => $zxTypeId,
            ":type" => $type,
            ":prev_page" => ($page - 1) * $page_size,
            ":curr_page" => $page_size
        );

        // 机构相关的字段有不同key，对应查询
        foreach (self::$agencyKey as $keyName) {
            if (!empty($option[$keyName])) {
                $params[":{$keyName}"] = $option[$keyName];
                $condition .= " AND `{$keyName}` = :{$keyName}";
            }
        }

        if ($is_real_site === true || ($is_real_site === false && $is_all_site === false)){
            // 如若需要匹配站点但站点为空，数据为空，没意义,直接返回
            if ($site_id == 0) {
                return false;
                //return array('count' => 0, 'list' => array());
            }
            $condition .= " AND site_id IN (:siteIds) ";
        }

        return array('cond'=>$condition, 'param'=>$params);
    }

    public function getListV2($type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array(),$is_real_site=false, $count_only=false, $is_bxt=false,$need_count=true) {

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id` FROM " .$this->tableName()
            ;

        $arr = $this->buildCondQuery($type, $sort, $is_all_site, $is_display, $site_id, $option, $is_real_site, $is_bxt);

        if ($arr === false) {
            return array('count' => 0, 'list' => array());
        }

        $condition = $arr['cond'];
        $params = $arr['param'];
        $order = " ORDER BY `id` DESC";
        $result = array();
        $count = 0;

        // 展示顺序12045
        $arr_deal_status1 = array(
            self::$DEAL_STATUS['progressing'],
            self::$DEAL_STATUS['full'],
            self::$DEAL_STATUS['waiting'],
        );

        $arr_deal_status2 = array(
            self::$DEAL_STATUS['repaying'],
            self::$DEAL_STATUS['repaid'],
        );

        // 第一分页展示进行中、满标、等待确认标的
        if ($page <= 1) {
            foreach ($arr_deal_status1 as $deal_status) {
                $cond = "`deal_status` = '{$deal_status}'" . $condition;
                $sql_tmp = $sql . " WHERE " . $cond . $order;

                $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);

                foreach($data as $v) {
                    $result[] = $v;
                }
            }

        } else {
            $page--;

            $start = ($page - 1) * $page_size;
            $end = $page * $page_size;


            //逐个状态进行读取，满足条数则返回结果
            foreach ($arr_deal_status2 as $deal_status) {
                $sql_cnt = "SELECT count(*) FROM " .$this->tableName();
                $cond = "`deal_status` = '{$deal_status}'" . $condition;
                $cnt = $this->countBySql($sql_cnt . " WHERE " . $cond, $params, true);

                // 如果当前状态没有标的，直接检测下一状态
                if ($cnt <= 0) {
                    continue;
                }

                $total = $count;
                $count += $cnt;

                if ($start < $count && $end > $total) {
                    $size = $end - $total;
                    $size = $size > $page_size ? $page_size : $size;

                    $start_tmp = $start > $total ? $start - $total : 0;

                    $limit = " LIMIT {$start_tmp}, {$size}";
                    $sql_tmp = $sql . " WHERE " . $cond . $order . $limit;

                    $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);
                    $c = count($data);

                    foreach($data as $v) {
                        $result[] = $v;
                    }

                    // 剩余查询量减少本次查询量
                    $page_size -= $c;
                    if ($page_size <= 0) {
                        // 剩余查询量为0时，如果需要计数，继续循环，但只执行计数统计；否则直接退出循环返回结果
                        if ($need_count) {
                            continue;
                        } else {
                            break;
                        }
                    }
                }
            }
        }
        if ($count_only === false) {
            return array("count"=>$count, "list"=>$result);
        } else {
            return array("count"=>$count);
        }
    }

    /**
     * 获取投资列表
     * @param $type string 类型分类（tab标签）
     * @param $sort array() 排序规则
     * @param $page int
     * @param $page_size int
     * @param $is_all_site bool
     * @param $is_display bool
     * @param bool $is_real_site 是true的话，site_id 不读配置，只读传过来的site_id,false 不做任何处理，默认是false
     * @param bool $count_only 默认为false，为减少数据访问以节省流量，count_only为true时仅获取count数据
     * @param bool $is_bxt 默认为false，如果为False的话，原先的逻辑，如果为true只显示变现通标的
     * @param bool $need_count 默认为true 为false的话不需要进行count统计
     * @return array("count"=>xx, "list"=>array(***))
     */
    public function getList($type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array(),$is_real_site=false, $count_only=false, $is_bxt=false,$need_count=true) {

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id`,`product_class_type` FROM " .$this->tableName()
            ;

        $arr = $this->buildCondQuery($type, $sort, $is_all_site, $is_display, $site_id, $option, $is_real_site, $is_bxt);

        if ($arr === false) {
            return array('count' => 0, 'list' => array());
        }

        $condition = $arr['cond'];
        $params = $arr['param'];
        $order = " ORDER BY `id` DESC,`deal_status` DESC";

        // 展示顺序12045
        $arr_deal_status = array(
            self::$DEAL_STATUS['progressing'],
            self::$DEAL_STATUS['full'],
            self::$DEAL_STATUS['waiting'],
            self::$DEAL_STATUS['repaying'],
            //self::$DEAL_STATUS['repaid'],
        );

        $start = ($page - 1) * $page_size;
        $end = $page * $page_size;
        $count = 0;
        $result = array();

        //逐个状态进行读取，满足条数则返回结果
        foreach ($arr_deal_status as $deal_status) {
            $cond = "`deal_status` = '{$deal_status}'" . $condition;
            $sql_cnt = "SELECT count(`id`) AS 'c' FROM " . $this->tableName() . " WHERE `deal_status` = '{$deal_status}' " . $condition;
            $r = $this->findBySql($sql_cnt, $params, true);
            $cnt = $r['c'];

            // 如果当前状态没有标的，直接检测下一状态
            if ($cnt <= 0) {
                continue;
            }

            $total = $count;
            $count += $cnt;

            // 标的列表排序，仅进行中的标的依次按产品分类('供应链','企业经营贷','消费贷','个体经营贷')、期限(短到长)、id;
            // DEAL_LIST_SORT_PRODUCTCLASSTYPE: 配置product_class_type id 排序序列; （field倒序，因match不到会赋值0排最前面); 生产typeid顺序：232,5,316,315,223
            // ID查询SQL:select id, name from firstp2p_deal_type_grade where name in ('个体经营贷','消费贷','企业经营贷','供应链') order by field(name,'个体经营贷','消费贷','企业经营贷','供应链')
            if ($deal_status == self::$DEAL_STATUS['progressing']) {
                $conf_sort_product_class_type = app_conf('DEAL_LIST_SORT_PRODUCTCLASSTYPE');
                if (!empty($conf_sort_product_class_type)) {
                    $order = " ORDER BY field(`product_class_type`,{$conf_sort_product_class_type}) DESC, if(`loantype`=5, `repay_time`, `repay_time`*30), `id` DESC ";
                }
            }

            if ($start < $count && $end > $total) {
                $size = $end - $total;
                $size = $size > $page_size ? $page_size : $size;

                $start_tmp = $start > $total ? $start - $total : 0;

                $limit = " LIMIT {$start_tmp}, {$size}";
                $sql_tmp = $sql . " WHERE " . $cond . $order . $limit;

                $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);
                $c = count($data);

                foreach($data as $v) {
                    $result[] = $v;
                }

                // 剩余查询量减少本次查询量
                $page_size -= $c;
                if ($page_size <= 0) {
                    // 剩余查询量为0时，如果需要计数，继续循环，但只执行计数统计；否则直接退出循环返回结果
                    if ($need_count) {
                        continue;
                    } else {
                        break;
                    }
                }
            }
        }

        if ($count_only === false) {
            return array("count"=>$count, "list"=>$result);
        } else {
            return array("count"=>$count);
        }
    }

    /**
     * 获取 投资限定条件等于34
     * @param $return_type 1 返回数组，2返回以逗号隔开的字符串
     */
    public function getListBatchImportByDealCrowd($return_type = 1){

        $sql = "SELECT id FROM firstp2p_deal d WHERE d.deal_status IN (1,2) AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0
            AND d.`is_visible`='1' AND d.deal_crowd IN (".self::DEAL_CROWD_CUSTORM . ", ".self::DEAL_CROWD_GROUP.")";

        $result = $this->findAllBySqlViaSlave($sql, true);
        if ($return_type == 2){
            $str = '';
            if (!empty($result)){
                $dealIds = array();
                foreach($result as $v){
                    $dealIds[$v['id']] = $v['id'];
                }

                if (!empty($dealIds)){
                    $str = implode(',',$dealIds);
                }
            }
            return $str;
        }
        return $result;
    }
    /**
     * 获取处于还款中正在还款的deal数量
     * @param int $is_login 0 未登录，1已登录
     */
    public function getDuringRepayCount($user_id, $is_during_repay = 1) {
        $condition =sprintf("`user_id`= '%d' AND `is_during_repay`= '%d'", $user_id, $is_during_repay );
        return $this->count($condition);
    }



    /**
     * 获取标的分类数量
     * @param int $is_login 0 未登录，1已登录
     */
    public function getDealCategoryNum($is_login = 1) {
        //多投宝类型
        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);

        $common_condition = " `deal_status` = 1 AND `is_effect`='1' AND `is_delete`='0' AND `is_visible`='1' AND `publish_wait` = 0 ";
        if ( !empty($dtbTypeId) ){
            $common_condition .= " AND `type_id`!=':dtbTypeId' ";
        }

        if (app_conf('DEAL_ID_FORBIDDEN_LIST')) {
            $ids_forbidden = explode(',', app_conf('DEAL_ID_FORBIDDEN_LIST'));
            $common_condition .= " AND `id` NOT IN (" . implode(",", $ids_forbidden) . ")";
        }
        $common_condition .= " AND `id` IN(SELECT `deal_id` FROM ".DealSiteModel::instance()->tableName()." WHERE `site_id` IN (:siteIds))";

        $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
        $site_id = $site_id==0 ? app_conf('TEMPLATE_ID') : $site_id;
        $params = array(
                ":dtbTypeId" => $dtbTypeId,
                ":siteIds" => $site_id,
                );

        $deals = [];
        $dealings = $this->findAllViaSlave($common_condition, true, 'id, deal_type,deal_crowd', $params);
        $res = ['ZX' => 0, 'P2P' => 0, 'JYS' => 0,'CUZX' => 0];

        foreach ($dealings as $val) {
            // 统计定制标 #1691
            if ($val['deal_crowd'] == self::DEAL_CROWD_CUSTORM && $is_login){
                $res['CUZX'] ++;
                continue;
            }
            if ($val['deal_crowd'] == self::DEAL_CROWD_GROUP){
                continue;
            }
            if ($val['deal_type'] == self::DEAL_TYPE_EXCLUSIVE && $is_login) {
                $res['ZX'] ++;
                continue;
            }
            if ($val['deal_type'] == self::DEAL_TYPE_EXCHANGE) {
                $res['ZX'] ++;
                $res['JYS'] ++;
                continue;
            }
            $p2pType = explode(',', self::DEAL_TYPE_ALL_P2P);
            if (in_array($val['deal_type'], $p2pType)) {
                $res['P2P'] ++;
                continue;
            }
        }


        return $res;
    }

    /**
     * 根据siteId获取当前进行中的新手标
     * @return array
     */
    public function getNewUserDealList() {
        $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
        $site_id = $site_id == 0 ? 0 : $site_id;
        $condition = sprintf("`deal_crowd`='1' AND `deal_status`='1'  AND `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0 AND `site_id` IN (%s) LIMIT 1", $site_id);
        $row = $this->findByViaSlave($condition);
        return $row;
    }

    /**
     * 获取当前可投的专享标的
     * @param int $count
     * @return array
     */
    public function getProcessingList($count,$min_loan_money=100) {
        $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
        $site_id = $site_id == 0 ? 0 : $site_id;
        $sql = "select *, case when loantype=5 then  repay_time else repay_time * 30 end as real_repay_time FROM firstp2p_deal ";
        $sql.=" WHERE deal_type='0' AND `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0 AND `deal_crowd` != '1'  AND `deal_status`='1' ";

        if($min_loan_money > 0 ){
            $sql.=" AND `min_loan_money` <=100 ";
        }

        $sql.=" AND `site_id` IN (%s)  ORDER BY real_repay_time ASC LIMIT %d";

        $sql = sprintf($sql, $site_id, $count);
        return $this->findAllBySqlViaSlave($sql);
    }

    /**
     * getListNew
     * 新查询接口，区间段获取信息
     *
     * @param mixed $type
     * @param mixed $sort
     * @param mixed $page
     * @param mixed $page_size
     * @param mixed $is_all_site
     * @param mixed $is_display
     * @param int $site_id
     * @param array $option
     * @access public
     * @return void
     */
    public function getListNew($type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array()) {
        $params = array();
        $limit = " LIMIT :prev_page , :curr_page";
        $params[':prev_page'] = ($page - 1) * $page_size;
        $params[':curr_page'] = $page_size;

        $condition = " `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0 AND `deal_status` in(0,1,2,4,5)";
        if ($is_display == true) {
            $condition .= " AND `is_visible`='1'";
        }
        if ($type) {
            $condition .= " AND `type_id` IN (:type)";
            $params[':type'] = $type;
        }

        if(isset($option['show_crowd_specific']) && $option['show_crowd_specific'] == 0){
            $condition .= " AND `deal_crowd` != 2";
        }
        $order  = " ORDER BY";

        $field = $sort['field'];
        $field_sort = $sort['type'];
        if ($field && $field_sort) {
            if($field == 'deal_status'){
                $order .= " FIELD(`deal_status`, 1,0,2,4,5,3)  {$this->escape($field_sort)} ,`sort` DESC, `id` DESC";
            } else {
                $order .= " {$this->escape($field)} {$this->escape($field_sort)}, `sort` DESC, `id` DESC";
            }
        } else {
            $order .= " FIELD(`deal_status`, 1,0,2,4,5,3) , `update_time` DESC, `sort` DESC, `id` DESC";
        }

        if ($is_all_site === false) {
            $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
            $site_id = $site_id==0 ? app_conf('TEMPLATE_ID') : $site_id;
            $condition .= " AND `id` IN (SELECT `deal_id` FROM " . DealSiteModel::instance()->tableName() . " WHERE `site_id` in ({$site_id}))";
        }
        if (is_array($option['income_rate'])) {
            if (isset($option['income_rate']['min']) && $option['income_rate']['min'] != -1) {
                $condition .= " AND income_total_rate >= ':rate_min'";
                $params[':rate_min'] = $option['income_rate']['min'];
            }
            if (isset($option['income_rate']['max']) && $option['income_rate']['max'] != -1) {
                $condition .= " AND income_total_rate <= ':rate_max'";
                $params[':rate_max'] = $option['income_rate']['max'];
            }
        }
        if (is_array($option['total'])) {
            if (isset($option['total']['min']) && $option['total']['min'] != -1) {
                $condition .= " AND borrow_amount >= ':borrow_amount_min'";
                $params[':borrow_amount_min'] = $option['total']['min'];
            }
            if (isset($option['total']['max']) && $option['total']['max'] != -1) {
                $condition .= " AND borrow_amount <= ':borrow_amount_max'";
                $params[':borrow_amount_max'] = $option['total']['max'];
            }
        }

        // 如果搜索条件中有投资期限条件，则根据还款方式和还款期限搜索，如果为按天还款，则需要把搜索期限*30
        // TODO 对于时间变为区间如何处理
        if (is_array($option['repay_time'])) {
            if (isset($option['repay_time']['min']) && isset($option['repay_time']['max']) && $option['repay_time']['min'] != -1 && $option['repay_time']['max'] != -1) {
                $condition .= " AND ((loantype != 5 AND repay_time >= ':month_min' AND repay_time <= ':month_max') OR (loantype=5 AND repay_time >= ':day_min' AND repay_time <= ':day_max'))";
                $params[':month_min'] = $option['repay_time']['min'];
                $params[':month_max'] = $option['repay_time']['max'];
                $params[':day_min'] = $option['repay_time']['min'] * self::DAY_OF_MONTH;
                $params[':day_max'] = $option['repay_time']['max'] * self::DAY_OF_MONTH;
            } elseif (isset($option['repay_time']['min']) && $option['repay_time']['min'] != -1) {
                $condition .= " AND ((loantype != 5 AND repay_time >= ':month_min') OR (loantype=5 AND repay_time >= ':day_min'))";
                $params[':month_min'] = $option['repay_time']['min'];
                $params[':day_min'] = $option['repay_time']['min'] * self::DAY_OF_MONTH;
            } elseif (isset($option['repay_time']['max']) && $option['repay_time']['max'] != -1) {
                $condition .= " AND ((loantype != 5 AND repay_time <= ':month_max') OR (loantype=5 AND repay_time <= ':day_max'))";
                $params[':month_max'] = $option['repay_time']['max'];
                $params[':day_max'] = $option['repay_time']['max'] * self::DAY_OF_MONTH;
            }
        }

        if (isset($option['uids'])) {
            $condition .= " AND user_id IN (:uids)";
            $params[':uids'] = $option['uids'];
        }

        $count = $this->countViaSlave($condition, $params);
        $data = $this->findAllViaSlave($condition . $order . $limit, true, '*', $params);

        return array("count"=>$count, "list"=>$data);
    }

    /**
     * 处理deal数据
     * @param $deal
     * @param int $data_type 0-全部deal数据 1-首页访问，减少不需要的数据库访问
     * $is_user_status  标对应用户的状态 要不要显示 默认显示
     * @return array()
     */
    public function handleDeal($deal, $data_type=0, $needSwitch = true) {
        $deal['old_name'] = $deal['name'];
        $deal['name'] = msubstr($deal['name'], 0, 40); //坑
        $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);

        if ($data_type!=1) {
            // 获取扩展信息
            if ($deal['cate_id'] > 0) {
                $sql = "`is_effect`='1' AND `is_delete`='0' AND `id`='{$this->escape($deal['cate_id'])}'";
                //只走从库
                $deal['cate_info'] = DealCateModel::instance()->findByViaSlave($sql);
            }
            if ($deal['type_id'] > 0) {
                //只走从库
                $deal['type_info'] = DealLoanTypeModel::instance()->findByViaSlave("`is_effect`='1' AND `is_delete`='0' AND `id`='{$this->escape($deal['type_id'])}'");

            }
        }

        if ($deal['agency_id'] > 0) {
            //只走从库
            $deal['agency_info'] = DealAgencyModel::instance()->findByViaSlave("`is_effect`='1' AND `id`='{$this->escape($deal['agency_id'])}'");
        }

        // 获取资产推荐方信息
        if (isset($deal['advisory_id']) && $deal['advisory_id'] > 0) {
            $deal['advisory_info'] = DealAgencyModel::instance()->findByViaSlave("`is_effect`='1' AND `id`='{$this->escape($deal['advisory_id'])}'");
        }

        // 格式化借款数据
        $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
        $deal['borrow_amount_format_detail'] = format_price($deal['borrow_amount'] / 10000,false);
        $deal['borrow_amount_wan_int'] = format_price($deal['borrow_amount'] / 10000, false)."万";
        $deal['rate_foramt'] = number_format($deal['rate'],2);

        $deal['remain_time'] = $deal['start_time'] + $deal['enddate'] * 24 * 3600 - get_gmtime();
        $deal['deal_tag'] = explode(',', $deal['deal_tag_name']) ;
        $i_tag =0;
        foreach($deal['deal_tag'] as $tag){
            if($i_tag){
                $deal['deal_tag_name'.$i_tag] = $tag;
            }else{
                $deal['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }
        unset($deal['deal_tag']);
        //投标剩余时间
        if ($deal['deal_status'] != 1 || $deal['remain_time'] <= 0) {
            $deal['remain_time_format'] = "0" . self::DAY . "0" . self::HOUR . "0" . self::MINUTE;
        } else {
            $d = intval($deal['remain_time'] / 86400);
            $h = floor($deal['remain_time'] % 86400 / 3600);
            $m = floor($deal['remain_time'] % 3600 / 60);
            $deal['remain_time_format'] = $d . self::DAY . $h . self::HOUR . $m . self::MINUTE;
        }

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        //起投金额大于剩余投资金额，起投金额等于最低起投金额
        if(bccomp($deal['need_money_decimal'], $deal['min_loan_money'], 2) == -1){
            $deal['min_loan_money'] = $deal['need_money_decimal'];
        }
        $deal['need_money'] = format_price($deal['need_money_decimal']);
        $deal['need_money_detail'] = format_price($deal['need_money_decimal'], false);

        $deal['min_loan_money_format'] = $deal['min_loan_money'] >= 10000 ?
            format_price($deal['min_loan_money'] / 10000, false)."万" : format_price($deal['min_loan_money'], false);
        $deal['min_loan_money_format_yuan'] = format_price($deal['min_loan_money']);

        // 流标时间
        if (!empty($deal['bad_time'])) {
            if (date("Y", $deal['bad_time']) != date("Y", get_gmtime())) {
                $bad_time_format = "Y年m月d日";
            } else {
                $bad_time_format = "m月d日";
            }
            $deal['flow_standard_time'] = to_date($deal['bad_time'], $bad_time_format);
        } else {
            $deal['flow_standard_time'] = "-";
        }
        // 满标时间
        if (!empty($deal['success_time'])) {
            if (date("Y", get_gmtime()) != date("Y", $deal['success_time'])) {
                $su_time_format = "Y年m月d日";
            } else {
                $su_time_format = "m月d日";
            }
            $deal['full_scale_time'] = to_date($deal['success_time'], $su_time_format);
        } else {
            $deal['full_scale_time'] = "-";
        }

        $deal['loantype_name'] = isDealP2P($deal['deal_type']) ? str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]) : $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        //修改此处为借款用途的图标
        if (isset($deal['type_info']['icon']) && $deal['type_info']['icon']) {
            $deal['icon'] = str_replace("./public/images/dealtype/","./static/img/dealtype/",$deal['type_info']['icon']);
        }

        if ($data_type != 1) {
            $deal['user_deal_name'] = $this->getDealUserName($deal['user_id']);

            //还款计划相关的内容
            $deal_repay = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);
            if ($deal_repay) {
                $deal['month_repay_money'] = $deal_repay->repay_money;
            } else {
                $pmt_info = $this->getPmtByDeal($deal);
                $deal['month_repay_money'] = $pmt_info['pmt'];
            }

            $deal['true_month_repay_money'] = ceilfix($deal['month_repay_money']);
        }

        //后台填的年利率
        $deal['int_rate'] = $deal['rate'];

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']){
            $period_income_rate = (1 + $deal['int_rate']/12/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /12/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 12 / $deal['repay_time']*100, 2);
            $deal['rate'] = number_format($deal['rate'], 2) . "%";
        /*} elseif ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $period_income_rate = (1 + $deal['int_rate']/4/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /4/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 4 / $deal['repay_time']*100, 2)."%";*/
        } else {
            //出借人年化收益率
            \FP::import("app.deal");
            $deal['rate'] = ($deal['income_fee_rate'] > 0) ? $deal['income_fee_rate']: get_invest_rate_data($deal['loantype'], $deal['repay_time']);
            $deal['rate'] = number_format($deal['rate'], 2) . "%"; // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
        }
        $deal['rate_show'] = number_format( (float)$deal['rate'], 2);
        $deal['repay_time_array'] = $this->numberToArrayForPic($deal['repay_time']);

        //获取此标的投资人群
        $deal['crowd_str'] = $GLOBALS['dict']['DEAL_CROWD'][$deal['deal_crowd']];
        //后台修改的借款年利率
        $deal['deal_rate'] = number_format($deal['int_rate'], 2) .'%';

        $deal['show_focus'] = 1;
        //获取此标的担保人状态
        $deal['guarantor_status'] = DealGuarantorModel::instance()->checkDealGuarantorStatus(intval($deal['id']));

        // 订单附加信息
        $deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->escape($deal['id'])}'");

        if (!empty($deal_ext)) {
            $ext_info = $deal_ext->getRow();
            foreach ($ext_info as $k => $v) {
                if (!isset($deal[$k])) {
                    $deal[$k] = $v;
                }
            }
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_float_rate'] = number_format($deal['income_float_rate'], 2, ".", "");
        $deal['income_subsidy_rate'] = number_format($deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        $deal['income_total_show_rate'] = number_format($deal['rate'] + $deal['income_subsidy_rate'], 2, ".", "");
        $deal['rate_show_array'] = $this->numberToArrayForPic($deal['income_total_show_rate']);
        $deal['max_rate'] = number_format( (float)$deal['rate'], 2);
        //订单状态文字
        $deal['deal_status_text'] = $this->getDealStatusText($deal);

        if ($data_type != 1) {
            $deal = $this->getEarningsInfo($deal);
        }

        // 开标时间，如果开标时间未到，则赋值给格式化后的开标时间，模板根据格式化后的开标时间判断是否显示开标时间
        if (!empty($deal['start_loan_time']) && $deal['start_loan_time']>get_gmtime() && $deal['deal_status']==0) {
            if (date("Y", get_gmtime()) != date("Y", $deal['start_loan_time'])) {
                $st_time_format = "Y-m-d H:i";
            } else {
                $st_time_format = "m-d H:i";
            }
            $deal['start_loan_time_format'] = to_date($deal['start_loan_time'], $st_time_format);
        //} else {
        //    $deal['start_loan_time_format'] = "-";
        }

        $project_obj =  DealProjectModel::instance()->findViaSlave($deal['project_id']);
        $project_info = !empty($project_obj) ? $project_obj->getRow() : array();
        if (!empty($project_obj)) {
            $project_info = $project_obj->getRow();

            $pro_service = new DealProjectService();
            $deal['is_entrust_zx']  =  $pro_service->isProjectEntrustZX($project_info['id']); // 是否为受托专享
            $deal['is_deal_zx'] = (new DealService())->isDealEx($deal['deal_type']); // 是否为专享(包含专享1.75和1.5)

            $fixed_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($project_info['fixed_value_date']) + 86399); // 因为目前固定起息日存的是当天 0 点,现在要显示成当天 23:59:59
            $start_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($deal['start_time']));
            $end_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($deal['start_time'] + $deal['enddate'] * 24 * 3600));

            $deal['formated_fixed_value_date']  =  $fixed_date_obj->getDateTime(); // 固定起息日
            // 标的发布时间
            $deal['formated_start_time']  =  $start_date_obj->getDateTime();
            // 标的截止时间
            $deal['formated_end_time']  =  $end_date_obj->getDateTime();
            $now_date_obj = XDateTime::now();
            if ($fixed_date_obj->getTime() <= $now_date_obj->getTime()) {
                $deal['formated_diff_time'] = array("day" => 0, "hour" => 0,"min" => 0, "sec" => 0);  // 过了固定起息日，就显示 0
            } else {
                $deal['formated_diff_time'] = XDateTime::getDiffInfo($now_date_obj, $fixed_date_obj);
            }
        }

        $deal['repay_start_time_name'] = '收益起算日';
        $deal['formated_repay_start_time'] = '--';
        if (in_array($deal['deal_status'], array(self::$DEAL_STATUS['waiting'], self::$DEAL_STATUS['progressing'], self::$DEAL_STATUS['full']))) {
            if ($deal['is_deal_zx']) {
                // JIRA#5410 这里区分专享1.5和1.75,显示不同文案
                $deal['formated_repay_start_time'] = !empty($project_info['fixed_value_date']) ? to_date($project_info['fixed_value_date'], "Y-m-d") : '放款后开始起算收益';
                $deal['repay_start_time_name'] =  !empty($project_info['fixed_value_date']) ? '预计收益起算日' : '收益起算日';

                // 针对投资确认页 的预计起息日显示
                $deal['expected_repay_start_time'] = sprintf('%s%s', $deal['repay_start_time_name'], $deal['formated_repay_start_time']);
            } else {
                $deal['formated_repay_start_time'] = '放款后开始起算收益';
            }
        } elseif (in_array($deal['deal_status'], array(self::$DEAL_STATUS['repaying'], self::$DEAL_STATUS['repaid']))) {
            $deal['repay_start_time_name'] = $deal['is_deal_zx'] ?'收益起算日' : $deal['repay_start_time_name'];
            $deal['formated_repay_start_time'] = to_date($deal['repay_start_time'], 'Y-m-d');
        }


        $deal = $this->UserDealStatusSwitch($deal);
        if ($needSwitch === true) {
            $deal = $this->UserDealStatusSwitch($deal);
        }
        $deal['is_crowdfunding'] = ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) ? 1 : 0;
        return $deal;
    }


    public function handleDealNew($deal, $data_type=0) {
        $deal['old_name'] = $deal['name'];
        $deal['name'] = msubstr($deal['name'], 0, 40); //坑
        $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);
        $deal['deal_tag'] = explode(',', $deal['deal_tag_name']) ;
        $i_tag =0;
        foreach($deal['deal_tag'] as $tag){
            if($i_tag){
                $deal['deal_tag_name'.$i_tag] = $tag;
            }else{
                $deal['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }
        unset($deal['deal_tag']);
        // 格式化借款数据
        $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
        $deal['borrow_amount_format_detail'] = format_price($deal['borrow_amount'] / 10000,false);
        $deal['borrow_amount_wan_int'] = format_price($deal['borrow_amount'] / 10000, false)."万";
        $deal['rate_foramt'] = number_format($deal['rate'],2);

        $deal['remain_time'] = $deal['start_time'] + $deal['enddate'] * 24 * 3600 - get_gmtime();

        //投标剩余时间
        if ($deal['deal_status'] != 1 || $deal['remain_time'] <= 0) {
            $deal['remain_time_format'] = "0" . self::DAY . "0" . self::HOUR . "0" . self::MINUTE;
        } else {
            $d = intval($deal['remain_time'] / 86400);
            $h = floor($deal['remain_time'] % 86400 / 3600);
            $m = floor($deal['remain_time'] % 3600 / 60);
            $deal['remain_time_format'] = $d . self::DAY . $h . self::HOUR . $m . self::MINUTE;
        }

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        //起投金额大于剩余投资金额，起投金额等于最低起投金额
        if(bccomp($deal['need_money_decimal'], $deal['min_loan_money'], 2) == -1){
            $deal['min_loan_money'] = $deal['need_money_decimal'];
        }

        $deal['need_money'] = format_price($deal['need_money_decimal']);
        $deal['need_money_detail'] = format_price($deal['need_money_decimal'], false);

        $deal['min_loan_money_format'] = $deal['min_loan_money'] >= 10000 ?
            format_price($deal['min_loan_money'] / 10000, false)."万" : format_price($deal['min_loan_money'], false);
        $deal['min_loan_money_format_yuan'] = format_price($deal['min_loan_money']);

        // 流标时间
        if (!empty($deal['bad_time'])) {
            if (date("Y", $deal['bad_time']) != date("Y", get_gmtime())) {
                $bad_time_format = "Y年m月d日";
            } else {
                $bad_time_format = "m月d日";
            }
            $deal['flow_standard_time'] = to_date($deal['bad_time'], $bad_time_format);
        } else {
            $deal['flow_standard_time'] = "-";
        }
        // 满标时间
        if (!empty($deal['success_time'])) {
            if (date("Y", get_gmtime()) != date("Y", $deal['success_time'])) {
                $su_time_format = "Y年m月d日";
            } else {
                $su_time_format = "m月d日";
            }
            $deal['full_scale_time'] = to_date($deal['success_time'], $su_time_format);
        } else {
            $deal['full_scale_time'] = "-";
        }

        $deal['loantype_name'] = isDealP2P($deal['deal_type']) ? str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]) : $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        //修改此处为借款用途的图标
        if (isset($deal['type_info']['icon']) && $deal['type_info']['icon']) {
            $deal['icon'] = str_replace("./public/images/dealtype/","./static/img/dealtype/",$deal['type_info']['icon']);
        }

        //后台填的年利率
        $deal['int_rate'] = $deal['rate'];

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']){
            $period_income_rate = (1 + $deal['int_rate']/12/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /12/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 12 / $deal['repay_time']*100, 2);
            $deal['rate'] = number_format($deal['rate'], 2) . "%";
        } else {
            //出借人年化收益率
            \FP::import("app.deal");
            $deal['rate'] = ($deal['income_fee_rate'] > 0) ? $deal['income_fee_rate']: get_invest_rate_data($deal['loantype'], $deal['repay_time']);
            $deal['rate'] = number_format($deal['rate'], 2) . "%"; // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
        }
        $deal['rate_show'] = number_format( (float)$deal['rate'], 2);
        $deal['repay_time_array'] = $this->numberToArrayForPic($deal['repay_time']);

        //获取此标的投资人群
        $deal['crowd_str'] = $GLOBALS['dict']['DEAL_CROWD'][$deal['deal_crowd']];
        //后台修改的借款年利率
        $deal['deal_rate'] = number_format($deal['int_rate'], 2) .'%';

        $deal['show_focus'] = 1;

        // 订单附加信息
        //$deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->escape($deal['id'])}'");
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

        if (!empty($deal_ext)) {
            $ext_info = $deal_ext->getRow();
            foreach ($ext_info as $k => $v) {
                if (!isset($deal[$k])) {
                    $deal[$k] = $v;
                }
            }
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_float_rate'] = number_format($deal['income_float_rate'], 2, ".", "");
        $deal['income_subsidy_rate'] = number_format($deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        $deal['income_total_show_rate'] = number_format($deal['rate'] + $deal['income_subsidy_rate'], 2, ".", "");
        $deal['rate_show_array'] = $this->numberToArrayForPic($deal['income_total_show_rate']);
        $deal['max_rate'] = number_format( (float)$deal['rate'], 2);
        //订单状态文字
        $deal['deal_status_text'] = $this->getDealStatusText($deal);

        if ($data_type != 1) {
            $deal = $this->getEarningsInfo($deal);
        }

        // 开标时间，如果开标时间未到，则赋值给格式化后的开标时间，模板根据格式化后的开标时间判断是否显示开标时间
        if (!empty($deal['start_loan_time']) && $deal['start_loan_time']>get_gmtime() && $deal['deal_status']==0) {
            if (date("Y", get_gmtime()) != date("Y", $deal['start_loan_time'])) {
                $st_time_format = "Y-m-d H:i";
            } else {
                $st_time_format = "m-d H:i";
            }
            $deal['start_loan_time_format'] = to_date($deal['start_loan_time'], $st_time_format);
            //} else {
            //    $deal['start_loan_time_format'] = "-";
        }

        $deal = $this->UserDealStatusSwitch($deal);
        $deal['is_crowdfunding'] = ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) ? 1 : 0;

        // JIRA#3844 获取项目相关信息 by fanjingwen
        $project_obj =  DealProjectModel::instance()->findViaSlave($deal['project_id']);
        if (!empty($project_obj)) {
            $project_info = $project_obj->getRow();
            $deal['project_name']   =  $project_info['name'];
            $deal['product_name']   =  $project_info['product_name'];
            $deal['product_class']  =  $project_info['product_class'];
        }

        \libs\utils\Logger::info("dealModel deal handleDealNew  deal:" . json_encode($deal));
        return $deal;
    }

    /**
     * 判断用户是否投资过某标的
     * @param $userId int 用户id
     * @param $dealId int 标id
     * @return bool
     */
    public function haveBidDeal($userId, $dealId) {
        if (empty($userId) || $dealId) {
            return false;
        }

        $count = DealLoadModel::instance()->countViaSlave("`user_id`='{$userId}' AND `deal_id`='{$dealId}'");
        return $count > 0 ? true : false;
    }

    /**
     * 修改首页 列表页面 缓存引起标的状态有问题的情况
     * @param $deal
     * @return mixed
     */
    public function UserDealStatusSwitch($deal){
        //PaymentApi::log('DealModel.UserDealStatusSwitch.1:'.$deal['id'].'-'.$deal['deal_status'].'-'.$deal['deal_type']);
        //处理首页和列表页 满标后的列表显示，jira 1156
        $deal['bid_flag'] = 1;//主要是控制链接是否出现的
//        $user_bid_deals = DealLoadModel::instance()->getUserLoadDealId();
        //默认未投

        $userId = (!empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : 0;
        $deal['have_bid_deal'] = $this->haveBidDeal($userId, $deal['id']) ? 1 : 0;
//        if($user_bid_deals && in_array($deal['id'], explode(',', $user_bid_deals))){
//            //当前登录用户 已投该标
//            $deal['have_bid_deal'] = 1;
//        }
        //PaymentApi::log('DealModel.UserDealStatusSwitch.1_1:'.$deal['have_bid_deal']);
        $deal['deal_compound_status'] = 0;//利滚利 未投资状态
        if(in_array($deal['deal_status'], array(2,4,5))){
            $deal['bid_flag'] = 0;
            if($deal['have_bid_deal'] == 1){
                $deal['bid_flag'] = 1;
                $deal['deal_compound_status'] = 1;
            }
        }
        //利滚利
        if($deal['deal_type'] == 1){
            $deal_compound = DealCompoundModel::instance()->findByViaSlave("`deal_id`='{$this->escape($deal['id'])}'");
            //PaymentApi::log('DealModel.UserDealStatusSwitch.2:'.json_encode($deal_compound, JSON_UNESCAPED_UNICODE));
            /* if($deal['have_bid_deal'] == 1){
                $deal['deal_compound_status'] = 1;
            } */

            if(!empty($deal_compound)){
                $compound_info = $deal_compound->getRow();
                $deal['redemption_period'] = $compound_info['redemption_period'];
                $deal['redemption_limit'] = $compound_info['redemption_limit'];
                $deal['redemption_end_date'] = $compound_info['end_date'];
                $deal['rate_day'] = bcmul($this->convertRateYearToDay(trim($deal['int_rate'],'%'), $deal['redemption_period']), '100', 5);
                $deal['lock_period'] = $compound_info['lock_period'];
            }

            if($deal['deal_status'] == 1){
                $deal['deal_compound_status'] = 0;
            }elseif($deal['deal_status'] == 4){//还款中
                //投资数量
                $bid_count = DealLoadModel::instance()->getDealLoanNumByUserId($deal['id']);
                $apply_count = CompoundRedemptionApplyModel::instance()->getDealApplyNumByUserId($deal['id']);

                if(intval($bid_count) > 0){
                    if($bid_count > $apply_count){
                        $deal['deal_compound_status'] = 2;//待赎回
                    }else{
                        $apply_repay_count = CompoundRedemptionApplyModel::instance()->getDealApplyNumByUserId($deal['id'], 1);
                        if($apply_repay_count < $apply_count){
                            $deal['deal_compound_status'] = 3;//还款中
                        }else{
                            $deal['deal_compound_status'] = 4;//已还款
                        }
                    }
                }else{
                    $deal['deal_compound_status'] = 0;//未投资
                }
                //$repay = DealLoanRepayModel::instance()->getUserCompoundLoanByDealID($deal['id']);
                //PaymentApi::log('DealModel.UserDealStatusSwitch.3:'.json_encode($repay, JSON_UNESCAPED_UNICODE));
                /* if(!empty($repay)){
                    $repay_info = $repay->getRow();
                    if($repay_info['status'] == 1){
                        $deal['deal_compound_status'] = 4;//已还款
                    }else{
                        $deal['deal_compound_status'] = 3;//还款中
                    }
                }else{//待赎回
                    if($deal['have_bid_deal']){
                        $deal['deal_compound_status'] = 2;//待赎回
                    }else{
                        $deal['deal_compound_status'] = 0;//未投资
                    }
                } */
            }
        }
        return $deal;
    }

    /**
     * 获取订单详情的收益信息
     *
     * @param $deal 订单信息
     * @return mixed
     */
    public function getEarningsInfo($deal) {
        $deal['min_loan'] = number_format(bcdiv($deal['min_loan_money'] , 10000,5),2);
        //$deal['borrow_amount_format'] = intval($deal['borrow_amount'] / 10000);
        //$deal['borrow_amount_format_detail'] = intval($deal['borrow_amount_format_detail']);
        $deal['loan_rate'] = round((1 - $deal['need_money_decimal'] / $deal['borrow_amount']) * 100, 2);
        //$deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        //$deal['need_money_format'] = number_format($deal['need_money_decimal'], 2);
        if ($GLOBALS['user_info']) {
            //if ($deal['deal_crowd'] == self::DEAL_CROWD_NEW) {
            //    $total_money = $GLOBALS['user_info']['money'];
            //    $deal['bonus_money'] = 0;
            //} else {
            $bonus_service = new BonusService();
            $bonus = $bonus_service->get_useable_money($GLOBALS['user_info']['id']);

            $total_money = bcadd($bonus['money'], !empty($GLOBALS['user_info']['money']) ? $GLOBALS['user_info']['money'] : '0.00', 2);

            $userThirdBalanceService = new \core\service\UserThirdBalanceService();
            $balanceResult = $userThirdBalanceService->getUserSupervisionMoney($GLOBALS['user_info']['id']);
            $total_money = bcadd($total_money,$balanceResult['supervisionBalance'], 2);

            //$total_money = $GLOBALS['user_info']['money'];
            $deal['bonus_money'] = $bonus['money'];
            //}
            $max_loan = $total_money > $deal['need_money_decimal'] ? $deal['need_money_decimal'] : $total_money;
        } else {
            $max_loan = $deal['need_money_decimal'];
        }

        $max_loan = number_format($max_loan, 2, ".", "");

        $earning = new EarningService();
        if(in_array($deal['deal_crowd'], array(1,8)))//新手专享 || 手机新手专享
        {
            if($max_loan > $deal['min_loan_money'])
            {
                $crowd_min_loan = number_format($deal['min_loan_money'],2,'.','');
            }
            else
            {
                $crowd_min_loan = $max_loan;
            }
            $deal['crowd_min_loan'] = $crowd_min_loan;
            if($max_loan > $deal['max_loan_money'] && $deal['max_loan_money']>0)
            {
                $max_loan = $deal['max_loan_money'];
            }
            if ($deal['need_money_decimal'] < (2*$deal['min_loan_money'])) {
                $max_loan = $deal['need_money_decimal'];
            }
            $money_earning = $earning->getEarningMoney($deal['id'], $deal['crowd_min_loan'], true);
            $expire_rate = $earning->getEarningRate($deal['id'], true);
        }
        else
        {
            $money_earning = $earning->getEarningMoney($deal['id'], $max_loan, true);
            if ($max_loan > 0) {
                $expire_rate = $money_earning / $max_loan * 100;
            } else {
                $expire_rate = $earning->getEarningRate($deal['id'], true);
            }
            if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款(信分期单独处理)
                $expire_rate = $earning->getEarningRate($deal['id']);
            }
        }

        $max_loan = $deal['deal_status'] != 1 ? 0 : $max_loan;
        $deal['max_loan'] = $max_loan;
        $deal['expire_rate'] = $expire_rate;
        $deal['money_earning'] = $money_earning;
        return $deal;
    }

    /**
     * 处理投资逻辑
     * @param array $user
     * @param float $money
     * @param int $source_type
     * @param int $site_id
     * @param string $short_alias 优惠码
     * @return int|false
     */
    public function bidDeal($user, $money, $source_type, $site_id, $bonus = array(), $short_alias=null) {
        // 开启事务
        $this->db->startTrans();

        try {
            if ($this->isFull()) {
                throw new \Exception('投标失败');
            }

            //处理红包的转账
            $handle_bonus = ($bonus['money'] > 0 && $bonus['bonuses']) ? true : false;
            if($handle_bonus){
                if($this->bidBonusTransfer($user['id'], $bonus['bonuses']) === false){
                    throw new \Exception('投标失败');
                }
            }

            $ip = get_real_ip();
            $loan_id = $this->updatePercent($money, $user['id'], $user['user_name'], $ip, $source_type, $site_id,$short_alias);
            if ($loan_id === false) {
                throw new \Exception('投标失败');
            }

            //消费红包
            if($handle_bonus){
                if($this->bidBonusUse($this->id, $loan_id, $user['id'], $bonus) === false){
                    throw new \Exception('投标失败');
                }
            }

            //更改资金记录
            $msg = "编号{$this->id} {$this->name}";
            $user = UserModel::instance()->find($user['id']);

            $bizToken = [
                'dealId' => $this->id,
                'dealLoadId' => $loan_id,
            ];
            if ($user->changeMoney($money, "投标冻结", $msg, 0, 0, 1, 0,$bizToken) === false) {
                throw new \Exception('投标冻结失败');
            }

            $this->db->query("UPDATE " . $this->tableName() . " SET `is_send_half_msg`='1', `update_time`='".get_gmtime()."' WHERE `id`='{$this->id}'");

            syn_deal_status($this->id);

            $this->db->commit();
            return $loan_id;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * 处理流标逻辑，增加事务处理
     * @param array $deal
     */
    public function failDeal($deal) {
        // 开启事务
        $this->db->startTrans();

        try {
            // 先修改订单状态
            $deal_dao = DealModel::instance()->find($deal['id']);
            $deal_dao->deal_status = 3;
            $deal_dao->is_doing = 0;    // 表示流标操作结束

            $bad_time = ($deal_dao->deal_type == self::DEAL_TYPE_GENERAL) ? $deal_dao->bad_time : ($deal['start_time'] + $deal['enddate'] * 24 * 3600);
            if ($deal['bad_time'] != $bad_time) {
                $deal_dao->bad_time = $bad_time;
            }
            if($deal['is_send_bad_msg']==0){
                $deal_dao->is_send_bad_msg = 1;
            }
            if ($deal_dao->save() === false) {
                throw new \Exception("fail deal error");
            }

            // 流标向用户返还金额
            $load_list = DealLoadModel::instance()->findAll("`is_repay`='0' AND `deal_id`='{$deal['id']}' AND `from_deal_id`='0'");
            $arr_user = array();
            $deal_service = new DealService();
            $isP2pDeal = $deal_service->isP2pPath($deal_dao);

            $isDT = $deal_service->isDealDT($deal['id']);
            $dt_service = new \core\service\DtTransferService();

            $isDealXH = $deal_service->isDealYtsh($deal['id']);

            if ($load_list) {
                $user_dao = new UserModel();
                foreach ($load_list as $v) {
                    $user_id = $v['user_id'];
                    $user = $user_dao->find($user_id);
                    $arr_user[$user_id] = array();
                    $arr_user[$user_id]['mobile'] = $user['mobile'];
                    $arr_user[$user_id]['user_type'] = $user['user_type'];
                    $note = '编号' . $deal['id'] .' ' . $deal['name'] . '，单号' . $v['id'];
            // TODO finance? 取消投标
                    $user->changeMoneyDealType = $deal_service->getDealType($deal_dao);
                    $bizToken = [
                        'dealId' => $deal['id'],
                        'dealLoadId' =>  $v['id'],
                    ];
                    $chg_rs = $user->changeMoney(-$v['money'], "取消投标", $note, 0, 0, 1, 0, $bizToken);


                    if($isDealXH) {
                        $user->changeMoney($v['money'], '享花流标冻结', '冻结 "' . $deal['name'] .'" 取消投标',0,0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                        $XHService = new \core\service\XHService();
                        $XHService->failSuccessNotify($deal['id'],$v['user_id'],$v['id'],$v['money']);
                        continue;
                    }

                    if ($deal_service->isDealJF($v['site_id']) == true) {
                        if (!$deal_service->transferRepayJF($user, $v['money'], $deal['id'], $v['order_id'])) {
                            throw new \Exception("jifu transfer error {$note}\n");
                        }
                    }

                    $dt_rs = true;
                    if ($isDT === true) {
                        $dt_rs = $dt_service->transferFailDT($user, $v['money'], $v['id']);
                    }

                    if ($chg_rs === false || $dt_rs === false) {
                        throw new \Exception("change_money error {$note}\n");
                    }
                }

                // 统一将投资记录设置为已还
                $deal_load_model = new DealLoadModel();
                $deal_load_model->setIsrepayByDealId($deal['id']);

                // 处理多投宝逻辑
                if ($isDT === true) {
                    $jobs_model = new JobsModel();
                    $jobs_model->priority = 84;
                    $param = array(
                        'deal_id' => $deal['id'],
                    );
                    $r = $jobs_model->addJob('\core\service\DtDealService::failDeal', $param);
                    if ($r === false) {
                        throw new \Exception("Add DT Jobs Fail");
                    }
                }
            }

            $jobs_model = new JobsModel();
            $param = array(
                'deal_id' => $deal['id'],
            );
            $jobs_model->priority = 85;
            $r = $jobs_model->addJob('\core\service\jifu\JfLoanRepayService::syncFailToJf', $param);
            if ($r === false) {
                throw new \Exception("add sync jifu job error\n");
            }

            // 将删除合同等 后续操作放到 事务中
            FailState::afterMoney($deal);

            $this->db->commit();

            // 向投资人发送短信。
            if(!$isDT) { //不是智多鑫才发送流标短信
                FailState::sendSmsToLoader($load_list, $arr_user, $deal);
            }

            if($deal['is_send_bad_msg']==0){
                send_full_failed_deal_message($deal, 'failed');

                $log = array(
                    "type" => "deal",
                    "act" => "fail",
                    "is_succ" => 1,
                    "id" => $deal['id'],
                    "name" => $deal['name'],
                );
                Logger::info(implode(" | ", $log));
            }

            return true;
        } catch (\Exception $e) {
            // 出现异常则回滚
            $this->db->rollback();

            $log = array(
                "type" => "deal",
                "act" => "fail",
                "is_succ" => 0,
                "id" => $deal['id'],
                "name" => $deal['name'],
                "err" => $e->getMessage(),
            );
            Logger::error(implode(" | ", $log));

            return false;
        }
    }

    /**
     * 处理放款逻辑，增加事务处理
     * @param int $deal_id
     * @param int $consult_user_id 咨询机构id
     * @param int $guarantee_user_id 担保机构id
     * @param int $loan_user_id 平台机构id
     * @param int $pay_user_id 支付机构id
     */
    public function makeDealLoans($deal_data, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id, $canal_user_id, $adm_info = array())
    {
        $deal_id = intval($deal_data['id']);

        //记录随鑫约放款缓存
        $obj_reserve = new GTaskService();
        $event_reserve = new ReserveDealLoansCacheEvent($deal_id);
        $obj_reserve->doBackground($event_reserve, 1);

        $dealService = new DealService();

        $this->db->startTrans();
        try {
            //更新为已打款状态
            $deal = $this->find($deal_data['id']);
            $isP2pPath = $dealService->isP2pPath($deal);
            $dealType = $dealService->getDealType($deal);

            if ($deal->is_has_loans != 2) {
                throw new \Exception("标不是放款中的状态");
            }

            /*$change_status = $this->changeLoansStatus($deal_id, 1);
            if(!$change_status){
                throw new \Exception("更新已打款状态失败");
            }*/

            // 记录资金变动日志
            $syncRemoteData = array();

            //将出借人冻结资金扣除
            $user_dao = new UserModel();

            // 获取合并后的投资列表
            $loan_list = DealLoadModel::instance()->findAll("`deal_id`='{$this->escape($deal_id)}' ORDER BY `id` ASC");
            $user_loan_info_collection = array();
            foreach ($loan_list as $k => $v) {
                if (bccomp($v['money'], '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => $deal_id,
                        'payerId' => $v['user_id'],
                        'receiverId' => $deal->user_id,
                        'repaymentAmount' => bcmul($v['money'], 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 4,
                        'batchId' => $deal_id,
                    );
                }

                // 不统计公益标、预约投标
                if ($deal['loantype'] == 7 || $v['source_type'] == DealLoadModel::$SOURCE_TYPE['reservation']) {
                    continue;
                }
                if (isset($user_loan_info_collection[$v['user_id']])) {
                    $user_loan_info_collection[$v['user_id']]['money'] += $v['money'];
                    $user_loan_info_collection[$v['user_id']]['count'] += 1;
                } else {
                    $user_loan_info_collection[$v['user_id']]['money'] = $v['money'];
                    $user_loan_info_collection[$v['user_id']]['count'] = 1;
                }
            }
            $deal_service = new DealService;
            if(!$deal_service->isDealDT($this->escape($deal_id))){//多投不发送站内信
                // 发送站内信
                foreach ($user_loan_info_collection as $user_id => $loan_info) {
                    $content = sprintf('您投资的“%s”（共%d笔）已成交，投资款%s已放款，开始计息。', $deal['name'], $loan_info['count'], format_price($loan_info['money']));
                    $structured_content = array(
                        'main_content' => $content,
                        'turn_type' => MsgBoxEnum::TURN_TYPE_REPAY_CALENDAR,
                    );
                    $msgbox = new MsgBoxService();
                    $msgbox->create($user_id, 19, '放款计息', $content, $structured_content);
                }

            }

            $user_id = $deal->user_id;
            $user = $user_dao->find($user_id);
            $user->changeMoneyAsyn = $isP2pPath ? false : true;
            $user->changeMoneyDealType = $dealType;
            $note = "编号{$deal_id} {$deal['name']} 借款人ID{$user_id} 借款人姓名{$user['real_name']}";
            // TODO finance 投标成功相关扣款 扣投标款， 交纳平台手续费，交纳咨询费，交纳担保费 | 已同步包括下面三个奋勇的扣款

            // 放款 及 手续费收取
            $fee_user_id_arr = array(
                'loan_user_id' => $loan_user_id,
                'consult_user_id' => $consult_user_id,
                'guarantee_user_id' => $guarantee_user_id,
                'pay_user_id' => $pay_user_id,
                'management_user_id' => $management_user_id,
                'canal_user_id' => $canal_user_id,
            );
            $deal_ext_info = DealExtModel::instance()->getInfoByDeal(intval($deal_id));
            if (in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                list($syncRemoteData_fee, ) = $this->changeFeeMoney($deal->id, $user, $fee_user_id_arr, $note, $adm_info, $isP2pPath, $deal_data['isDtb']);
                $services_fee = 0; // 因为是收费后放款，提现时，不再扣除手续费
                $bizToken = [
                    'dealId' => $deal->id,
                ];
                $this->changeMoney($user, $deal->borrow_amount, '招标成功', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            } else {
                $bizToken = [
                    'dealId' => $deal->id,
                ];
                $this->changeMoney($user, $deal->borrow_amount, '招标成功', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
                list($syncRemoteData_fee, $services_fee) = $this->changeFeeMoney($deal->id, $user, $fee_user_id_arr, $note, $adm_info, $isP2pPath, $deal_data['isDtb']);
            }

            $syncRemoteData = array_merge($syncRemoteData, $syncRemoteData_fee);
            if (!$isP2pPath && !empty($syncRemoteData)) {
                FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            \libs\utils\Logger::error($e->getMessage());
            $this->db->rollback();
            return false;
        }

        // JIRA#3102 投资短信整合 PM:lipanpan
        // 放款成功，根据用户订阅设置发送sms或者email
        $obj = new GTaskService();
        $event = new DealLoansMsgEvent($deal_id);
        $obj->doBackground($event, 1);

        return array("ret"=>true, "data"=>array("services_fee"=>$services_fee));
    }

    /**
     * 收取各项手续费
     * @params int $deal_id
     * @params object $user
     * @params array $fee_user_id_arr ['loan_user_id', 'consult_user_id', 'guarantee_user_id', 'pay_user_id', 'management_user_id']
     * @params string $note
     * @params array $adm_info
     * @params boolean $is_p2p_path
     * @params boolean $is_dtb
     * @return list ($sync_remote_data, $service_fee)
     */
    public function changeFeeMoney($deal_id, $user, $fee_user_id_arr, $note, $adm_info, $is_p2p_path, $is_dtb = false)
    {
        $deal_info = DealModel::instance()->find(intval($deal_id));
        $deal_ext_info = DealExtModel::instance()->getInfoByDeal(intval($deal_id), false);
        $sync_remote_data = array();
        $fee = array();
        list($sync_remote_data[], $fee['loan_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['loan_user_id'], $user, 'loan_fee', '平台手续费', $note, $adm_info);
        list($sync_remote_data[], $fee['consult_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['consult_user_id'], $user, 'consult_fee', '咨询费', $note, $adm_info);
        list($sync_remote_data[], $fee['guarantee_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['guarantee_user_id'], $user, 'guarantee_fee', '担保费', $note, $adm_info);
        list($sync_remote_data[], $fee['pay_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['pay_user_id'], $user, 'pay_fee', '支付服务费', $note, $adm_info);
        list($sync_remote_data[], $fee['canal_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['canal_user_id'], $user, 'canal_fee', '渠道服务费', $note, $adm_info);


        if ($is_dtb) { // 多投才收管理费
            list($sync_remote_data[], $fee['management_fee']) = $this->changeOneFeeMoney($is_p2p_path, $deal_info, $deal_ext_info, $fee_user_id_arr['management_user_id'], $user, 'management_fee', '管理服务费', $note, $adm_info);
        }
        Logger::info(sprintf('change fee,deal_id:%d, adm_info:%d, fee-detail:%s [%s:%s]', $deal_id, json_encode($adm_info), json_encode($fee), __FILE__, __LINE__));

        return array($sync_remote_data, array_sum($fee));
    }

    /**
     * 收取指定项手续费
     * @params boolean $is_p2p_path
     * @params object $deal 对应 deal 表
     * @params object $deal_ext 对应 deal_ext 表
     * @params int $fee_user_id 收取费用的用户id
     * @params object $user 对应 user 表
     * @params string $fee_name eg. loan_fee consult_fee ..
     * @params string $message 扣费类型
     * @params string $note 备注
     * @params array $adm_info 后台操作用户 [adm_id]
     * @return list ($sync_remote_data, $services_fee)
     */
    public function changeOneFeeMoney($is_p2p_path, $deal, $deal_ext, $fee_user_id, $user, $fee_name, $message, $note, $adm_info)
    {
        // 获取费用金额
        $fee = $this->getOneFee($deal, $deal_ext, $fee_name);

        // 扣除费用
        $sync_remote_data = array();
        if ($fee_user_id && bccomp($fee, '0.00', 2) > 0) {
            $sync_remote_data = array(
                'outOrderId' => sprintf('%s|%d', strtoupper($fee_name), $deal->id),
                'payerId' => $deal->user_id,
                'receiverId' => $fee_user_id,
                'repaymentAmount' => bcmul($fee, 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 4,
                'batchId' => $deal->id,
            );
            $bizToken = [
                'dealId' => $deal->id,
            ];
            // 从借款人扣除手续费
            $this->changeMoney($user, -$fee, $message, $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            // 相关有用户手续费所得
            $fee_user = UserModel::instance()->find($fee_user_id);
            $fee_user->changeMoneyAsyn = true;
            $deal_service = new DealService();
            $fee_user->changeMoneyDealType = $deal_service->getDealType($deal);
            $this->changeMoney($fee_user, $fee, $message, $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
        }

        Logger::info(sprintf('change one-fee,deal_id:%d, params:%s [%s:%s]', $deal->id, json_encode(func_get_args()), __FILE__, __LINE__));

        return array($sync_remote_data, $fee);
    }

    /**
     * 此项手续费应收金额
     * @params object $deal 对应 deal 表
     * @params object $deal_ext 对应 deal_ext 表
     * @params string $fee_name eg. loan_fee consult_fee ..
     * @return float $fee
     */
    public function getOneFee($deal, $deal_ext, $fee_name)
    {
        // 获取各字段名
        $fee_rate_field = sprintf('%s_rate', $fee_name);
        $fee_rate_type_field = sprintf('%s_rate_type', $fee_name);
        $fee_ext_field = sprintf('%s_ext', $fee_name);

        // 计算费用
        if ($deal_ext->loan_type == UserCarryModel::LOAN_AFTER_CHARGE || !$deal_ext->$fee_ext_field) { // 收费后放款，统一按前收处理
            if (DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE == $deal_ext->$fee_rate_type_field) { // 固定比例收取费用
                $fee_rate = $deal->$fee_rate_field;
            } else {
                $fee_rate = Finance::convertToPeriodRate($deal->loantype, $deal->$fee_rate_field, $deal->repay_time, false);
            }
            $fee = $this->floorfix($deal->borrow_amount * $fee_rate / 100.0);
        } else {
            $fee_arr = json_decode($deal_ext->$fee_ext_field, true);
            $fee = $fee_arr[0];
        }

        return $fee;
    }

    /**
     * 借款人账户余额是否负担得起标的的手续费
     * @params int $deal_id
     * @return boolean true-can
     */
    public function canUserAffordDealFee($deal_id)
    {
        $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
        $user_info = UserModel::instance()->findViaSlave($deal_info->user_id, 'id,money');

        $fee = $this->getAllFee($deal_id);

        return (bccomp($user_info->money, Finance::addition(array($fee['loan_fee'], $fee['consult_fee'], $fee['guarantee_fee'], $fee['pay_fee'], $fee['manage_fee'])),2) >= 0);
    }

    /**
     * 根据标id 获取标的的各项费用金额
     * @params int $deal_id
     * @return array ['loan_fee', 'consult_fee', 'guarantee_fee', 'pay_fee', 'manage_fee']
     */
    public function getAllFee($deal_id)
    {
        $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
        $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
        $fee = array();

        $fee['loan_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'loan_fee');
        $fee['consult_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'consult_fee');
        $fee['guarantee_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'guarantee_fee');
        $fee['pay_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'pay_fee');
        $deal_service = new DealService();
        $fee['manage_fee'] = $deal_service->isDealDT($deal_id) ? $this->getOneFee($deal_info, $deal_ext_info, 'manage_fee') : 0;

        return $fee;
    }

    /**
     * proxy user.changeMoney
     *
     */
    private function changeMoney($user,$money, $message, $note, $admin_id = 0, $is_manage = 0, $money_type = 0, $negative = 0, $bizToken = []){
        $result = $user->changeMoney($money, $message, $note, $admin_id, $is_manage, $money_type, $negative, $bizToken);
        if($result == false){
            throw new \Exception($note.", ".$message."失败");
        }
    }

    /**
     * JIRA#1062 金额改为舍余处理
     * @param float $value
     * @param int $precision 小树位数
     * @return float
     */
    public function floorfix($value, $precision = 2) {
        $t = pow(10, $precision);
        if (!$t) {
            return 0;
        }
        $value = round($value*$t, 5);
        return (float)floor($value) / $t;
    }

    /**
     * 根据投资金额计算预期收益
     * @param $principal float 本金
     * @param $is_preview bool 是否是预览收益
     * @return float 收益
     */
    public function getEarningMoney($principal) {
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) { //公益募捐
            return 0;
        }
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            $earning = $principal * $this->income_fee_rate / 100 / 12 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            $earning = $principal * $this->income_fee_rate / 100 / 4 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
            $earning =  0;
            $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
            if(intval($repay_time) <=0) {
                $repay_time = to_timespan(date("Y-m-d"));
            }
            $deal_ext = \core\dao\DealExtModel::instance()->getInfoByDeal($this->id, false);
            $first_repay_day = $deal_ext['first_repay_interest_day'];
            $left_need_repay_principal = $principal;
            $rate = $this->income_fee_rate / 100;
            $month_rate =  $rate / 12;
            $repay_times = $this->getRepayTimes();
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$principal);
                    if ($i == 1) {
                        $interest_day = ($first_repay_day - $repay_time) / 86400;
                        $interest = $principal * $rate * $interest_day / 360;
                    } else {
                        $interest = $left_need_repay_principal * $month_rate;
                    }
                $earning += $this->floorfix($interest,2);
                $left_need_repay_principal -= $this->floorfix($repay_principal, 2);
            }
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){
            $repay_times = $this->getRepayTimes();
            $earning = 0;
            for($i=1;$i<=$repay_times;$i++) {
                $earning += $this->floorfix(($principal- $principal/$repay_times * ($i - 1)) * $this->income_fee_rate / 100 / 12);
            }
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']){
            $repay_times = $this->getRepayTimes();
            $earning = 0;
            for($i=1;$i<=$repay_times;$i++) {
                $earning += $this->floorfix(($principal- $principal/$repay_times * ($i - 1)) * $this->income_fee_rate / 100 / 4);
            }
        } else {
            $pmt = $this->getPmtByDeal($this);
            $earning = $principal / $this->borrow_amount * $pmt['income_fee'];
        }
        return $earning;
    }

    /**
     * 获取平台补贴金额
     * @param string $principal 投资本金
     * @return float
     */
    public function getSubsidyMoney($principal) {
        //$deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->id}'");
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($this->id);
        if (!$deal_ext || !$deal_ext['income_subsidy_rate']) {
            return 0;
        }
        $rate = $deal_ext['income_subsidy_rate'];

        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $money = $principal * $rate / 100 * $this->repay_time / 360;
        } else {
            $money = $principal * $rate / 100 * $this->repay_time / 12;
        }
        return $money;
    }

    /**
     * 计算需要拆分为多少期进行还款
     *
     * @return integer
     **/
    public function getRepayTimes() {
        $repay_times = 0;
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_times = $this->repay_time / 3;
        }
        return $repay_times;
    }

    /**
     * 根据还款方式，借款期限，计算需要拆分为多少期进行还款
     * @param int $loantype 还款方式
     * @param int $repay_time 借款期限
     * @return integer
     **/
    public static function getRepayTimesByLoantypeAndRepaytime($loantype, $repay_time)
    {
        $deal_model = new self();
        $deal_model->loantype = $loantype;
        $deal_model->repay_time = $repay_time;
        return $deal_model->getRepayTimes();
    }

    /**
     * 获取全站最高收益率
     * @param int $deal_id
     * @return float
     */
    public function getMaxIncomeRate($site_id = 0) {
        $site_id = intval($site_id);
        $sql = "SELECT MAX(`income_fee_rate`) AS `max` FROM %s WHERE `income_fee_rate`>0 AND `publish_wait`='0' AND `is_effect`='1' AND `is_delete`='0'";
        $sql = sprintf($sql, $this->tableName());
        if ($site_id !== 1 && $site_id !== 0) {
            $sql .= sprintf(" AND `id` IN (SELECT `deal_id` FROM %s WHERE `site_id`='%d')", DealSiteModel::instance()->tableName(), $this->escape($site_id));
        }
        $result = $this->findBySql($sql);
        return $result['max'];
    }

    /**
     * 根据标的id获取PMT信息方法
     * @param $deal_id int
     * @return array|bool
     */
    public function getPmtByDealId($deal_id) {
        $deal = $this->find($deal_id);
        return $this->getPmtByDeal($deal);
    }

    /**
     * 根据标的信息获取PMT信息方法
     * @param $deal array
     * @return array|bool
     */
    public function getPmtByDeal($item) {
        if (!$item) {
            return false;
        }

        $data = array();

        $data['loantype'] = $item['loantype'];
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['desc'] = $item['repay_time'] . '天' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        } else {
            $data['desc'] = $item['repay_time'] . '月' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        }

        $data['borrow_sum'] = isset($item['borrow_sum']) ? $item['borrow_sum'] : 0; // 借款总额度
        $data['borrow_amount'] = $item['borrow_amount'];  // 借款分配额度
        $data['repay_time'] = $item['repay_time']; // 借款期限
        $data['repay_interval'] = $this->get_delta_month_time($item['loantype'], $item['repay_time']); // 还款间隔月数

        $data['rate'] = $item['rate'] / 100;  // 年华借款利率

        // 如果是按天一次性
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            if($item['id'] <= $GLOBALS['dict']['OLD_DEAL_DAY_ID']){
                $data['repay_fee_rate'] = $data['rate'] / 365 * $data['repay_interval']; // 借款期间利率
            }else{
                $data['repay_fee_rate'] = $data['rate'] / self::DAY_OF_YEAR * $data['repay_interval']; // 借款期间利率
            }
        } else {
            $data['repay_fee_rate'] = $data['rate'] / self::MONTH_OF_YEAR * $data['repay_interval']; // 借款期间利率
        }

        $data['repay_num'] = $data['repay_time'] / $data['repay_interval']; // 还款次数

        $data['borrow_rate'] = $data['borrow_sum'] ? $data['borrow_amount'] / $data['borrow_sum'] : 0; // 借款分配比例
        $data['fv'] = 0; // Fv为未来值（余值），或在最后一次付款后希望得到的现金余额，如果省略Fv，则假设其值为零，也就是一笔贷款的未来值为零。
        $data['type'] = 0; // Type数字0或1，用以指定各期的付款时间是在期初还是期末。1代表期初（先付：每期的第一天付），不输入或输入0代表期末（后付：每期的最后一天付）。
        $data['pmt'] = self::getPmtMoney($data['repay_fee_rate'], $data['repay_num'], $data['borrow_amount']); //借款人每期还款额
        $data['manage_fee_rate'] = $item['manage_fee_rate'] / 100; // 账户管理费率年化
        $data['interest'] = $data['pmt'] * $data['repay_num'] - $data['borrow_amount']; // 总利息

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / self::DAY_OF_YEAR * $data['repay_time']; // 管理费
        } else {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / self::MONTH_OF_YEAR * $data['repay_time']; // 管理费
        }

        $data['manage_rate'] = $data['manage_fee'] / $data['pmt']; // 管理费收取比例
        $data['income_fee'] = $data['interest'] - $data['manage_fee'];  // 理财总收益
        $data['real_repay_fee_rate'] = $data['interest'] / $data['borrow_amount']; // 实际借款利率
        $data['income_fee_rate'] = $data['income_fee'] / $data['borrow_amount']; // 实际理财收益率

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['period_income_rate'] = (1 + $data['rate'] /self::DAY_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /self::DAY_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * self::DAY_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (self::DAY_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        } else {
            $data['period_income_rate'] = (1 + $data['rate'] / self::MONTH_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /self::MONTH_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * self::MONTH_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (self::MONTH_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        }

        return $data;
    }

    /**
     * PMT年金计算方法
     * @param $i float 期间收益率
     * @param $n int 期数
     * @param $p float 本金
     * @return float 每期应还金额
     */
    public static function getPmtMoney($i, $n, $p) {
        return $p * $i * pow((1 + $i), $n) / ( pow((1 + $i), $n) -1);
    }

    /**
     * 插入一条借款数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertDealData($data){

        if(empty($data)){
            return false;
        }
        //增加事物，确保合同服务与P2P复制标的数据一致性
        $this->db->startTrans();

        $this->setRow($data->getRow());
        $this->create_time = get_gmtime();
        $this->update_time = get_gmtime();
        $this->buy_count = 0;
        $this->load_money = 0;
        $this->repay_money = 0;
        $this->start_time = 0;
        $this->success_time = 0;
        $this->repay_start_time = 0;
        $this->last_start_time = 0;
        $this->next_repay_time = 0;
        $this->sort = 0;
        $this->is_has_loans = 0;
        $this->is_send_half_msg = 0;
        $this->is_during_repay = 0;
        $this->point_percent = 0;
        $this->approve_number = '';
        $this->deal_status = 0;
        $this->publish_wait = 1;
        $this->parent_id = -1;
        $this->is_effect = $data['is_effect']; //有效无效和原标保持一致
        $this->deal_type = $data['deal_type']; //标的类型和原标保持一致
        $this->report_type = $data['report_type']; //标的类型和原标保持一致
        $this->report_status = 0; //标的类型和原标保持一致

        if($this->insert()){
            $dealId = $this->db->insert_id();
            //如果是合同服务的标的,复制需要插入标的对应的合同分类
            if(is_numeric($data['contract_tpl_type'])){
                $rpc = new Rpc('contractRpc');
                //合同服务设置标的模板分类ID
                $contractRequest = new RequestSetDealCId();
                $contractRequest->setDealId(intval($dealId));
                $contractRequest->setCategoryId(intval($data['contract_tpl_type']));
                $contractRequest->setType(0);
                $contractRequest->setSourceType($data['deal_type']);

                $contractResponse = $rpc->go("\NCFGroup\Contract\Services\Category","setDealCId",$contractRequest);
                if($contractResponse->status != true){
                    $this->db->rollback();
                    throw new \Exception("合同服务调用失败：".$contractResponse->errorCode.":".$contractResponse->errorMsg);
                    return false;
                }
            }

            $this->db->commit();
            return $dealId;
        }else{
            $this->db->rollback();
            return false;
        }
    }

    /*
     * 账户总览 -- 用户投资概况
     *
     * @param $user_id int 用户id
     * @param $status int|array 借款状态
     * @return array counts & money
     */
    public function getInvestOverview($user_id, $status){

        if(is_array($status)){
            foreach($status as &$item){
                $item = $this->escape($item);
            }
            $status_condition = ' in ('.implode(',', $status).')';
        }else{
            $status_condition = ' = '.intval($status);
        }

        $sql = "SELECT COUNT(*) AS counts,SUM(d_l.money) AS money
                FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l
                ON d_l.deal_id = d.id WHERE d.deal_status ".$status_condition." AND d.is_delete = 0 AND parent_id != 0
                AND d_l.user_id = :user_id";

        $rs = $this->findBySql($sql,array(":user_id" => $user_id), true);
        if($status == 4 || $status == 5 ){
            $sql_fix = "SELECT COUNT(*) AS counts,SUM(d_l.money) AS money ,d_l.status as status
                FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_loan_repay` AS d_l
                ON d_l.deal_id = d.id WHERE d.deal_status = '4' AND d.is_delete = 0 AND parent_id != 0 AND d.deal_type = 1
                AND d_l.loan_user_id = :user_id AND d_l.status = 1 AND d_l.type = 8";
            $rs_fix = $this->findBySql($sql_fix,array(":user_id" => $user_id), true);
        }

        //回款中  单独处理通知贷的情况
        if($status == 4 && $rs_fix){
            //这部分减去
            $rs['counts'] = $rs['counts'] - $rs_fix['counts'];
            $rs['money'] = bcsub($rs['money'], $rs_fix['money'], 2);
        }
        //已汇款  单独处理通知贷的情况 //加上
        if($status == 5 && $rs_fix){
            $rs['counts'] = $rs['counts'] + $rs_fix['counts'];
            $rs['money'] = bcadd($rs['money'], $rs_fix['money'], 2);
        }

        return $rs;
    }

    /**
     * getDealRepayOverviewByTime
     * 账户总览 -- 回款计划
     *
     * @param mixed $user_id
     * @param mixed $begin
     * @param mixed $end
     * @access public
     * @return void
     */
    public function getDealRepayOverviewByTime($user_id, $begin = null, $end = null) {
        $sql = "SELECT COUNT(*) AS counts ,SUM(money) AS money FROM `firstp2p_deal_loan_repay`"
                ." WHERE type IN (1,2,3,4,5,7,8,9) AND money!=0 AND loan_user_id = :user_id AND `status` != '2' AND `time`!='0'";
        if (!empty($begin)) {
            $sql .= " AND time >= :begin";
        }
        if (!empty($end)) {
            $sql .= " AND time <= :end";
        }
        return $this->findBySql($sql, array(
                            ':user_id' => $user_id,
                            ':begin' => $begin,
                            ':end' => $end,
                        )
                        , true
                    );
    }

    public function getDealRepayOverview($user_id, $condition){

        $sql = "SELECT COUNT(*) AS counts ,SUM(money) AS money FROM `firstp2p_deal_loan_repay`
                WHERE type in (1,2,3,4,5,7) and money!=0 and loan_user_id = {$user_id} ".$condition;

        return $this->findBySql($sql);
    }

    /**
     * 根据贷款类型，获得每两次还款的间隔时间，单位为“月”
     */
    public function get_delta_month_time($loantype, $repay_time) {
        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $delta_month_time = 3;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $delta_month_time = 1;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $delta_month_time = $repay_time;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $delta_month_time = 1;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $delta_month_time = 3;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $delta_month_time = 1;
        }
        else if($loantype == 5)
        {
            $delta_month_time = $repay_time;
        }

        return $delta_month_time;
    }

    /**
     * 计算并更新进度和余额
     *
     * @return int
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function updatePercent($money, $user_id, $user_name, $ip, $source_type = 0, $site_id = null, $short_alias = null) {
        $site_id = $site_id ?$site_id: app_conf("TEMPLATE_ID");

        $buy_conut = $this->buy_count + 1;
        $load_money = $this->load_money + $money;
        $point_percent = $load_money / $this->borrow_amount ;

        // Do update
        $this->db->query("UPDATE ".$this->tableName()." SET `load_money`='{$load_money}', `point_percent`='{$point_percent}', `buy_count`='{$buy_conut}' WHERE `id` ='{$this->id}' AND `borrow_amount`>`load_money` AND `buy_count`='{$this->buy_count}' AND `deal_status` IN (0,1)");
        if (!$this->db->affected_rows()) {
            return false;
        }

        //写进 deal_load的log，记录下来投普通单的情况
        $data['money'] = $money;
        $data['user_id'] = $user_id;
        $data['user_name'] = $user_name;
        $data['user_deal_name'] = get_deal_username($user_id); //添加投标列表显示的用户名
        $data['create_time'] = get_gmtime();
        $data['from_deal_id'] = 0;
        $data['deal_id'] = $this->id;
        $data['source_type'] = $source_type;
        $data['deal_parent_id'] = -1;
        $data['site_id'] = $site_id;
        $data['ip'] = $ip;
        $data['deal_type'] = $this->deal_type;
        if (!empty($short_alias)){
            $data['short_alias'] = strtoupper($short_alias);
        }
        $this->db->autoExecute(DB_PREFIX."deal_load",$data,"INSERT");
        return $this->db->insert_id();
    }

    /**
     * 检查是否已经满标
     *
     * @return bool
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function isFull()
    {
        $deal = $this->find($this->id, 'deal_status');
        if ($deal['deal_status'] == 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 向 p2p传输 deal 和  deal_load数据
     */
    public function put_p2p_data($id){
        //$deal = $GLOBALS['db']->getRow(sprintf("SELECT id,loantype,repay_money,repay_time,type_id FROM ".DB_PREFIX."deal WHERE id=%d".$id));
        $deal = $this->findBy("id = :id", '*', array(':id' => intval($id)));

        if(!$GLOBALS['sys_config']['P2P_API_URL_iS_OK']) return false;

        if(empty($deal)){
            return false;
        }

        //$load_list = $GLOBALS['db']->getAll(sprintf("SELECT id,deal_id,user_id,user_name,user_deal_name,money,create_time,from_deal_id FROM ".DB_PREFIX."deal_load WHERE deal_id = %d", $this->escape($deal['id'])));
        $load_list = DealLoadModel::instance()->findAll("deal_id = :deal_id", true, '*', array(':deal_id' => $deal['id']));

        foreach ($load_list as &$item)
        {
            $item['adviser'] = $this->getAdviserInfo($item['id']);
            $item['create_time'] = strtotime(to_date($item['create_time']));
        }

        $post_data = array(
            'deal_info' => array(
                0 => $deal
            ),
            'deal_load' => $load_list
        );
        $url = $GLOBALS['sys_config']['P2P_API_URL'].'?act=first_p2p&type=order';

        // 数据插入到队列表
        $data = array();
        $data['dest'] = $url;
        $data['send_type'] = 2;
        $data['content'] = serialize($post_data);
        $data['create_time'] = get_gmtime();

        $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$data,"INSERT");

        return true;
    }

    /**
     * 根据借款金额计算预期还款总额
     * @return $repay_money float 还款总额
     */
    public function getAllRepayMoney() {
        $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
        $repay_cycle = $this->getRepayCycle();
        $repay_times = $this->getRepayTimes();

        $repay_money = 0;
        for($i = 0; $i < $repay_times; $i++) {
            $repay_time = $this->nextRepayDay($repay_time, $repay_cycle, $this->loantype);
            $is_last = (($i + 1) == $repay_times);
            $repay_info = $this->getRepayMoney($this->borrow_amount, $is_last);
            $repay_money += $repay_info['total'];
        }

        return $repay_money;
    }

    /**
     * 计算两次还款的间隔周期, 根据不同的还款方式，结果可能是月份或者天数
     *
     * @return integer
     **/
    public function getRepayCycle()
    {
        $repay_cycle= 0;
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $repay_cycle = 1;
        }
        return $repay_cycle;
    }

    /**
     * 根据给定的还款时间以及还款周期计算下次还款时间
     *
     * @param integer $time 本次还款时间或者开始还款时间
     * @param integer $repay_cycle 还款周期，可能是月数或者天数
     * @param integer $loantype 还款方式
     *
     * @return integer unix time
     **/
    public function nextRepayDay($time, $repay_cycle, $loantype)
    {
        $y = to_date($time,"Y");
        $m = to_date($time,"m");
        $d = to_date($time,"d");

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y."-".$m."-".$d,"Y-m-d") + $repay_cycle*24*60*60;
        }else{
            $target_m = $m + $repay_cycle;
            if($target_m > 12){
                ++$y;
            }
            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
            }

            return to_timespan($y."-".$m."-".$d,"Y-m-d");
        }
    }

    /**
     * 计算每期还款本金和利息以及总额
     *
     * @param boolen $is_last 是否最后一期
     * @param flaot $total_principal 本金总额
     * @param int $is_loan 0还款 1回款
     *
     * @return array 示例:array('total'=>111,'interest'=>222, 'principal'=>333)
     * total: 本期总还款额 interest: 本期利息  principal: 本期本金
     **/
    public function getRepayMoney($total_principal, $is_last = false, $is_loan = false, $interest_day = false, $periods_index=0) {
        $rate = $is_loan ? $this->income_fee_rate : $this->rate;
        $result = array();
        $repay_times = $this->getRepayTimes();
        $result['principal'] = $repay_times == 1 ? $total_principal : $total_principal / $repay_times;  // 计算每期本金
        if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 /12 * $repay_times)); //每期应还利息
            }
            if($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['interest'] + $result['principal'];
            }else{
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 / 4 * $repay_times));
            }
            if ($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['principal'] + $result['interest'];
            } else {
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $left_need_repay_principal = $total_principal;
            $month_rate = $rate / 12 /100;
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$total_principal);
                if ($periods_index == $i) {
                    if ($i == 1) {
                        $interest = $total_principal * $rate /100 * $interest_day / 360;
                    } else {
                        $interest = $left_need_repay_principal * $month_rate;
                    }
                    $repay_money['principal'] = $this->floorfix($repay_principal, 2);
                    $repay_money['interest'] = $this->floorfix($interest, 2);
                    $repay_money['total'] = bcadd($repay_money['principal'],$repay_money['interest'],2);
                    return $repay_money;
                }
                $left_need_repay_principal -= $this->floorfix($repay_principal, 2);
            }
        } elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){
            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷12）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index - 1)) * $rate / 100 /12);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $avgPrincipal = $this->floorfix($total_principal / $repay_times, 2);
            if ($is_last) {
                $result['principal'] = $total_principal - $avgPrincipal * ($repay_times - 1);
            } else {
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷4）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal / $repay_times * ($periods_index - 1)) * $rate / 100 / 4);
            $result['total'] = bcadd($result['principal'], $result['interest'], 2);
        }else { //按月付息之外的其他新借款
            $finance = new Finance();
            $pmt = $finance->getPmtByDealId($this->id, $periods_index, $total_principal);
            if($pmt !== false){
                if (!$periods_index) {
                    $interest = $is_loan ? $pmt['income_fee'] : $pmt['interest'];
                    $result['interest'] = $this->floorfix($interest * $total_principal / $this->borrow_amount / $repay_times);
                    $result['total'] = $result['interest'] + $result['principal'];
                } else {
                    $result['principal'] = $pmt['pmt_principal'];
                    $result['interest'] = $this->floorfix($pmt['pmt'] - $pmt['pmt_principal']);
                    $result['total'] = $pmt['pmt'];
                }
            }
            $result['total'] = $result['interest'] + $result['principal'];
        }

        $result['principal'] = $this->floorfix($result['principal']); //计算每期正常情况下应还本金
        $result['total'] = $this->floorfix($result['total']);
        return $result;
    }


    /**
     * 根据还款类型 以及 还款周期，获得每个周期需要还的本金和利息
     *
     * @author edit by wenyanlei  2013-8-15
     * @param $repay_mode 借款类型
     * @param $repay_period 借款期限
     * @param $total_loan_amount 借款金额
     * @param $rate 借款利率
     * @return float
     */
    function get_deal_repay_money($repay_mode, $repay_period, $total_loan_amount, $rate) {
        if($repay_mode == 3){//到期支付本金收益
            return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period)));
        }elseif($repay_mode == 2){//按月等额还款
            return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period);
        }elseif($repay_mode == 1){//按季等额还款
            return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/($repay_period/3));
        }

        return 0;
    }

    /**
     * 计算按月支付收益到期还本类型的每期还款额
     *
     * @param $repay_mode 借款类型
     * @param $repay_period 借款期限
     * @param $total_loan_amount 借款金额
     * @param $repay_mode 借款类型
     * @param $rate 借款利率
     * @param $is_last 是否最后一次还款
     * @param $month_interest 每月还款利息部分
     * @param $month_loan_amount 每月还款本金部分
     * @return float
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 11:09:13
     **/
    function get_deal_repay_money_month_interest($repay_mode, $repay_period, $total_loan_amount, $rate, $is_last = false, &$month_interest= null, &$month_loan_amount = null) {
        $month_loan_amount = $total_loan_amount / $repay_period; //计算每月应还本金
        $month_amount = $total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period; //每月应还总额
        $month_interest = $month_amount - $month_loan_amount; //每月应还利息
        if($is_last) {
            return ceilfix($month_interest + $total_loan_amount);
        } else {
            return ceilfix($month_interest);
        }
    }

    /**
     * 把利率金额数字转成数组，供数字图片化使用
     *
     * @param $str 利率金额数字字符串
     * @return bool|mixed
     */
    public function numberToArrayForPic($str) {
        if (empty($str)) {
            return false;
        }
        $result = str_split($str);
        $search = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ',');
        $replace = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'point', 'comma');
        return str_replace($search, $replace, $result);
    }

    /**
     * 获取订单状态
     *
     * @param $deal
     * @return bool|string
     */
    public function getDealStatusText($deal) {
        if (empty($deal)) {
            return false;
        }
        /*if (!isset($deal['guarantor_status'])) {
            $deal['guarantor_status'] = DealGuarantorModel::instance()->checkDealGuarantorStatus(intval($deal['id']));
        }*/
        $result = "";
        //if ($deal['is_update'] == 1 || $deal['deal_status'] == 0 || $deal['guarantor_status'] != 2) {
        if ($deal['is_update'] == 1 || $deal['deal_status'] == 0) {
            $result = "等待确认";
        } elseif ($deal['deal_status'] == 2) {
            $result = "满标";
        } elseif ($deal['deal_status'] == 3) {
            $result = "流标";
        } elseif ($deal['deal_status'] == 4) {
            $result = "还款中";
        } elseif ($deal['deal_status'] == 5) {
            $result = "已还清";
        } else {
            $result = "进行中";
        }
        return $result;
    }

    /**
     *
     * 获取投标的用户名称
     * @author Liwei
     * @date Jul 2, 2013 11:02:10 AM
     *
     */
    public function getDealUserName($user_id) {
        if (empty($user_id)) {
            return false;
        }
        $user_info = UserModel::instance()->find(intval($user_id), "*", true);
        if (empty($user_info)) {
            return false;
        }
        $sex = $user_info['sex'];
        if ($user_info['sex'] == -1) {
            $sexnum = -1;
            if (strlen($user_info['idno']) == 15) {
                $sexnum = substr($user_info['idno'], -1);
            } elseif (strlen($user_info['idno']) == 18) {
                $sexnum = substr($user_info['idno'], -2, 1);
            }
            if ($sexnum > 0) $sex = $sexnum % 2 ? 1 : 0;
        }
        //企业会员,显示企业名字
        if ($user_info['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
            $enterpriseInfo = EnterpriseModel::instance()->findByViaSlave('user_id=:user_id', '*', array(':user_id' => $user_id));
            if ($enterpriseInfo ) {
                return $enterpriseInfo->company_name;
            }
        } else {
            $company = UserCompanyModel::instance()->findByViaSlave("user_id = '$user_id'", 'name');
            if($company) {
                return $company->name;
            }
        }
        $user_sex_name = $GLOBALS['dict']['USER_SEX'][$sex];
        $real_name = $user_info['real_name'];

        //先取第一串英文字母，取不到的话，按中文截取
        if(preg_match('/^[a-zA-Z0-9]+/', $real_name, $out)){
            $pre_name = ($out[0] == $real_name) ? substr_replace($real_name, '******', 1, -1) : $out[0];
        }else{
            $pre_name = mb_substr($real_name, 0, 1, 'utf-8');
        }
        return $pre_name.$user_sex_name;
    }

    /**
     * 还款完成时的相关处理
     *
     * @return void
     **/
    public function repayCompleted($is_force_repay=false) {
        // 如果还有未完成还款 不能改为已还清状态
        if($is_force_repay){
            $condition = sprintf("`deal_id`=$this->id AND status=0");
            $count = DealRepayModel::instance()->count($condition);
            if($count > 0){
                \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",不能更改标的为已还清状态 因为还有未完成的还款 dealId:{$this->id}");
                return false;
            }
        }


        $this->deal_status = self::$DEAL_STATUS['repaid'];
        $this->update_time = get_gmtime();
        return $this->save();
    }

    /**
     * 判断是否新手标
     *
     * @param $id 订单id
     * @return bool
     */
    public function isForBeginner($id) {
        $sql = "SELECT deal_crowd FROM " . $this->tableName() . " WHERE id=:id";
        $deal = $this->findBySql($sql, array(":id" => $id));
        return $deal['deal_crowd'] == '1';

    }

    /**
     * 获取还款中借款的投资列表
     * @param string $where 查询条件
     * @param int $page 页码
     * @param int $page_size 每页条数
     */
    public function getRepayDealBidList($where = '', $limit = ''){

        $count_sql = "SELECT COUNT(*)
                FROM ".$this->tableName()." a RIGHT JOIN ".DealLoadModel::instance()->tableName()." b
                ON a.id = b.deal_id WHERE a.deal_status = 4 AND a.is_delete = 0 AND b.deal_parent_id != 0 ".$where;

        $list_sql = "SELECT a.id,a.name,a.user_id as deal_userid,a.borrow_amount,
                a.income_total_rate,a.repay_time,a.loantype,a.income_fee_rate,a.next_repay_time,
                b.id as bid,b.user_id as bid_userid,b.create_time,b.money as bid_money
                FROM ".$this->tableName()." a RIGHT JOIN ".DealLoadModel::instance()->tableName()." b
                ON a.id = b.deal_id WHERE a.deal_status = 4 AND a.is_delete = 0 AND b.deal_parent_id != 0 $where
                ORDER BY a.id DESC ";

        $res = array(
                'count' => $this->countBySql($count_sql, null, true),
                'list' => $this->findAllBySql($list_sql.$limit, true, null, true),
        );
        return $res;
    }

    /**
     * 检查是否已经申请或已完成提前还款
     * @return boolean
     **/
    public function isAppliedPrepay() {
        $deal_prepay = new DealPrepayModel();
        $condition = "`deal_id` = '%d' and (`status` ='0' or `status` = '1')";
        $condition = sprintf($condition, $this->escape((int)$this->id));
        $count = $deal_prepay->count($condition);
        return $count > 0;
    }

    /**
     * 检查是否已经逾期
     * @return boolean
     **/
    public function isOverdue() {
        return $this->next_repay_time + 24*3600 < get_gmtime();
    }

    /**
     * 检查是否已经可以进行提前还款
     * @return boolean
     **/
    public function canPrepay() {
        $gone_days = (get_gmtime() - $this->repay_start_time)/(24*60*60);
        return $gone_days >= $this->prepay_days_limit;
    }

    /**
     * 计算总计需还款金额
     * @return float
     **/
    public function totalRepayMoney() {
        $deal_repay = new DealRepayModel();
        $sql = "SELECT sum(repay_money) AS `sum` FROM %s WHERE `deal_id`='%d'";
        $sql = sprintf($sql, $deal_repay->tableName(), $this->escape((int)$this->id));
        $res = $this->findBySql($sql);
        return $res['sum'];
    }

    /**
     * 待还金额
     * @return float
     **/
    public function remainRepayMoney() {
        return $this->totalRepayMoney() - $this->repay_money;
    }

    /**
     * 获取借款类型名称
     *
     * @return string
     **/
    public function getLoantypeName() {
        return $GLOBALS['dict']['LOAN_TYPE'][$this->loantype];
    }

    /**
     * 根据cate获取分站满标
     * @param int $site_id
     * @return array
     */
    public function getFullDealsByCate($site_id = 0,$cate = 0) {
        $condition = "`deal_status` = '%d' AND `is_delete` = '0'";
        $condition = sprintf($condition, self::$DEAL_STATUS['full']);
        if(!empty($cate))
        {
            $condition .= sprintf(" AND `type_id`=%d",$this->escape($cate));
        }
        if (!empty($site_id)) {  //所有分站显示所有的
            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(), $site_id);
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `id` IN ($result1)";
        }
        return $this->findAll($condition);


//         $condition = "`deal_status` = '%d' AND `is_delete` = '0'";
//         $condition = sprintf($condition, self::$DEAL_STATUS['full']);
//         if(!empty($cate))
//         {
//             $condition .= sprintf(" AND `type_id`=%d",$this->escape($cate));
//         }
//         if (!empty($site_id)) {  //所有分站显示所有的
//             $condition .= sprintf(" AND `id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(), $site_id);
//         }
//         return $this->findAll($condition);
    }


    /**
     * 获取分站满标
     * @param int $site_id
     * @return array
     */
    public function getFullDeals($site_id = 0) {
        $condition = "`deal_status` = '%d' AND `is_delete` = '0'";
        $condition = sprintf($condition, self::$DEAL_STATUS['full']);
        if (!empty($site_id)) {  //所有分站显示所有的
            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(), $site_id);
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `id` IN ($result1)";
            //$condition .= sprintf(" AND `id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(), $site_id);
        }
        return $this->findAllViaSlave($condition);
    }

    /**
     * 通过uid 获取列表
     * @param $uid
     * @param $status
     * @param int | string $deal_type
     * @param boolen $has_entrust_zx 标识是否包含受托专享项目的标的
     * @param $limits
     */
    public function getListByUid($uid,$status,$limits, $deal_type = false, $has_entrust_zx = true){
        $sql = " deal_status = %d AND borrow_amount>0 AND user_id=%d AND is_visible = 1 ";
        $sql = sprintf($sql,$status,$uid);

        if(false !== $deal_type) {
            $sql .= sprintf(' and deal_type in (%s) ',$deal_type);
        }

        if(!$has_entrust_zx) {
            // 收集 此用户借的专享项目 id 用于过滤
            list($project_list) = DealProjectModel::instance()->getEntrustProjectListByUserId($uid);
            $pro_ids_collection = array(0);
            foreach ($project_list as $project) {
                $pro_ids_collection[] = $project['id'];
            }

            $sql .= sprintf(' and `project_id` NOT IN (%s) ', implode(',', $pro_ids_collection));
        }

        $limit = " LIMIT %d,%d ";
        $limit = sprintf($limit,$limits[0],$limits[1]);
        $order = " ORDER BY id DESC";
        $list = $this->findAll($sql . $order . $limit,true);
        $count = $this->count($sql . $order);
        if(!$list){
            return array();
        }
        $rs = array();
        foreach($list as $k => $deal){
            $rs[$k] = $this->handleDeal($deal);
        }
        return array('list'=>$rs,'count'=>$count);
    }

    /**
     * 根据项目id 及子标状态 查找子标集合
     * @param int $pro_id
     * @param array|int $deal_status
     * @param boolean $is_array 返回结构是否为数组
     * @return array
     * @author zhanglei5@ucfgroup.com
     */
    public function getDealByProId($pro_id,$deal_status=array(), $is_array = true) {
        $sql = "SELECT * FROM %s  WHERE project_id = ':pro_id' AND `is_delete` = 0 AND `publish_wait` = 0";
        $param = array(':pro_id'=>$pro_id);

        if(is_array($deal_status) && count($deal_status) > 0) {
            $sql .= " AND `deal_status` IN (".implode(',', $deal_status).")";
        }elseif(is_numeric($deal_status)) {
            $sql .= " AND `deal_status` = ':deal_status'";
            $param = array(':deal_status'=>$deal_status);
        }

        $sql = sprintf($sql, $this->tableName() );
        $result = $this->findAllBySql($sql, $is_array,$param);
        if(!$result){
            return array();
        }
        return $result;
    }

    /**
     * 根据状态类型、时间区间和审批单号获得标的列表
     * 走从库
     * @param int $type deal_type
     * @param int $status deal_status
     * @param int $start_time
     * @param int $end_time
     * @param string $approve_number
     * @access public
     * @return array()
     */
    public function getDealListByStatusTypeTime($type, $status, $start_time, $end_time, $approve_number, $fields = "*", $page_num = 1, $page_size = 100) {
        $limit = " LIMIT :prev_page , :curr_page";
        $params = array(
            ":prev_page" => ($page_num - 1) * $page_size,
            ":curr_page" => $page_size
        );

        $condition = sprintf(" `deal_type` = '%d' AND `deal_status`='%d' AND `is_delete` = '0' AND `publish_wait` = '0' AND `is_effect` = '1' ", intval($type), intval($status));
        if (!empty($start_time)) {
            $condition .= " AND `last_repay_time` >= '{$start_time}'";
        }
        if (!empty($end_time)) {
            $condition .= " AND `last_repay_time` <= '{$end_time}'";
        }
        if (!empty($approve_number)) {
            $condition .= " AND `approve_number` like '{$approve_number}'";
        }

        $count = $this->countViaSlave($condition, $params);
        $condition .= $limit;
        $list = $this->findAllViaSlave($condition, true, $fields,$params);
        $res['total_page'] = ceil(bcdiv($count,$page_size,2));
        $res['total_size'] = intval($count);
        $res['res_list'] = $list;
        return $res;
    }


    /**
     * 根据项目id 及子标状态 查找首标
     * @param int $pro_id
     * @param array|int $deal_status
     * @return array
     * @author wangjiantong@ucfgroup.com
     */
    public function getFirstDealByProId($pro_id,$deal_status=array()) {
        $sql = "SELECT * FROM %s  WHERE project_id = ':pro_id' AND `is_delete` = 0 AND `publish_wait` = 0";
        $param = array(':pro_id'=>$pro_id);

        if(is_array($deal_status) && count($deal_status) > 0) {
            $sql .= " AND `deal_status` IN (".implode(',', $deal_status).")";
        }elseif(is_numeric($deal_status)) {
            $sql .= " AND `deal_status` = ':deal_status'";
            $param = array(':deal_status'=>$deal_status);
        }

        $sql = sprintf($sql, $this->tableName() );
        $result = $this->findBySqlViaSlave($sql,$param);
        if(!$result){
            return array();
        }
        return $result;
    }

    public function getDealExt()
    {
        return DealExtModel::instance()->getInfoByDeal($this->id);
    }

    /**
     * getNoticeRepay
     * 查询出所有的指定天数之内需要还款的标
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-14
     * @param int $warn_day_start
     * @param int $warn_day_end
     * @access public
     * @return array
     */
    public function getNoticeRepay($warn_day_start, $warn_day_end){
        $type_id_xffq = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFFQ);

        $sql = "select d.*,e.need_repay_notice from firstp2p_deal as d LEFT JOIN firstp2p_deal_ext as e ON d.id = e.deal_id WHERE d.`type_id` NOT IN (".$type_id_xffq.") AND d.`deal_status` = 4 AND d.`parent_id` != 0 AND e.need_repay_notice = 1 AND (next_repay_time - ".get_gmtime().") /24/3600 between ".$warn_day_start." AND ".$warn_day_end;

        return $this->findAllBySqlViaSlave($sql);
    }

    /**
     * getCanDeleteByIds
     * 获得传入id中可以删除的 id
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-17
     * @param array/int $deal_ids
     * @access public
     * @return array
     */
    public function getCanDeleteByIds($deal_ids) {
        $ids = '';
        if (is_array($deal_ids)) {
            $ids = implode(',', $deal_ids);
        } else {
            $ids = $deal_ids;
        }
        $sql = "select id from firstp2p_deal where id in ($ids) and load_money =0 or id in ({$ids}) and deal_status = 3";
        $rs =  $this->findAllBySql($sql,true);
        foreach ($rs as $row) {
            $rids[] = $row['id'];
        }
        return $rids;
    }

    /**
     * searchDealById
     * 更具标ID获取标详情
     *
     * @param mixed $dealId
     * @access public
     * @return void
     */
    public function searchDealById($dealId)
    {
        $count = 1;
        $data = $this->find($dealId);
        return array("count" => $count, "list" => array($data));
    }

    /**
     * 处理投资使用红包的转账
     * @param unknown $bid_user_id
     * @param unknown $bonus_money
     */
    public function bidBonusTransfer($bid_user_id, $bonuses){

        //从机构账户扣款
        $payerMoney = array();
        $defaultPayer = app_conf('BONUS_BID_PAY_USER_ID');
        $bonus_money = 0;
        $transferService = new TransferService();
        $transferService->payerChangeMoneyAsyn = true;

        foreach ($bonuses as $bonus) {
            // TODO check o2o 红包返利规则
            if ($accountId = OtoBonusAccountModel::instance()->getAccount($bonus)) {
                $payerMoney[$accountId] = bcadd($payerMoney[$accountId], $bonus['money'], 2);
            } else {
                // 验证当前红包返利规则
                $taskId = '';
                if ($bonus['task_id']) {
                    $taskId = $bonus['task_id'];
                }
                if ($bonus['group_id']) {
                    $groupInfo = BonusGroupModel::instance()->find($bonus['group_id']);
                    if (isset($groupInfo['task_id']) && $groupInfo['task_id']) {
                        $taskId = $groupInfo['task_id'];
                    }
                }
                if ($taskId > 1000000) {
                    $taskType = BonusAccountModel::TYPE_RULE;
                } else {
                    $taskType = BonusAccountModel::TYPE_TASK;
                }
                if ($taskId && $accountId = BonusAccountModel::instance()->getAccountByTypeAndId($taskType, $taskId)) {
                    $payerMoney[$accountId] = bcadd($payerMoney[$accountId], $bonus['money'], 2);
                } else {
                    $payerMoney[$defaultPayer] = bcadd($payerMoney[$defaultPayer], $bonus['money'], 2);
                }
            }
        }

        // 分批转账
        foreach ($payerMoney as $payerId => $money) {
            $payObj = UserModel::instance()->find($payerId);
            if(empty($payObj)){
                return false;
            }
            //$payRes = $payObj->changeMoney(-$money, '红包充值', "{$bid_user_id}使用红包充值投资{$this->name}", 0, 0, 0);
            $payType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payNote = "{$bid_user_id}使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$this->name}";
            $receiverType = '充值';
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$this->name}";
            $transRes = $transferService->transferById($payerId, $bid_user_id, $money, $payType,
                                           $payNote, $receiverType, $receiverNote, $outOrderId = '');
            if ($transRes === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 消费红包
     * @param unknown $deal_id
     * @param unknown $load_id
     * @param unknown $bonus
     * @return Ambigous <boolean, unknown>
     */
    public function bidBonusUse($deal_id, $load_id, $bid_user_id, $bonus){

        //$syncRemoteData[] = array(
        //        'outOrderId' => 'BONUSTOBID|' . $load_id,
        //        'payerId' => app_conf('BONUS_BID_PAY_USER_ID'),
        //        'receiverId' => $bid_user_id,
        //        'repaymentAmount' => bcmul($bonus['money'], 100), //以分为单位
        //        'curType' => 'CNY',
        //);
        //FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');

        $id_arr = array();
        foreach($bonus['bonuses'] as $row){
            $id_arr[] = $row['id'];
        }

        $bonus_obj = new BonusService();
        return $bonus_obj->consume($id_arr, $load_id, $deal_id);
    }

    /**
     * 改变标的还款状态
     * @param unknown $status
     * @return boolean
     */
    public function changeRepayStatus($status){
        $this->is_during_repay = $status;
        $affect_row = 0;
        return $this->save();
    }

    /**
     * 将日利率转为年利率
     * @param float $rate_day
     * @param float
     */
    public function convertRateDayToYear($rate_day){
        $rate_year = pow(1+$rate_day/100, 360) - 1;
        return $this->floorfix($rate_year, 5);
    }

    /**
     * 将年利率转为日利率
     * @param float $rate_year
     * @param int $redemption_period 赎回周期
     * @return float
     */
    public function convertRateYearToDay($rate_year, $redemption_period){
        $rate_year = (float)$rate_year / 100;
        $rate_day = pow(1+$rate_year/360*$redemption_period, 1/$redemption_period) - 1;
        //$rate_day = pow(1+$rate_year, 1/360) - 1;
        return $this->floorfix($rate_day, 7);
    }

    /**
     * getByStatusTime
     * 根据状态和时间区间获得标的
     *
     * @param int $status
     * @param int $start_time
     * @param int $end_time
     * @access public
     * @return void
     */
    public function getByStatusTime($status, $start_time, $end_time) {
        $sql = "select id, `success_time`, `name` from firstp2p_deal where `deal_status` = {$status} AND `success_time` >= {$start_time} AND `success_time` <= {$end_time}";
        $rs =  $this->findAllBySql($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }

    /**
     * 更新标的放款中间状态
     * @param unknown $deal_id
     * @param unknown $status
     * @return boolean|Ambigous <number, boolean>
     */
    public function changeLoansStatus($deal_id, $status){
        if(!in_array($status, array(1, 2))){
            return false;
        }
        $old_status = ($status == 1) ? 2 : 0;
        $sql = "UPDATE `%s` SET `is_has_loans`='%d',`update_time`='%s' WHERE `id`='%d' AND `is_has_loans` = '%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($status), get_gmtime(), $this->escape($deal_id), $old_status);
        return $this->updateRows($sql);
    }

    /**
     * 更新标的还款中状态
     * @param int $deal_id
     * @return boolean|Ambigous <number, boolean>
     */
    public function changeDealStatus($deal_id){
        $old_status = 2;
        $status = 4;
        $sql = "UPDATE `%s` SET `deal_status`='%d',`repay_start_time`='%s' WHERE `id`='%d' AND `deal_status` = '%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($status), to_timespan(date("Y-m-d")), $this->escape($deal_id), $old_status);
        return $this->updateRows($sql);
    }

    /**
     * 检查一个用户是否是借款人
     * @param int $user_id
     * @return bool
     */
    public function isBorrowUser($user_id) {
        $params = array(
            ':user_id' => $user_id,
        );
        $cnt = $this->countViaSlave("`user_id`=':user_id' AND `is_effect`='1' AND `is_delete`='0'", $params);
        if ($cnt == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
    * 根据时间获取时间段内还款完成的标的
    * @param int start 时间下限
    * @param int end 时间上限
    */
    public function getRepayDoneDealsDurTimes($start,$end) {
        // 回款日历中去掉掌众、信石标的
        $zz_type_id = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        $al_type_id = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XSJK);
        $sql = "SELECT `id`,`name`,`borrow_amount` FROM firstp2p_deal where `deal_status`=5 AND `type_id` NOT IN ('{$zz_type_id}', '{$al_type_id}') AND `deal_type`=0 AND `last_repay_time` BETWEEN {$start} AND {$end}";
        $rs =  $this->findAllBySqlViaSlave($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }

    /**
    * 获取标的所有有还款的日期
    */
    public function getRepayDays($offset=0,$limit=15){
        // 数据库晚了8小时，查询出来的时间需要＋8小时
        // 当前时间需要－8小时获取今天2点的时间戳,今天开始时间减去8小时
        //$time = strtotime(date("Y-m-d"))+7200-28800;
        $time = to_timespan(date("Y-m-d"));
        $sql = "SELECT count(*) as count, FROM_UNIXTIME( last_repay_time+28800,'%Y%m%d') AS ymd FROM
                firstp2p_deal WHERE `deal_status`=5 AND `deal_type` =0 AND last_repay_time<{$time} AND last_repay_time>1420012800  GROUP BY ymd  ORDER BY `last_repay_time` DESC LIMIT {$offset},{$limit}";
        $rs =  $this->findAllBySqlViaSlave($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }

    /**
    * 获取getRepayDays纪录总条数
    */
    public function getRepayDaysCount(){
        $time = to_timespan(date("Y-m-d")) + 7200;
        $sql = "SELECT id FROM firstp2p_deal WHERE `deal_status`=5 AND `deal_type` =0 AND last_repay_time<{$time} AND last_repay_time>1420012800 GROUP BY FROM_UNIXTIME( last_repay_time+28800,'%Y%m%d')";
        $counts = $this->findAllBySqlViaSlave($sql,true);
        return count($counts);
    }

    /**
     * 处理投资逻辑
     * @param array $user
     * @param float $money
     * @param int $source_type
     * @param int $site_id
     * @param string $short_alias 优惠码
     * @return int|false
     */
    public function bidNew($user, $money, $source_type, $site_id, $bonus = array(), $short_alias=null) {
        // 开启事务
        $this->db->startTrans();

        try {
            if ($this->isFull()) {
                throw new \Exception('标的已经满标');
            }

            //处理红包的转账
            $handle_bonus = ($bonus['money'] > 0 && $bonus['bonuses']) ? true : false;
            if($handle_bonus){
                if($this->bidBonusTransfer($user['id'], $bonus['bonuses']) === false){
                    throw new \Exception('红包转账失败');
                }
            }
            $ip = get_real_ip();
            $loan_id = $this->doBid($money, $user['id'], $user['user_name'], $ip, $source_type, $site_id,$short_alias);
            if ($loan_id === false) {
                throw new \Exception('更新标的失败');
            }
            //消费红包
            if($handle_bonus){
                if($this->bidBonusUse($this->id, $loan_id, $user['id'], $bonus) === false){
                    throw new \Exception('消费红包失败');
                }
            }
            //更改资金记录
            $msg = "编号{$this->id} {$this->name}";
            $user = UserModel::instance()->find($user['id']);
            $user->changeMoneyDealType = $this->deal_type;

            $bizToken = [
                'dealId' => $this->id,
                'dealLoadId' => $loan_id,
            ];
            if ($user->changeMoney($money, "投标冻结", $msg, 0, 0, 1, 0, $bizToken) === false) {
                throw new \Exception('投标冻结失败');
            }
            $this->db->commit();
            return $loan_id;
        } catch (\Exception $e) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $user['id'], $money, $source_type, $site_id, $short_alias, $e->getMessage())));
            $this->db->rollback();
            return false;
        }
    }

    /**
     * 计算并更新进度和余额
     *
     * @return int
     **/
    public function doBid($money, $user_id, $user_name, $ip, $source_type = 0, $site_id = null, $short_alias = null) {
        $site_id = $site_id ?$site_id: app_conf("TEMPLATE_ID");
        $point_percent = bcadd($this->load_money, $money, 2) / $this->borrow_amount;

        //更新标的信息的金额限定条件
        $updateMoneyCond = '';
        // 只有专享和交易所标的并且设置了浮动起投才会计算
        if($this->is_float_min_loan == self::DEAL_FLOAT_MIN_LOAN_MONEY_YES && in_array($this->deal_type,array(self::DEAL_TYPE_EXCLUSIVE,self::DEAL_TYPE_EXCHANGE))){

            $dealMaxLoanCount = self::DEAL_MAX_LOAN_COUNT; // 标的最大投资次数
            // 读取配置项
            $jys_min_loan_money = $this->getJYSMinLoanMony($this->jys_id);
            if (empty($jys_min_loan_money)){
                $dealMinLoanUnit = self::DEAL_MIN_LOAN_UNIT; // 标的最小投资单位
            }else{
                $dealMinLoanUnit =  $jys_min_loan_money;
            }


            $updateMoneyCond = " AND ( ( (`borrow_amount`= ROUND(`load_money`+'{$money}', 2)) AND (buy_count+1 <={$dealMaxLoanCount}) ) OR ( (ROUND(`borrow_amount`-`load_money`-'{$money}', 2) >= {$dealMinLoanUnit})  AND (buy_count+1<{$dealMaxLoanCount}) ) ) ";
        } else {
            $updateMoneyCond = " AND (`borrow_amount`= ROUND(`load_money`+'{$money}', 2) OR ROUND(`borrow_amount`-`load_money`-'{$money}', 2) >= `min_loan_money` ) ";
        }

        // Do update
        $r = $this->db->query("UPDATE ".$this->tableName()." SET `load_money` = ROUND(`load_money`+'{$money}', 2), `point_percent`='{$point_percent}', `buy_count`=`buy_count`+1 WHERE `id` ='{$this->id}' {$updateMoneyCond} AND `deal_status` IN (0,1,6) AND `is_effect`='1'");
        if ($r === false || !$this->db->affected_rows()) {
            throw new \Exception('更新标的信息失败');
        }

        $deal = $this->find($this->id);
        $deal->update_time = get_gmtime();
        $deal->is_send_half_msg = 1;
        if (bccomp($deal['load_money'], $deal['borrow_amount'], 2) != -1) {
            $deal->deal_status = 2;
            $deal->success_time = get_gmtime();
        }else{
            $deal->min_loan_money = $this->getFloatMinLoanMoney($deal);
        }


        if ($deal->save() === false) {
            throw new \Exception('更新标的状态失败');
        }

        //写进 deal_load的log，记录下来投普通单的情况
        $data['money'] = $money;
        $data['user_id'] = $user_id;
        $data['user_name'] = $user_name;
        $data['user_deal_name'] = get_deal_username($user_id); //添加投标列表显示的用户名
        $data['create_time'] = get_gmtime();
        $data['from_deal_id'] = 0;
        $data['deal_id'] = $this->id;
        $data['source_type'] = $source_type;
        $data['deal_parent_id'] = -1;
        $data['site_id'] = $site_id;
        $data['ip'] = $ip;
        $data['deal_type'] = $this->deal_type;
        $data['short_alias'] = strtoupper($short_alias);
        if ($this->db->autoExecute(DB_PREFIX."deal_load",$data,"INSERT") === false) {
            throw new \Exception('插入投资记录失败');
        }
        return $this->db->insert_id();
    }

    /**
     * 标的浮动起投金额
     * @param $deal
     * @return float|string
     */
    public function getFloatMinLoanMoney($deal){
        // 只有专享和交易所标的并且设置了浮动起投才会计算
        if($deal->is_float_min_loan == self::DEAL_FLOAT_MIN_LOAN_MONEY_YES && in_array($deal->deal_type,array(self::DEAL_TYPE_EXCLUSIVE,self::DEAL_TYPE_EXCHANGE))){

            $remain_count = self::DEAL_MAX_LOAN_COUNT - $deal->buy_count;
            $remain_amount = bcsub($deal->borrow_amount,$deal->load_money,2);



            $min_loan_money = ceil(bcdiv($remain_amount, $remain_count * self::DEAL_MIN_LOAN_UNIT, 10)) * self::DEAL_MIN_LOAN_UNIT;

            $jys_min_loan_money = $this->getJYSMinLoanMony($deal->jys_id);
            if (empty($jys_min_loan_money)) {
                $deal->min_loan_money = ($min_loan_money < self::DEAL_MIN_LOAN_MONEY ? self::DEAL_MIN_LOAN_MONEY : $min_loan_money);
            }else{
                // 读取配置项
                $deal->min_loan_money = ($min_loan_money < $jys_min_loan_money ? $jys_min_loan_money : $min_loan_money);
            }
        }

        return $deal->min_loan_money;
    }
    /**
     *
     * 获取配置交易所最低起投额
     */
    public function getConfJYSMinLoanMony(){

        $conf = app_conf('JYS_MIN_LOAN_MONEY');

        if (empty($conf)){
            return array();
        }

        $conf_array = explode('|',$conf);

        $ret = array();
        foreach ($conf_array as $key => $v){
            list($jys_id,$min_loan_money) = explode(',',$v);

            $ret[$jys_id] = $min_loan_money;
        }

        return $ret;
    }

    /**
     *  获取交易所最低投资额
     */
    public function getJYSMinLoanMony($jys_id){

        if (empty($jys_id)){
            return  false;
        }

        $jys_conf_arr = $this->getConfJYSMinLoanMony();

        if (empty($jys_conf_arr)) return false;

        if (!isset($jys_conf_arr[$jys_id]) && empty($jys_conf_arr[$jys_id])){
            return false;
        }

        return $jys_conf_arr[$jys_id];
    }

    /**
     * 截标操作，将标的借款金额改为当前投资总额
     * @param int $deal_id
     * @return bool
     */
    public function updateMoney($deal_id) {
        $sql = "UPDATE " . $this->tableName() . " SET `borrow_amount`=`load_money`, `point_percent`='1', `deal_status`='2', `success_time`='" . get_gmtime() . "', `update_time`='" . get_gmtime() . "' WHERE `id`='{$deal_id}'";
        return $this->execute($sql);
    }

    /**
     * 根据表状态统计投资人已累计投资金额
     * @param $deal_status
     * @string $deal_types 标的类型
     */
    public function getTotalLoanMoneyByDealStatus($status,$deal_types='') {
        $deal_type_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }
        $sql = sprintf("SELECT SUM(load_money) as `sum` from %s WHERE deal_status IN (%s) %s ",$this->tableName(),$status,$deal_type_cond);
        $result = $this->findAllBySqlViaSlave($sql,true,null);
        return $result['0']['sum'];
    }

    /**
     * 通过标id批量获取标信息
     * @param array $deal_ids
     * @return array
     */
    public function getDealInfoByDealIds($deal_ids){
        $dealInfos = array();
        $result = $this->getDealInfoByIds($deal_ids, 'id,name,deal_status,rate,repay_time,loantype,repay_start_time');
        foreach($result as $item){
            $dealInfos[$item['id']]['deal_id'] = $item['id'];
            $dealInfos[$item['id']]['name'] = $item['name'];
            $dealInfos[$item['id']]['deal_status'] = $item['deal_status'];
            $dealInfos[$item['id']]['rate'] = $item['rate'];
            $dealInfos[$item['id']]['repay_time'] = $item['repay_time'];
            $dealInfos[$item['id']]['loantype'] = $item['loantype'];
            $dealInfos[$item['id']]['repay_start_time'] = $item['repay_start_time'];
        }
        return $dealInfos;
    }

    /**
     * 通过标id批量获取标信息
     * @param array $deal_ids
     * @param string $columns
     * @return array
     */
    public function getDealInfoByIds($deal_ids, $columns){
        $dealInfos = array();
        if(!empty($deal_ids) && is_array($deal_ids)){
            $deal_ids = array_map('intval', $deal_ids);
            $condition = ' id in ('.implode(',', $deal_ids).') ';
            $dealInfos = $this->findAllViaSlave($condition ,true, $columns);
        }
        return $dealInfos;
    }

    /**
     * 根据状态获取标的列表
     * @param $deal_status
     */
    public function getDealListByDealStatus($deal_status) {
        $condition = sprintf("`deal_status` = '%d'", $deal_status);
        return $this->findAllViaSlave($condition);
    }
    /**
     * getListBySiteId
     * 新查询接口，区间段获取信息
     *
     * @param mixed $type
     * @param mixed $sort
     * @param mixed $page
     * @param mixed $page_size
     * @param mixed $is_all_site
     * @param mixed $is_display
     * @param int $site_id
     * @param array $option
     * @access public
     * @return void
     */
    public function getListBySiteId( $sort, $page, $page_size, $site_id, $option=array()) {
        $params = array();
        $limit = " LIMIT :prev_page , :curr_page";
        $params[':prev_page'] = ($page - 1) * $page_size;
        $params[':curr_page'] = $page_size;
        $condition = "  `is_delete`='0' AND `deal_type` !=1  AND `publish_wait` = 0 AND `deal_status` in(0,1,2,4,5,3)";
        if(isset($option['deal_status'])){
            $condition .= " AND `deal_status` = (:deal_status)";
            $params[':deal_status'] = intval($option['deal_status']);
        }
        if(isset($option['id']) && intval($option['id']) > 0){
            $condition .= " AND `id` = (:id)";
            $params[':id'] = intval($option['id']);
        }

         if(isset($option['real_user_id']) && $option['real_user_id'] > 0){
             $condition .= " AND `user_id` = (:real_user_id)";
             $params[':real_user_id'] = intval($option['real_user_id']);
         }
        if(isset($option['name']) && $option['name'] !=''){
            $condition .= " AND `name` like '%:name%'";
            $params[':name'] = $option['name'];
        }
        $order  = " ORDER BY";
        $order .= " `id` DESC";
        $deslids = null;
        $count　= 0;
        $data= array();
        if($site_id){
            $deslids= DealSiteModel::instance()->getDealIdsBySiteId($site_id);
              }
         if($deslids){
             $deslids = implode(',', $deslids);
             $condition .= " AND `id` IN ($deslids)";
             $count = $this->countViaSlave($condition, $params);
             $data = $this->findAllViaSlave($condition . $order . $limit, true, '*', $params);
           }
        return array("count"=>$count, "list"=>$data);
    }

    /**
     * [getEntrustDealInfoList:获取代签状态的代理签署标的的信息列表（JIRA#3255）]
     * @author <fanjingwen@ucfgroup.com>
     * @param int $pageStart [如果有分页，此页从那行开始]
     * @param int $rowOfPage [每页显示的行数]
     * @param int $condStartTime [option：满标区间的开始时间，时间戳]
     * @param int $condEndTime [option：满标区间的结束时间，时间戳]
     * @param int $condDealID [option：标的id]
     * @param string $condUserIDs [option：标的所属用户的字符串数组，逗号分隔]
     * @param int $condDealStatus [option：标的状态，默认为2，满标]
     * @return array ['count' => 符合条件的标的总数, 'list' => 标的信息列表]
     */
    public function getEntrustDealInfoList($pageStart = 0, $rowOfPage = 0, $condStartTime = 0, $condEndTime = 0, $condDealID = 0, $condUserIDs = '', $condDealStatus = 2)
    {
        $proIDCondSql = "(SELECT `id` FROM `" . DealProjectModel::instance()->tableName() . "` WHERE `entrust_sign` = 1)";
        $dealIDCondSql = "(SELECT `deal_id` FROM `" . DealContractModel::instance()->tableName() . "` WHERE `status` = 0 AND `user_id` <> 0)";

        // 必要的查询参数
        $params = array(
            ':deal_status' => $condDealStatus,
        );

        $orderBy = " ORDER BY `success_time` DESC";

        $condition = " `project_id` IN {$proIDCondSql} AND `id` IN {$dealIDCondSql}";

        // 满标区间 - 开始时间
        if (!empty($condStartTime)) {
            $params[':con_start_time'] = $condStartTime;
            $condition .= " AND `success_time` >= :con_start_time";
        }

        // 满标时间 - 结束时间
        if (!empty($condEndTime)) {
            $params[':con_end_time'] = $condEndTime;
            $condition .= " AND `success_time` <= :con_end_time";
        }

        // 标的id
        if (!empty($condDealID)) {
            $params[':con_deal_id'] = $condDealID;
            $condition .= " AND `id` = :con_deal_id";
        }

        // 标的所属的用户
        if (!empty($condUserIDs)) {
            $params[':con_user_ids'] = $condUserIDs;
            $condition .= " AND `user_id` IN (:con_user_ids)";
        }

        // 获取符合条件的标的总数
        $count = $this->countViaSlave($condition, $params);

        // 是否有分页查询
        if (!empty($rowOfPage)) {
            $params[':page_start'] = $pageStart;
            $params[':row_of_page'] = $rowOfPage;
            $limit = " LIMIT :page_start, :row_of_page";
            $listOfDealInfo = $this->findAllViaSlave($condition . $orderBy . $limit, true, '*', $params);
        } else {
            $listOfDealInfo = $this->findAllViaSlave($condition . $orderBy, true, '*', $params);
        }

        return ['count' => $count, 'list' => $listOfDealInfo];
    }

    /**
     * [获取对应类型、状态的标的列表]
     * @author <fanjingwen@ucfgroup.com>
     * @param int $typeID [type_id]
     * @param int $dealStatus [deal_status]
     * @return array
     */
    public function getDealListByTypeAndStatus($typeID = -1, $dealStatus = -1)
    {
        $condition = " `is_delete` = 0";
        $params = [];
        $isRetArray = true;

        if (-1 != $typeID) {
            $condition .= " AND `type_id` = :type_id";
            $params[':type_id'] = intval($typeID);
        }

        if (-1 != $dealStatus) {
            $condition .= " AND `deal_status` = :deal_status";
            $params[':deal_status'] = intval($dealStatus);
        }

        return $this->findAll($condition, $isRetArray, '*', $params);
    }

    /**
     * 更新标的开始还款时间
     * @param int $deal_id
     * @param int $time
     * @return boolean
     */
    public function changeRepayStartTime($dealID, $time){
        if (empty($time)) {
            return false;
        }
        $data = array(
            'repay_start_time' => intval($time),
        );
        $conditon = " `id` = " . intval($dealID);
        return $this->updateBy($data, $conditon);
    }

    /**
     * 获取加息返利年化折算系数
     * @param int $loantyp 还款方式
     * @return float
     */
    public function getRebateRate($loantype) {
        switch($loantype) {
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANJI');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUE');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_XFFQ');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUEBJ');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANJIBJ');
                break;
            default:
                $r = 1;
                break;
        }

        return $r ? $r : 1;
    }

    /**
     * 披露信息放款总额
     * @string $deal_types 标的类型
     * @int $time 起始时间
     * @return float
     */
    public function getPublishBorrowAmountTotal($deal_types = '',$time = 0) {
        $deal_type_cond = '';
        $time_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }
        if(intval($time) > 0){
            $time_cond = ' AND start_time > '.intval($time);
        }
        $sql = 'SELECT SUM(borrow_amount) as total FROM '. $this->tableName() .' WHERE is_delete = 0 AND is_effect = 1 AND loantype != 7 AND  deal_status IN (4,5) %s '.$time_cond;
        $sql = sprintf($sql,$deal_type_cond);
        $res = $this->findBySqlViaSlave($sql);
        if(empty($res)) {
            return 0;
        }
        return floatval($res['total']);
    }

    /**
     * 披露信息累计收益
     * @string $deal_types 标的类型
     * @int $time 起始时间
     * @return float
     */
    public function getPublishInterestTotal($deal_types = '',$time = 0) {

        $deal_type_cond = '';
        $repay_time_cond = '';
        $prepay_time_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }
        if($time > 0){
            $repay_time_cond = 'AND true_repay_time > '.intval($time);
            $prepay_time_cond = 'AND prepay_time > '.intval($time);
        }

        $repaySql = 'SELECT SUM(interest) AS rTotal FROM firstp2p_deal_repay WHERE status != 0 '.$deal_type_cond.$repay_time_cond;
        $repay = $this->findBySqlViaSlave($repaySql);
        $prepaySql = 'SELECT SUM(prepay_interest+prepay_compensation) AS prTotal FROM firstp2p_deal_prepay WHERE status IN(1) '.$deal_type_cond.$prepay_time_cond;
        $prepay = $this->findBySqlViaSlave($prepaySql);
        return floatval($repay['rTotal'] + $prepay['prTotal']);
    }

    /**
     * 披露信息已放款的投资次数
     * @string $deal_types 标的类型
     * @int $time 统计开始时间
     * @return int
     */
    public function getPublishBuyCountTotal($deal_types = '',$time = 0) {
        $deal_type_cond = '';
        $time_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }
        if($time > 0){
            $time_cond = 'AND start_time > '.intval($time);
        }
        $sql = 'SELECT SUM(buy_count) as total FROM '. $this->tableName() .' WHERE is_delete = 0 AND is_effect = 1 AND loantype != 7 AND  deal_status IN (4,5) %s '.$time_cond;
        $sql = sprintf($sql,$deal_type_cond);
        $res = $this->findBySqlViaSlave($sql);
        if(empty($res)) {
            return 0;
        }
        return intval($res['total']);
    }

    /**
     * 获取智多鑫进行中的标的列表
     * @return array
     */
    public function getZDXProgressDealList() {
        $list = array();
        // 获取deal_status[进行中]的标的列表
        $sql = sprintf('SELECT `id`, `name`, `deal_status`, `type_id`, `advisory_id`, `project_id`, `loantype`, `repay_time`, `borrow_amount`, `load_money`, `deal_type`, `create_time`, `min_loan_money` FROM `%s` WHERE deal_status = %d AND publish_wait = 0 AND is_delete = 0 AND is_effect = 1 AND report_status = 1 ORDER BY `id` ASC', $this->tableName(),DealModel::$DEAL_STATUS['progressing']);
        $dealListDb = $this->findAllBySqlViaSlave($sql, true);
        if (empty($dealListDb)) {
            return $list;
        }

        $dealList = $dealIds = array();
        foreach ($dealListDb as $item) {
            $dealList[$item['id']] = $item;
        }

        $tagInfo = TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT);
        if (empty($tagInfo)) {
            return $list;
        }

        // 获取智多鑫标对应TAG的标的信息
        $sql = sprintf('SELECT deal_id,GROUP_CONCAT(tag_id) AS tag_id_group FROM `firstp2p_deal_tag` WHERE deal_id IN (%s) AND tag_id = %d GROUP BY deal_id', join(',', array_keys($dealList)), $tagInfo['id']);
        $dealTagList = $this->findAllBySqlViaSlave($sql, true);
        if (empty($dealTagList)) {
            return $list;
        }

        // 整理标的列表
        foreach ($dealTagList as $dt) {
            if(!empty($dealList[$dt['deal_id']])) {
                $list[] = $dealList[$dt['deal_id']];
            }
        }
        return $list;
    }


    public function updateReportStatus($dealId,$status){
        $this->db->query("UPDATE " . $this->tableName() . " SET  `report_status`={$status} WHERE `id` ='{$dealId}'");
        return $this->db->affected_rows();
    }

    /**
     * 专享委托标的扣除用户投标冻结金额
     * @param int $deal_data
     * @param int $consult_user_id 咨询机构用户id
     * @param int $guarantee_user_id 担保机构用户id
     * @param int $loan_user_id 平台机构用户id
     * @param int $pay_user_id 支付机构用户id
     * @param int $guarantee_user_id 担保机构用户id
     * @param int $guarantee_user_id 委托机构用户id
     */
    public function makeEntrustDealLoans($deal_data, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id,$entrust_user_id,$canal_user_id, $adm_info = array())
    {
        $deal_id = intval($deal_data['id']);

        $obj = new GTaskService();
        $event = new DealLoansMsgEvent($deal_id);
        $obj->doBackground($event, 1);
        $this->db->startTrans();
        $deal_ext = DealExtModel::instance()->getInfoByDeal($deal_id, false);
        try {
            //更新为已打款状态
            $deal = $this->find($deal_data['id']);
            if ($deal->is_has_loans != 2) {
                throw new \Exception("标不是放款中状态");
            }
            $syncRemoteData = array();
            $deal_service = new DealService();
            //将出借人冻结资金扣除,专享标转账至委托机构(人)账户
            $syncRemoteData = $deal_service->createUserSyncRemoteData($deal['id']);
            $serviceFee = $deal_service->makeServiceFee($deal['id']);
            $loan_fee = $serviceFee['loan_fee'];
            $consult_fee = $serviceFee['consult_fee'];
            $guarantee_fee = $serviceFee['guarantee_fee'];
            $pay_fee = $serviceFee['pay_fee'];
            $management_fee = $serviceFee['management_fee'];
            $canal_fee = $serviceFee['canal_fee'];

            $services_fee = $loan_fee + $consult_fee + $guarantee_fee + $pay_fee + $management_fee + $canal_fee;
            if (!empty($syncRemoteData)) {
                FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            }
            $this->db->commit();


        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }

        $result_data = array(
            "services_fee"=>$services_fee,
            "borrow_amount"=>$deal->borrow_amount,
            "loan_fee"=>$loan_fee,
            "consult_fee"=>$consult_fee,
            "guarantee_fee"=>$guarantee_fee,
            "pay_fee"=>$pay_fee,
            "management_fee"=>$management_fee,
            "canal_fee"=>$canal_fee,
        );

        return array("ret"=>true, "data"=>$result_data);
    }

    public function makeEntrustProjectLoans($project,$deals_result,$borrow_user_id,$consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id, $entrust_user_id, $canal_user_id, $adm_info){

        //为防止直接用项目金额和费率计算错误,传入该项目之前的放款总金额,各项总费率计算各种费用收取
        $project_id = intval($project['id']);
        $services_fee = $deals_result['services_fee'];
        $borrow_amount = $deals_result['borrow_amount'];
        $loan_fee = $deals_result['loan_fee'];
        $consult_fee = $deals_result['consult_fee'];
        $guarantee_fee = $deals_result['guarantee_fee'];
        $pay_fee = $deals_result['pay_fee'];
        $management_fee = $deals_result['management_fee'];
        $canal_fee = $deals_result['canal_fee'];


        $this->db->startTrans();
        try {
            $user_dao = new UserModel();
            $syncRemoteData = array();

            //给委托人转账
            $user = $user_dao->find($entrust_user_id);
            $user->changeMoneyAsyn = true;
            $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
            $note = "{$project['name']}";
            // TODO finance 投标成功相关扣款 扣投标款， 交纳平台手续费，交纳咨询费，交纳担保费

            $bizToken = [
                'projectId' => $project_id,
            ];

            //给委托人放款
            $this->changeMoney($user, $borrow_amount, '委托投资放款', $note, $adm_info['adm_id'],0,0, 0, $bizToken);

            //扣除放款金转给借款人
            $this->changeMoney($user, -$borrow_amount, '转让放款', $note, $adm_info['adm_id'],0,0, 0, $bizToken);

            //给借款人转账
            $borrow_user = $user_dao->find($borrow_user_id);
            $borrow_user->changeMoneyAsyn = true;
            $borrow_user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;;

            //给借款人转账记录
            $syncRemoteData[] = array(
                'outOrderId' => "ZX".$project_id,
                'payerId' => $entrust_user_id,
                'receiverId' => $project['user_id'],
                'repaymentAmount' => bcmul($borrow_amount, 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 4,
                'batchId' => $project_id,
            );
            $bizToken = [
                'projectId' => $project_id,
            ];
            $this->changeMoney($borrow_user, $borrow_amount, '项目招标成功', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            //给各个机构转账
            if (bccomp($loan_fee, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'LOAN_FEE|' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $loan_user_id,
                    'repaymentAmount' => bcmul($loan_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );

                $this->changeMoney($borrow_user, -$loan_fee, '平台手续费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            }

            if (bccomp($consult_fee, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'CONSULT_FEE' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $consult_user_id,
                    'repaymentAmount' => bcmul($consult_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );

                $this->changeMoney($borrow_user, -$consult_fee, '咨询费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            }

            if (bccomp($guarantee_fee, '0.00',2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'GUARANTEE_FEE|' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $guarantee_user_id,
                    'repaymentAmount' => bcmul($guarantee_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );

                $this->changeMoney($borrow_user, -$guarantee_fee, '担保费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            }

            if (bccomp($pay_fee, '0.00',2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'PAY_SERVICE_FEE|' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $pay_user_id,
                    'repaymentAmount' => bcmul($pay_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );

                $this->changeMoney($borrow_user, -$pay_fee, '支付服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            // 管理服务费
            if (bccomp($management_fee, '0.00',2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'MANAGEMENT_SERVICE_FEE|' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $management_user_id,
                    'repaymentAmount' => bcmul($management_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );
                $this->changeMoney($user, -$management_fee, '管理服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            // 渠道服务费
            if (bccomp($canal_fee, '0.00',2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'CANAL_SERVICE_FEE|' . "ZX".$project_id,
                    'payerId' => $project['user_id'],
                    'receiverId' => $canal_user_id,
                    'repaymentAmount' => bcmul($canal_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $project_id,
                );

                $this->changeMoney($borrow_user, -$canal_fee, '渠道服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);

            }

            //平台用户增加手续费
            if($loan_fee > 0){
                $user = $user_dao->find($loan_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $loan_fee, '平台手续费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            if($consult_fee > 0){
                $user = $user_dao->find($consult_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $consult_fee, '咨询费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            if($guarantee_fee > 0){
                $user = $user_dao->find($guarantee_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $guarantee_fee, '担保费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            if($pay_fee > 0){
                $user = $user_dao->find($pay_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $pay_fee, '支付服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            if($canal_fee > 0){
                $user = $user_dao->find($canal_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $canal_fee, '渠道服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            // 管理服务费
            if($management_fee > 0){
                $user = $user_dao->find($management_user_id);
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;
                $this->changeMoney($user, $management_fee, '管理服务费', $note, $adm_info['adm_id'], 0, 0, 0, $bizToken);
            }

            if (!empty($syncRemoteData)) {
                FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            }
            $this->db->commit();


        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }

        return array("ret"=>true, "data"=>array("services_fee"=>$services_fee));
    }

    /**
     * 下个还款日
     */
    public function next_replay_month_with_delta($time, $delta_month_time){
        $y = to_date($time,"Y");
        $m = to_date($time,"m");
        $d = to_date($time,"d");
        $target_m = $m + $delta_month_time;

        $year = floor($target_m / 12);
        $y += $year;

        $m = $target_m % 12;
        if ($m == 0) {
            $m = 12;
            $y--;
        }

        return to_timespan($y."-".$m."-".$d,"Y-m-d");
    }

    public function next_replay_day_with_delta($time, $day){
        $y = to_date($time,"Y");
        $m = intval(to_date($time,"m"));
        $d = intval(to_date($time,"d"));

        $td  = mktime(0, 0, 0, $m, $d +$day, $y);
        return $td;
    }

    /**
     * @param int $project_id
     * @return floatval
     */
    public function getFullDealsMoneySumByProjectId($project_id)
    {
        $sql = sprintf('SELECT SUM(`borrow_amount`) AS `full_money` FROM %s WHERE `project_id` = %d AND `deal_status` = %d', $this->tableName(), $project_id, self::$DEAL_STATUS['full']);
        $res = DealModel::instance()->findBySql($sql);
        return empty($res) ? 0 : floorfix($res['full_money']);
    }

    /**
     * 改变标的状态为 流标进行时
     * @param int $deal_id
     * @param int $bad_time 流标时间
     * @return boolen
     */
    public function changeDealIntoFailing($deal_id, $bad_time)
    {
        $deal = DealModel::instance()->find(intval($deal_id));
        if (empty($deal)) {
            return false;
        }

        $deal->deal_status = 3;
        $deal->is_doing = 1;
        $deal->time = intval($bad_time);

        return $deal->save();
    }

    /**
     * 根据项目id 统计标的数量
     * @param int $project_id
     * @param array | boolean $deal_status_arr
     * @return int count
     */
    public function countDealByProId($project_id, $deal_status_arr = false)
    {
        if ($project_id <= 0) {
            return 0;
        }

        $condition = sprintf(' `project_id` = %d ', $project_id);
        $condition .= (false === $deal_status_arr) ? '' : sprintf(' AND `deal_status` IN (%s) ', implode(',', $deal_status_arr));

        return $this->count($condition);
    }

    /**
     * 查询用户是否有【借款】记录
     * @param int $userId
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId) {
        $data = $this->findByViaSlave("user_id=':user_id' AND is_effect=1 AND is_delete=0 LIMIT 1", 'id', array(':user_id'=>(int)$userId));
        return !empty($data['id']) ? true : false;
    }

    /**
     * @author : yanjun5@ucfgroup.com
     * @function : 根据期限和剩余可投金额筛选标的列表
     * @param : $minInvest
     * @param : $minRepayTime
     * @return : array()
     */
    public function getDealListByDiscount($minInvest, $minRepayTime, $dealType = '', $offset = 0, $count = 20)
    {
        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id`"
            .", (borrow_amount - load_money) as remain_money"
            .", case when loantype=5 then  repay_time else repay_time * 30 end as real_repay_time"
            ." FROM firstp2p_deal"
        ;
        $condition = " WHERE `deal_status`='1' AND `deal_type` IN (%s) AND `publish_wait` = 0 AND `is_effect`='1' AND `is_delete`='0'  AND `is_visible`='1' ";
    $siteIdConf = formatConf(app_conf('DEAL_SITE_ALLOW'));
    $siteIds = !empty($siteIdConf) ? $siteIdConf : 0;
    $condition .= " AND site_id IN (".$siteIds.")";
        $orderByLoadMoney = '';
        if(!empty($minInvest)){
            $condition .= " AND (`borrow_amount` - `load_money`) >= " . $minInvest;
            $orderByLoadMoney = " ,remain_money ASC";
        }
        if(!empty($minRepayTime)){
            $condition .= " AND ( (`loantype`!='5' AND `repay_time` * 30 >= %d) OR (`loantype`='5' AND `repay_time` >= %d) )";
        }
        $orderBy = " ORDER BY real_repay_time ASC";
        $limit = " LIMIT {$offset} , {$count}";
        $sql .= $condition . $orderBy. $orderByLoadMoney. $limit;
        $sql = sprintf($sql, $dealType,$minRepayTime, $minRepayTime);
        return $this->findAllBySqlViaSlave($sql);
    }

    /**
     * 根据审批单号获取标的的状态
     * @param $approve_number
     * @return \libs\db\Model
     */
    public function getDealStatueByAppronum($approve_number) {
        $condition = sprintf("`approve_number` = '%s'", htmlentities($approve_number));
        return $this->findAllViaSlave($condition, true, 'id, deal_status');
    }
    /**
     * 查询用户借款未还金额
     * @param int $user_id
     * @return float
     */
    public function getUnrepayMoneyByUid($user_id) {
        $sql = sprintf("SELECT SUM(`borrow_amount` - `repay_money`) AS `m` FROM " . $this->tableName() . " WHERE `user_id` = '%d' AND `deal_status` IN (0,1,2,4,6)", $user_id);
        $res = $this->findBySql($sql);
        return $this->floorfix($res['m']);
    }

    /**
     * 通过多个uid获取这些用户p2p未还款金额,只统计需要报备的p2p忽略以前的p2p标的借款
     * @param array $user_ids
     * @return float
     */
    public function getUnrepayP2pMoneyByUids(array $user_ids) {
        if(empty($user_ids)) {
            return 0;
        }
        $s = new \core\service\ncfph\DealService();
        return $s->getUnrepayP2pMoneyByUids($user_ids);
    }

    /**
     * 获取定制的标
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     */
    public function getDealCustomUserList($is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false){

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id` FROM " .$this->tableName()
        ;

        $arr = $this->buildCondQuery(false, false, $is_all_site, $is_display, $site_id, $option, $is_real_site, false);

        if ($arr === false) {
            return array();
        }

        $cond = $arr['cond'];
        $params = $arr['param'];
        $result = array();
        $sql_tmp = $sql . " WHERE 1=1 " .$cond;

        $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);

        foreach($data as $v) {
            $result[] = $v;
        }

        return $result;

    }
}
