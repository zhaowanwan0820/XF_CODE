<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26772969
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;

use core\dao\ifapush\IfaUserModel;
use core\dao\risk\UserRiskAssessmentModel;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use core\service\ifapush\PushBase;
use core\service\user\BankService;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;

class PushUser extends PushBase
{
    public $userId;

    public $userInfo;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->dbModel = new IfaUserModel();
    }

    public function collectData()
    {
        $userInfo = UserService::getUserById($this->userId,'user_type,country_code,real_name,id_type,mobile,email,create_time,idno');
        $this->userInfo = $userInfo;

        $userIdcard = $userInfo['idno'];

        $userPhone = $userInfo['mobile'];
        if($userInfo['user_type'] == 1){
            $userCompInfo = UserService::getEnterpriseInfo($this->userId);
            $userIdcard = $userCompInfo['credentials_no'];

            $ec = UserService::getEnterpriseContactByUserId($this->userId);

            $userPhone = $ec['contract_mobile'];
            $userPhone = empty($userPhone) ? $userCompInfo['legalbody_mobile'] : $userPhone;
        }

        $bankCardInfo = BankService::getNewCardByUserId($this->userId);
        $bankInfo = $bankCardInfo ? BankService::getBankInfoByBankId($bankCardInfo['bank_id']) : '';

        $data = [
            'order_id' => Idworker::instance()->getId(),
            'userId' => $this->userId,
            'userIdcard'=> $userIdcard,
            'userCreateTime' => date('Y-m-d H:i:s',($userInfo['create_time']+28800)),
            'userStatus' => 1, // 1-新增／2-变更／3-失效
            'userType' => ($userInfo['user_type'] == 0) ? 1 : 2, // 用户类型 1-自然人／2-企业
            'userAttr' => $this->getUserAttr(), // 用户属性 1-投资／2-借贷／3-投资＋借贷
            'userName' => $userInfo['real_name'], // 投资人／借款人姓名／企业名称 
            'countries' => $this->getCountries($userInfo['country_code']), // 1-中国大陆；2-中国港澳台；3-国外
            'cardType' => $this->getCardType(), // 1-身份证 2-护照 3-军官
            'userPhone' => !empty($userPhone) ? $userPhone : '-1', // 用户联系手机号
            'userLawperson' => ($userInfo['user_type'] == 0) ? -1 : ($userCompInfo['legalbody_name'] ? $userCompInfo['legalbody_name'] : -1), // 法人代表
            'userFund' => ($userInfo['user_type'] == 0) ? -1 : $userCompInfo['reg_amt']/10000, // 注册资金 (万元)
            'userAddress' => ($userInfo['user_type'] == 0) ? -1 : (empty($userCompInfo['registration_address']) ? '-1' : $userCompInfo['registration_address']), // 注册地址
            'registerDate' => $userInfo['user_type'] == 0 ? -1 : $userCompInfo['credentials_expire_date'],
            'userMail' => $userInfo['email'], // 注册人邮箱
            'userSex' => ($userInfo['user_type'] == 0) ? (isset($userInfo['userSex']) ? $userInfo['userSex'] : 0) : -1, // 用户性别 默认女
            'riskRating' => $this->getRiskRating(), // 风险评 用大写字母 A～Z 来表示  --firstp2p_user_risk_assessment
            'userPay' => '海口联合农商银行', // 用户的第三 方支付平台名称 ／用户的存 管银行名称
            'userPayAccount' => $this->userId, // 用户的第三方支付账号 ／用户的存管银行账号
            'userBank' => !empty($bankInfo['name']) ? $bankInfo['name'] : '-1', // 用户关联银行
            'userBankAccount' => !empty($bankCardInfo['bankcard']) ? $bankCardInfo['bankcard'] : '-1', // 用户关联银行账号
        ];
        return $data;
    }

    // 1-投资／2-借贷／3-投资＋借贷
    private function getUserAttr(){
        $accountType = AccountService::getAccountType($this->userId);
        if($accountType == UserAccountEnum::ACCOUNT_INVESTMENT){
            return 1;
        }elseif($accountType == UserAccountEnum::ACCOUNT_FINANCE){
            return 2;
        }else{
            return 3;
        }
    }

    private function getCardType(){
        if($this->userInfo['user_type'] == 1){
            return 7;
        }
        $firstp2pIdType = array(
            1 => 'IDC', // 身份证
            4 => 'GAT', // 港澳居民来往内地通行证/港澳台身份证
            6 => 'GAT', // 台湾居民往来大陆通行证/港澳台身份证
            2 => 'PASS_PORT', // 护照
            3 => 'MILIARY', // 军官证
            'default' => 'IDC', // 默认
        );
        $ifaCardType = array(
            1 => '身份证',
            2 => '护照',
            3 => '军官证'
        );
        return array_key_exists($this->userInfo['id_type'],$ifaCardType) ? $this->userInfo['id_type'] : 1;
    }

    // 1-中国大陆；2-中国港澳 台；3-国外
    private function getCountries($s){
        $con = array(
            'cn' => 1,
            'hk' => 2,
            'mo' => 2,
            'tw' => 2,
        );
        return array_key_exists($s,$con) ? $con[$s] : 3;
    }

    private function getRiskRating(){
        $risks = array(
            '进取型' => 'Z',
            '策略型' => 'Y',
            '稳健型' => 'X',
            '保守型' => 'W',
            '风险厌恶型' => 'V',
        );
        $risk = UserRiskAssessmentModel::instance()->getURA($this->userId);
        return array_key_exists($risk['last_level_name'],$risks) ? $risks[$risk['last_level_name']] : '-1';
    }
}