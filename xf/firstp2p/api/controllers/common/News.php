<?php
/**
 * news
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Curl;

class News extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "name" => array("filter" => "string"),
            "page" => array("filter" => "int"),
            "since_date" => array("filter" => "string"),
            "type" => array("filter" => "string"),
        );
        $this->form->validate();
    }


    public function invoke()
    {
        $data = $this->form->data;
        $page = intval($data['page']) ? $data['page'] : 1;
        $type = intval($data['type']) ? $data['type'] : 1;
        $since_date = !empty($data['since_date']) ? $data['since_date'] : time();
        //财经资讯
        $url['finance'] = 'http://iphone.myzaker.com/zaker/article_telecom.php?app_id=4&for=wangxinlicai&num=40&since_date='.$since_date.'&nt='.($page-1);
        //水皮
        $url['talkShow'] = 'http://www.chinatimes.net.cn/shuipimore_data?from=wxlc20160708&type='.$type.'&page='.$page;

        $res = Curl::get($url[$data['name']]);

        if ($data['name'] == 'finance') {
            $res = gzdecode($res);
        }
        $confKeys = explode(',', app_conf('FINANCE_BLACKLIST_KEYWORDS'));
        $arr = array();
        if ($arr = json_decode($res, true)) {
            $newList = [];
            foreach($arr['data']['list'] as $k => $v) {
                switch ($data['name']) {
                    case 'finance':
                        $title = 'title';
                        break;
                    case 'talkShow':
                        if ($type == 1) {
                            $title = 'v_title';
                        } elseif ($type == 2) {
                            $title = 'a_title';
                        }
                        break;
                }
                $isBlackKey = false;
                foreach ($confKeys as $key) {
                    if (false !== stripos($v[$title], $key)) {
                        $isBlackKey = true;
                    }
                }
                if (!$isBlackKey) {
                    $newList[] = $v;
                }
                if (isset($v['date'])) {
                    $since_date = strtotime($v['date']);
                }
            }
            $arr['data']['list'] = $newList;
            $arr['data']['next_url'] = $this->getHost().'/common/news?name='.$data['name'].'&page='.($page+1).'&type='.$type;
            if (!empty($since_date)) {
                $arr['data']['next_url'] .= '&since_date='.$since_date;
            }
        }

        $this->log();
        header('Content-type: application/json');
        echo json_encode($arr);
        exit;
    }

}
