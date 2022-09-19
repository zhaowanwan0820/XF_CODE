<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use core\service\candy\CandyWishService;
use libs\web\Form;

class Double11Status extends AppBaseAction
{
    //夺宝活动开始时间
    const SNATCH_START_TIME = '2018-11-05';
    //秒杀活动开始时间
    const SECKILL_START_TIME = '2018-11-11';

    // 许愿抽奖开始时间
    const LOTTERY_START_TIME = '2018-11-16 10:00:00';
    // 双十一活动结束
    const DOUBLE11_ACTIVITY_END = '2018-11-17';
    // 隐藏导航
    const GUIDE_HIDE = '2018-11-16';

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token不能为空'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $this->json_data = array(
            'countDown' => $this->getCountDown(),
            'step' => $this->getStep(),
            'isSnatchStart' => time() > strtotime(self::SNATCH_START_TIME) || $inSnatchWhiteList,
            'isSeckillStart' => time() > strtotime(self::SECKILL_START_TIME),
            'isLotteryStart' => time() > strtotime(self::LOTTERY_START_TIME),
            'isActivityEnd' => time() > strtotime(self::DOUBLE11_ACTIVITY_END),
            'isGuideHide' => time() > strtotime(self::GUIDE_HIDE),
        );
    }

    private function getCountDown()
    {
        return max(0, ceil((strtotime('2018-11-10') - time()) / 86400));
    }

    private function getStep()
    {
        if (time() < strtotime("2018-11-10")) {
            return 1;
        }
        
        if (time() < strtotime("2018-11-13")) {
            return 2;
        }

        return 3;
    }

}