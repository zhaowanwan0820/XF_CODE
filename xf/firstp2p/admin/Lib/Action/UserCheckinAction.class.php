<?php
use libs\utils\Logger;

class UserCheckinAction extends CommonAction
{
    const CONFIG_KEY = 'user_checkin_config'; //api_conf配置名

    private $confModel;
    private $dataObj;

    public function __construct()
    {
        $this->confModel = M('ApiConf');
        $condition['name'] = self::CONFIG_KEY;
        $this->dataObj = $this->confModel->where($condition);
        parent::__construct();
    }

    public function index()
    {
        $vo = $this->dataObj->find();
        if (empty($vo)) {
            $vo = array(
                'title' => '签到活动配置',
                'name' => self::CONFIG_KEY,
                'conf_type' => 3,
                'value' => '',
                'is_effect' => 0,
                'create_time' => time(),
            );
            $this->confModel->add($vo);
        }
        $confVal = json_decode($vo['value'], true);
        if (empty($confVal['roundData'])) {
            $confVal['roundData'] = array('' => array());
        }
        $this->assign("roundDay", $confVal['roundDay']);
        $this->assign("roundCount", $confVal['roundCount']);
        $this->assign("bgImg", $confVal['bgImg']);
        $this->assign("roundData", $confVal['roundData']);
        $this->assign("isEffect", $vo['is_effect']);
        $this->display();
        return;
    }

    public function save()
    {
        B('FilterString');
        extract($_REQUEST);
        $roundDay = intval($roundDay);
        $roundCount = intval($roundCount);
        $bgImg = trim($bgImg);
        $roundData = array();
        foreach ($times as $k => $v) {
            if (empty($v)) continue;
            $roundData[$v] = array(
                'awardType' => $awardType[$k],
                'awards' => $awards[$k],
                'remark' => $remark[$k],
                'prize' => $prize[$k],
            );
        }
        ksort($roundData);
        $data = array();
        $data['is_effect'] = intval($is_effect);
        $data['update_time'] = time();
        $data['value'] = json_encode(
            array(
                'roundDay' => $roundDay,
                'roundCount' => $roundCount,
                'bgImg' => $bgImg,
                'roundData' => $roundData,
            )
        );
        $res = $this->dataObj->save($data);
        if (false !== $res) {
            save_log($data['value'].L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            save_log($data['value'].L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, L("UPDATE_FAILED"));
        }
    }
    
    public function UserDetail()
    {
        $user_id = intval($_REQUEST['uid']);
        if ($user_id) {
            $userCheckinModel = M("UserCheckin");
            $res = $userCheckinModel->where(array('user_id' => $user_id))->find();
            $roundData = json_decode($res['round_data'], true);
            $pastRoundData = json_decode($res['round_data_past'], true);
            $this->assign("roundData", $roundData);
            $this->assign("pastRoundData", $pastRoundData);
            $this->assign("firstTime", date('Y-m-d H:i:s',$res['first_time']));
            $this->assign("recentTime", date('Y-m-d H:i:s',$res['recent_time']));
            $this->assign("sum", $res['sum']);
            $this->assign("uid", $user_id);
        }
        $this->display();
        return;
    }
}
