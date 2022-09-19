<?php


namespace core\service\deal;

use core\dao\repay\DealRepayModel;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use core\service\repay\DealPrepayService;
use core\service\repay\DealRepayService;
use libs\utils\Logger;
use libs\utils\Finance;
use libs\lock\LockFactory;
use core\enum\DealEnum;
use core\enum\DealLoadEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\LoanOplogEnum;
use core\enum\DealExtEnum;
use core\enum\ReserveEnum;
use core\enum\JobsEnum;
use core\enum\DealLoanRepayEnum;
use core\enum\contract\ContractEnum;
use core\service\BaseService;
use core\dao\deal\DealModel;
use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealExtraModel;
use core\dao\project\DealProjectModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealSiteModel;
use core\dao\contract\DealContractModel;
use core\dao\dealloan\LoanOplogModel;
use core\dao\jobs\JobsModel;
use core\service\deal\state\WaitingState;
use core\service\supervision\SupervisionService;
use core\service\reserve\ReservationMatchService;
use core\service\deal\DealTagService;
use core\service\contract\ContractService;
use core\service\contract\ContractNewService;
use core\service\contract\CategoryService;
use core\service\coupon\CouponService;
use core\dao\deal\DealLoadModel;
use NCFGroup\Common\Library\Idworker;
use core\service\duotou\DtDealService;
use core\dao\repay\DealLoanRepayModel;
use core\service\dealload\DealLoadService;
use core\service\user\UserService;
use core\enum\contract\ContractServiceEnum;
use core\service\bonus\BonusService;
use core\service\msgbus\MsgbusService;
use core\enum\MsgbusEnum;
use core\dao\deal\DealGroupModel;
use core\enum\CouponGroupEnum;

/// 有效的类导入
use libs\web\Url;
use libs\utils\Aes;

class DealService extends BaseService
{

    const ZONE_KEY = 'NCFWX_P2P_ZONE_DEAL_TAGS';

    //小贷标首页显示数量
    const PETTY_LOAN_INDEX_COUNT = 7;
    //专享标首页最多显示数量
    const ZX_INDEX_COUNT_MAX = 10;

    const LOAN_TYPE_MONTH = 5;//按天一次性还款


    public static $FIELD_HASH = array(
        '0' => 'id',
        '1' => 'income_total_rate',
        '2' => 'repay_time',
        '3' => 'point_percent',
        '4' => 'deal_status',
    );

    public static $TYPE_HASH = array(
        '0' => 'asc',
        '1' => 'desc',
    );

    public function getDealInfo($dealId)
    {
        return DealModel::instance()->getDealInfo($dealId);
    }


    /**
     * 生成旧版的借款标题，带有前缀 [前缀+项目名称+‘A’+9位编号]
     * @param int $deal_id
     * @param int $project_id
     * @return string $old_deal_name
     */
    public function getOldDealNameWithPrefix($deal_id, $project_id)
    {
        // 获取前缀名
        $deal_ext_obj = DealExtModel::instance()->getDealExtByDealId($deal_id);

        // 获取项目名
        $project_obj = DealProjectModel::instance()->find($project_id);

        // 拼接成旧版借款标题
        $id_str = str_pad(strval($deal_id), 9, strval(0), STR_PAD_LEFT);
        $deal_name = $deal_ext_obj->deal_name_prefix . $project_obj->name . 'A' . $id_str;

        return $deal_name;
    }

