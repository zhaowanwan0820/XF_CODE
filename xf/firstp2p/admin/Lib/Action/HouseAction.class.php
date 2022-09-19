<?php

use libs\db\Db;
use libs\utils\ExportCsv;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\dao\house\HouseUserModel;
use core\service\house\HouseService;
use core\dao\house\HouseDealApplyModel;
use core\dao\house\HouseInfoModel;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;
use core\dao\CouponBindModel;
use core\event\HouseApplyEvent;

class HouseAction extends CommonAction {

    private static $SUPPLIER = array(
        1 => '深圳一房',
    );

    const CONFIG_KEY = 'house_parameters_config'; //api_conf配置名

    // 错误码
    const AJAX_SUCCESS = 0;
    const AJAX_FAILURE = 1;

    public function __construct() {
        parent::__construct();
    }

    /*
     * 获取参数配置信息
     */
    public function basicParaConfig() {

        $houseService = new HouseService();
        $data = $houseService->getHouseConfAdmin();
        $confId = $data['id'];
        $confVal = json_decode($data['value'], true);
        $cityList = $houseService->getHouseConfCityList();
        $cityListStr = '';
        foreach ($cityList as $item) {
            $cityListStr .= $item['city'].','.$item['annualized'].';';
        }
        $confVal['cityList'] = $cityListStr;

        $this->assign('confId',$confId);
        $this->assign('confVal', $confVal);
        $this->assign('paybackModes',HouseEnum::$REPAYMENT_MODES);
        $this->display();
    }

