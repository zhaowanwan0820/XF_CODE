<?php
/**
 * 项目提前还款相关
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\DealPrepayModel;
use core\dao\DealProjectModel;
use core\dao\CouponDealModel;
use core\dao\UserModel;
use core\dao\JobsModel;

use core\service\DealPrepayService;
use core\service\DealService;
use core\service\CouponDealService;

use libs\utils\Finance;
use libs\utils\Logger;

class DealProjectPrepayService extends BaseService
{
    /**
     * 检查项目是否符合提前还款条件
     * @throw Exception
     */
    public function prepayCheckByProjectId($project_id)
    {
        $deal_list = DealModel::instance()->getDealByProId($project_id,array(4));
        foreach ($deal_list as $deal) {
            try{
                $ds = new DealPrepayService();
                $ds->prepayCheck($deal['id']);
            }catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * 计算项目提前还款各项费用
     * @throw Exception
     */
    public function prepayCalcProject($project_id, $end_day)
    {
        $deal_list = DealModel::instance()->getDealByProId($project_id,array(4));
        $deal_prepay_service = new DealPrepayService();
        $res_collection = array();
        foreach ($deal_list as $deal) {
            $res = $deal_prepay_service->prepayTryCalc($deal['id'], $end_day);
            if (empty($res_collection)) {
                $res_collection = $res;
            } else {
                $res_collection['prepay_money'] = Finance::addition(array($res['prepay_money'], $res_collection['prepay_money']));
                $res_collection['remain_principal'] = Finance::addition(array($res['remain_principal'], $res_collection['remain_principal']));
                $res_collection['prepay_interest'] = Finance::addition(array($res['prepay_interest'], $res_collection['prepay_interest']));
                $res_collection['prepay_compensation'] = Finance::addition(array($res['prepay_compensation'], $res_collection['prepay_compensation']));
                $res_collection['loan_fee'] = Finance::addition(array($res['loan_fee'], $res_collection['loan_fee']));
                $res_collection['consult_fee'] = Finance::addition(array($res['consult_fee'], $res_collection['consult_fee']));
                $res_collection['guarantee_fee'] = Finance::addition(array($res['guarantee_fee'], $res_collection['guarantee_fee']));
                $res_collection['pay_fee'] = Finance::addition(array($res['pay_fee'], $res_collection['pay_fee']));
                $res_collection['canal_fee'] = Finance::addition(array($res['canal_fee'], $res_collection['canal_fee']));
                $res_collection['pay_fee_remain'] = Finance::addition(array($res['pay_fee_remain'], $res_collection['pay_fee_remain']));
            }
        }

        return $res_collection;
    }

    /**
     * 执行项目提前还款
     * @param int $project_id
     * @param array $admInfo ['adm_id', 'adm_name']
     * @param boolean $is_borrower
     * @throw Exception
     */
    public function prepayPipelineProject($projectId, $admInfo, $is_borrower = false, $end_day = '')
    {
        try {
            $GLOBALS['db']->startTrans();

            // 借款人操作 需执行提前还款保存
            if ($is_borrower) {
                if (empty($end_day)) {
                    throw new \Exception('还款日期不能为空！');
                } else {
                    $this->saveProjectPrepayInfo($projectId, $end_day, 0);
                }
            }

            $dealService = new DealService();

            //验证标的状态
            $status = DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'];
            $dealType = DealModel::DEAL_TYPE_EXCLUSIVE;
            $condition = "id = $projectId  AND deal_type = $dealType AND fixed_value_date > 0 AND business_status = $status";
            $project = DealProjectModel::instance()->findByViaSlave($condition);

            if(empty($project)){
                throw new \Exception("项目不存在或项目状态异常!");
            }

            $deals = DealModel::instance()->getDealByProId($project['id'],array(4));

            if(count($deals) == 0){
                throw new \Exception("没有还款中状态的标的!");
            }
            $prepayModel = new DealPrepayModel();
            $prepayMoney = 0;
            $prepayIds = array();

            //对各标的进行还款操作
            foreach ($deals as $deal) {
                $prepayRecord = $prepayModel->findBy("deal_id=".$deal['id']." and status = 0");
                $prepayUserId = $dealService->getRepayUserAccount($deal['id'],$prepayRecord->repay_type);
                if(!$prepayUserId) {
                    throw new \Exception("还款用户ID获取失败");
                }

                // 标的优惠码设置信息
                $deal_coupon = CouponDealModel::instance()->findByViaSlave(sprintf(' deal_id = %d ', $deal['id']));
                if(!$deal_coupon) {
                    throw new \Exception("优惠码设置信息获取失败deal_id:{$deal['id']}");
                }
                // 优惠码结算时间为放款时结算：直接保存计算后得出的各项数据
                // 优惠码结算时间为还清时结算： 保存结算后的各项数据 并修改优惠码返利天数
                if($deal_coupon['pay_type'] == 1) {
                    $rebate_days = floor((get_gmtime() - $deal['repay_start_time'])/86400); // 优惠码返利天数=操作日期-放款日期

                    if($rebate_days < 0) {
                        throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebate_days);
                    }
                    // 更新优惠码返利天数
                    $coupon_deal_service = new CouponDealService();
                    $coupon_res = $coupon_deal_service->updateRebateDaysByDealId($deal['id'], $rebate_days);;
                    if(!$coupon_res){
                        throw new \Exception("更新标优惠码返利天数失败");
                    }


                }

                $dealRecord = DealModel::instance()->find($deal['id']);
                // 将标的置为还款中
                $res = $dealRecord->changeRepayStatus(DealModel::DURING_REPAY);
                if ($res == false) {
                    throw new \Exception("chage repay status error");
                }

                //设置项目还款状态为还款中
                if(false === DealProjectModel::instance()->changeProjectStatus($projectId,DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay'])){
                    throw new \Exception("变更项目正在还款状态失败");
                }

                // 自动审核提前还款
                $prepayRecord->status = 1;
                $prepayRecord->save();

                $prepayMoney += $prepayRecord['prepay_money'];
                $prepayIds[] = $prepayRecord->id;
            }


            $negativeIds = app_conf('PROJECT_REPAY_NEGATIVE');
            if($negativeIds){
                $negativeIds = explode(',',$negativeIds);
            }
            $canNegative = in_array($projectId,$negativeIds) ?  1 : 0;

            $user = UserModel::instance()->find($prepayUserId);
            $user->changeMoneyDealType = $deal['deal_type'];

            $bizToken = [
                'projectId' => $projectId,
            ];

            $res = $user->changeMoney($prepayMoney, "提前还款", $project['name'], $admInfo['adm_id'], 0, UserModel::TYPE_LOCK_MONEY,$canNegative, $bizToken);

            if(!$res) {
                throw new \Exception("用户提前还款资金冻结失败");
            }

            // 启动jobs进行还款操作
            $function  = '\core\service\DealPrepayService::projectPrepay';
            $param = array('projectId' => $projectId, 'status' => 1, 'success' => '成功' , 'admInfo' => $admInfo,'prepayUserId'=>$prepayUserId,'prepayIds'=>$prepayIds, 'isBorrowerSelf' => $is_borrower);

            $job_model = new JobsModel();
            $job_model->priority = 111;
            $job_model->addJob($function, array('param' => $param), false, 0);

            $GLOBALS['db']->commit();
            Logger::info(sprintf('提前还款操作成功，项目id：%d', $projectId));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(sprintf('提前还款操作失败，项目id：%d，错误信息：%s', $projectId, $e->getMessage()));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 保存项目下各标的的提前还款信息
     * @param int $project_id
     * @param string $end_day eg '2017-03-23'
     * @param int $repay_type 还款类型(0-普通 1-待垫)
     * @return boolean
     */
    public function saveProjectPrepayInfo($project_id, $end_day, $repay_type)
    {
        try{
            $deal_list = DealModel::instance()->getDealByProId($project_id,array(4));
            $deal_prepay_service = new DealPrepayService();
            $redis = \SiteApp::init()->dataCache->getRedisInstance();

            // 更新项目下的所有标的，如果有一个失败，则全部回滚
            $GLOBALS['db']->startTrans();
            if($redis){
                //事务开始，在redis中增加一个键值对
                $redis->set('admin_cache_action_deal_project_repay_save_prepay_start_transaction_'.$project_id, 1);
                Logger::info(__FILE__." | ".__LINE__." | redis set key. project_id:".$project_id);
            }
            foreach ($deal_list as $deal) {
                $calc_res = $deal_prepay_service->prepayTryCalc($deal['id'], $end_day);
                $data = array(
                    'deal_id'             => $deal['id'],
                    'user_id'             => $calc_res['user_id'],
                    'prepay_time'         => $calc_res['prepay_time'],
                    'remain_days'         => $calc_res['remain_days'],
                    'prepay_money'        => $calc_res['prepay_money'],
                    'remain_principal'    => $calc_res['remain_principal'],
                    'prepay_interest'     => $calc_res['prepay_interest'],
                    'prepay_compensation' => $calc_res['prepay_compensation'],
                    'loan_fee'            => $calc_res['loan_fee'],
                    'consult_fee'         => $calc_res['consult_fee'],
                    'guarantee_fee'       => $calc_res['guarantee_fee'],
                    'pay_fee'             => $calc_res['pay_fee'],
                    'canal_fee'           => $calc_res['canal_fee'],
                    'repay_type'          => $repay_type,
                    'pay_fee_remain'      => $calc_res['pay_fee_remain'],
                    'deal_type'           => $calc_res['deal_type'],
                );
                if ($deal['isDtb'] == 1) {
                    $data['management_fee'] = $calc_res['management_fee'];
                }
                if (false === $deal_prepay_service->prepaySave($deal['id'], $data)) {
                    throw new \Exception(sprintf('save deal_prepay error deal_id:%d', $deal['id']));
                }
            }
            $GLOBALS['db']->commit();

            //事务结束，在redis中删除该键值对
            if($redis){
                $redis->del('admin_cache_action_deal_project_repay_save_prepay_start_transaction_'.$project_id);
                Logger::info(__FILE__." | ".__LINE__." | redis del key. project_id:".$project_id);
            }
            return true;
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(sprintf('提前还款保存失败，项目id：%d, error_msg：%s', $project_id, $e->getMessage()));
            //事务回滚，在redis中删除该键值对
            if($redis){
                $redis->del('admin_cache_action_deal_project_repay_save_prepay_start_transaction_'.$project_id);
                Logger::info(__FILE__." | ".__LINE__." | redis del key. project_id:".$project_id);
            }
            return false;
        }
    }
}