    /**
     * 初始化标的信息
     * @param int $deal_id
     * @param int $is_credit 是否是信贷传过来的标，1：信贷
     * @return bool
     * @throw \Exception
     */
    public function initDeal($deal_id, $is_credit = 1)
    {
        try {
            if (empty($deal_id)) {
                throw new \Exception('invalid deal_id');
            }

            $deal = DealModel::instance()->find(intval($deal_id));
            if (empty($deal)) {
                throw new \Exception('empty deal info');
            }

            if ($deal['deal_status'] != DealEnum::DEAL_STATS_WAITING) {
                throw new \Exception('标的初始化必须在等待状态');
            }


            if (1 == $is_credit) {
                // JIRA#3271 平台产品名称定义 - 更新标的名
                if (!$this->updateDealNameAndPrefix($deal['id'], $deal['project_id'], DealEnum::DEAL_NAME_PREFIX)) {
                    throw new \Exception('update deal name fail');
                }
            }

            $deal['deal_status'] = 0;
            $state_manager = new \core\service\deal\state\StateManager($deal);
            $state_manager->work();
            Logger::info(sprintf('success:init deal,deal_id:%d,file:%s,line:%s', $deal_id, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('fail:init deal,deal_id:%d,error-msg:%s,file:%s,line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            throw $e;
        }
    }

    /*
   * @author : fanjingwen@ucfgroup.com
   * @function :  根据标id、标名前缀，更新标名 及标的名的前缀字段
   * @param : $dealID
   * @param : $projectID
   * @param : $prefixTitle 标名前缀
   * @return boolean
   */
    public function updateDealNameAndPrefix($dealID, $projectID, $prefixTitle)
    {
        if (empty($dealID) || empty($projectID)) {
            return false;
        }

        // 生成标的名称
        $dealName = $this->createDealName($dealID, $projectID);

        $date = ['name' => $dealName];
        $cond = "`id` = '{$dealID}'";

        // 同步更新标名，及其前缀字段
        $GLOBALS['db']->startTrans();
        try {
            // 更新标名
            $rsDeal = DealModel::instance()->updateBy($date, $cond);
            if (empty($rsDeal)) {
                throw new \Exception("标的名称更新失败" . $dealID);
            }

            // 更新前缀
            DealExtModel::instance()->updateDealNamePrefix($dealID, $prefixTitle);

            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:update_deal_name,id:%d,new_name:%s,file:%s,line:%s', $dealID, $dealName, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail:update_deal_name,id:%d,new_name:%s,error-msg:%s,file:%s,line:%s', $dealID, $dealName, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * @author : fanjingwen@ucfgroup.com
     * @function : 根据标id、标名前缀，生成标名
     * @param : $dealID
     * @param : $projectID
     * @return : string 标名 (prefix + projectName + 'A' . postfix)
     */
    public function createDealName($dealID, $projectID)
    {
        $projectObj = DealProjectModel::instance()->find($projectID);
        $dealObj = DealModel::instance()->find($dealID, 'id,type_id,agency_id');

        // JIRA#3844 产品名称整体更新
        $product_class = empty($projectObj) ? '' : $projectObj->product_class;

        // deal id补全9位
        $idStr = str_pad(strval($dealID), 9, strval(0), STR_PAD_LEFT);
        $nddTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_NDD);

        // 农担支农贷，则使用定制的命名方式  name = 国担支农贷 + 空格 + 担保机构简写 + 空格 + 编号
        if ($nddTypeId == $dealObj['type_id']) {
            $agencyObj = DealAgencyModel::instance()->getDealAgencyById($dealObj['agency_id']);
            // 担保机构名称前面加个空格
            $agencyName = empty($agencyObj['short_name']) ? '' : ' ' . $agencyObj['short_name'];
            $dealName = '国担支农贷' . $agencyName . ' A' . $idStr;
            return $dealName;
        }

        $dealName = $product_class . 'A' . $idStr;

        return $dealName;
    }

    /**
     * @function :  根据标id、标名前缀，更新标名
     * @param : $dealID
     * @param : $projectID
     * @return : string 插入的标名
     */
    public function updateDealName($dealID, $projectID)
    {
        if (empty($dealID) || empty($projectID)) {
            return '';
        }

        // 生成标的名称
        $dealName = $this->createDealName($dealID, $projectID);

        $date = ['name' => $dealName];
        $cond = "`id` = '{$dealID}'";
        $rs = DealModel::instance()->updateBy($date, $cond);
        if (empty($rs)) {
            throw new \Exception("标的名称更新失败" . $dealID);
        }

        return $dealName;
    }

    /**
     * 根据项目id 及子标状态 查找子标集合
     * @param int $pro_id
     * @param array|int $deal_status
     * @return array
     * @author zhanglei5@ucfgroup.com
     */
    public function getDealByProId($pro_id, $deal_status = array())
    {
        $result = DealModel::instance()->getDealByProId($pro_id, $deal_status);
        return $result;
    }

    /**
     * 比较要删除的 deal_ids
     * @return array
     */
    public function compareDeleteByIds($deal_ids)
    {
        $deny_ids = $allow_ids = array();
        $allow_ids = DealModel::instance()->getCanDeleteByIds($deal_ids);
        if (is_array($deal_ids)) {
            if (empty($allow_ids)) {
                $allow_ids = array();
            }
            $deny_ids = array_diff($deal_ids, $allow_ids);
        } else {
            if (count($allow_ids) == 0) {
                $deny_ids = $deal_ids;
            }
        }

        return array('allow' => $allow_ids, 'deny' => $deny_ids);

    }

    /**
     * 根据id 批量逻辑删除 恢复
     * @param string $ids
     * @param int $delete_value 1删除，0恢复
     * @return bool
     */
    public function batchDeleteByIds($ids, $delete_value = 1)
    {

        if (empty($ids) || !is_string($ids)) {
            return false;
        }
        $ids = addslashes($ids);
        $deal_model = new DealModel();
        $condition = "id in ($ids)";
        $delete = empty($delete_value) ? 0 : 1;

        return $deal_model->updateAll(array('is_delete' => $delete), $condition, true);

    }

    /**
     * 复制借款
     *
     * @param $deal_id 借款id
     * @param $deal_project_id 项目id
     * @return bool
     */
    public function copyDeal($deal_id, $deal_project_id = 0)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_id, $deal_project_id);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $GLOBALS['db']->startTrans();
        try {
            $deal_id = intval($deal_id);
            if ($deal_id <= 0) {
                throw new \Exception("被复制标ID不能小于0");
            }
            $old_deal = DealModel::instance()->getDealInfo($deal_id);
            if (empty($old_deal)) {
                throw new \Exception("被复制的标信息不存在");
            }

            $deal_model = new DealModel();
            if ($deal_project_id) {
                $old_deal['project_id'] = $deal_project_id;
            }

            $deal_insert_id = $deal_model->insertDealData($old_deal);
            if (empty($deal_insert_id)) {
                throw new \Exception("复制标失败");
            }

            // JIRA#3844 更新标的名 by fanjingwen
            $dealName = $this->updateDealName($deal_insert_id, $old_deal->project_id);

            //处理扩展信息表
            $old_deal_ext = DealExtModel::instance()->findBy('deal_id =' . $deal_id);
            if ($old_deal_ext) {
                $deal_ext_model = new DealExtModel();
                $old_deal_ext['deal_id'] = $deal_insert_id;
                $ext_insert = $deal_ext_model->insertDealExt($old_deal_ext);
                if (empty($ext_insert)) {
                    throw new \Exception("复制标扩展信息失败");
                }
            }

            //处理额外信息表
            $old_deal_extra = DealExtraModel::instance()->findBy('deal_id =' . $deal_id);
            if ($old_deal_extra) {
                $deal_extra_model = new DealExtraModel();
                $old_deal_extra['deal_id'] = $deal_insert_id;
                $extra_insert = $deal_extra_model->insertDealExtra($old_deal_extra);
                if (empty($extra_insert)) {
                    throw new \Exception("复制标额外信息失败");
                }
            }

            //处理所属站点信息
            $site_list = DealSiteModel::instance()->findAll('deal_id = ' . $deal_id);
            if ($site_list) {
                $deal_site_model = new DealSiteModel();
                foreach ($site_list as $site_row) {
                    $site_insert = $deal_site_model->insertDealSite($deal_insert_id, $site_row->site_id);
                    if (empty($site_insert)) {
                        throw new \Exception("复制所属展示信息失败");
                    }
                }
            }

            //复制专享标的信息
            if ($old_deal['deal_crowd'] == 2) {
                $dealGroupModel = new DealGroupModel();
                $dealGroups = $dealGroupModel->getListByDeal($deal_id);
                $groupIds = [];
                $relation = 0;
                foreach ($dealGroups as $item) {
                    $relation = $item['relation'];
                    $groupIds[] = $item['user_group_id'];
                }
                if (!empty($groupIds)) {
                    $dealGroupModel->saveDealGroup($deal_insert_id, $groupIds, $relation);
                }
            }

            //复制标的自定义tag
            $tag_service = new DealTagService();
            $tag_arr = $tag_service->getTagByDealId($deal_id);
            if (count($tag_arr) > 0 && is_array($tag_arr)) {
                $tag_service->insert($deal_insert_id, implode(',', $tag_arr));
                if (empty($tag_service)) {
                    throw new \Exception("复制自定义tag失败");
                }
            }

            // 复制优惠码标的设置
            $couponOldInfo = CouponService::getCouponDealByDealId($deal_id);
            if (empty($couponOldInfo)) {
                throw new \Exception('获取标的优惠码信息失败');
            }
            $couponInfo = array(
                'dealId' => $deal_insert_id,
                'rebateDays' => 0,
                'payType' => $couponOldInfo['pay_type'],
                'payAuto' => $couponOldInfo['pay_auto'],
            );

            // 优惠码设置又放消息总线了。。。。
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_CREATE,$couponInfo);
            $rs = $GLOBALS['db']->commit();
            if (empty($rs)) {
                throw new \Exception("复制标事务提交失败");
            }

            Logger::info(implode(" | ", array_merge($log_info, array('复制标相关信息成功，优惠码设置还未复制'))));

        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array_merge($log_info, array('复制标失败:' . $e->getMessage()))));
            return false;
        }

