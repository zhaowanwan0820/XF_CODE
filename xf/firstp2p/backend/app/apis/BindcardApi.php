<?php

namespace NCFGroup\Ptp\Apis;
use core\service\UserService;

/**
 * 信仔机器人-实名绑卡接口
 */
class BindcardApi {
    /**
     * 参数列表
     * @var array
     */
    private $params;


    private function init($isCheckUserId = true) {
        $di = getDI();
        $this->params = $di->get('requestBody');
        if ($isCheckUserId && empty($this->params['userId'])) {
            throw new \Exception('缺少参数userId');
        }
    }

    /**
     * 信仔-气泡接口
     * @return array
     */
    public function bubble() {
        try {
            $this->init();

            // 用户ID
            $userId = (int)$this->params['userId'];

            $userService = new UserService($userId);
            $opts = ['check_validate' => false];
            $userCheck = $userService->isBindBankCard($opts);
            // 检查用户是否实名绑卡成功
            $content = '';
            if ($userCheck['ret'] !== true) {
                if ($userCheck['respCode'] == UserService::STATUS_BINDCARD_IDCARD) {
                    $keyword = "实名认证";
                    $content = '您尚未完成实名认证';
                }
                if ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND) {
                    $keyword = "绑定银行卡";
                    $content = '您尚未绑定银行卡';
                }
            }
            return ['errorCode'=>0, 'errorMsg'=>'success', 'data' => ['content' => $content, 'keyword' => $keyword]];

        } catch (\Exception $ex) {
            return ['errorCode'=>-1, 'errorMsg'=>$ex->getMessage(), 'data' => []];
        }
    }

    /**
     * 信仔-消息接口
     * @return array
     */
    public function msg() {
        try {
            $this->init();

            // 用户ID
            $userId = (int)$this->params['userId'];

            $userService = new UserService($userId);
            $opts = ['check_validate' => false];
            $userCheck = $userService->isBindBankCard($opts);
            // 检查用户是否实名绑卡成功
            $idcardUri = $bindcardUri = '';
            $idcardStatus = $bindcardStatus = 1; //实名和绑卡状态，默认是1
            $idcardBtnTitle = $bindcardBtnTitle = '';
            if ($userCheck['ret'] !== true) {
                //未实名
                if ($userCheck['respCode'] == UserService::STATUS_BINDCARD_IDCARD) {
                    $idcardUri = '{"type":31}';
                    $idcardStatus = $bindcardStatus = 0;
                    $idcardBtnTitle = '<span color="#4297FE">去进行实名认证</span>';
                    $bindcardBtnTitle = '<span color=#D4D4D7">去绑定银行卡</span>';
                }
                //未绑卡
                if ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND) {
                    $bindcardUri = '{"type":31}';
                    $bindcardStatus = 0;
                    $bindcardBtnTitle = '<span color=#4297FE">去绑定银行卡</span>';
                }
            }
            $title = '以下是投资前准备流程';
            return ['errorCode' => 0, 'errorMsg' => 'success', 'data' =>
                [
                    'content' => [
                        'actionList' => [
                            ['title' => '实名认证', 'subTitle' => '二代身份证，方便快捷，10秒搞定', 'buttonTitle' => $idcardBtnTitle, 'type' => 1, 'uri' => $idcardUri, 'status' => $idcardStatus],
                            ['title' => '绑定银行卡', 'subTitle' => '资金同卡进出，保障账户安全', 'buttonTitle' => $bindcardBtnTitle, 'type' => 1, 'uri' => $bindcardUri, 'status' => $bindcardStatus],
                        ],
                        'rowNum' => 2,
                    ],
                    'title' => $title,
                    'type' => 3,
                ]
            ];

        } catch (\Exception $ex) {
            return ['errorCode'=>-1, 'errorMsg'=>$ex->getMessage(), 'data' => []];
        }

    }
}
