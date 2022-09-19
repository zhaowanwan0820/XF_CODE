<?php
/**
 * 每月1号 生成月对账单的邮件附件
 * 0 8 1 * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php every_month_email.php
 * @author zhanglei5 2014-6-1
 */

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use core\service\UserService;
use core\service\DealLoadService;
use core\service\UserLogService;
use core\service\DealLoanRepayService;
use libs\utils\Logger;
//error_reporting(E_ALL ^ E_WARNING);ini_set('display_error', 1);
set_time_limit(0);
class email_bill_pdf {
    public $user_service;
    public $deal_load_service;
    public $user_log_service;
    public $deal_loan_repay_service;
    public $page_size = 500;
	private $_main_site = '';
	private $_wk_path = '/usr/local/bin/wkhtmltopdf';
	private $_tk_path = '/usr/bin/pdftk';


    function __construct() {
        $this->user_service  = new UserService();
        $this->deal_load_service = new DealLoadService();
        $this->user_log_service = new UserLogService();
        $this->deal_loan_repay_service = new DealLoanRepayService();
		$this->_main_site =  'http://'.$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];
    }

    function run($list_uid) {

            foreach ($list_uid as $val) {
                $user_id = $val;
                $data = array();
                //判断如果是借款人

                if($this->is_investor( $user_id)){
                    $data = $this->getData($user_id);
                    $data['site'] = 'http://'.$GLOBALS['sys_config']['STATIC_DOMAIN_NAME'].$GLOBALS['sys_config']["STATIC_DOMAIN_ROOT"];
                    $data['main_site'] = $this->_main_site;
                    $email = $data['user_info']['email'];
                    $content = $this->makePage(APP_ROOT_PATH.'web/views/email/every_month_pdf.php', $data);
				//	$content = $this->makePage(APP_ROOT_PATH.'web/views/email/every_month_template.php', $data);
				//	$content = $email;	//file_get_contents('t2.html');
                    //$pdf_content = $cnt;
					$cont_info = array('number'=>$data['user_info']['idno'],'content'=>$content,'user_id'=>$user_id);
					$data = array('year'=>date('Y',time()),'month'=>date('m',time()),'user_id'=>$user_id,'html_content'=>'','create_time'=>date('Y-m-d H:i:m',time()),'email'=>$email);
					if(strlen($data['user_info']['idno']) > 0) {
						$rs_up = $this->makePdf($cont_info);
						$month_service = new \core\service\MonthBillService();
						if($rs_up['status']) {	// success
							$data['attachment_id'] = $rs_up['aid'];
						}else {	// 	faild
							$data['attachment_id'] = 0;
							$data['html_content'] = $content;
							$data['idno'] = $data['user_info']['idno'];
							$data['rs_upload'] = json_encode($rs_up);
							echo('上传错误！');
						}
					}else {
						$data['attachment_id'] = 0;
						$data['html_content'] = $content;
						$data['rs_upload'] = json_encode(array('reason'=>'身份证号为空,没有上传!'));
					}
					$id = $month_service->insert($data);

					$log['user_id'] = $user_id;
                    Logger::wLog($log); //  写入日志  以防止意外，可以追踪
                }
				else{
					//echo "\n $user_id not have touzi";
				}
				//sleep(1);
            }
    }

    /**
     * 组装页面
     * @param string $tpl
     * @param array $data
     * @return string
     */
    function makePage($tpl,$data){
        if(is_array($data)) {
            extract($data);
        }
        ob_start();
        include($tpl);
        $page = ob_get_contents();
        ob_end_clean();
        return $page;
    }



    function getData($user_id) {
        $user_info = $this->user_service->getUser($user_id);
        //用户统计
        $user_statics = user_statics($user_id);
        //资产总额
        $user_statics['money_all'] = $user_info['money'] + $user_info['lock_money'] + $user_statics['stay'];
        $referrals = $this->user_log_service->getReferrals($user_id);
        //累计收益 = 已赚利息 + 违约金 + (邀请返利 + 注册返利)
        $data['earning_all'] = $user_statics['load_earnings'] + $user_statics['load_tq_impose'] + $referrals + $user_statics['load_yq_impose'] ;
        //上月收支明细
        $last = $this->user_log_service->getLastMonthAll($user_id);
        $data['last'] = $last;
        $data['user_statics'] = $user_statics;
        $data['user_info'] = $user_info;

        $arr_time = getTimeStartEnd('last_month');
        $data['prev_month_start'] = $arr_time['start'];
        $data['prev_month_end'] = $arr_time['end'];
        //上月投资总额
        $loan_sum = $this->deal_load_service->getTotalLoanMoneyByUserId($user_id,$arr_time['start'],$arr_time['end'],array(4,5));

        // 上个月收支明细
        $data['detail'] = $last['detail'];

        //当月回款计划
        $arr_time = getTimeStartEnd('cur_month');
        $start = $arr_time['start'];
        $end = $arr_time['end'];
        $repay_list = $this->deal_loan_repay_service->getRepayList($user_id,$start,$end,array(0,5),'api');
        $data['repay_list'] = $repay_list['list'];
        /* if(count($repay_list['list']) > 0 ) {
            $content .= $this->make_repay($repay_list['list']);
        } */
        $data['prev_month'] = date('m',$data['prev_month_end']) - 0;
        $data['cur_month'] = date('m',$end) - 0;
        $data['loan_sum'] = $loan_sum;    // 投资总额
        return $data;
    }


    /**
     * 判断是否为投资人
     * @param int $user_id
     * @return number
     */
    function is_investor($user_id) {
        $cnt = $this->deal_load_service->countByUserId($user_id);
        $cnt = intval($cnt);
        return $cnt;
    }

    function makePdf($cont_info) {
        //本地生成pdf
		$user_id = $cont_info['user_id'];
        //$html_name = md5 ( $cont_info['number'] ) .'_'. getmypid().".html";
        $html_name = $user_id .'_'. getmypid().".html";
        $file_path = APP_ROOT_PATH.'runtime/';
		$pdf_name = $user_id.'_'.md5 ( $user_id.'_'.$cont_info['number'] ) . ".pdf"; 

		$fp = fopen($file_path.$html_name,'w');
		if(!$fp) {
			die("open file $file_path faild!");
		}
		$content = str_replace('lc.p2pstatic','p2pstatic',$cont_info['content']);
		fwrite($fp,$content);
		fclose($fp);
		
		//test eviroment use   online need  delete this line @todo
		//system("mv -f $file_path$html_name /apps/product/nginx/htdocs/firstp2p/firstp2p/runtime/");
		$pdf = system($this->_wk_path.' --page-size A3 -T 0 http://localhost/'.$html_name.' '.$file_path.$pdf_name.' >wk 2>&1');

   		// 加密
        $pwd = substr($cont_info['number'], -6);
		$new_pdf = $cont_info['user_id'].'_'.$pdf_name;
		$rs = system($this->_tk_path.' '.$file_path.$pdf_name.' output '.$file_path.$new_pdf." user_pw '".$pwd."'");
		
		
        //存储到vfs
		$arr_time = getTimeStartEnd('last_month');
		$last_month = date('m',$arr_time['end']);
		$file['name'] = '网信电子对账单'.date('Y',$arr_time['end']).'年'.($last_month-0).'月份.pdf';
        //$file['name'] = $new_pdf;
        $file['type'] = 'application/pdf';
        $file['tmp_name'] = $file_path.$new_pdf;
        $file['error'] = 0;
        $file['size'] = filesize($file_path.$new_pdf);

        $uploadFileInfo = array(
            'file' => $file,
            'isImage' => 0,
            'asAttachment' => 1,
            'asPrivate' => true,
        );
		$doupload = uploadFile($uploadFileInfo);
		//	echo "\n rm -f $file_path$new_pdf $file_path$pdf_name ".getmypid();	//  $file_path$html_name";
		if($doupload['status']) {
			system("rm -f $file_path$new_pdf $file_path$pdf_name");
		}
		/*
		*/
		return $doupload;
    }

}

	$list_uid = array();
	$ids = '';
	if($argc > 1) {
		$ids = $argv[1];
		$list_uid = explode(',',$ids);
		$obj = new email_bill_pdf();
		if($argc == 2) {
			$obj->run($list_uid);
		}/*
			else if($argc == 3){
			$obj->update($list_uid);
		}
		*/
	}


