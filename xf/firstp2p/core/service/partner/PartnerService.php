<?php
/**
 * 第三方合作伙伴服务
 */

namespace core\service\partner;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;

use core\service\DealProjectService;
use core\service\partner\RequestService;
use core\service\UserTagService;
use libs\utils\Logger;

class PartnerService
{
    /**
     * 获取第三方伙伴的资产代号
     * @params int $type_id 借款类别
     * @return string $partner_project_name  如果此type_id 无对应第三方，则返回 ''
     */
    static public function getPartnerProjectName($type_id)
    {
        $partner_project_name_map = array(
            DealLoanTypeModel::TYPE_XSJK => 'urealsoon',
            //功夫贷暂时不推
            //DealLoanTypeModel::TYPE_XJDGFD => 'treefinance',
        );

        $type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId(intval($type_id));

        return isset($partner_project_name_map[$type_tag]) ? $partner_project_name_map[$type_tag] : '';
    }

    /**
     * 通知第三方还款计划详情
     * @params int $deal_id
     * @params string $partner_project_name
     * @return true | throw
     */
    static public function noticeDealRepayList($deal_id, $partner_project_name)
    {
        //让功夫贷过往失败重试成功
        if ($partner_project_name == 'treefinance') {
            return true;
        }

        try {
            if (empty($deal_id) || empty($partner_project_name)) {
                throw new \Exception('error params');
            }

            $deal_info = DealModel::instance()->findViaSlave(intval($deal_id), 'id,project_id,user_id,type_id,approve_number,repay_start_time');
            if (empty($deal_info)) {
                throw new \Exception('empty deal_info');
            }

            $post_info['deal_id'] = $deal_info->id;
            $post_info['open_id'] = $deal_info->user_id;
            $post_info['approve_number'] = $deal_info->approve_number;
            $post_info['loan_time'] = strtotime(to_date($deal_info->repay_start_time, "Y-m-d"));

            // 获取还款计划列表
            $cond_deal_repay = sprintf('`deal_id` = %d', $deal_info->id);
            $deal_repay_list = DealRepayModel::instance()->findAll($cond_deal_repay);
            $post_deal_repay_list = array();
            foreach ($deal_repay_list as $deal_repay) {
                $one_deal_repay['repay_money'] = $deal_repay['repay_money'];
                $one_deal_repay['repay_time'] = to_date($deal_repay['repay_time'], 'Y-m-d');
                $one_deal_repay['principal'] = $deal_repay['principal'];
                $one_deal_repay['interest'] = $deal_repay['interest'];
                $one_deal_repay['loan_fee'] = $deal_repay['loan_fee'];
                $one_deal_repay['consult_fee'] = $deal_repay['consult_fee'];
                $one_deal_repay['guarantee_fee'] = $deal_repay['guarantee_fee'];
                $one_deal_repay['pay_fee'] = $deal_repay['pay_fee'];
                $one_deal_repay['manage_money'] = $deal_repay['manage_money'];
                $one_deal_repay['management_fee'] = $deal_repay['management_fee'];
                $post_deal_repay_list[] = $one_deal_repay;
                unset($one_deal_repay);
            }

            $post_info['repayment'] = base64_encode(json_encode($post_deal_repay_list));

            $response = RequestService::init($partner_project_name)
                ->setApi('repay.notify')
                ->setPost($post_info)
                ->request();

            if (isset($response['errorCode']) && 0 == $response['errorCode']) {
                Logger::info(sprintf('success: deal_id:%d, function:%s [%s:%s]', $deal_id, __FUNCTION__, __FILE__, __LINE__));
                return true;
            } else {
                throw new \Exception(sprintf('deal_id:%d,err-msg:%s', $deal_id, json_encode($response)));
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('fail: %s, function:%s [%s:%s]', $e->getMessage(), __FUNCTION__, __FILE__, __LINE__));
            throw $e;
        }
    }


    /**
     * 用户打了第三方tag的用户修改银行卡后通知该第三方
     */
    static public $projectTag = [
        'xianghua' => [ //享花项目
            'tag' => 'FROM_XIANGHUA', //tag名称
            'api' => 'fourEle.notify', //接口
            ],
        ];

    static public function modifyCardNotify($userId)
    {
        if (!$userId) {
            return;
        }
        $userTagService = new UserTagService();
        foreach (self::$projectTag as $project => $value) {
            if ($userTagService->getTagByConstNameUserId($value['tag'], $userId)) {
                $res = RequestService::init($project)
                    ->setApi($value['api'])
                    ->setPost(['open_id' => $userId])
                    ->setAsyn()
                    ->request();
                Logger::info('NotifyModifyCard.Taskid:'.var_export($res,true));
            }
        }
    }

    /**
     * 项目状态变更通知工单系统
     * status 1:进行时 2：满标
     */
    public static function projectStatusChangedNotify($dealId, $status)
    {
        if (empty($dealId)) {
            return false;
        }

        $sql = sprintf("SELECT `project_id`, `deal_status` from %s WHERE `id` = '%s'", DealModel::instance()->tableName(), $dealId);
        $dealInfo = DealModel::instance()->findBySqlViaSlave($sql);

        if (empty($dealInfo)) {
            return false;
        }

        $projectId = $dealInfo['project_id'];
        $isJysAndYy = DealProjectModel::instance()->isJysAndYy($projectId);

        if ($isJysAndYy === true) {

            $hasAccessToNotify = false;    //是否满足条件： 状态为进行中需判断是否是首标；状态为满标需判断是否满标

            if ($status == 1) {
                $firstDeal = DealModel::instance()->getFirstDealByProId($projectId);
                $hasAccessToNotify = $dealId == $firstDeal['id'];
            } elseif ($status == 2) {
                $hasAccessToNotify = true;
            }

            if ($hasAccessToNotify) {
                $postInfo['projectId'] = $projectId;
                $postInfo['status'] = $status;
                $response = RequestService::init('gongdan')->setApi('status.notify')->setPost($postInfo)->setAsyn()->request();
                Logger::info('NotifyProjectStatus.Taskid:'.var_export($response,true) . ', dealId: ' . $dealId . ', projectId: ' . $projectId);
            } else {
                Logger::info('gongdan hasAccessToNotify: ' . $hasAccessToNotify . ', dealId: ' . $dealId . ', projectId: ' . $projectId);
            }

        } else {
            return false;
        }
    }

}
