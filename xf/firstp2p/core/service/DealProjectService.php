<?php
/**
 * DealProjectService.php
 * @author wenyanlei@ucfgroup.com
 */
namespace core\service;

use core\dao\DealAgencyModel;
use core\dao\DealProjectModel;
use core\dao\DealProjectCompoundModel;
use core\dao\DealInfoModel;
use core\dao\PlatformManagementModel;
use core\dao\ProductManagementModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\DealRepayModel;
use core\dao\UserModel;
use core\service\DealProjectCompoundService;
use libs\utils\Logger;
use core\service\DealProjectRiskAssessmentService;


class DealProjectService extends BaseService{

    const DEAL_TYPE_LGL = 1;

    /**
     * 查询项目信息
     * @param $id
     */
    public function getProInfo($id, $deal_id = 0){
        $pro = DealProjectModel::instance()->findViaSlave($id);
        $pro['intro_html'] = '';

        // 标的活动简介
        if (!empty($deal_id)) {
            $deal_intro = DealInfoModel::getDealActivityIntroductionByDealId($deal_id);
            $pro['intro_html'] .= $deal_intro;
        }

        if($pro && $pro['intro']){
            $pro['intro_html'] .= str_replace("\n", "<br/>", $pro['intro']);
        }
        if (!empty($pro['risk_bearing'])){
            $pro_risk_service = new DealProjectRiskAssessmentService();
            $pro['risk'] =  $pro_risk_service->getAssesmentNameById($pro['risk_bearing']);

        }
        if ($pro['deal_type'] == self::DEAL_TYPE_LGL) {

            $m_dc = new DealProjectCompoundModel();
            $row = $m_dc->getInfoByProId($id);
            if ($row !== false) {
                //已赎回本金 这儿可能会查询比较多
                $pro['redeemed_principal'] = DealProjectCompoundService::getPayedProjectCompoundPrincipal($row['id']);
                // 赎回中本金
                $pro['redeeming_principal'] = DealProjectCompoundService::getUnpayedCompoundPrincipal($row['id']);
                // 已赎回利息
                $pro['redeemed_interest'] = DealProjectCompoundService::getPayedCompoundInterest($row['id']);
                // 赎回中利息
                $pro['redeeming_interest'] = DealProjectCompoundService::getPayedCompoundInterest($row['id']);

                $pro['lock_period'] = $row['lock_period'];
                $pro['redemption_period'] = $row['redemption_period'];
            }

        }
        return $pro;
    }

    /**
     * @根据信贷审批单号查询项目
     * @param  string $approveNumber
     * @return bool
     */
    public function getDealProjectByApproveNumber($approveNumber, $fields = "*")
    {
        $dealProjectModel = new DealProjectModel();
        return $dealProjectModel->findBy("`approve_number` = '" . $dealProjectModel->escape($approveNumber) . "'", $fields, array(), true);
    }
    /**
     * 获取项目的风险承受能力
     * @param $id
     */
    public function getProRisk($id){
        if (!is_numeric($id)){
            return false;
        }
        $pro = DealProjectModel::instance()->findViaSlave($id,'risk_bearing');
        $pro['risk'] = [
            'name' => '',
            'describe' => '',
        ];
        if (!empty($pro['risk_bearing'])){
            $pro_risk_service = new DealProjectRiskAssessmentService();
            $pro['risk'] =  $pro_risk_service->getAssesmentNameById($pro['risk_bearing']);
        }

        return $pro;
    }

    /**
     * 更新项目的已上标金额
     * @param $id
     * @return float||bool
     */
    public function updateProBorrowed($id){
        $project = DealProjectModel::instance()->find($id);
        if($project){
            $money_borrowed = DealProjectModel::instance()->getProBorrowed($id);
            $project->money_borrowed = $money_borrowed;
            if($project->save()){
                return $money_borrowed;
            }
        }
        return false;
    }

    /**
     * 更新项目的已投资金额
     * @param $id
     * @return float||bool
     */
    public function updateProLoaned($id){
        $project = DealProjectModel::instance()->find($id);
        if($project){
            $money_loaned = DealProjectModel::instance()->getProLoaned($id);
            $project->money_loaned = $money_loaned;
            if($project->save()){
                return $money_loaned;
            }
        }
        return false;
    }

