<?php namespace NCFGroup\Ptp\Apis;
/**
 * 信仔机器人签到接口
 */

use \core\service\CheckinService;

class CheckinApi
{
    private $params = [];
    private $checkinSrv = null;
    private $userId = null;
    private $output = ['errorCode' => 0, 'errorMsg' => 'success', 'data' => null];

    private function _init()
    {
        $this->params = json_decode(file_get_contents('php://input'), true);
        if (empty($this->params['userId'])) {
            throw new \Exception('userId is required', 1);
        }
        $this->userId = intval($this->params['userId']);
        $this->checkinSrv = new CheckinService();
    }

    public function remind()
    {
        $this->output['data']['content'] = '';
        try {
            $this->_init();
            $checkedInfo = $this->checkinSrv->getCheckedInfo($this->userId);
            if ($checkedInfo['checkedStatus'] == 0) {
                $this->output['data']['content'] = '今日还未签到';
            }
        } catch (\Exception $e) {
            $this->output['errorCode'] = $e->getCode();
            $this->output['errorMsg'] = $e->getMessage();
        }

        return $this->output;
    }

    public function message()
    {
        try {
            $this->_init();
            $checkedInfo = $this->checkinSrv->checkin($this->userId);

            $msg[] = '<span color="red">今日签到成功</span>';
            if (!empty($checkedInfo['vipPoint'])) {
                $msg[] = '+'.$checkedInfo['vipPoint'].'经验值';
            }
            if (!empty($checkedInfo['awards'])) {
                $msg[] = sprintf('本轮您已累计签到%s天', $checkedInfo['checkedCount']);
                $msg[] = sprintf('恭喜您获得%s，%s已经发到您的账户',
                        $checkedInfo['awards']['prize'],
                        $checkedInfo['awards']['awardName']
                        );
            }

            if ($checkedInfo['remainDay'] == 0) {
                $msg[] = '明日签到开启下一轮';
            } elseif (empty($checkedInfo['awards'])){
                $msg[] = '再签'.$checkedInfo['nextAwardDays'].'天可获奖励';
            }

            $message = join("\n", $msg);

            $title = '签到详情';
            $this->output['data'] = [
                'type' => 1,
                'title' => "信仔已经为您签到成功",
                'content' => [
                    'title' => $message,
                    'columnNum' => 1,
                    'actionList' => [
                        [
                            'type' => 1,
                            'imageUrl' => '',
                            'title' => $title,
                            'uri' => '{"type":28}',
                        ],
                    ],
                ],
            ];
        } catch (\Exception $e) {
            $this->output['errorCode'] = $e->getCode();
            $this->output['errorMsg'] = $e->getMessage();
        }

        return $this->output;
    }

}