        return true;
    }

    /*
     * 标的是否报备到存管行
     * @param $dealId
     * @return bool
     */
    public function hasReportToBank($dealId)
    {
        $deal = $this->getDealInfo($dealId);
        return $deal->report_status == DealEnum::DEAL_REPORT_STATUS_YES ? true : false;
    }

    /**
     * 通过多个uid获取这些用户p2p未还款金额,只统计需要报备的p2p忽略以前的p2p标的借款
     * @param array $user_ids
     * @return float
     */
    public function getUnrepayP2pMoneyByUids(array $user_ids)
    {
        return DealModel::instance()->getUnrepayP2pMoneyByUids($user_ids);
    }

    /*
     * 保存标的各项费率
     * @param int $deal_id
     * @param array $period_fee_arr 分期手续费 二维数组 各项手续费默认为 0 ('loan_fee_arr' => 0, 'consult_fee_arr' => 0, 'guarantee_fee_arr' => 0, 'pay_fee_arr' => 0, 'management_fee_arr' => 0)
     * @param string-json $old_period_fee_json 原有的 fee_ext 信息，格式为 json_encode 结果
     * @param array $is_keep_old_fee_ext 如果是分期类的收费方式时，是否保留原有的 fee_ext 信息
     * @return boolean
     */
    static public function updateHandlingCharge($deal_id, $period_fee_arr = array(), $is_keep_old_fee_ext = false)
    {
        if (empty($period_fee_arr)) {
            $period_fee_arr = array('loan_fee_arr' => 0, 'consult_fee_arr' => 0, 'guarantee_fee_arr' => 0, 'pay_fee_arr' => 0, 'management_fee_arr' => 0);
        }

        $deal_obj = DealModel::instance()->getDealInfo($deal_id);
        $deal_ext_obj = DealExtModel::instance()->findBy(sprintf('`deal_id` = %d', $deal_id));
        $loan_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->loan_fee_rate, $deal_ext_obj->loan_fee_rate_type, $period_fee_arr['loan_fee_arr'], $deal_ext_obj->loan_fee_ext, $is_keep_old_fee_ext); // 平台费
        $consult_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->consult_fee_rate, $deal_ext_obj->consult_fee_rate_type, $period_fee_arr['consult_fee_arr'], $deal_ext_obj->consult_fee_ext, $is_keep_old_fee_ext); // 咨询费
        $guarantee_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->guarantee_fee_rate, $deal_ext_obj->guarantee_fee_rate_type, $period_fee_arr['guarantee_fee_arr'], $deal_ext_obj->guarantee_fee_ext, $is_keep_old_fee_ext); // 担保费
        $pay_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->pay_fee_rate, $deal_ext_obj->pay_fee_rate_type, $period_fee_arr['pay_fee_arr'], $deal_ext_obj->pay_fee_ext, $is_keep_old_fee_ext); // 支付服务费
        $management_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->management_fee_rate, $deal_ext_obj->management_fee_rate_type, $period_fee_arr['management_fee_arr'], $deal_ext_obj->management_fee_ext, $is_keep_old_fee_ext); // 管理费
        $canal_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->canal_fee_rate, $deal_ext_obj->canal_fee_rate_type, $period_fee_arr['canal_fee_arr'], $deal_ext_obj->canal_fee_ext, $is_keep_old_fee_ext); // 渠道费


        return DealExtModel::instance()->saveDealExtServicefee($deal_obj->id, $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr, $pay_fee_arr, $canal_fee_arr, $management_fee_arr);
    }

    /**
     * 计算标的各项手续费
     * @param object $deal_obj 标的数据对象
     * @param int $fee_rate 收费利率
     * @param int $fee_rate_type 收费方式
     * @param array $period_fee_arr 分期手续费 二维数组 ['loan_fee_arr' => [], 'consult_fee_arr' => [], 'guarantee_fee_arr' => [], 'pay_fee_arr' => [], 'management_fee_arr' => []]
     * @param string-json $old_period_fee_json 原有的 fee_ext 信息，格式为 json_encode 结果
     * @param boolean $is_keep_old_fee_ext 标识此项利率是否保持原有的 fee_ext 信息
     * @return array 各期应收费用 (元)
     */
    static private function calcHandlingCharge($deal_obj, $fee_rate, $fee_rate_type, $period_fee_arr, $old_period_fee_json, $is_keep_old_fee_ext)
    {
        if (!is_object($deal_obj)) {
            return array();
        }

        // 不同的收费方式
        $ext_fee_arr = array();
        if ($is_keep_old_fee_ext && in_array($fee_rate_type, array(DealExtEnum::FEE_RATE_TYPE_PERIOD, DealExtEnum::FEE_RATE_TYPE_PROXY))) { // 分期一类的收费方式，可以选择保留原值
            $ext_fee_arr = json_decode($old_period_fee_json, true);
        } else if (in_array($fee_rate_type, array(DealExtEnum::FEE_RATE_TYPE_PERIOD, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD)) && is_array($period_fee_arr)) { // 分期收、固定比例分期收
            $ext_fee_arr = array_map('addslashes', $period_fee_arr);
        } else if ($fee_rate_type == DealExtEnum::FEE_RATE_TYPE_BEHIND) { // 后收
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00); // 尾期之前都为0
            $period_fee_rate = Finance::convertToPeriodRate($deal_obj->loantype, $fee_rate, $deal_obj->repay_time, false); // 期间利率
            array_push($ext_fee_arr, $deal_obj->floorfix($deal_obj->borrow_amount * $period_fee_rate / 100.0)); // 尾期
        } else if ($fee_rate_type == DealExtEnum::FEE_RATE_TYPE_PROXY && is_array($period_fee_arr)) { // 代销分期
            $period_fee_arr = array_map('addslashes', $period_fee_arr);
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00);
            $ext_fee_arr[0] = $period_fee_arr[0]; // 0 期
            $ext_fee_arr[] = $period_fee_arr[1]; // 尾期
        } else if (DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND == $fee_rate_type) { // 固定比例后收
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00); // 尾期之前都为0
            array_push($ext_fee_arr, $deal_obj->floorfix($deal_obj->borrow_amount * $fee_rate / 100.0)); // 尾期
        }

        return $ext_fee_arr;
    }

    /**
     * 后台标的更新合同和优惠码设置
     * @param $deal_id
     * @param $admin
     * @param $coupon_info
     */
    public function updateContract($deal_id, $admin, $contract_tpl_type, $coupon_info)
    {


        if (empty($deal_id)) {
            throw new \Exception('参数错误');
        }
        if (!empty($contract_tpl_type)) {
            $contract_info = CategoryService::getDealCId(intval($deal_id));
            if (!empty($contract_info)){
                //合同服务更新标的模板分类ID
                $contractResponse = CategoryService::updateDealCId(intval($deal_id), intval($contract_tpl_type));
            }else{
                $contractResponse = CategoryService::setDealCId(intval($deal_id), intval($contract_tpl_type));
            }
            if ($contractResponse == false) {
                $errMsg = '更新合同模板分类失败';
                throw new \Exception($errMsg);
            }
        }

        return true;
    }

    public function insertContract($deal_id, $admin, $contract_tpl_type)
    {

        if (empty($deal_id)) {
            throw new \Exception('更新合同和优惠码标的参数错误');
        }

        if (!empty($contract_tpl_type)) {
            //合同服务设置标的模板分类ID
            $contractResponse = CategoryService::setDealCId(intval($deal_id), intval($contract_tpl_type));
            if ($contractResponse == false) {
                $errMsg = '添加合同模板分类失败 ';
                throw new \Exception($errMsg);
            }
        }

        return true;

    }

    /**
     * 根据还款方式，借款期限，计算需要拆分为多少期进行还款
     * @param int $loantype 还款方式
     * @param int $repay_time 借款期限
     * @return integer
     **/
    public function getRepayTimesByLoantypeAndRepaytime($loantype, $repay_time)
    {
        $deal_model = new DealModel();
        $deal_model->loantype = $loantype;
        $deal_model->repay_time = $repay_time;
        return $deal_model->getRepayTimes();
    }

    /**
     * 获取投资列表
     *
     * @param $have_crowd_specific 是否显示‘特定用户组’的标
     * @param $needCount 是否需要统计count信息 默认统计
     */
    public function getList($cate, $type, $field, $page, $page_size = 0, $is_all_site = false, $site_id = 0, $show_crowd_specific = true, $dealTypes = '', $dealTagName = '', $needCount = true, $isShowP2p = false, $option = array())
    {
        $deal_types_data = DealLoanTypeModel::instance()->getDealTypes();
        $arr_types = $deal_types_data['others'];
        if (!in_array($cate, $arr_types)) {
            $cate = 0;
        }

        $type = isset($type) ? $type : null;
        $field = isset($field) ? $field : null;

        $sort['field'] = isset(self::$FIELD_HASH[$field]) ? self::$FIELD_HASH[$field] : null;
        $sort['type'] = isset(self::$TYPE_HASH[$type]) ? self::$TYPE_HASH[$type] : null;

        $page = $page <= 0 ? 1 : $page;
        $page_size = $page_size <= 0 ? app_conf("DEAL_PAGE_SIZE") : $page_size;

        $deal_type = $deal_types_data['data'];

        $option['show_crowd_specific'] = $show_crowd_specific ? 1 : 0;

        $option['deal_type'] = $dealTypes;

        $option['isHitSupervision'] = $isShowP2p;

        if (!empty($dealTagName)) {
            $option['deal_tag_name'] = explode(',', $dealTagName);
        }

        foreach ($deal_type as $k => $v) {
            $type_id = isset($v['id']) ? $v['id'] : false;
            if ($cate == $k) {
                $deals = DealModel::instance()->getList($type_id, $sort, $page, $page_size, $is_all_site, true, $site_id, $option, false, false, false, $needCount);
                $list = $deals['list'];
                foreach ($list as $key => $deal) {
                    $list[$key] = DealModel::instance()->handleDealNew($deal, 1);
                }
                $result['page_size'] = $page_size;
                if ($needCount) {
                    $result['count'] = $deals['count'];
                }
            }
        }

        $data['list'] = $list;
        $result['list'] = $data;
        $result['sort'] = $sort;
        $result['deal_type'] = $deal_type;
        $result['cate'] = $cate;
        return $result;
    }

    public function getP2pSiteTags()
    {
        $return = array();
        $product_class_types = array();
        $loan_user_customer_types = array();

        $customerTypes = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'];
        foreach ($customerTypes as $k => $v) {
            $loan_user_customer_types[] = array('id' => $k, 'name' => $v);
        }

        $sortCond = " ORDER BY FIELD(name,'车贷','供应链','个体经营贷','消费贷') DESC ";
        $secondLayers = DealTypeGradeService::getAllSecondLayersByName('P2P', $sortCond);
        if (!empty($secondLayers)) {
            foreach ($secondLayers as $layer) {
                // 去掉p2p下的'国担支农系列产品'的产品大类
                if ($layer['name'] === '国担支农贷') {
                    continue;
                }
                $product_class_types[] = array('id' => $layer['id'], 'name' => $layer['name']);
            }
        }
        $return['product_class_types'] = $product_class_types;
        $return['loan_user_customer_types'] = $loan_user_customer_types;
        return $return;
    }

    /**
     * 检查是否满足18岁以上可投的条件
     *
     * @param int $year
     * @return boolean 是否可投
     */
    public function allowedBidByCheckAge($user)
    {

        $res_err = array(
            'error' => false,
            'msg' => '',
        );

        $year = empty($user['byear']) ? 0 : $user['byear'];
        $month = empty($user['bmonth']) ? 0 : $user['bmonth'];
        $day = empty($user['bday']) ? 0 : $user['bday'];


        //精确到天判断
        if ($year > 0) {
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $day = str_pad($day, 2, '0', STR_PAD_LEFT);

            $age_min = DealLoadEnum::BID_AGE_MIN;
            $now_ymd = \libs\utils\XDateTime::now('Ymd')->getDate();
            if (intval(($year + $age_min) . $month . $day) > $now_ymd) {
                $res_err = array(
                    'error' => true,
                    'msg' => sprintf("本项目仅限%d岁及以上用户投资", $age_min),
                );
            }
        }
        return $res_err;
    }

    /**
     * 根据source type和deal crowd判定是否能够投标
     * @param int $source_type 来源类型 0前台正常投标 1后台预约投标 3 ios 4 Android
     * @param int $deal_crowd 投资人群 0全部用户 1 新手专享 2 专享 4 手机专享 8 手机新手专享
     * @param array $user 当前投资用户实体
     */
    public function allowedBidBySourceType($source_type, $deal_crowd, $user)
    {
        // default allowed
        $res_arr = array(
            'error' => false,
            'msg' => '',
            'is_app_first_loan' => false,
            'is_first_loan' => false,
        );
        $user_id = $user['id'];
        $source_type = intval($source_type);
        $deal_crowd = intval($deal_crowd);

        //如果限制app端标,来源是pc
        if (in_array($deal_crowd, array(DealEnum::DEAL_CROWD_MOBILE, DealEnum::DEAL_CROWD_MOBILE_NEW)) && $source_type == DealLoadModel::$SOURCE_TYPE['general']) {
            $res_arr['error'] = true;
            $res_arr['msg'] = '请打开手机客户端进行投资。';
        } elseif ($deal_crowd == DealEnum::DEAL_CROWD_MOBILE_NEW) {  //如果手机新手标
            $dl = new DealLoadModel();
            $app_load_cnt = $dl->getCountByUserIdInSuccess($user_id, array(DealLoadModel::$SOURCE_TYPE['ios'], DealLoadModel::$SOURCE_TYPE['android']));
            if ($app_load_cnt > 0) {
                $res_arr['error'] = true;
                $res_arr['msg'] = '该项目为新手专享项目，只有初次出借的新用户可以出借';
            }
        }

        return $res_arr;
    }

    /**
     * 根据标的ID,获取还款人id
     * @author <wangjiantong@ucfgroup.com>
     * @param int $dealId 0:借款人,1:代垫机构
     * @param int $type 0:借款人,1:代垫机构 默认代垫
     * @return int accountUserId; 还款用户id
     */

    public function getRepayUserAccount($dealId, $type = 1)
    {

        $deal = DealModel::instance()->find(intval($dealId));
        if ($type == DealRepayEnum::DEAL_REPAY_TYPE_SELF) {
            return $deal['user_id'];
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN) {//代垫-代垫机构
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['advance_agency_id']));
            if ($dealAgency) {
                return $dealAgency['user_id'];
            }
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG) {//代偿 担保机构代偿
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['agency_id']));
            if ($dealAgency) {
                return $dealAgency['user_id'];
            }
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI) {//代充值
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['generation_recharge_id']));
            if ($dealAgency) {
                return $dealAgency['user_id'];
            }
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU) {//代扣
            return $deal['user_id'];
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) { //间接代偿 担保机构代偿
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['agency_id']));
            if ($dealAgency) {
                return $dealAgency['user_id'];
            }
        }else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_PART_SELF) {
            return $deal['user_id'];
        } else if ($type == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) { //提前结清
            return $deal['user_id'];
        }
        return false;
    }

    public function getRepayAccountType($repayAccountType){
        if($repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN){
            return UserAccountEnum::ACCOUNT_REPLACEPAY;
        }elseif($repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG){
            return UserAccountEnum::ACCOUNT_GUARANTEE;
        }elseif($repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI){
            return UserAccountEnum::ACCOUNT_RECHARGE;
        }elseif($repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU){
            return UserAccountEnum::ACCOUNT_FINANCE;
        }elseif($repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
            return UserAccountEnum::ACCOUNT_GUARANTEE;
        }else{
            return UserAccountEnum::ACCOUNT_FINANCE;
        }
    }

    /**
     * 判断是否是多投宝标的
     * @param int
     */
    public function isDealDT($deal_id)
    {
        $tag_service = new DealTagService();
        $tags = $tag_service->getTagListByDealId($deal_id);
        return in_array(DtDealService::TAG_DT, $tags);
    }

    /**
     * @param $uid
     * @param $status
     * @param $limit array(0,5);
     * @param int | string $deal_type
     * @return array
     */
    public function getListByUid($uid,$status,$limits, $deal_type = false){
        $rs = DealModel::instance()->getListByUid($uid,$status,$limits, $deal_type);
        return $rs;
    }

    /**
     * getManualColumnsVal
     * 获取选定列信息
     *
     * @param mixed $dealId
     * @param mixed $columnsStr
     * @access public
     * @return void
     */
    public function getManualColumnsVal($dealId, $columnsStr)
    {
        $model = new DealModel();
        $dealInfo = $model->find($dealId, $columnsStr, true);
        return $dealInfo;
    }

    /**
     * 根据投资信息获取投资应获收益
     * @param $deal_loan_info array
     * return float
     */
    public function getExpectEarningByDealLoan($deal_loan_info)
    {
        $deal_dao = new DealModel();
        $deal = $deal_dao->findViaSlave($deal_loan_info['deal_id']);
        if ($deal['deal_status'] == DealEnum::DEAL_STATUS_REPAY || $deal['deal_status'] == DealEnum::DEAL_STATUS_REPAID) {
            $deal_loan_repay = new DealLoanRepayModel();
            if ($deal['deal_type'] == 0 || $deal['deal_type'] == 2 || $deal['deal_type'] == 3) {
                return $deal_loan_repay->getTotalMoneyByTypeLoanId($deal_loan_info['id'], DealLoanRepayEnum::MONEY_INTREST, 0);
            } else {
                return $deal_loan_repay->getTotalMoneyByTypeLoanId($deal_loan_info['id'], DealLoanRepayEnum::MONEY_COMPOUND_INTEREST, 0);
            }
        } else {
            $earning = $deal->getEarningMoney($deal_loan_info['money']);
            return $deal_dao->floorfix($earning, 2);
        }
    }

    /**
     * 获取订单详情
     */
    public function getDeal($id, $read_only = false, $hand_deal = true, $is_array = false)
    {
        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_BXT);
        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_DTB);
        $ndTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_NDD);
        $deal_model = new DealModel();
        // 已还清的从备份库读
        if ($read_only === true) {
            $deal = $deal_model->getDealInfoViaSlave($id);
        } else {
            $deal = $deal_model->getDealInfo($id);
        }
        if (empty($deal)) {
            return false;
        }
        // 转化为数组才能使用http请求传输数据
        if ($is_array) {
            $deal = $deal->getRow();
        }
        //获取产品类别的标识
        $deal['type_tag'] = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);

        if ($hand_deal) {
            if ($read_only === false) {
                $deal_model->is_slave = 0;
            }
            $deal = $deal_model->handleDeal($deal, 0, false);
        }
        $deal['isBxt'] = 0;
        if ($deal['type_id'] == $bxtTypeId) {
            $deal['isBxt'] = 1;
        }
        $deal['isDtb'] = 0;
        if ($this->isDealDT($id)) {
            $deal['isDtb'] = 1;
        }
        $deal['isNd'] = 0;
        if ($deal['type_id'] == $ndTypeId) {
            $deal['isNd'] = 1;
        }
        $i_tag = 0;
        if (!empty($deal['deal_tag_name'])) {
            $deal_tag_name = explode(',', $deal['deal_tag_name']);
            foreach ($deal_tag_name as $tag) {
                if ($i_tag) {
                    $deal['deal_tag_name' . $i_tag] = $tag;
                } else {
                    $deal['deal_tag_name'] = $tag;
                }
                $i_tag++;
            }
        }
        return $deal;
    }


    /**
     * 判断标的是否在指定的资产类型里面
     * @param int $dealId 标的ID
     * @param array $dealLoanTypeList tagType数组
     */
    public function isDealOfDealTypeList($dealId, $dealLoanTypeList, $dealInfo = [])
    {
        if (empty($dealInfo)) {
            $dealInfo = DealModel::instance()->find($dealId, 'type_id', true);
        }
        if (empty($dealInfo) || (int)$dealInfo['type_id'] <= 0 || empty($dealLoanTypeList)) {
            return false;
        }
        // 通过tag数组，批量获取typeId
        $dealTypeIds = DealLoanTypeModel::instance()->getIdListByTag($dealLoanTypeList);
        return !empty($dealTypeIds) ? in_array((int)$dealInfo['type_id'], $dealTypeIds) : false;
    }

    /**
     * 投资首投回调
     * @param $userId
     * @param $money
     * @param $couponId
     * @param $dealLoadId
     * @param bool $isRedeem
     * @param int $siteId
     * @return bool
     */
    public function dealEvent($userId, $money, $couponId, $dealLoadId, $isRedeem = false, $siteId = 1) {

        try {
            $firstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
            if ($dealLoadId > 0 && $firstDeal['id'] == $dealLoadId) {
                // 首投回调
                $digObject = new \core\service\DigService('firstLoan', array(
                    'id' => $userId,
                    'cn' => $couponId,
                    'loadid' => $dealLoadId,
                    'isRedeem' => $isRedeem,
                    'money' => $money,
                    'siteId' => $siteId,
                ));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 处理流标逻辑
     * @param int $deal_id
     */
    public function failDeal($deal_id) {
        $dealModel = new DealModel();
        $deal = $dealModel->find($deal_id);
        $state_manager = new \core\service\deal\state\StateManager($deal);
        $state_manager->setDeal($deal);

        $rs = $state_manager->work();

        return $rs;
    }

    /**
     * 获取订单的用户企业信息
     *
     * @param $deal 订单信息
     * @return array
     */
    public function getDealUserCompanyInfo($deal) {
        $data = array();
        $data['user_id'] = $deal['user_id'];
        $data['is_company'] = 0;

        //个人借款
        $user_info = UserService::getUserById($deal['user_id'], "user_name,real_name,idno,address,mobile,email");
        if ($user_info) {
            $data['show_name'] = $user_info['user_name'];
            //合同变量
            $data['borrow_real_name'] = $user_info['real_name']; //真实姓名
            $data['borrow_user_name'] = $user_info['user_name']; //用户名
            $data['borrow_user_idno'] = $user_info['idno']; //身份证
            $data['borrow_address'] = $user_info['address']; //地址
            $data['borrow_mobile'] = $user_info['mobile']; //手机
            $data['borrow_postcode'] = $user_info['email']; //邮箱 （历史错误）
            $data['borrow_email'] = $user_info['email']; //邮箱
            $data['real_name'] = $user_info['real_name']; //邮
        }

        /*
         *公司借款 合同服务相关
         */
        if(is_numeric($deal['contract_tpl_type'])){
            $tpl_info = CategoryService::getCategoryById($deal['contract_tpl_type']);
            $tpl_info['contract_type'] = $tpl_info['contractType'];
        }else{
            $tpl_info = $deal['contract_tpl_type'] ? CategoryService::getCategoryLikeTypeTag($deal['contract_tpl_type']) : array();
        }
        $data['company_description_html'] = '';
        if (!empty($tpl_info) && $tpl_info['contract_type'] == ContractServiceEnum::TYPE_COMPANY) {
            $company_info = UserService::getUserCompanyInfo($deal['user_id']);
            $data['is_company'] = 1;
            $data['show_name'] = isset($company_info['name']) ? $company_info['name'] : '';
            $data['company_name'] = isset($company_info['name']) ? $company_info['name'] : ''; //名称
            $data['company_address'] = isset($company_info['address']) ? $company_info['address'] : ''; //注册地址
            $data['company_legal_person'] = isset($company_info['legal_person']) ? $company_info['legal_person'] : ''; //法定代表人
            $data['company_tel'] = isset($company_info['tel']) ? $company_info['tel'] : ''; //联系电话
            $data['company_license'] = isset($company_info['license']) ? $company_info['license'] : ''; //营业执照号
            $data['company_description'] = isset($company_info['description']) ? $company_info['description'] : ''; //简介
            $data['company_address_current'] = isset($company_info['domicile']) ? $company_info['domicile'] : ''; //借款公司住所地
            $company_info['is_html'] = isset($company_info['is_html']) ? intval($company_info['is_html']) : 0;
            $tempDes = $data['company_description'];
            if($company_info['is_html'] === 0) { //数据处理
               $tempDes = str_replace("\n", "<br/>", $data['company_description']);
            }
            $data['company_description_html'] = $tempDes;
        }
        return $data;
    }
    /**
     * 获取首页展示的投资列表
     * edit by wangyiming 20160217 pm:heping 首页仅显示全部的标的，并去掉count数字
     * 20160906 增加专享标列表 by quanhengzhuang
     */
    public function getIndexList()
    {
        //普通标
        $p2pCount = app_conf('WEB_INDEX_P2P_COUNT');
        if ($p2pCount <= 0) {
            $p2pCount = self::P2P_INDEX_COUNT;
        }
        $option = array();
        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P;


        $deals = DealModel::instance()->getListV2(null, false, 1, $p2pCount, FALSE, TRUE, 0, $option, false, false, false, false);
        $result['list'] = $this->handleDealForList($deals['list']);

        //专享标
        $zxCount = app_conf('WEB_INDEX_ZX_COUNT');
        if ($zxCount <= 0) {
            $zxCount = self::ZX_INDEX_COUNT;
        }

        $option['deal_type'] = DealEnum::DEAL_TYPE_EXCLUSIVE . "," . DealEnum::DEAL_TYPE_EXCHANGE;
        $deals = DealModel::instance()->getListV2(null, false, 1, self::ZX_INDEX_COUNT_MAX, FALSE, TRUE, 0, $option, false, false, false, false);

        //超过配置数量的，只显示进行中的标的
        foreach ($deals['list'] as $key => $item) {
            if ($key >= $zxCount && $item['deal_status'] != 1) {
                unset($deals['list'][$key]);
            }
        }

        $result['zx_list'] = $this->handleDealForList($deals['list']);

        //小贷标
        $pettyLoanCount = app_conf('WEB_INDEX_PETTY_LOAN_COUNT');
        if ($pettyLoanCount <= 0) {
            $pettyLoanCount = self::PETTY_LOAN_INDEX_COUNT;
        }
        $option['deal_type'] = DealEnum::DEAL_TYPE_PETTYLOAN;
        $deals = DealModel::instance()->getListV2(null, false, 1, $pettyLoanCount, FALSE, TRUE, 0, $option, false, false, false, false);
        $result['petty_loan_list'] = $this->handleDealForList($deals['list']);

        //分开查是因为列表需要单独分页。故分页
        //$result['bxt_list'] = $this->getBXTList(1,3,false);

        return $result;
    }

    /**
     * 获取农担首页展示的投资列表
     *
     */
    public function getIndexListNdd()
    {
        //普通标
        $p2pCount = app_conf('WEB_INDEX_P2P_COUNT');
        if ($p2pCount <= 0) {
            $p2pCount = self::P2P_INDEX_COUNT;
        }
        $option = array();
        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P;

        $deals = DealModel::instance()->getList(null, false, 1, $p2pCount, FALSE, TRUE, 0, $option, false, false, false, false);
        $result['list'] = $this->handleDealForList($deals['list']);

        return $result;
    }

    /**
     * 对标的列表进行批量handleDeal
     */
    public function handleDealForList($list, $data_type=1)
    {
        $deal_list = array();

        if ($list) {
            foreach ($list as $key => $deal) {
                $list[$key] = DealModel::instance()->handleDealNew($deal, $data_type);
            }
            $deal_list['list'] = $list;
        } else {
            $deal_list['list'] = array();
        }

        return $deal_list;
    }

    /**
     * 获取标的列表，供列表页使用，功能是一样的，但是由于getList已经有别处使用，所以新加一个方法
     * @param int $page
     * @param int $page_size
     * @param bool $is_all_site
     * @param int $site_id
     */
    public function getDealsList($type_id, $page, $page_size=0, $is_all_site=false, $site_id=0,$option=array(),$is_real_site=false) {
        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;

        $deals = DealModel::instance()->getList($type_id, null, $page, $page_size, $is_all_site, true, $site_id,$option, $is_real_site, false, false, false);

        $result['list'] = $this->handleDealForList($deals['list'], 0);
        $result['page_size'] = $page_size;
        $result['count'] = $deals['count'];
        return $result;
    }

    /**
     * 修改首页 列表页面 缓存引起标的状态有问题的情况
     * @param $list
     * @return mixed
     */
    public function UserDealStatusSwitch($list){
        foreach($list as $k=>$v){
            $list[$k] = DealModel::instance()->UserDealStatusSwitch($v);
        }
        return $list;
    }

    /**
     *对dealId进行加密
     * @param $list
     * @return mixed
     */
    public function EncryptDealIds($list){
        foreach($list as $k=>$v){
            $list[$k]['url'] = Url::gene("d", "", Aes::encryptForDeal($v['id']), true);
            $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
        }
        return $list;
    }

    /**
     * 判断是否是农旦标
     */
    public function isDealND($deal_id){
        $deal= DealModel::instance()->find(intval($deal_id), 'type_id', true);
        $ndTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_NDD);
        return ($ndTypeId == $deal->type_id) ? true : false;
    }

    /**
     * 判断是否是农旦标
     */
    public function isPartRepayDealND($deal_id,$repayType){
        $deal= DealModel::instance()->find(intval($deal_id), 'type_id', true);
        $ndTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_NDD);
        if(($ndTypeId == $deal->type_id) && ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_PART_SELF)) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否是智多鑫三期清盘的标的
     * @param int
     */
    public function isDealDTV3($deal_id) {
        $tag_service = new DealTagService();
        $tags = $tag_service->getTagListByDealId($deal_id);
        return in_array(DtDealService::TAG_DT_V3, $tags);
    }
    /**
     *  更新vip经验值 获取投资年化额(除通知贷),投资券确认页面用
     */
    public static function getAnnualizedAmountByDealIdAndAmount($dealId, $amount) {
        $dealModel = DealModel::instance()->findViaSlave($dealId);
        if (empty($dealModel)) {
            Logger::error(__CLASS__.' '.__FUNCTION__.' get fail '.$dealId.' '.$amount);
            return 0;
        }
        $finance = new Finance();
        // 计算年化额
        $moneyYear = $finance->getMoneyYearPeriod($amount, $dealModel->loantype, $dealModel->repay_time);
        $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
        $annualizedAmount = round(bcmul($moneyYear , $rebateRate, 2), 2);
        return $annualizedAmount;
    }

    /**
     * 判断手续费收费为固定收取类型
     * @param int $loan_fee_rate_type
     */
    static public function isDealFeeRateTypeFixed($loan_fee_rate_type)
    {
        return in_array($loan_fee_rate_type, array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD));
    }

    /**
     *  根据deal_type判断标的是否为专享
     */
    public function isDealEx($deal_type)
    {
        return (DealEnum::DEAL_TYPE_EXCLUSIVE == $deal_type) ? true : false;
    }

    /**
     * @根据信贷审批单号查询标
     * @param  string $approveNumber
     * @return bool
     */
    public function getDealByApproveNumber($approveNumber, $fields = "*")
    {
        $dealModel = new DealModel();
        return $dealModel->findBy("approve_number = '" . $dealModel->escape($approveNumber) . "'", $fields, array(), true);
    }

    /**
     * getExtManualColumnsVal
     * 获取选定列信息
     * @param mixed $dealId
     * @param mixed $columnsStr
     * @access public
     * @return void
     */
    public function getExtManualColumnsVal($dealId, $columnsStr)
    {
        $model = new DealExtModel();
        $extInfo = $model->findByViaSlave('deal_id='.$dealId, $columnsStr);
        return $extInfo;
    }

    /**
     * 标的还款试算
     * @param $deal DealModel 对象
     * @param $dealRepayId dealRepay表对应的还款ID
     * @param $repayDay YYYY-mm-dd
     * @param $repayType 1--提前 2--正常
     * @return array
     * @throws \Exception
     */
    public function dealRepayTrial($deal,$dealRepayId,$repayDay,$repayType=false,$isForceTrial=false){
        if(!$deal instanceof DealModel){
            throw new \Exception("参数类型不正确 deal需要DealModel对象");
        }
        if($deal->deal_status != DealEnum::$DEAL_STATUS['repaying']){
            throw new \Exception("标的状态需要还款中才能发起试算");
        }
        if($isForceTrial === false && $deal->is_during_repay == DealEnum::DEAL_DURING_REPAY){
            throw new \Exception("标的状正在还款不能发起试算");
        }

        $dealRepayModel = DealRepayModel::instance()->find($dealRepayId);
        if(!$dealRepayModel){
            throw new \Exception("还款信息不存在");
        }

        if($dealRepayModel->status != DealRepayEnum::STATUS_WAITING){
            throw new \Exception("该期还款已完成不能进行试算");
        }

        $planRepayTime = strtotime(to_date($dealRepayModel->repay_time,'Y-m-d')); // 预计还款时间
        $repayTime = strtotime($repayDay); // 实际还款时间

        $imposeMoney = 0; // 预期罚息费用
        if($repayType == 1 || ($planRepayTime > $repayTime && $repayType == false)){ // 提前还款
            $dealExt = DealExtModel::instance()->findByViaSlave('deal_id='.$deal['id']);
            $type = 1;
            $ps = new DealPrepayService();
            $ps->setDeal($deal);
            $psRes = $ps->prepayCalc($repayDay,true);

            $prepayCompensation = $psRes['prepay_compensation'];
            $repayPrincipal = $psRes['remain_principal'];
            $repayInterest = $psRes['prepay_interest'];
            $loanFee = $psRes['loan_fee'];
            $consultFee = $psRes['consult_fee'];
            $guaranteeFee = $psRes['guarantee_fee'];
            $payFee = $psRes['pay_fee'];
            $managentFee = $psRes['management_fee'];
            $interestDays = $psRes['remain_days'];
        }else{ // 正常还款
            $dps = new DealRepayService();
            $lastInterestTime =  $dps->getMaxRepayTimeByDeal($deal);
            // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
            $lastInterestTime = to_timespan(to_date($lastInterestTime,'Y-m-d')); // 转换为零点开始
            $endInterestTime = to_timespan($repayDay); // 计息结束日期
            $interestDays = ceil(($endInterestTime - $lastInterestTime)/86400); // 利息天数

            $type = 2;
            $prepayCompensation = 0;
            $repayPrincipal = $dealRepayModel->principal;
            $repayInterest = $dealRepayModel->interest;
            $loanFee = $dealRepayModel->loan_fee;
            $consultFee = $dealRepayModel->consult_fee;
            $guaranteeFee = $dealRepayModel->guarantee_fee;
            $payFee = $dealRepayModel->pay_fee;
            $managentFee = $dealRepayModel->management_fee;
        }

        $data = array(
            "deal_id" => $deal['id'],
            "type" => $type, // 1正常还款 2提前还款
            "repay_start_time" => to_date($deal->repay_start_time,'Y-m-d'), // 放款时间
            "interest_days" => $interestDays, // 利息天数
            "repay_principal" => $repayPrincipal,// 还款本金
            "repay_interest" => $repayInterest,// 还款利息
            "impose_money" => $imposeMoney, //预期罚息
            "prepay_compensation" => $prepayCompensation, // 提前还款违约金
            "loan_fee" => $loanFee,// 手续费
            "consult_fee" => $consultFee,//咨询费
            "guarantee_fee" => $guaranteeFee,//担保费,
            "pay_fee" => $payFee,// 支付服务费
            "management_fee" => $managentFee,
            "total_repay" => $repayPrincipal + $repayInterest + $loanFee + $consultFee + $guaranteeFee + $payFee + $managentFee + $prepayCompensation + $imposeMoney,
        );
        logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "标的还款试算" ,json_encode($data))));
        return $data;
    }


    /**
     * 根据标的id 获取项目信息
     * @param int $deal_id
     * @return array
     */
    static public function getProjectInfoByDealId($deal_id)
    {
        $deal_model = new DealModel();
        $deal_obj = $deal_model-> getDealInfo ($deal_id);
        if (empty($deal_obj)) {
            return array();
        } else {
            $project_obj = DealProjectModel::instance()->findViaSlave($deal_obj->project_id);
            return empty($project_obj) ? array() : $project_obj->getRow();
        }
    }

    /**
     * 根据状态类型、时间区间和和审批单号获得标的列表
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
        $result = DealModel::instance()->getDealListByStatusTypeTime($type, $status, $start_time, $end_time, $approve_number, $fields, $page_num, $page_size);
        return $result;
    }


    /**
     * getNoticeRepay
     * 查询出所有的指定天数之内需要还款的标
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-14
     * @access public
     * @return array
     */
    public function getNoticeRepay(){
        $warn_day_end = intval(app_conf('REPAY_WARN_DAY'));//还款提醒天数
        $warn_day_end = $warn_day_end >= 0 ? $warn_day_end : 10;
        $warn_day_start = $warn_day_end - 1;
        $result = DealModel::instance()->getNoticeRepay($warn_day_start,$warn_day_end);
        return $result;
    }

    /**
     * searchDealById
     * 根据标ID获取标详情
     *
     * @param mixed $dealId
     * @access public
     * @return void
     */
    public function searchDealById($dealId)
    {
        $deals = DealModel::instance()->searchDealById($dealId);
        $result = array();
        foreach ($deals['list'] as $k => $v) {
            $result[$k] = DealModel::instance()->handleDeal($v);
        }
        return $result;
    }

    /**
     * 为券的可用标的列表 提供标的数据 只取p2p
     * 增加tagName属性
     *
     * @date 2018.11.23
     * @author sunxuefeng@ucfgroup.com
     */
    public function getDealsListForDiscount($user, $sourceType, $pageNum = 1, $pageSize = 100) {
        if (empty($user)) {
            Logger::error('DealService.getDealsListForDiscount userInfo is empty');
            return array();
        }
        if (empty($sourceType)) {
            Logger::error('DealService.getDealsListForDiscount sourceType is empty');
            return array();
        }

        $deals = DealModel::instance()->getDealListForProcessing(false, $pageNum, $pageSize);
        $dealList = array();
        if (!empty($deals)) {
            $tagService = new DealTagService();
            $dealLoadService = new DealLoadService();

            foreach ($deals as $deal) {
                $deal = DealModel::instance()->handleDealForDiscount($deal);
                $deal['tag'] = $tagService->getTagByDealId($deal['id']);
                $deal['category'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($deal['type_id']);

                // 定制标过滤命中
                if (!$dealLoadService->canUseDeal($deal, $user, $sourceType)) {
                    // 加上name方便调试
                    Logger::info("DealService.canUseDeal 定制标 dealId:{$deal['id']}, name:{$deal['name']}, deal_crowd:{$deal['deal_crowd']}");
                    continue;
                }
                // 月份转天
                $deal['repayTime'] = $deal['loantype'] != self::LOAN_TYPE_MONTH ? $deal['repay_time'] * 30 : $deal['repay_time'];
                $deal['consumeType'] = CouponGroupEnum::CONSUME_TYPE_P2P;

                $dealList[] = $deal;
            }

        }
        return $dealList;
    }
     /**
     * getDingzhiByUid
     * 获取首页新手标
     *
     * @access public
     * @return array
     */
    public function getIndexNewUserList(){

        $deals = DealModel::instance()->getIndexNewUserList();
        $list = $deals['list'];
        foreach ($list as $key => $deal) {
           $list[$key] = DealModel::instance()->handleDealNew($deal, 1);
        }
        $data['list'] = $list;
        $result['list'] = $data;

        return $result;
    }

    public function getZoneList()
    {
        $siteId = formatConf(app_conf('DEAL_SITE_ALLOW'));
        if ($siteId == '') {
            return [];
        }
        $sql = "SELECT * FROM `firstp2p_deal` WHERE `deal_status` = 1 AND `is_delete` = 0 AND `is_effect` = 1 AND site_id IN($siteId) LIMIT 200";
        return DealModel::instance()->findAllBySqlViaSlave($sql, true);
    }

    /**
     * 是否是提现后收费标的
     * @param $deal_id
     * @return bool
     */
    public function isAfterGrantFee($deal_id)
    {
        $deal = DealModel::instance()->find(intval($deal_id), 'type_id', true);
        $dealType = DealLoanTypeModel::instance()->find($deal->type_id);
        //放款后收费未打钩
        if ($dealType->after_fee != 1) {
            return false;
        }
        //所有费用必须前收
        $dealExtData = DealExtModel::instance()->getInfoByDeal(intval($deal_id), false);
        if ($dealExtData['loan_fee_rate_type'] == DealExtEnum::FEE_RATE_TYPE_BEFORE //手续费
            && $dealExtData['consult_fee_rate_type'] == DealExtEnum::FEE_RATE_TYPE_BEFORE //咨询费
            && $dealExtData['guarantee_fee_rate_type'] == DealExtEnum::FEE_RATE_TYPE_BEFORE //担保费
            && $dealExtData['pay_fee_rate_type'] == DealExtEnum::FEE_RATE_TYPE_BEFORE //支付费
        ) {
            return true;
        }
        return false;
    }

    /*
    *根据标id获取产品名称
    */
    public function getProductNameByDealId($dealId){
        $deal = DealModel::instance()->find(intval($dealId),"project_id", true);
        if(empty($deal)){
            throw new \Exception("标信息不存在");
        }
        $project = DealProjectModel::instance()->find(intval($deal['project_id']),"product_mix_3",true);
        return empty($project) ? '' : $project['product_mix_3'];
    }
}