    /**
     * @param $deal_project_id
     * @return bool
     */
    public function copyDealProject($deal_project_id) {
        $deal_project_id = intval($deal_project_id);
        if ($deal_project_id <= 0) {
            return false;
        }

        $obj = DealProjectModel::instance()->find($deal_project_id);
        $row = $obj->getRow();

        if (!$row) {
            return false;
        }

        $GLOBALS['db']->startTrans();

        try {
            $dp_model = new DealProjectModel();
            unset($row['id']);
            $dp_model->setRow($row);
            $dp_model->name = $dp_model->name . "[复制]";
            $dp_model->money_borrowed = 0;
            $dp_model->money_loaned = 0;
            $dp_model->business_status = DealProjectModel::$PROJECT_BUSINESS_STATUS['waitting'];
            if ($dp_model->save() === false) {
                throw new \Exception("copy project fail");
            }
            $id = $dp_model->id;

            // 通知贷需要复制project_compound表
            if ($row['deal_type'] == 1) {
                $dpc_info = DealProjectCompoundModel::instance()->getInfoByProId($deal_project_id);
                $dpc_model = new DealProjectCompoundModel();
                $dpc_model->project_id = $id;
                $dpc_model->lock_period = $dpc_info['lock_period'];
                $dpc_model->redemption_period = $dpc_info['redemption_period'];
                if ($dpc_model->save() === false) {
                    throw new \Exception("copy project compound fail");
                }
            }

            $deal = $dp_model->getFirstDealByProjectId($deal_project_id);
            if (!$deal) {
                throw new \Exception("project has no deal");
            }

            $deal_service = new \core\service\DealService();
            $rs = $deal_service->copyDeal($deal['id'], $id);
            if ($rs === false) {
                throw new \Exception("copy deal fail");
            }

            if ($this->updateProLoaned($id) === false) {
                throw new \Exception("update loaned fail");
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }

    /**
     * 后台交易平台用款预警使用
     * 获取项目对应咨询机构下，状态为“待确认”、“进行中”、“满标”的所有标的借款金额，以及状态为“还款中”的所有标的的未还金额、项目层未上标金额
     * @param
     * @return array
     */
    public function getPlatManagement()
    {
        //获取后台配置的平台咨询机构名称及用款限额
        $model = PlatformManagementModel::instance();
        $advisory_name = $model->getAllPlatformInfo(true,"advisory_id,money_limit,advisory_name");
        if (empty($advisory_name)) {
            return false;
        }
        //根据取得的咨询机构去查询产品已用用款限额
        $maxId = (DealProjectModel::instance()->getDealMaxid());
        $maxId['0']['max_id'] = $maxId['0']['max_id']+1;
        $loopId = 500000;
        $loopCount = bcdiv($maxId['0']['max_id'],$loopId);
        $modId = bcmod($maxId['0']['max_id'],$loopId);
        foreach ($advisory_name as $key => $value) {
            if ($loopCount > 0) {
                $loop = array();
                for ($i = 1 ;$i <= $loopCount;$i++) {
                    $loop[$i] = DealProjectModel::instance()->getPlatManagement($value['advisory_id'],$i*$loopId,($i-1)*$loopId);
                }
                $loop[$i] = $modId > 0 ? DealProjectModel::instance()->getPlatManagement($value['advisory_id'],($i-1)*$loopId + $modId,($i-1)*$loopId) : 0;
            } else {
                $loop['0'] = DealProjectModel::instance()->getPlatManagement($value['advisory_id'],$maxId['0']['max_id'],0);
            }
            $money = array_sum($loop);
            //获取预警提示级别0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
            $level = getWarningLevelByMoney($value['money_limit'], $money);
            //更新后台数据
            $plat_update = $model->updatePlatformInfoByCondition($money,$value['advisory_id'],$level);
            $res[$value['advisory_id']]['level'] = $plat_update ? $level : 0;
            $res[$value['advisory_id']]['advisory_name'] = $value['advisory_name'];
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_platform use_money :'.$money,'advisory_name:'.$value['advisory_name'])));
        }

        return $res;
    }
    /**
     * 后台交易平台用款预警使用
     * 获取项目对应产品名称下，状态为“待确认”、“进行中”、“满标”的所有标的借款金额，以及状态为“还款中”的所有标的的未还金额、项目层未上标金额
     * @param
     * @return array
     */
    public function getProductManagement()
    {
        //获取后台配置的产品名称及用款限额
        $model = ProductManagementModel::instance();
        $product_name = $model->getAllProductInfo(true,"product_name,product_id,money_limit");
        if (empty($product_name)) {
            return false;
        }
        //根据取得的产品名称去查询产品已用用款限额
        foreach ($product_name as $value) {
            $use_money = DealProjectModel::instance()->getProductManagement($value['product_name']);
            //获取预警提示级别0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
            $level = getWarningLevelByMoney($value['money_limit'], $use_money);
            //更新后台数据
            $update_res = $model->updateProductInfoByCondition($use_money,$value['product_name'],$level);
            $res[$value['product_id']]['use_money'] = $use_money;
            $res[$value['product_id']]['level'] = $update_res ? $level : 0;
            $res[$value['product_id']]['product_name'] = $value['product_name'];
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_platform use_money :'.$use_money,'product_name:'.$value['product_name'])));
        }
        return $res;
    }
    /**
     * 检查项目是否满标
     * @param int $project_id
     * @return boolean
     */
    public function isProjectFull($project_id)
    {
        $project = DealProjectModel::instance()->find($project_id, 'borrow_amount');
        $full_money = DealModel::instance()->getFullDealsMoneySumByProjectId($project_id);

        return 0 == bccomp($project['borrow_amount'], $full_money, 2);
    }

    /**
     * 是否是专享1.75的盈嘉项目
     * @param $project_id
     * @return bool
     */
    public function isProjectYJ175($project_id){
        $s = new DealProjectRepayYjService();
        $pids = $s->getYjProjectIds();
        if(!is_array($pids)){
            throw new \Exception("项目ID获取异常");
        }
        return in_array($project_id,$pids);

       // $project = DealProjectModel::instance()->find($project_id, 'deal_type,product_class');
       // return ($project['deal_type'] == 3 && $project['product_class'] == '盈嘉') ? true : false;
    }

    /**
     * 项目是否为受托专享 即：专享 1.75
     * @param int $project_id
     * @return boolean
     */
    public function isProjectEntrustZX($project_id)
    {
        $sql = sprintf(' SELECT `id` FROM %s WHERE `fixed_value_date` > 0 AND `deal_type` = %d AND `id` = %d ', DealProjectModel::instance()->tableName(), DealModel::DEAL_TYPE_EXCLUSIVE, $project_id);
        $project = DealProjectModel::instance()->findBySqlViaSlave($sql);

        return !empty($project);
    }

    /**
     * 处理项目取消放款逻辑
     * @param int $project_Id
     * @return boolean true: 流标任务添加成功
     */
    public function failProject($project_id)
    {
        try {
            if (empty($project_id)) {
                throw new \Exception('项目id 无效');
            }

            // 获取这个项目下所有正在进行中、及满标状态的标的
            $deal_list = DealModel::instance()->getDealByProId($project_id, array(DealModel::$DEAL_STATUS['progressing'], DealModel::$DEAL_STATUS['full']));
            if (empty($deal_list)) {
                throw new \Exception(sprintf('此项目下不存在可流标的标的，project_id: %d', $project_id));
            }

            $GLOBALS['db']->startTrans();

            // 项目进入取消放款状态
            if (!DealProjectModel::instance()->changeProjectStatus($project_id, DealProjectModel::$PROJECT_BUSINESS_STATUS['cancel_loan'])) {
                throw new \Exception(sprintf('项目业务状态更新失败，project_id: %d', $project_id));
            }

            foreach ($deal_list as $deal) {
                // 标的进入流标状态
                if (false === DealModel::instance()->changeDealIntoFailing($deal['id'], get_gmtime())) {
                    throw new \Exception(sprintf('项目-标的流标状态更新失败，project_id: %d', $project_id));
                }

                // add jobs 执行流标操作
                $function = '\core\service\DealService::failDeal';
                $param = array('deal_id' => $deal['id']);
                if (false === JobsModel::instance()->addJob($function, $param)) {
                    throw new \Exception(sprintf('项目-流标任务添加失败，project_id: %d', $project_id));
                }
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $e->getMessage(), 'line:'.__LINE__)));
            return false;
        }
    }

