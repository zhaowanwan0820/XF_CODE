<?php
namespace api\controllers\message;

/**
 * 站内公告 
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Notice extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'offset' => array('filter' => 'int'),
            'count' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->user;
        $site_id = $data['site_id'] ?: $this->defaultSiteId;

        $offset = isset($data['offset']) ? intval($data['offset']) : 0;
        $count = isset($data['count']) ? intval($data['count'])+10 : 20;

        try {
            $result = $this->rpc->local(
                'NoticeService\getList',
                array($user['id'], $offset, $count, mktime(0,0,0,date('m')-3,date('d')-1,date('Y')))//仅显示近三个月的消息
            );
        } catch (\Exception $e) {
            Logger::error('NoticeError:'.$e->getMessage());
            $result = array();
        }
        //发布无效，强制去除
        $result = array();
        if ($result) {
            $return_data = array();
            foreach ($result as $value) {
                $str = str_replace(array('，','|',' '), ',', $value['exclude_site']);
                if (in_array($site_id, explode(',', $str))){
                    continue;
                }
                $value['time'] = date('Y-m-d H:i:s', $value['time']);
                $return_data[] = $value;
            }
        }

        $this->json_data = array_slice($return_data, 0, 10);
    }

}
