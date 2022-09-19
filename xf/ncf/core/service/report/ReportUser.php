<?php

namespace core\service\report;

use core\dao\report\ReportUserModel;
use core\service\report\ReportBase;
use core\service\user\UserService;
use core\dao\deal\DealModel;
use libs\utils\DBDes;

class ReportUser extends ReportBase
{
    public $userId;

    public $dealId;

    public function __construct($dealId)
    {

        $this->dealId = $dealId;
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->userId = $this->dealInfo['user_id'];
        $this->userInfo = UserService::getUserById($this->userId,'real_name,id_type,idno,mobile');
    }

    public function collectData()
    {
        $data = [
            'user_id' => $this->userId,
            'deal_id' => $this->dealId,
            'project_id' => $this->dealInfo->project_id,  //项目编号
            'name' => $this->userInfo['real_name'],  // 借款人姓名
            'id_type' => $this->getCardType($this->userInfo['id_type']),    //证件类型
            'id_num' => $this->userInfo['idno'],   //证件号码
            'work' => '',   //工作性质
            'extra_info' => '',  //其他借款信息
//            'credit_report' => '暂时无法提供', //征信报告
            'overdue_times' => '', //逾期次数
            'overdue_money' => '',  //逾期金额
            'incoming_debt' => '',  //收入及负债情况
            'create_time' => time(),
            'update_time' => time(),
            'mobile' =>$this->userInfo['mobile'],     //手机号码
        ];
//        $data['id_num'] = DBDes::encryptOneValue($data['id_num']);
//        $data['mobile'] = DBDes::encryptOneValue($data['mobile']);
        return $data;
    }


    private function getCardType($idType){
        $firstp2pIdType = array(
            1 => '身份证',
            4 => '港澳居民来往内地通行证/港澳台身份证',
            6 => '台湾居民往来大陆通行证/台湾身份证',
            2 => '护照',
            3 => '军官证',
            'default' => '身份证', // 默认
        );
        $reportCardType = array(
            '0' => '身份证',
            'A' => '香港身份证',
            'B' => '澳门身份证',
            'C' => '台湾身份证',
            'X' => '其他证件',
            '1' => '户口簿',
            '2' => '护照',
            '3' => '军官证',
            '4' => '士兵证',
            '5' => '港澳台居民来往内地通行证',
            '6' => '台湾同胞来往内地通行证',
            '7' => '临时身份证',
            '8' => '外国人居留证',
            '9' => '警官证',
        );

        return in_array($firstp2pIdType[$idType],$reportCardType) ? array_search($firstp2pIdType[$idType],$reportCardType) :'';

    }

}