    /**
     * 根据借款人id 获取专享项目还款信息
     * @param  int $user_id
     * @param  int | boolen $business_status_arr
     * @param  int $limit_start
     * @param  int $limit_end
     * @return array [list => [], count => int]
     */
    public function getRepayEntrustProjectInfoByUserId($user_id, $business_status_arr = array(), $limit_start = 0, $limit_end = 0)
    {
        list($project_list, $project_count) = DealProjectModel::instance()->getEntrustProjectListByUserId($user_id, $business_status_arr);

        foreach ($project_list as $key => $project) {
            $first_deal = DealProjectModel::instance()->getFirstDealByProjectId($project['id']);
            $project['deal'] = DealModel::instance()->handleDealNew($first_deal);

            $deal_list = DealModel::instance()->getDealByProId($project['id'], array(), false);
            $project['true_month_repay_money'] = 0;
            $project['remain_repay_money'] = 0;
            foreach ($deal_list as $deal) {
                //还款计划相关的内容
                $deal_repay = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);
                if (!empty($deal_repay)) {
                    $project['true_month_repay_money'] = bcadd($deal_repay['repay_money'], $project['true_month_repay_money'], 2);
                }

                // 待还余额
                $project['remain_repay_money'] = bcadd($deal->remainRepayMoney(), $project['remain_repay_money'], 2);
            }

            $project_list[$key] = $project;
        }