    /*
     * 保存参数配置
     */
    public function saveParaConfig() {
        $houseService = new HouseService();
        $houseConfValue = $_POST['confVal'];
        $houseConfValue['cityList'] = $this->strToArray($houseConfValue['cityList']);
        $houseConfId = $_POST['confId'];
        $res = $houseService->updateHouseConf($houseConfId,$houseConfValue);
        if(false !== $res) {
            save_log($houseConfValue.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"),0);
        } else {
            save_log($houseConfValue . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, L("UPDATE_FAILED"));
        }
    }

    private function strToArray($cityListStr)
    {
        if (empty($cityListStr)) {
            return false;
        }
        $cityList = explode(';', rtrim($cityListStr,';'));
        foreach ($cityList as $key => $item) {
            $city = explode(',', $item);
            $cityList[$key] = array(
                'city' => $city[0],
                'annualized' => $city[1]
            );
        }
        return $cityList;
    }

    /*
     * 获取借款信息
     */
    public function loanManage() {
        $id = intval($_GET['id']);
        $orderId = intval($_GET['order_id']);
        $userId = intval($_GET['user_id']);
        $timeStart = isset($_GET['time_start']) ? trim($_GET['time_start']) : '';
        $timeEnd = isset($_GET['time_end']) ? trim($_GET['time_end']) : '';
        $mobile = trim($_GET['mobile']);
        $idno = trim($_GET['idno']);
        $supplier = trim($_GET['supplier']);
        $isExport = intval($_GET['export']);

        if ($mobile != '') {
            $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
            $userId = $userInfo ? $userInfo['id'] : -1;
        }

        if ($idno != '') {
            $userInfo = UserModel::instance()->getUserByIdno($idno, 'id');
            $userId = $userInfo ? $userInfo['id'] : -1;
        }

        $queryString = " `status` >= ".HouseEnum::STATUS_CHECKING;

        if ($id > 0) {
            $queryString .= " AND id = ".$id;
        }

        if ($orderId > 0) {
            $queryString .= " AND order_id = ".$orderId;
        }

        if ($userId > 0 || $userId == -1) {
            $queryString .= " AND user_id = ".$userId;
        }

        if ($timeStart > 0) {
            $queryString .= " AND create_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd > 0) {
            $queryString .= " AND create_time <= '". strtotime($timeEnd) ."'";
        }

        if ($supplier != '') {
            $queryString .= ' AND supplier = '.$supplier;
        }

        $this->assign('supplier', self::$SUPPLIER);
        $this->model = M('HouseDealApply', 'Model', true);

        $_REQUEST ['listRows'] = 25;
        $result = $this->_list($this->model, $queryString);
        if (empty($result)) {
            $this->assign('list', array());
            $this->display();
            return;
        }
        //导出
        if ($isExport) {
            $this->exportData($queryString);
        }

        $dataList = array();
        $userCache = array();
        foreach ($result as $key=>$item) {
            $userId = $item['user_id'];
            if (empty($userCache[$userId])) {
                $userInfo = UserModel::instance()->find($userId, 'id,real_name,mobile,idno', true);
                $userCache[$userId] = $userInfo;
            } else {
                $userInfo = $userCache[$userId];
            }

            $data['id'] = intval($item['id']);
            $data['orderId'] = intval($item['order_id']);
            $data['houseId'] = intval($item['house_id']);
            $data['userId'] = intval($item['user_id']);
            $data['realName'] = userNameFormat($userInfo['real_name']);
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['idno'] = idnoFormat($userInfo['idno']);
            $data['createTime'] = date("Y-m-d H:i:s",$item['create_time']);
            $data['borrowMoney'] = number_format($item['borrow_money'],"2",".",",");
            $data['borrowDeadlineType'] = $item['borrow_deadline_type'];
            $paybackMode = intval($item['payback_mode']);
            $data['paybackMode'] = isset(HouseEnum::$REPAYMENT_MODES) ? HouseEnum::$REPAYMENT_MODES[$paybackMode] : '';

            $supplier = intval($item['supplier']);
            $data['supplier'] = isset(self::$SUPPLIER[$supplier]) ? self::$SUPPLIER[$supplier] : '';
            $isAgain = intval($item['is_again']);
            $isNcfStaff = intval($item['is_ncf_staff']);
            $data['isAgain'] = $isAgain == 1 ? '是' : '否';
            $data['isNcfStaff'] = $isNcfStaff == 1 ? '是' : '否';

            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    /*
     * 获取贷款详情
     */
    public function loanDetail() {
        $orderId = intval($_GET['orderId']);
        $houseService = new HouseService();
        $result = $houseService->getLoanDetail($orderId);
        $result['actual_money'] = number_format($result['actual_money'],"2",".",",");
        $result['extra_money'] = number_format($result['extra_money'],"2",".",",");

        if(empty($result['success_date']) || $result['success_date'] <= 0) {
            $result['success_date'] = '';
        }
        $result['success_date'] = date("Y-m-d",$result['success_date']);

        if(empty($result['plan_repay_finish_date']) || $result['plan_repay_finish_date'] <= 0) {
            $result['plan_repay_finish_date'] = '';
        }
        $result['plan_repay_finish_date'] = date("Y-m-d",$result['plan_repay_finish_date']);

        if(empty($result['actual_repay_finish_date']) || $result['actual_repay_finish_date'] <= 0) {
            $result['actual_repay_finish_date'] = '';
        }
        $result['actual_repay_finish_date'] = date("Y-m-d",$result['actual_repay_finish_date']);
        if(empty($result)){
            $this->display();
            return;
        }
        $this->assign('loanDetail',$result);
        $this->display();
    }

    /*
     * 获取还款计划
     */
    public function repayPlan() {
        $userId = intval($_GET['userId']);
        $orderId = intval($_GET['orderId']);
        $houseService = new HouseService();
        $result = $houseService->getRepayList($userId,$orderId);

        if(empty($result)){
          $this->display();
          return;
        }
        $this->assign('repayPlan',$result['payback_plan']);
        $this->display();
    }

    /*
     * 获取抵押房产详情
     */
    public function houseInfo() {
        $houseId = intval($_GET['houseId']);
        $result = HouseInfoModel::instance()->getHouseById($houseId);
        if (empty($result)) {
            $this->display();
            return;
        }

        $houseInfo = array();
        $houseInfo['city'] = $result['house_city'];
        $houseInfo['district'] = $result['house_district'];
        $houseInfo['area'] = $result['house_area'];
        $houseInfo['address'] = $result['house_address'];
        $houseInfo['value'] = json_decode($result['house_value'],true);
        foreach($houseInfo['value'] as $key => $info){
          $info['time'] = date('Y-m-d',$info['time']);
          $info['value'] = number_format($info['value'],2,".",",");
          $houseInfo['value'][$key] = $info;
        }
        $this->assign('houseInfo',$houseInfo);
        $this->display();
    }
    /*
     * 贷款材料管理
     */
    public function loanMaterial() {
        $userId = intval($_GET['user_id']);
        $mobile = trim($_GET['mobile']);

        if ($mobile != '') {
            $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
            $userId = $userInfo ? $userInfo['id'] : -1;
        }
        $queryString = " 1=1";
        if ($userId > 0 || $userId == -1) {
            $queryString .= " AND user_id = ".$userId;
        }

        $this->model = M('HouseInfo', 'Model', true);
        $count = $this->model->where($queryString)->count('DISTINCT(user_id)');

        $queryString .= " GROUP BY user_id";
        $_REQUEST ['listRows'] = 25;

        $result = $this->_list($this->model, $queryString, '', false, true, $count);

        if (empty($result)) {
            $this->assign('list', array());
            $this->display();
            return;
        }
        $dataList = array();
        $house = array();
        foreach ($result as $key=>$item) {
            $userId = $item['user_id'];
            $houseInfo = HouseInfoModel::instance()->getHouseListByUserId($userId);
            $userInfo = \core\dao\UserModel::instance()->find($userId, 'real_name,mobile,idno', true);
            $data['userId'] = intval($item['user_id']);
            $data['realName'] = userNameFormat($userInfo['real_name']);
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['idno'] = idnoFormat($userInfo['idno']);
            $data['houseInfo'] = $houseInfo;

            $dataList[] = $data;
            $house[] = $houseInfo;
        }

        $this->assign('list', $dataList);
        $this->assign('house', $house);
        $this->display();
    }
    /*
     * 获取房产评估价值
     */
    public function houseValue() {
        $houseId = intval($_GET['houseId']);
        $result = HouseInfoModel::instance()->getHouseById($houseId);
        if(empty($result)){
            $this->display();
            return;
        }
        $houseInfo = $result;
        $houseValue = json_decode($result['house_value'],true);
        foreach ($houseValue as $key => $value){
          $houseValue[$key]['time'] = date("Y-m-d",$houseValue[$key]['time']);
        }
        $this->assign('houseInfo',$houseInfo);
        $this->assign('houseValue',$houseValue);
        $this->display();
    }

    private function getVfsImage($image) {
        return is_numeric($image) ? 'm.php?m=House&a=vfsImage&imageId='.$image : $image;
    }

    /*
     * 获取身份证材料
     */
    public function idCardMaterial() {
        $userId = intval($_GET['userId']);
        $result = HouseUserModel::instance()->getUserByUserId($userId);
        if(empty($result)){
            $this->display();
            return;
        }
        $idCardMaterial = array();
        $idCardMaterial['front'] = $this->getVfsImage($result['usercard_front']);
        $idCardMaterial['back'] = $this->getVfsImage($result['usercard_back']);
        $this->assign('idCardMaterial', $idCardMaterial);
        $this->display();
    }

    public function vfsImage() {
        $imageId = trim($_GET['imageId']);
        $houseService = new HouseService();
        echo $houseService->getImageForAdmin($imageId);
    }

    /*
     * 获取房本信息(材料)
     */
    public function houseMaterial() {
        $houseId = intval($_GET['houseId']);
        $result = HouseInfoModel::instance()->getHouseById($houseId);
        if(empty($result)){
            $this->display();
            return;
        }
        $houseMaterial = array();
        $houseMaterial['first'] = $this->getVfsImage($result['house_deed_first']);
        $houseMaterial['second'] = $this->getVfsImage($result['house_deed_second']);
        $this->assign('houseMaterial',$houseMaterial);
        $this->display();
    }

    public function exportData($queryString){

        $fileName = '借款管理列表';
        $fileName = $fileName . "_" . date('Ymd') . ".csv";
        $title = array('借款ID', '用户ID', '借款人姓名','联系方式','身份证号', '申请时间','申请金额(元)',
            '期望借款期限', '申请还款方式', '融资成本','实际借款金额(元)','实际借款期限','实际还款方式',
            '实际放款金额(元)','总利息(元)','实际放款时间','预计结清日期', '实际结清日期', '借款状态',
            '抵押房产所在城市','抵押房产所在市辖区','抵押房产坐落地址','抵押房产面积', '抵押房产评估价值(元)',
            '是否一押','合作机构','是否续贷','是否集团员工');

        $exportList = array();

        $this->model = M('HouseDealApply', 'Model', true);
        $count = $this->model->where($queryString)->count();
        $_REQUEST ['listRows'] = 100;
        $pageCount = ceil($count/$_REQUEST['listRows']);

        $count = 0;
        for ($i=0;$i<$pageCount;$i++) {
            $this->model = M('HouseDealApply', 'Model', true);
            $_GET['p'] = $i+1;
            $result = $this->_list($this->model, $queryString);
            $userCache = array();
            foreach ($result as $index=>$item) {
                if (empty($item['house_id'])) {
                    Logger::error("订单".$item['order_id']."的房产编号为空！");
                    continue;
                }
                $count++;
                $userId = $item['user_id'];
                if (empty($userCache[$userId])) {
                    $userInfo = UserModel::instance()->find($userId, 'id,real_name,mobile,idno', true);
                    $userCache[$userId] = $userInfo;
                } else {
                    $userInfo = $userCache[$userId];
                }
                //用户信息
                $exportList[$count][] = intval($item['id']);
                $orderId = intval($item['order_id']);
                $houseId = intval($item['house_id']);
                $exportList[$count][] = intval($item['user_id']);
                $exportList[$count][] = userNameFormat($userInfo['real_name']);
                $exportList[$count][] = adminMobileFormat($userInfo['mobile']);
                $exportList[$count][] = idnoFormat($userInfo['idno']);

                //house_deal_apply数据表可拿到的信息
                $exportList[$count][] = ' '.date("Y-m-d H:i:s",$item['create_time']);
                $exportList[$count][] = number_format($item['borrow_money'],"2",".",",");
                $exportList[$count][] = $item['borrow_deadline_type'];
                $paybackMode = intval($item['payback_mode']);
                $exportList[$count][] = isset(HouseEnum::$REPAYMENT_MODES) ? HouseEnum::$REPAYMENT_MODES[$paybackMode] : '';
                $exportList[$count][] = $item['expect_annualized'].'%';

                //实际借款信息
                $houseService = new HouseService();
                $exportActualLoanDetail = $houseService->getLoanDetail($orderId);
                $exportList[$count][] = number_format($exportActualLoanDetail['borrow_money'],"2",".",",");
                $exportList[$count][] = $exportActualLoanDetail['borrow_deadline_type'];
                $exportList[$count][] = $exportActualLoanDetail['payback_mode'];
                $exportList[$count][] = number_format($exportActualLoanDetail['actual_money'],"2",".",",");
                $exportList[$count][] = number_format($exportActualLoanDetail['extra_money'],"2",".",",");
                if(empty($exportActualLoanDetail['success_date']) || $exportActualLoanDetail['success_date'] <= 0) {
                    $exportActualLoanDetail['success_date'] = '';
                }
                $exportList[$count][] = date("Y-m-d",$exportActualLoanDetail['success_date']);
                if(empty($exportActualLoanDetail['plan_repay_finish_date']) || $exportActualLoanDetail['plan_repay_finish_date'] <= 0 ) {
                    $exportActualLoanDetail['plan_repay_finish_date'] = '';
                }
                $exportList[$count][] = date("Y-m-d",$exportActualLoanDetail['plan_repay_finish_date']);
                if(empty($exportActualLoanDetail['actual_repay_finish_date']) || $exportActualLoanDetail['actual_repay_finish_date'] <= 0) {
                    $exportActualLoanDetail['actual_repay_finish_date'] = '';
                }
                $exportList[$count][] = date("Y-m-d",$exportActualLoanDetail['actual_repay_finish_date']);
                $exportList[$count][] = $exportActualLoanDetail['status_info'];

                //房产信息
                $exportHouseInfo = HouseInfoModel::instance()->getHouseById($houseId);
                $exportList[$count][] = $exportHouseInfo['house_city'];
                $exportList[$count][] = $exportHouseInfo['house_district'];
                $exportList[$count][] = $exportHouseInfo['house_address'];
                $exportList[$count][] = $exportHouseInfo['house_area'];
                $houseValue = json_decode($exportHouseInfo['house_value'],true);
                $houseValueContent = '';
                foreach ($houseValue as $index => $value){
                    $houseValue[$index]['time'] = date("Y-m-d",$houseValue[$index]['time']);
                    $houseValue[$index]['value'] = number_format($houseValue[$index]['value'],"2",".",",");
                    $houseValueContent .= $houseValue[$index]['time']." : ".$houseValue[$index]['value'].PHP_EOL;
                }
                $exportList[$count][] = $houseValueContent;
                $exportList[$count][] = ($exportHouseInfo['is_first'] == 1) ? '是' : '否';

                $supplier = intval($item['supplier']);
                $exportList[$count][] = isset(self::$SUPPLIER[$supplier]) ? self::$SUPPLIER[$supplier] : '';

                $isAgain = intval($item['is_again']);
                $isNcfStaff = intval($item['is_ncf_staff']);
                $exportList[$count][] = ($isAgain == 1 )? '是' : '否';
                $exportList[$count][] = ($isNcfStaff == 1) ? '是' : '否';

            }
        }
        $this->export($fileName,$title,$exportList);
    }

    public function houseReportForm() {
        $isExport = intval($_GET['export']);
        $timeStart = isset($_GET['time_start']) ? trim($_GET['time_start']) : '';
        $timeEnd = isset($_GET['time_end']) ? trim($_GET['time_end']) : '';
        $queryString = ' `status` >= '.HouseEnum::STATUS_MAKING_LOAN;

        if ($timeStart > 0) {
            $queryString .= " AND actual_success_date >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd > 0) {
            $queryString .= " AND actual_success_date <= '". strtotime($timeEnd) ."'";
        }

        $this->model = M('HouseDealApply', 'Model', true);
        $_REQUEST ['listRows'] = 25;
        $result = $this->_list($this->model, $queryString);
        if (empty($result)) {
            $this->assign('list', array());
            $this->display();
            return;
        }
        //导出
        if ($isExport) {
            $this->exportForm($queryString);
        }

        $dataList = array();
        foreach ($result as $key=>$item) {
            if (empty($item['house_id'])) {
                Logger::error("订单".$item['order_id']."的房产编号为空！");
                continue;
            }
            if (empty($item['actual_success_date'])) {
                $item['actual_success_date'] = '';
            }
            $data['actualDate'] = date("Y-m-d H:i:s",$item['actual_success_date']);
            $houseService = new HouseService();
            $exportActualLoanDetail = $houseService->getLoanDetail(intval($item['order_id']));
            $data['id'] = intval($item['id']);
            $data['supplier'] = '深圳一房';
            $data['userId'] = intval($item['user_id']);
            $data['actualMoney'] = number_format($exportActualLoanDetail['actual_money'],"2",".",",");
            $data['deadline'] = intval($item['borrow_deadline_type']);
            $annualizedMoney = $exportActualLoanDetail['actual_money']*intval($item['borrow_deadline_type'])/12;
            $data['annualized'] = number_format($annualizedMoney,"2",".",",");

            $userId = intval($item['user_id']);
            $res = UserModel::instance()->find($userId,'group_id',true);
            $groupId = intval($res['group_id']);
            $data['borrowerGroupId'] = $groupId;

            $res = UserGroupModel::instance()->find($groupId,'name',true);
            $data['borrowerGroupName'] = $res['name'];

            $userIds = array($userId);
            $res = CouponBindModel::instance()->getByUserIds($userIds,true);
            if (empty($res[$userId]['short_alias'])) {
                $data['recommendCode'] = "";
                $data['recommenderId'] = "";
                $data['recommenderGroupId'] = "";
                $data['recommenderGroupName'] = "";
                $data['recommendPercent'] = "";
                $data['recommendMoney'] = "";
            }else{
                $data['recommendCode'] = $res[$userId]['short_alias'];
                $data['recommenderId'] = $res[$userId]['refer_user_id'];
                $res = UserModel::instance()->find($data['recommenderId'],'group_id',true);
                $data['recommenderGroupId'] = intval($res['group_id']);
                $res = UserGroupModel::instance()->find($data['recommenderGroupId'],'name',true);
                $data['recommenderGroupName'] = trim($res['name']);
                $data['recommendPercent'] = "0.10%";
                $actualRebateMoney = $annualizedMoney*0.001;
                $data['recommendMoney'] = number_format($actualRebateMoney,"2",".",",");
            }
            $data['rebateOrganizationId'] = "";
            $data['rebateOrganizationName'] = "";
            $data['OrganizationPercent'] = "";
            $data['OrganizationMoney'] = "";

            $dataList[] = $data;
        }
        $this->assign('list',$dataList);
        $this->display();
    }

    public function exportForm($queryString) {
        $queryString = trim($queryString);
        $fileName = '房贷报表';
        $fileName = $fileName . "_" . date('Ymd') . ".csv";
        $title = array('借款ID','实际放款时间','借款服务机构', '借款人ID','借款金额(元)','借款期限', '返利基数(年化借款额)(元)',
            '借款人所属组别ID','借款人所属组别名称', '推荐人邀请码','推荐人ID','推荐人所属组别ID','推荐人所属组别名称',
            '推荐人返点比例','推荐人返点金额(元)','返利机构ID','返利机构名称','机构返点比例','机构返点金额(元)');

        $exportList = array();

        $this->model = M('HouseDealApply', 'Model', true);
        $count = $this->model->where($queryString)->count();
        $_REQUEST ['listRows'] = 100;
        $pageCount = ceil($count/$_REQUEST['listRows']);
        $count = 0;
        for ($i=0;$i<$pageCount;$i++) {
            $_GET['p'] = $i+1;
            $result = $this->_list($this->model, $queryString);
            foreach ($result as $index=>$item) {
                if (empty($item['house_id'])) {
                    Logger::error("订单".$item['order_id']."的房产编号为空！");
                    continue;
                }
                $count++;
                $exportList[$count][] = intval($item['id']);
                if (empty($item['actual_success_date'])) {
                    $item['actual_success_date'] = '';
                }
                $exportList[$count][] = ' '.date("Y-m-d H:i:s",$item['actual_success_date']);
                $houseService = new HouseService();
                $exportActualLoanDetail = $houseService->getLoanDetail($item['order_id']);
                $exportList[$count][] = "深圳一房";
                $exportList[$count][] = intval($item['user_id']);
                $exportList[$count][] = number_format($exportActualLoanDetail['actual_money'],"2",".",",");
                $exportList[$count][] = $item['borrow_deadline_type'];
                $annualizedMoney = $exportActualLoanDetail['actual_money']*intval($item['borrow_deadline_type'])/12;
                $exportList[$count][] = number_format($annualizedMoney,"2",".",",");

                $userId = intval($item['user_id']);
                $res = UserModel::instance()->find($userId,'group_id',true);
                $groupId = intval($res['group_id']);
                $exportList[$count][] = $groupId;

                $res = UserGroupModel::instance()->find($groupId,'name',true);
                $exportList[$count][]  = $res['name'];
                $userIds = array($userId);
                $res = CouponBindModel::instance()->getByUserIds($userIds,true);
                if (empty($res[$userId]['short_alias'])) {
                    $exportList[$count][] = "";
                    $exportList[$count][] = "";
                    $exportList[$count][] = "";
                    $exportList[$count][] = "";
                    $exportList[$count][] = "";
                    $exportList[$count][] = "";

                }else{
                    $exportList[$count][] = $res[$userId]['short_alias'];
                    $referUserId = $res[$userId]['refer_user_id'];
                    $exportList[$count][] = $referUserId;
                    $res = UserModel::instance()->find($referUserId,'group_id',true);
                    $referGroupId = intval($res['group_id']);
                    $exportList[$count][] = $referGroupId;
                    $res = UserGroupModel::instance()->find($referGroupId,'name',true);
                    $data['recommenderGroupName'] = trim($res['name']);
                    $exportList[$count][] = "0.10%";
                    $actualRebateMoney = $annualizedMoney*0.001;
                    $exportList[$count][] = number_format($actualRebateMoney,"2",".",",");
                }
                $exportList[$count][] = "";
                $exportList[$count][] = "";
                $exportList[$count][] = "";
                $exportList[$count][] = "";
            }
        }
        $this->export($fileName,$title,$exportList);
    }

    public function export($fileName,$title,$exportList) {
        $csv = new ExportCsv();
        $csv->setExportName($fileName);
        $csv->setExportTitle($title);
        $csv->setExportData($exportList);
        $csv->export($fileName, $title, $exportList);
        return;
    }

    public function commitApply()
    {
        $orderId = intval($_POST['orderId']);

        if (empty($orderId)) {
            $this->error('订单id不合法: '.$_POST['orderId']);
            return false;
        }
        $houseService = new HouseService();
        $loanDetail = $houseService->getLoanDetail($orderId);
        if (empty($loanDetail['user_id'])) {
            $this->error('用户id不合法: '.$loanDetail['user_id']);
            return false;
        }
        $houseUserModel = HouseUserModel::instance();
        $houseUser = $houseUserModel->getUserByUserId($loanDetail['user_id']);

        // 房产信息中 正反面身份证信息
        $user_info = array(
            'usercard_front' =>$houseUser['usercard_front'],
            'usercard_back' =>$houseUser['usercard_back']
        );
        // 申请记录信息
        $apply_info = array(
            'order_id' => $orderId,
            'house_id' => $loanDetail['house_id'],
            'borrow_money' => $loanDetail['borrow_money'],
            'borrow_deadline_type' => $loanDetail['borrow_deadline_type'],
            'is_ncf_staff' => $loanDetail['is_ncf_staff'],
            'is_again' => $loanDetail['is_again']
        );
        // get userinfo
        $condition = ' id = '.$loanDetail['user_id'];
        $userPrivateInfo = UserModel::instance()->findBy($condition);

        $other_info = array(
            'real_name' => $userPrivateInfo['real_name'],
            'phone' => $userPrivateInfo['mobile'],
            'usercard_id' => $userPrivateInfo['idno'],
        );
        $commitInfo = array(
            'user_info' => $user_info,
            'apply_info' => $apply_info,
            'other_info' => $other_info
        );
        $houseApplyEvent = new HouseApplyEvent($commitInfo);
        try {
            $result = $houseApplyEvent->execute();
            return $this->ajaxReturn($result, 'success', self::AJAX_SUCCESS);
        } catch (\Exception $ex) {
            $this->ajaxReturn($ex->getMessage(), 'failure', self::AJAX_FAILURE);
        }
    }
}
