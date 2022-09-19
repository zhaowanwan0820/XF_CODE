<?php namespace NCFGroup\Ptp\Apis;
/**
 * 信仔机器人健步接口
 */

use \core\service\AppStepsBonusService;

class WalkApi
{
    private $params = [];
    private $confValue = [];
    private $stepData = 0;
    private $isAward = 0;

    private $output = ['errorCode' => 0, 'errorMsg' => 'success', 'data' => null];

    private function _init()
    {
        $this->params = json_decode(file_get_contents('php://input'), true);
        if (empty($this->params['userId'])) {
            throw new \Exception('userId is required', 1);
        }

        $this->confValue = confToArray(app_conf('APP_STEPS_BONUS_CONF'));
        if (empty($this->confValue['remind'])) {
            throw new \Exception();
        }

        $stepService = new AppStepsBonusService();
        $stepRes = $stepService->getWalk($this->params['userId']);
        $this->stepData = !empty($stepRes['steps']) ? intval($stepRes['steps']) : 0;
        $this->isAward = !empty($stepRes['is_award']) ? intval($stepRes['is_award']) : 0;
    }

    public function remind()
    {
        try {
            if (!$this->_isIOS()) {
                throw new \Exception();
            }
            $this->_init();
            $this->output['data']['content'] = '';
            if ($this->isAward == 0 &&
                $this->stepData > $this->confValue['remind'] &&
                $this->stepData < $this->confValue['steps']) {

                $this->output['data']['content'] = sprintf(
                    '你和红包的距离只差了%s步',
                    strval($this->confValue['steps']-$this->stepData)
                );
            }
        } catch (\Exception $e) {
            $this->output['errorCode'] = $e->getCode();
            $this->output['errorMsg'] = $e->getMessage();
            $this->output['data']['content'] = '';
        }

        return $this->output;
    }

    public function message()
    {
        try {
            $this->_init();
            $topTitle = "为您找到的健步详情";
            if (!$this->_isIOS()) {
                $this->output['data'] = [
                    'type' => 1,
                    'title' => $topTitle,
                    'content' => [
                        'title' => '尴尬啦！信仔还没学会在安卓手机上统计步数哦~',
                        'columnNum' => 1,
                        'actionList' => [],
                        ],
                    ];
            } else {
                if ($this->stepData < $this->confValue['steps'] && $this->isAward == 0) {
                    $message = sprintf('你和红包的距离还差%s步,加油！%s', strval($this->confValue['steps'] - $this->stepData), $this->confValue['tip']);
                    $title = '查看网信健步详情';

                } else {
                    $message = $this->confValue['tip'];
                    $title = '领取网信健步红包';
                }

                $this->output['data'] = [
                    'type' => 1,
                    'title' => $topTitle,
                    'content' => [
                        'title' => $message,
                        'columnNum' => 1,
                        'actionList' => [
                            [
                                'type' => 1,
                                'imageUrl' => '',
                                'title' => $title,
                                'uri' => '{"type":26}',
                            ],
                        ],
                    ],
                ];
            }
        } catch (\Exception $e) {
            $this->output['errorCode'] = $e->getCode();
            $this->output['errorMsg'] = $e->getMessage();
        }

        return $this->output;
    }

    private function _isIOS()
    {
        return !empty($_SERVER['HTTP_OS']) ? stripos($_SERVER['HTTP_OS'], 'ios') !== false : false;
    }

    private function _getDeviceNo()
    {
        return !empty($_SERVER['HTTP_DEVICESID']) ? $_SERVER['HTTP_DEVICESID'] : '';
    }
}
