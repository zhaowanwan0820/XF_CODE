<?php
/**
 * CouponBindAction.class.php.
 *
 * 理财师邀请码修改管理
 *
 * @date 2015-07-08
 *
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */
use libs\utils\Logger;
use core\service\CouponService;
use core\service\CouponLevelService;
use core\dao\EnterpriseContactModel;
use core\dao\UserModel;
use core\service\UserGroupService;
use core\service\CouponBindService;

class CouponBindAction extends CommonAction
{
    public function __construct(){
        parent::__construct();
        $this->pageEnable = isset($_REQUEST['_page']) && $_REQUEST['_page'] == 1 ? true : false;
    }
    /**
     * 用户服务人列表.
     */
    public function index($type = 1)
    {
        if (empty($_REQUEST['_order'])) {
            $_REQUEST['_order'] = 'update_time';
            $_REQUEST['_sort'] = 0;
        }

        // 列表过滤器，生成查询Map对象
        $map = $this->_search();
        if (!empty($_REQUEST['user_num'])) {
            $map['user_id'] = de32Tonum($_REQUEST['user_num']);
            unset($map['user_num']);
        }
        if (!empty($_REQUEST['operator'])) {
            $admin_id = M('Admin')->where("adm_name='".$_REQUEST['operator']."'")->getField('id');
            if (!empty($admin_id)) {
                $map['admin_id'] = $admin_id;
            } else {
                $map['admin_id'] = '-1';
            }
        }
        if (!empty($_REQUEST['user_mobile'])) {
            $user_info = \core\dao\UserModel::instance()->getUserinfoByUsername($_REQUEST['user_mobile']);
            if (!empty($user_info)) {
                $map['user_id'] = $user_info['id'];
                unset($map['user_mobile']);
            } else {
                $map['user_id'] = '-1';
            }
        }
        $operationTime = trim($_REQUEST['begin']);
        $operationTimeEnd = trim($_REQUEST['end']);
        if ($operationTime) {
            $map['update_time'] = array('between', to_timespan($operationTime).','.to_timespan($operationTimeEnd));
        }
        /* 邀请码转理财师id */
        if (!empty($_REQUEST['short_alias'])) {
            $couponService = new CouponService();
            $refer_user_id = $couponService->shortAliasToReferUserId($_REQUEST['short_alias']);
            if ($refer_user_id) {
                $map['refer_user_id'] = $refer_user_id;
            }
        }

        /* 邀请码转理财师id */
        if (!empty($_REQUEST['invite_code'])) {
            $couponService = new CouponService();
            $invite_user_id = $couponService->shortAliasToReferUserId($_REQUEST['invite_code']);
            if ($invite_user_id) {
                $map['invite_user_id'] = $invite_user_id;
            }
        }

        if (!empty($_REQUEST['refer_user_mobile'])) {
            $refer_user_info = \core\dao\UserModel::instance()->getUserinfoByUsername($_REQUEST['refer_user_mobile']);
            if (!empty($refer_user_info)) {
                $map['refer_user_id'] = $refer_user_info['id'];
                unset($map['refer_user_mobile']);
            } else {
                $map['refer_user_id'] = '-1';
            }
        }

        if (!empty($_REQUEST['invite_user_mobile'])) {
            $invite_user_info = \core\dao\UserModel::instance()->getUserinfoByUsername($_REQUEST['invite_user_mobile']);
            if (!empty($invite_user_info)) {
                $map['invite_user_id'] = $invite_user_info['id'];
                unset($map['invite_user_mobile']);
            } else {
                $map['invite_user_id'] = '-1';
            }
        }

        if (!empty($map['refer_user_id']) || !empty($map['refer_user_id'])) {
            unset($_REQUEST['_order']);
            unset($_REQUEST['_sort']);
        }
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $this->model = MI(MODULE_NAME);
        if (!empty($this->model)) {
            $this->_list($this->model, $map);
        }

        if (CouponBindService::TYPE_INVITE == $type) {
            $this->display('inviteIndex');
        } elseif(CouponBindService::TYPE_DISCOUNT == $type) {
            $this->display('discountIndex');
        }else{
            $this->display();
        }

        return;
    }

    //邀请人列表
    public function inviteIndex()
    {
        $this->index(CouponBindService::TYPE_INVITE);
    }

