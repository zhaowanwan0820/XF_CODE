<?php
namespace core\service\report;

use core\dao\report\ReportCompanyUserModel;
use core\dao\report\ReportUserModel;
use core\dao\risk\UserRiskAssessmentModel;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use core\service\report\ReportBase;
use core\service\user\BankService;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;
use core\dao\deal\DealModel;

class ReportCompanyUser extends ReportBase{

    public $dealId;

    public $userId;

    public function __construct($dealId)
    {

        $this->dealId = $dealId;
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->userId = $this->dealInfo['user_id'];
        $this->userInfo = UserService::getUserById($this->userId,'real_name,idno');
        $this->userCompanyInfo = UserService::getEnterpriseInfo($this->userId);

    }

    public function collectData(){

        $data = array(
            'user_id' => $this->userId,
            'deal_id' => $this->dealId,
            'project_id' => $this->dealInfo->project_id,  //项目编号
            'name' => $this->userInfo['real_name'],  //企业名称
            'registered_capital' => $this->userCompanyInfo['reg_amt'],  //注册资本（单位元）
            'registered_address' => (empty($this->userCompanyInfo['registration_address']) ? '': $this->userCompanyInfo['registration_address']), // 注册地址
            'start_time' => date('Ymd',strtotime($this->userCompanyInfo['credentials_expire_date'])),  //注册时间
            'corporate' => (empty($this->userCompanyInfo['legalbody_name']) ? '': $this->userCompanyInfo['legalbody_name']),  //法人
//            'corporate_info' => '',  //法人信用信息
            'industry' => '',  //行业
            'incoming_debt' => '', //收入及负债情况
//            'credit_report' =>'暂时无法提供',   //征信报告
            'extra_info' =>'', //其他借款信息
//            'stockholder' => '',  //股东信息
//            'contributed_capital' => '',   //实缴资本
//            'address' => '',   //办公地点
//            'region' => '',  //经营区域
            'id_num' =>$this->userCompanyInfo['credentials_no'],  //社会信用代码
            'create_time' => time(),
            'update_time' => time(),
        );
        return $data;
    }


}