        return array($project_list, $project_count);
    }
    /**
     * 第三级产品结构是否被应用
     * @param char $product_mix_3
     * @return int
     */
    public function isUselProductMix3($product_mix_3)
    {
        $sql = sprintf(' SELECT count(1) as count FROM %s WHERE `product_mix_3` = "%s" ', DealProjectModel::instance()->tableName(),$product_mix_3);
        $project = DealProjectModel::instance()->findBySqlViaSlave($sql);
        return $project['count'];
    }

    /**
     * 获取网信交易系统dealtype为“交易所”且产品大类为“盈益”且状态为“等待确认”且状态为“正常”的项目信息
     */
    public function getProjectInfo($startTime = '')
    {
        $startTime = $startTime ?: 0;
        $sql = sprintf("SELECT d.`project_id` as p_id, p.`name`, count(d.id) as count, d.`agency_id`, a.`name` as agency_name, d.`advisory_id`,ac.`name` as advisory_agency_name, p.`repay_time`, p.`borrow_amount`, p.`business_status` , p.`user_id`, u.`real_name`, d.`approve_number`, d.`loantype`
from %s d LEFT JOIN %s p ON d.`project_id` = p.`id` LEFT JOIN %s u ON p.user_id = u.id LEFT JOIN %s a ON a.`id` = d.`agency_id` LEFT JOIN %s ac ON ac.`id` = `advisory_id` WHERE d.`deal_status` = 0 AND d.`deal_type` = %d AND d.`is_delete` = 0 AND d.`create_time` > %d AND p.`product_class` = '%s' AND p.`status` = 0 
GROUP BY `project_id` HAVING count = (SELECT count(id) FROM %s WHERE `project_id` = `p_id`) ORDER BY count DESC,d.`update_time` DESC",
            DealModel::instance()->tableName(), DealProjectModel::instance()->tableName(), UserModel::instance()->tableName(), DealAgencyModel::instance()->tableName(), DealAgencyModel::instance()->tableName(), DealModel::DEAL_TYPE_EXCHANGE, intval($startTime), '盈益', DealModel::instance()->tableName());

        $projects = DealModel::instance()->findAllBySqlViaSlave($sql);

        if (empty($projects)) {
            return false;
        }
        $projectInfo = array();
        if (!empty($projects) && is_array($projects)) {
            foreach ($projects as $key => $value) {
                $temp['projectId'] = $value['p_id'];
                $temp['projectName'] = $value['name'];
                $temp['repayTime'] = $value['repay_time'];
                $temp['repayPeriodType'] = $value['loantype'] == 5 ? 1 : 2;
                $temp['borrowAmount'] = $value['borrow_amount'];
                $temp['status'] = $value['business_status'];
                $temp['realName'] = $value['real_name'];
                $temp['advisoryAgencyName'] = $value['advisory_agency_name'];
                $temp['agencyName'] = $value['agency_name'];
                $temp['approveNumber'] = $value['approve_number'];
                $projectInfo[] = $temp;
            }
        }
        return $projectInfo;
    }

}