    public function discountIndex()
    {
        $this->index(CouponBindService::TYPE_DISCOUNT);
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            if (!empty($item['user_name'])) {
                $item['user_name'] = userNameFormat($item['user_name']);
            }
            if (!empty($item['refer_user_name'])) {
                $item['refer_user_name'] = userNameFormat($item['refer_user_name']);
            }
            $item['refer_user_id'] = $item['refer_user_id']==0? '':$item['refer_user_id'];
            $item['invite_user_id'] = $item['invite_user_id']==0? '':$item['invite_user_id'];
            $user = $this->get_user_data($item['user_id']);
            $item['user_num'] = $this->_get_user_link($item['user_id'], numTo32($item['user_id']));
            $refer_user = $this->get_user_data($item['refer_user_id']);
            $invite_user = $this->get_user_data($item['invite_user_id']);
            $user_group =$this->get_group_data(intval($user['group_id']));
            $refer_user_group =$this->get_group_data(intval($refer_user['group_id']));
            $invite_user_group =$this->get_group_data(intval($invite_user['group_id']));
            $invite_user_group_fixed =$this->get_group_data(intval($item['invite_user_group_id']));
            $item['real_name'] = empty($user) ? '' : $user['real_name'];
            $item['refer_real_name'] = empty($refer_user) ? '' : $refer_user['real_name'];
            $item['invite_real_name'] = empty($invite_user) ? '' : $invite_user['real_name'];
            $item['is_fixed'] = $item['is_fixed'] ? '已绑定' : '未绑定';
            $item['user_group_name'] = isset($user_group['name'])?$user_group['name']:'';
            $item['refer_user_group_name'] = isset($refer_user_group['name'])?$refer_user_group['name']:'';
            $item['invite_user_group_name'] = isset($invite_user_group['name'])?$invite_user_group['name']:'';
            $item['invite_user_group_name_fixed'] = isset($invite_user_group_fixed['name'])?$invite_user_group_fixed['name']:'';
            $item['service_status'] = isset($refer_user_group['service_status'])? (intval($refer_user_group['service_status'])?"有效":'无效'):'';
            $item['refer_real_name'] = $this->_get_user_link($item['refer_user_id'], $item['refer_real_name']);
            $item['invite_real_name'] = $this->_get_user_link($item['invite_user_id'], $item['invite_real_name']);
            //邀请码动态生成
            $couponService = new CouponService();
            if(!empty($item['refer_user_id'])){
                $item['short_alias'] = $couponService->userIdToHex($item['refer_user_id']);
            }
            if(!empty($item['invite_user_id'])){
                $item['invite_code'] = $couponService->userIdToHex($item['invite_user_id']);
            }
            $item['user_id'] = intval($item['user_id']);
            if (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
                $userMobile = EnterpriseContactModel::instance()->findByViaSlave(" user_id = '{$item['user_id']}'", 'major_mobile');
                $item['user_mobile'] = adminMobileFormat($userMobile['major_mobile']);
            } else {
                $item['user_mobile'] = adminMobileFormat($user['mobile']);
            }

            if (!empty($refer_user)) {
                if (UserModel::USER_TYPE_ENTERPRISE == $refer_user['user_type']) {
                    $referUserMobile = EnterpriseContactModel::instance()->findByViaSlave(" user_id = '{$item['refer_user_id']}'", 'major_mobile');
                    $item['refer_user_mobile'] = adminMobileFormat($referUserMobile['major_mobile']);
                } else {
                    $item['refer_user_mobile'] = adminMobileFormat($refer_user['mobile']);
                }
            }

            if (!empty($invite_user)) {
                if (UserModel::USER_TYPE_ENTERPRISE == $invite_user['user_type']) {
                    $inviteUserMobile = EnterpriseContactModel::instance()->findByViaSlave(" user_id = '{$item['invite_user_id']}'", 'major_mobile');
                    $item['invite_user_mobile'] = adminMobileFormat($inviteUserMobile['major_mobile']);
                } else {
                    $item['invite_user_mobile'] = adminMobileFormat($invite_user['mobile']);
                }
            }

            //字段格式化放在sql后，否则user_id被格式化成链接会导致sql报错;
            //user_id格式化放在real_name之后，否则real_name字段会被格式化成链接中的链接
            $item['real_name'] = $this->_get_user_link($item['user_id'], $item['real_name']);
            $item['user_id_url'] = $this->_get_user_link($item['user_id'], $item['user_id']);
            $item['refer_user_id'] = $this->_get_user_link($item['refer_user_id'], $item['refer_user_id']);
        }
    }

    /**
     * 获取用户信息.
     */
    protected function get_user_data($user_id)
    {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return array();
        }
        static $user_info = array();
        if (!isset($user_info[$user_id])) {
            $user_data = \core\dao\UserModel::instance()->find($user_id, 'user_name,real_name,coupon_level_id,mobile,user_type,group_id',true);
            if ($user_data) {
                $user_info[$user_id] = $user_data->getRow();
            } else {
                return array();
            }
        }

        return $user_info[$user_id];
    }

    protected function get_group_data($group_id){
        $group_id = intval($group_id);
        if ($group_id <= 0) {
            return array();
        }
        static $group_info = array();
        if (!isset($group_info[$group_id])) {
            $group_data = \core\dao\UserGroupModel::instance()->find($group_id, 'name,service_status',true);
            if ($group_data) {
                $group_info[$group_id] = $group_data->getRow();
            } else {
                return array();
            }
        }

        return $group_info[$group_id];
    }

    /**
     * 修改用户打折系数
     */
    public function changeDiscountRatio(){
        \FP::import('libs.utils.logger');
        $ajax = intval($_REQUEST['ajax']);
        // 获取登录人session
        $adm_session = es_session::get(md5(conf('AUTH_KEY')));

        $discount_ratio = bcadd(trim($_REQUEST['discount_ratio']),0,2); // 新打折系数

        $user_ids = trim($_REQUEST['user_ids']);
        if (empty($user_ids)) {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    '投资人id不能为空',
                    'user_ids '.json_encode($user_ids),
                    $discount_ratio,
            )));
            $this->error('请选择要设置的用户', $ajax);
            return;
        }
        $user_ids = explode(',', $user_ids);
        if (bccomp($discount_ratio, 0,2) < 0  || bccomp($discount_ratio, 1,2) > 0 ) {
                Logger::info(implode(' | ', array(
                        __CLASS__,
                        __FUNCTION__,
                        __LINE__,
                        '打折系数比例区间错误',
                        'user_ids '.json_encode($user_ids),
                        $discount_ratio,
                )));
                $this->error('数据格式错误', $ajax);
        }

        //记录修改前的记录，用来误操作之后恢复
        $couponBindService = new CouponBindService();
        $old_data = $couponBindService->getByUserIds($user_ids);
        $result = $couponBindService->setDiscountRatioByUserIds($discount_ratio, $user_ids, intval($adm_session['adm_id']));
        if ($result) {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    $type,
                    '设置打折系数成功',
                    'old_short_aliases '.json_encode($old_data),
                    'user_ids '.json_encode($user_ids),
                    "discount_ratio '".$discount_ratio."'",
            )));
            foreach ($user_ids as $user_id) {
                save_log('set_discount_ratio-user_id:'.$user_id, 1, $old_data[$user_id]['discount_ratio'], $discount_ratio);
            }
            $this->display_success('操作成功', $ajax);
        } else {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    $type,
                    '设置打折系数失败',
                    'user_ids '.json_encode($user_ids),
                    "discount_ratio '".$discount_ratio."'",
            )));
            $this->error('操作失败', $ajax);
        }
    }

    /**
     * 批量修改邀请人.
     **/
    public function changeInviteCode()
    {
        $this->changeShortAlias(CouponBindService::TYPE_INVITE);
    }

    /**
     * 批量修改投资人服务人.
     */
    public function changeShortAlias($type = 1)
    {
        \FP::import('libs.utils.logger');
        $ajax = intval($_REQUEST['ajax']);
        // 获取登录人session
        $adm_session = es_session::get(md5(conf('AUTH_KEY')));

        $new_short_alias = addslashes(trim($_REQUEST['new_short_alias'])); // 新邀请码

        $user_ids = trim($_REQUEST['user_ids']);
        if (empty($user_ids)) {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    $type,
                    '投资人id不能为空',
                    'user_ids '.json_encode($user_ids),
                    $new_short_alias,
            )));
            $this->error('请选择要替换的用户', $ajax);
            return;
        }
        $user_ids = explode(',', $user_ids);
        $couponService = new \core\service\CouponService(); // 初始化邀请码service
        $couponBindService = new \core\service\CouponBindService(); // 初始化邀请码绑定service
        if (!empty($new_short_alias)) {
            // 验证邀请码是否有效
            $result = $couponService->checkCoupon($new_short_alias);
            if (!$result || $result['coupon_disable']) {
                Logger::info(implode(' | ', array(
                        __CLASS__,
                        __FUNCTION__,
                        __LINE__,
                        $type,
                        '邀请码无效',
                        'user_ids '.json_encode($user_ids),
                        $new_short_alias,
                )));
                $this->error('邀请码无效', $ajax);
            }
        }

        // 记录修改前的邀请码，用来误操作之后恢复
        $old_short_aliases = $couponBindService->getByUserIds($user_ids);

        // 批量修改投资人邀请码
        $result = $couponBindService->updateByUserIds($new_short_alias, $user_ids, intval($adm_session['adm_id']), $type);
        if ($result) {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    $type,
                    '更新邀请码成功',
                    'old_short_aliases '.json_encode($old_short_aliases),
                    'user_ids '.json_encode($user_ids),
                    "new_short_alias '".$new_short_alias."'",
            )));
            foreach ($user_ids as $user_id) {
                save_log('user_id:'.$user_id, 1, $old_short_aliases, $new_short_alias);
            }
            $this->display_success('操作成功', $ajax);
        } else {
            Logger::info(implode(' | ', array(
                    __CLASS__,
                    __FUNCTION__,
                    __LINE__,
                    $type,
                    '更新邀请码失败',
                    'user_ids '.json_encode($user_ids),
                    "new_short_alias '".$new_short_alias."'",
            )));
            $this->error('操作失败', $ajax);
        }
    }

    //批量修改邀请人
    public function importCsvInviteCode()
    {
        return $this->importCsvShortAlias(CouponBindService::TYPE_INVITE);
    }

    /**
     * 批量修改服务人.
     */
    public function importCsvShortAlias($type = 1)
    {
       $data = $this->checkCsvData();
       $adm_session = es_session::get(md5(conf('AUTH_KEY')));
       $couponBindService = new CouponBindService();
        foreach ($data['data'] as $value) {
            $result = $couponBindService->updateByUserIds($value['shortAlias'],array($value['userId']), intval($adm_session['adm_id']), $type);
                if(empty($result)){
                    unset($value['userId']);
                    $value['errorMsg'] = '数据库错误';
                    $data['errorData'][] = $value;
                }
        }
    
        if(empty($data['errorData'])){
            $this->success('导入成功,没有错误数据');
        }else{
            $this->assign('errorData',$data['errorData']);
            $this->display('error');
        }
    }

    /**
     * 校验csv数据
     */
    public function checkImportCsvShortAlias(){
        $data = $this->checkCsvData();
        if(empty($data['errorData'])){
            $this->success('没有错误数据');
        }else{
            $this->assign('errorData',$data['errorData']);
            $this->display('error.html');
        }
    }

    public function download_csv_datas()
    {
        $error_str = $_REQUEST['error_data'];
        $content = implode(',', array(
            '序号',
            '投资人姓名',
            '会员编号',
            '邀请码',
            '错误信息',
        ))."\n";
        $content .= $error_str;
        $datatime = date('YmdHis', get_gmtime());

        header("Content-Disposition: attachment; filename=couponbind_error_data_{$datatime}.csv");
        echo iconv('utf-8', 'gbk//ignore', $content);
        exit();
    }

    public function download_tpl()
    {
        $t = intval($_REQUEST['t']);
        $content = implode(',', array(
            '序号',
            '投资人姓名',
            '会员编号',
            $t==1?'新邀请人邀请码':'新服务人邀请码',
        ))."\n";

        header('Content-Disposition: attachment; filename=importReferUser.csv');
        echo iconv('utf-8', 'gbk//ignore', $content);
        exit;
    }

    /**
     *验证csv数据
     */
    private function checkCsvData(){

        if (empty($_FILES['upfile']['name'])) {
            $this->error('上传的文件不能为空');
        }
        if ('csv' != end(explode('.', $_FILES['upfile']['name']))) {
            $this->error('请上传csv格式的文件！');
        }

        $csvData = $this->getCsvData();
        if(empty($csvData)){
            $this->error('上传数据为空！');
        }

        if (count($csvData) > 2000) {
            $this->error('最大导入2000条数据');
        }

        $data = array('errorData'=>array(),'data'=>array());
        foreach ($csvData as $value) {
                $result = $this->checkCsvRowData($value);
                if($result['errorMsg'] !== ''){
                    unset($result['userId']);
                    $data['errorData'][] = $result;
                }else{
                    $data['data'][] = $result;
                }
        }

        return $data;
    }


    /**
     *获取Csv数据
     */
    private function getCsvData(){

        if (empty($_FILES['upfile']['name'])) {
            $this->error('上传的文件不能为空');
        }

        if ('csv' != end(explode('.', $_FILES['upfile']['name']))) {
            $this->error('请上传csv格式的文件！');
        }

        $data = array();
        if (false !== ($handle = fopen($_FILES['upfile']['tmp_name'], 'r'))) {
          while (false !== ($row_data = fgetcsv($handle))) {
                $data[] = $row_data;
            }
        }
        array_shift($data);
        return $data;
    }

    /**
     *检查每一行数据
     */
    private function checkCsvRowData($data,$riskCheck = false){

        $num = isset($data[0])?intval($data[0]):0;
        $realName = isset($data[1])? iconv('gbk', 'utf-8', trim($data[1])):'';
        $userNum = isset($data[2])?trim($data[2]):'';
        $shortAlias = isset($data[3])?trim($data[3]):'';
        $errorMsg = '';
        try {
            if (empty($num)) {
                throw new \Exception("序号不能为空");
            }

            if (empty($userNum)) {
                throw new \Exception("会员编码不能为空");
            }

            //读存库
            $userInfo= \core\dao\UserModel::instance()->find(de32Tonum($userNum),'id,real_name,group_id,create_time',true);
            if (empty($userInfo)) {
                throw new \Exception("用户不存在");
            }

            if ($userInfo['real_name'] != $realName) {
                throw new \Exception("用户编码和真实姓名不匹配");
            }

            if (!empty($shortAlias)) {
                $couponService = new \core\service\CouponService();
                $coupon = $couponService->checkCoupon($shortAlias);
                if (empty($coupon) || $coupon['coupon_disable']) {
                    throw new \Exception("邀请码无效");
                }
            }

            //风险数据校验
            if($riskCheck){
                $userBasicGroupModel = new UserBasicGroupModel();
                $userBasicGroupInfo = $userBasicGroupModel->find($userInfo['group_id'], 'rebate_effect_days');
                $rebate_effect_days = $userBasicGroupInfo['rebate_effect_days'];
                if($rebate_effect_days < (time()-$userInfo['create_time'])/86400 ){
                    throw new \Exception("注册{$rebate_effect_days}天之内的用户不可以操作服务关系");
                }

                $couponBindService = new \core\service\CouponBindService();
                $couponBindInfo = $couponBindService->getByUserId($userInfo['id']);
                if(!empty($shortAlias)){
                    if(empty($couponBindInfo['refer_user_id'])){
                        throw new \Exception("该用户无旧服务人");
                    }
                }else{
                    if(!empty($couponBindInfo['refer_user_id'])){
                        throw new \Exception("该用户无新服务人");
                    }
                }

                if($userInfo['group_id'] != $coupon['group_id']){
                    throw new \Exception("旧服务人与新服务人属于不同机构");
                }
            }
            
            } catch (Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return array(
                'num' =>$num,
                'realName' =>$realName,
                'userNum' => $userNum,
                'shortAlias' =>$shortAlias,
                'userId' => $userInfo['id'],
                'errorMsg' => $errorMsg
            );

    }

    /**
     * user_id转用户名称并加链接.
     *
     * @param $user_id
     * @param $text
     *
     * @return string
     */
    private function _get_user_link($user_id, $text)
    {
        return "<a href='".u('User/index', array('user_id' => $user_id))."' target='_blank'>".$text.'</a>';
    }


    public function superChangeShortAlias()
    {
        $size = intval($_REQUEST['size'])? intval($_REQUEST['size']):200;
        $this->assign("size", $size);
        $this->display();
    }

    public function superChangeDiscountRatio()
    {
        $size = intval($_REQUEST['size'])? intval($_REQUEST['size']):200;
        $this->assign("size", $size);
        $this->display();
    }

    /**
     *修改用户绑定邀请码
     */
    public function changeShortAliasFromData()
    {
        $errorData = array();
        $data = $_REQUEST['data'];

        if (empty($data)) {
            $this->error("请选择要替换的用户", 1);
        }

        foreach ($data as $key => &$row) {
            try {
                $result =$this->checkData($row);
                $this->updateData($result);
            } catch (\Exception $e) {
                $row[] = $e->getMessage();
                $errorData[] = $row;
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,__LINE__,"data " . json_encode($row))));
            }
            unset($data[$key]);
        }

        if (!empty($errorData)) {
            $this->ajaxReturn($errorData, "有错误数据", 0);
        } else {
            $this->success("处理成功", 1);
        }
    }


    /**
     *修改客户打折系数
     */
    public function changeDiscountRatioFromData()
    {
        $errorData = array();
        $data = $_REQUEST['data'];

        if (empty($data)) {
            $this->error("请选择要设置的用户", 1);
        }

        $couponService = new CouponBindService();
        foreach ($data as $key => &$row) {
            try {
                $result =$this->checkDiscountData($row);
                $this->updateDiscountData($result);
            } catch (\Exception $e) {
                $row[] = $e->getMessage();
                $errorData[] = $row;
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,__LINE__,"data " . json_encode($row))));
            }
            unset($data[$key]);
        }

        if (!empty($errorData)) {
            $this->ajaxReturn($errorData, "有错误数据", 0);
        } else {
            $this->success("处理成功", 1);
        }
    }

    /**
    *下载csv文件
    */
    public function downCSVTpl($content = '',$header = false)
    {
        $header = !empty($header)? $header : array('序号','真实姓名','会员编号','新服务人邀请码');
        $content = implode(',', $header )."\n".$content;
        header("Content-Disposition: attachment; filename=import_data_csv_tpl.csv");
        echo iconv('utf-8', 'gbk//ignore', $content);
    }

    public function downDiscountCSVTpl(){
        $header = array('序号','投资人姓名','投资人会员编号','新客户系数');
        $this->downCSVTpl('',$header);
    }


    private function updateData($data)
    {
        //对于重复更新的数据，不在处理
        static $successData = array();
        $key = md5($data['realName']."-".$data['userNum']."-".$data['shortAlias']."-".$data['userId']);
        if (isset($successData[$key]) && $successData[$key] == $data['userId']) {
            return true;
        }

        $successData[$key] = $data['userId'];
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $couponBindService = new \core\service\CouponBindService();
        $result = $couponBindService->updateByUserIds($data['shortAlias'], array($data['userId']), intval($adm_session ["adm_id"]));
        if (empty($result)) {
            throw new \Exception("更新邀请码信息失败");
        }
    }

    /**
     *修改客户系数
     */
    private function updateDiscountData($data)
    {
        //对于重复更新的数据，不在处理
        static $successData = array();
        $key = md5($data['realName']."-".$data['userNum']."-".$data['discountRatio']."-".$data['userId']);
        if (isset($successData[$key]) && $successData[$key] == $data['userId']) {
            return true;
        }

        $successData[$key] = $data['userId'];
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $couponBindService = new \core\service\CouponBindService();
        $couponBindInfo = $couponBindService->getByUserId($data['userId']);
        $result = $couponBindService->setDiscountRatioByUserIds($data['discountRatio'], array($data['userId']), intval($adm_session ["adm_id"]));
        if (empty($result)) {
            throw new \Exception("修改客户系数失败");
        }
        save_log('set_discount_ratio-user_id:'.$data['userId'], 1, $couponBindInfo['discount_ratio'], $data['discountRatio']);

    }



    private function checkData($data,$riskCheck = false)
    {
        $num = isset($data[0])?intval($data[0]):0;
        $realName = isset($data[1])? trim($data[1]):'';
        $userNum = isset($data[2])?trim($data[2]):'';
        $shortAlias = isset($data[3])?trim($data[3]):'';

        if (empty($num)) {
            throw new \Exception("序号不能为空");
        }

        if (empty($userNum)) {
            throw new \Exception("会员编码不能为空");
        }

        //读存库
        $userInfo= \core\dao\UserModel::instance()->find(de32Tonum($userNum),'id,real_name',true);
        if (empty($userInfo)) {
            throw new \Exception("用户不存在");
        }

        if ($userInfo['real_name'] != $realName) {
            throw new \Exception("用户编码和真实姓名不匹配");
        }

        if (!empty($shortAlias)) {
            $couponService = new \core\service\CouponService();
            $coupon = $couponService->checkCoupon($shortAlias);
            if (empty($coupon) || $coupon['coupon_disable']) {
                throw new \Exception("邀请码无效");
            }
        }

        if($riskCheck){
            $userBasicGroupModel = new UserBasicGroupModel();
            $userBasicGroupInfo = $userBasicGroupModel->find($userInfo['group_id'], 'rebate_effect_days');
            $rebate_effect_days = $userBasicGroupInfo['rebate_effect_days'];
            if($rebate_effect_days < (time()-$userInfo['create_time'])/86400 ){
                throw new \Exception("注册{$rebate_effect_days}天之内的用户不可以操作服务关系");
            }

            $couponBindService = new CouponBindService();
            $couponBindInfo = $couponBindService->getByUserId($userInfo['id']);
            if(!empty($shortAlias)){
                if(empty($couponBindInfo['refer_user_id'])){
                    throw new \Exception("该用户无旧服务人");
                }
            }else{
                if(!empty($couponBindInfo['refer_user_id'])){
                    throw new \Exception("该用户无新服务人");
                }
            }

            if($userInfo['group_id'] != $coupon['group_id']){
                throw new \Exception("旧服务人与新服务人属于不同机构");
            }
        }
            

        return array(
                'num' =>$num,
                'realName' =>$realName,
                'userNum' => $userNum,
                'shortAlias' =>$shortAlias,
                'userId' => $userInfo['id'],
            );
    }


     private function checkDiscountData($data)
    {
        $num = isset($data[0])?intval($data[0]):0;
        $realName = isset($data[1])? trim($data[1]):'';
        $userNum = isset($data[2])?trim($data[2]):'';
        $discountRatio = isset($data[3])?trim($data[3]):'';

        if (empty($num)) {
            throw new \Exception("序号不能为空");
        }

        if (empty($userNum)) {
            throw new \Exception("会员编码不能为空");
        }

        //读存库
        $userInfo= \core\dao\UserModel::instance()->find(de32Tonum($userNum),'id,real_name',true);
        if (empty($userInfo)) {
            throw new \Exception("用户不存在");
        }

        if ($userInfo['real_name'] != $realName) {
            throw new \Exception("用户编码和真实姓名不匹配");
        }

        if (!preg_match("/^[0-1]+(\.[0-9]{1,2})?$/",$discountRatio) ||
            bccomp($discountRatio, 0,2) < 0 || bccomp($discountRatio, 1,2) > 0 ) {
            throw new \Exception("数据格式错误");
        }
        $discountRatio = bcadd($discountRatio,0,2);

        return array(
                'num' =>$num,
                'realName' =>$realName,
                'userNum' => $userNum,
                'discountRatio' =>$discountRatio,
                'userId' => $userInfo['id'],
            );
    }

    public function getTestDataCsv()
    {
        $count =  intval($_REQUEST['count'])==0? 100:intval($_REQUEST['count']);
        $coupons = trim($_REQUEST['coupons']);
        if (!empty($coupons)) {
            $coupons = explode(',', $coupons);
        } else {
            $coupons = array('F017D2');
        }

        $i = 1;
        $sql = "select max(id) id from firstp2p_user ";
        $maxId = $this->global_db->getOne($sql);
        while ($i<=$count) {
            $user_id = mt_rand(1, $maxId);
            $sql = "select id,real_name from firstp2p_user  where id >=".$user_id ." limit 1";
            $result = $this->global_db->getRow($sql);

            if (!empty($result)) {
                $content .= $i.",".$result['real_name'].",".numTo32($result['id']).",".$coupons[array_rand($coupons, 1)]."\n";
                $i++;
            }
        }
        $this->downCSVTpl($content);
    }
}
