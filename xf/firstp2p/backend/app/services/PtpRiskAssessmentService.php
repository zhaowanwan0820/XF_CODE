<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use core\service\RiskAssessmentService;
use libs\utils\Logger;

/**
 * PtpRiskAssessmentService.
 *
 * @uses ServiceBase
 */
class PtpRiskAssessmentService extends ServiceBase
{

    /**
     * 获取用户的问卷
     *
     * @param ProtoUser $request
     * @access public
     *
     * @return array
     */
    public function getUserRiskQuestion(ProtoUser $request)
    {
        $response = new ResponseBase();
        $riskService = new RiskassessmentService();
        $userId = $request->getUserId();
        $question = $riskService->getQuestion();
        if (empty($question)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMsg = '评估暂未开放';
            return $response;
        }

        $uraData = $riskService->getUserRiskAssessmentData(intval($userId));
        if (empty($uraData)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMsg = '用户评估数据获取失败';
            return $response;
        }

        if (!isset($question['limit_type']) || ($question['limit_type'] == 1 && $uraData['assess_num'] >= $question['limit_times'])) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMsg = '您已无评估次数,请稍后重试';
            return $response;
        }
        $response->question = $question;
        return $response;
    }


    /**
     * 获取用户历史问卷信息
     *
     * @param ProtoUser $request
     * @access public
     *
     * @return arrary
     */
    public function getUserRiskData(ProtoUser $request)
    {
        $userId = $request->getUserId();
        if(!empty($userId)){
            $riskRes = (new RiskassessmentService())->getUserRiskAssessmentData(intval($userId));

            $response = new ResponseBase();
            $response->levelName = empty($riskRes['last_level_name']) ? '' : $riskRes['last_level_name'];
            if (isset($riskRes['remaining_assess_num'])) {
                $response->remainingNum = intval($riskRes['remaining_assess_num']);
            } else {
                $response->remainingNum = !empty($riskRes['ques']) ? 1 : 0;
            }
            $response->limitType = !empty($riskRes['ques']) ? $riskRes['ques']['limit_type'] : 0;
            $response->status = !empty($riskRes['ques']) ? 1 : 0;
        }
        return $response;
    }

    /**
     * 评估
     *
     * @param SimpleRequestBase $request
     * @access public
     *
     * @return arrary
     */
    public function doAssess(SimpleRequestBase $request)
    {
        $response = new ResponseBase();
        $params = $request->getParamArray();

        $riskService = new RiskassessmentService();
        $question = $riskService->getQuestionById(intval($params['questionId']));
        if (empty($question) || $question['status'] == 0) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMsg = '风险评估已经失效';
            return $response;
        }

        if ($question['limit_type'] == 1) {
            $uraData = $riskService->getUserRiskAssessmentData(intval($params['userId']));
            Logger::info("Assess Request. ura_data: " . json_encode($uraData));
            if (empty($uraData)) {
                $response->resCode = RPCErrorCode::FAILD;
                $response->resMsg = '用户评估数据获取失败';
                return $response;
            }

            if (!isset($question['limit_type'])|| ($question['limit_type'] == 1 && $uraData['assess_num'] >= $question['limit_times'])) {
                $response->resCode = RPCErrorCode::FAILD;
                $response->resMsg = '您已超出评估次数';
                return $response;
            }
        }

        $assessResult = $riskService->assess(intval($params['userId']), intval($params['questionId']), intval($params['score']));
        Logger::info("Assess Request. assess_result: " . json_encode($assessResult));
        if (empty($assessResult)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMsg = '评级失败,请重试';
            return $response;
        }
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->result = $assessResult;
        return $response;
    }

}
