<?php
namespace web\controllers\activity;

/**
 * 活动专区
 */
use libs\web\Url;
use libs\utils\Aes;
use web\controllers\BaseAction;
use libs\web\Form;

class ActivityZone extends BaseAction
{
    const PAGE_SIZE = 9;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "status" => array("filter" => "string"),
            "page" => array("filter" => "int"),
        );
        $this->form->validate();
    }
    public function invoke()
    {
        $data = $this->form->data;
        $nowPage = intval($data['page']) ?: 1;
        $res = $this->rpc->local('ApiConfService\getApiAdvConf', array('activity_zone',1,2),'conf');
        $actOngoing = [];
        $actExpired = [];
        $temp = [];

        if (!empty($res)) {
            foreach ($res as $k => $v) {
                $ad_value = json_decode($v['value'], true);
                if (empty($ad_value)) {
                    continue;
                }
                foreach ($ad_value as $key => $value) {
                    if (time() > strtotime($value['endTime'])) {
                        $value['isExpire'] = 1;
                    }elseif (time() > strtotime($value['startTime']) && time() < strtotime($value['endTime'])) {
                        $value['isExpire'] = 0;
                    }
                    if ($value['isExpire'] == 1) {
                        $actExpired[] = $value;
                    }elseif (isset($value['isExpire']) && $value['isExpire'] == 0) {
                        $actOngoing[] = $value;
                    }
                }
            }
        }
        if (1 == $data['status']) {
            $temp = $actExpired;
        }elseif (!strcmp($data['status'], 0)) {
            $temp = $actOngoing;
        }else {
            $temp = array_merge($actOngoing,$actExpired);
        }
        $ret = [];
        $ret = array_slice($temp,self::PAGE_SIZE*($nowPage-1),self::PAGE_SIZE);
        $this->tpl->assign('actInfo',$ret);
        $this->tpl->assign('status',$data['status']);
        $this->tpl->assign('nowPage',$nowPage);
        $this->tpl->assign('totalPage',ceil(count($temp)/self::PAGE_SIZE));
        $this->tpl->assign('count',count($temp));
        $this->template = 'web/views/activity/activity_zone.html';
    }
}
