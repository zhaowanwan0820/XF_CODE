<?php
/**
 * 资金记录
 * @author 曹龙<caolong@ucfgroup.com>
 **/

namespace web\controllers\account;

use core\service\UserLogService;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BaseModel;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");
/**
 * 我的邀请码
 * @author <caolong@ucfgroup.com>
 **/
class Money extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form('request');
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'log_info'=>array('filter'=>'string'),
                'start'=> array('filter'=> 'string'),
                'lately'=> array('filter'=> 'int'),
                'end'=> array('filter'=> 'string'),
                'export' => array('filter'=>'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }

    }

    const EXPORT_USER_LOG_MAX = 30000; //单次最大导出资金记录数
    public function invoke() {
        $data = $this->form->data;
        $user_info  = $this->rpc->local('UserService\getUser',array(intval($GLOBALS['user_info']['id'])));
        $level_info = $this->rpc->local('UserGroupService\getGroupInfo',array(intval($user_info['group_id'])));
        $point_level= $this->rpc->local('UserLevelService\getLevelInfo',array(intval($user_info['level_id'])));

        $user_info['user_level'] = $level_info['name'];
        $user_info['point_level']= $point_level['name'];
        $user_info['discount']   = $level_info['discount']*10;
        //分页
        $page = intval($data['p']);
        if($page==0){
            $page = 1;
        }
        $lately = intval($data['lately']);
        if($lately > 0) {
            $_GET['end'] = date("Y-m-d");
            switch ($lately) {
                case 1 : //最近一周
                    $_GET['start'] = date("Y-m-d",strtotime("-1 week"));
                    break;
                case 2 : //最近一个月
                    $_GET['start'] = date("Y-m-d",strtotime("-1 month"));
                    break;
                case 3 : //最近三个月
                    $_GET['start'] = date("Y-m-d",strtotime("-3 month"));
                    break;
            }
        }

        $result = $this->rpc->local('UserLogService\get_user_log',array(array(($page-1)*app_conf("PAGE_SIZE"),app_conf("PAGE_SIZE")),$user_info['id'],'money', true));
        if (isset($data['export'])) {
            if($result['count'] > self::EXPORT_USER_LOG_MAX) {
                return $this->show_error('当前选择的时间范围内资金记录过多，请缩小时间范围再导出','系统提示', 0, 0, '/account/money');
            }
            // 导出csv
            $exportData = $this->rpc->local('UserLogService\get_user_log',array(array(0,$result['count']),$user_info['id'],'money'));
            $this->doExport($data['start'], $data['end'], $exportData);
        }
        $page = new \Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象
        $p  =  $page->show();

        $this->tpl->assign('filter', UserLogService::$money_log_types);
        $this->tpl->assign('pages',$p);
        $this->tpl->assign("list",$result['list']);
        $this->tpl->assign("user_data",$user_info);
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_MONEY']);
        $this->tpl->assign('search_get',
                            array('log_info' => htmlspecialchars($data['log_info']),
                                'start' => htmlspecialchars($data['start']),
                                'end' => htmlspecialchars($data['end']),
                                'lately' => $lately,
                            )
        );
        $this->tpl->assign("inc_file","web/views/account/money.html");
        $this->template = "web/views/account/frame.html";
    }


    /**
     * 导出
     */
    public function doExport($fromDate, $toDate, $data) {
        $file_name = '资金记录'.$fromDate.'-'.$toDate;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        $title = array(
            "类型","时间","资金变动(元)","余额","备注",
        );

        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));

        while ($log = array_pop($data['list'])) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }

            $showmoney = format_price($log['showmoney']);
            if($log['label'] == UserLogService::LOG_INFO_SHOU) {
                $showmoney = '+'.$showmoney;
            }
            $row = sprintf("%s||%s||%s||\t%s||\t%s", $log['log_info'],to_date($log['log_time'],'Y/m/d H:i'), $showmoney,format_price($log['remaining_total_money']),$log['note']);
            fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
        }
        exit;
    }
}

