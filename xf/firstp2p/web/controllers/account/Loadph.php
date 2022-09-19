<?php
/**
 * Load.php
 * 普惠已投项目
 * @date 2018-08-08
 *
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\web\Url;
use libs\utils\Aes;
use core\dao\DealLoanTypeModel;
use core\service\ncfph\AccountService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");
/**
 * 个人中心-投资的项目
 *
 * Class Load
 * @package web\controllers\account
 */
class Loadph extends BaseAction
{

    public function init()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'status' => array('filter' => 'string'),
            'date_start'=>array("filter"=>'reg', "message"=>"起始时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'date_end'=>array("filter"=>'reg', "message"=>"结束时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'p' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        $status = intval($params['status']);
        $date_start = $params['date_start'];
        $date_end = $params['date_end'];
        $page = intval($params['p']);
        $page = $page <= 0 ? 1 : $page;
        $page_size = 6;
        $user_id = intval($GLOBALS['user_info']['id']);
        $page_size_loan = 7;
        $offset = ($page - 1) * $page_size;

        $type = 0;
        $accountService = new AccountService();
        $result = $accountService->getUserLoadList($user_id,$status,$date_start,$date_end,$page,$page_size);
        $count = $result['count'];
        $list = $result['list'];



        if ($count > $page_size) {
            $page_model = new \Page($count, $page_size); //初始化分页对象
            $pages = $page_model->show(array("addtourl" => 1, "status", "date_start", "date_end"));
            $this->tpl->assign('pages', $pages);
        }
        $this->tpl->assign("type", intval($params['type']));
        $this->tpl->assign("date_start", $date_start);
        $this->tpl->assign("date_end", $date_end);
        $this->tpl->assign("status", $status);
        $this->tpl->assign("list", $list);
        $this->tpl->assign("zxDealTypeId", 0);
        $this->tpl->assign("loadph",'ph');
        $this->tpl->assign("is_ph",1);
        $this->tpl->assign("page_title", "出借的项目");
        $this->tpl->assign("inc_file", "web/views/account/load.html");
        $this->tpl->assign("is_duotou_inner_user", !is_qiye_site() && is_duotou_inner_user() ? 1 : 0);
        $this->template = "web/views/account/frame.html";
    }

}
