<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use core\dao\AgencyImageModel;
use core\dao\DealAgencyModel;
use libs\vfs\Vfs;
use libs\vfs\VfsHelper;
use libs\utils\Logger;

class DealAgencyAction extends CommonAction
{
    public function index(){
        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        if ($this->is_cn) {
            $map['type'] =  array('in', array(1,2,4,5,6,8));

            $agency_type = isset($_REQUEST['agency_type']) ? intval($_REQUEST['agency_type']) : 0;
            if (!empty($agency_type)) {
                $map['type'] = array('eq', $agency_type);
            }
        }
        $model = MI(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model, $map);
        }
        $list = $this->get('list');
        $this->assign('list',$list);
        $this->assign('site_list',array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));
        $this->assign('organizeType', $GLOBALS['dict']['ORGANIZE_TYPE']);
        if ($this->is_cn) {
            $agency_type_map = (new DealAgencyModel)->getAgencyTypeMapCn();
        } else {
            $agency_type_map = (new DealAgencyModel)->getAgencyTypeMap();
        }
        if (!isset($_REQUEST['credit_display'])){
            $_REQUEST['credit_display'] = -1;
        }
        $this->assign('agency_type_map', $agency_type_map);
        $this->display();
    }

    /**
     * 组织查询条件
     * @param array $request
     */
    private function _getSqlMap(&$request)
    {
        //构造查询条件
        $map = array();
        $agency_type = isset($_REQUEST['agency_type']) ? intval($_REQUEST['agency_type']) : 0;
        if (!empty($agency_type)) {
            $map['type'] = array('eq', $agency_type);
        }
        $search_type = isset($_REQUEST['search_type']) ? intval($_REQUEST['search_type']) : 0;
        $keywords = isset($_REQUEST['keywords']) ? addslashes(trim($_REQUEST['keywords'])) : null;
        if ($keywords) {
            switch ($search_type) {
                case 1:
                    $map['name'] = array('like', '%' . $keywords . '%');
                    break;
                case 2:
                    $user_info = MI('User')->where("user_name = '{$keywords}'")->find();
                    if (!empty($user_info)) {
                        $map['user_id'] = array('eq', $user_info['id']);
                    } else {
                        $map['user_id'] = array('lt', 0);
                    }
                    break;
                case 3:
                    $user_info = MI('User')->where("user_name = '{$keywords}'")->find();
                    if (!empty($user_info)) {
                        $map['agency_user_id'] = array('eq', $user_info['id']);
                    } else {
                        $map['agency_user_id'] = array('lt', 0);
                    }
                    break;
            }
        }
        $credit_display = isset($_REQUEST['credit_display']) ? intval($_REQUEST['credit_display']) : -1;

        switch ($credit_display){
            case 1:
                $map['is_credit_display'] = array('eq', 1);
                break;
            case 0:
                $map['is_credit_display'] = array('eq', 0);
                break;
        }
        return $map;
    }

    public function add ()
    {
        $this->assign('type', $this->is_cn ? $GLOBALS['dict']['ORGANIZE_TYPE_CN'] : $GLOBALS['dict']['ORGANIZE_TYPE']);
        $this->assign('site_list',array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));
        $this->display();
    }

    public function insert ()
    {
        B('FilterString');
 
        // 当[关联用户ID]是[企业用户]时，检查选择的机构是否在该企业用户的用途中
        $type = intval($_POST['type']);
        if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 && $type > 0)
        {
            // 获取企业用户信息
            $userService = new \core\service\UserService($_POST['user_id']);
            $enterpriseBaseInfo = $userService->getEnterpriseInfo();
            // 企业用户账户用途
            $enterprisePurposeMap = !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE']) ? $GLOBALS['dict']['ENTERPRISE_PURPOSE'] : [];
            if (!empty($enterpriseBaseInfo) && !empty($enterprisePurposeMap))
            {
                // 检查机构列表是否在企业账户类型里面
                foreach ($enterprisePurposeMap as $purposeItem) {
                    if (!empty($purposeItem['organize_type']) && intval($purposeItem['organize_type']) === $type && intval($enterpriseBaseInfo['company_purpose']) != $purposeItem['bizId']) {
                        $this->error('关联会员账户未开通此用途，请检查！');
                    }
                }
            }
        }

        $userModule = M('User');
        $dealAgencyModule = M('DealAgency');
        $agencyUserModule = M('AgencyUser');

        $data = $dealAgencyModule->create();

        if($data['repay_inform_email']){
            $data['repay_inform_email'] = str_replace('，', ',', $data['repay_inform_email']);
            $email_arr = explode(',', $data['repay_inform_email']);
            foreach ($email_arr as $email) {
                if (! is_email($email)) {
                    $this->error("到期还款通知邮箱 格式有误！");
                }
            }
        }

        if(!$this->_checkEmail($data['exchange_repay_notice_email'])){
            $this->error("还款提醒邮箱 格式有误！");
        }

        if(!$this->_checkEmail($data['exchange_repay_plan_email'])){
            $this->error("还款计划表邮箱 格式有误！");
        }

        $dealAgencyModule->startTrans();
        try {
            if($data['site_id'] > 0){
                if($dealAgencyModule->where("site_id=".$data['site_id'])->getField("id")>0){
                    throw new Exception("分站已配置在其他机构");
                }
            }
            $res = $dealAgencyModule->add($data);
            $agency_id = $dealAgencyModule->getLastInsID();

            if($res === false || intval($agency_id) == 0){
                throw new Exception("添加失败");
            }

            $agency_user = trim($_POST['user']);
            if($agency_user){
                $user_filter = array_filter(explode(',', $agency_user));
                $user_filter = array_unique($user_filter);
                if(empty($user_filter)){
                    throw new Exception("机构确认账号填写错误");
                }

                foreach ($user_filter as $user) {
                    $user_id = $userModule->where(array('user_name' => $user))->getField('id');
                    if(empty($user_id)){
                        throw new Exception("机构确认账号：{$user}，用户不存在");
                    }

                    $agency_user_data = array();
                    $agency_user_data['user_id'] = $user_id;
                    $agency_user_data['user_name'] = $user;
                    $agency_user_data['agency_id'] = $agency_id;
                    if($agencyUserModule->add($agency_user_data) == false){
                        throw new Exception("机构确认账号：{$user}，添加失败");
                    }
                }
            }
            // 成功提示

            $this->saveAgencyImg($agency_id); // JIRA#3627 1+N资产端 by fanjingwen@

            $dealAgencyModule->commit();
            save_log('机构添加'.$data['name'].L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));

        } catch (Exception $e) {
            // 错误提示
            $dealAgencyModule->rollback();
            save_log('机构添加：'.$data['name'].'，添加失败：'.$e->getMessage(), 0);
            $this->error($e->getMessage());
        }
    }

    public function edit ()
    {
        $id = intval($_REQUEST['id']);

        $this->assign('type', $GLOBALS['dict']['ORGANIZE_TYPE']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();

        $info = $this->getAgencyUserName($id);
        $vo['user'] = implode(',', $info);
        // 会员列表的链接
        $userListUrl = 'User/index';
        if (!empty($vo) && $vo['user_id'] > 0)
        {
            $userService = new \core\service\UserService($vo['user_id']);
            $userService->isEnterprise() && $userListUrl = 'Enterprise/index';
        }
        $this->assign('vo', $vo);
        $this->assign('site_list',array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));
        $this->assign('userListUrl', $userListUrl);
        $this->assignAgencyImg($id); // JIRA#3627 1+N资产端 by fanjingwen@

        $this->display();
    }

    public function set_sort ()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $log_info = M(MODULE_NAME)->where("id=" . $id)->getField("name");
        if (! check_sort($sort)) {
            $this->error(l("SORT_FAILED"), 1);
        }
        M(MODULE_NAME)->where("id=" . $id)->setField("sort", $sort);
        save_log($log_info . l("SORT_SUCCESS"), 1);

        $this->success(l("SORT_SUCCESS"), 1);
    }

    public function update ()
    {
        B('FilterString');

        // 当[关联用户ID]是[企业用户]时，检查选择的机构是否在该企业用户的用途中
        $type = intval($_POST['type']);
        if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 && $type > 0)
        {
            // 获取企业用户信息
            $userService = new \core\service\UserService($_POST['user_id']);
            $enterpriseBaseInfo = $userService->getEnterpriseInfo();
            // 企业用户账户用途
            $enterprisePurposeMap = !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE']) ? $GLOBALS['dict']['ENTERPRISE_PURPOSE'] : [];
            if (!empty($enterpriseBaseInfo) && !empty($enterprisePurposeMap))
            {
                // 检查机构列表是否在企业账户类型里面
                foreach ($enterprisePurposeMap as $purposeItem) {
                    if (!empty($purposeItem['organize_type']) && intval($purposeItem['organize_type']) === $type && intval($enterpriseBaseInfo['company_purpose']) != $purposeItem['bizId']) {
                        $this->error('关联会员账户未开通此用途，请检查！');
                    }
                }
            }
        }

        $userModule = M('User');
        $dealAgencyModule = M('DealAgency');
        $agencyUserModule = M('AgencyUser');

        $data = $dealAgencyModule->create();

        if(empty($data['id'])){
            $this->error("错误操作");
        }

        if($data['repay_inform_email']){
            $data['repay_inform_email'] = str_replace('，', ',', $data['repay_inform_email']);
            $email_arr = explode(',', $data['repay_inform_email']);
            foreach ($email_arr as $email) {
                if (! is_email($email)) {
                    $this->error("到期还款通知邮箱 格式有误！");
                }
            }
        }

        if(!$this->_checkEmail($data['exchange_repay_notice_email'])){
            $this->error("还款提醒邮箱 格式有误！");
        }

        if(!$this->_checkEmail($data['exchange_repay_plan_email'])){
            $this->error("还款计划表邮箱 格式有误！");
        }

        $dealAgencyModule->startTrans();
        try {
            if($data['site_id'] > 0){
                $deal_agency_id = $dealAgencyModule->where("site_id=".$data['site_id'])->getField("id");

                //如果存在deal_agency记录并且不是要更新的记录则不允许添加分站配置（分站已配置在其他机构列表中）
                if($deal_agency_id>0 && $deal_agency_id <> $data['id']){
                    throw new Exception("分站已配置在其他机构");
                }
            }

            $res = $dealAgencyModule->save($data);

            if($res === false){
                throw new Exception("保存机构信息失败");
            }

            $del_res = $agencyUserModule->where(array('agency_id' => $data['id']))->delete();
            if($del_res === false){
                throw new Exception("删除原机构确认账号失败");
            }

            $agency_user = trim($_POST['user']);
            if($agency_user){
                $user_filter = array_filter(explode(',', $agency_user));
                $user_filter = array_unique($user_filter);
                if(empty($user_filter)){
                    throw new Exception("机构确认账号填写错误");
                }

                foreach ($user_filter as $user) {
                    $user_id = $userModule->where(array('user_name' => $user))->getField('id');
                    if(empty($user_id)){
                        throw new Exception("机构确认账号：{$user}，用户不存在");
                    }

                    $agency_user_data = array();
                    $agency_user_data['user_id'] = $user_id;
                    $agency_user_data['user_name'] = $user;
                    $agency_user_data['agency_id'] = $data['id'];
                    if($agencyUserModule->add($agency_user_data) == false){
                        throw new Exception("机构确认账号：{$user}，添加失败");
                    }
                }
            }

            $this->saveAgencyImg($data['id'], true); // JIRA#3627 1+N资产端 - 更新图片

            // 成功提示
            $dealAgencyModule->commit();
            save_log('机构修改，id:'.$data['id'].L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));

        } catch (Exception $e) {
            // 错误提示
            $dealAgencyModule->rollback();
            save_log('机构修改，id:'.$data['id'].$e->getMessage(), 0);
            $this->error($e->getMessage(), 0);
        }
    }

    protected function saveAgencyUser ()
    {
        $user = explode(',', $_POST['user']);
        $user = array_filter($user);
        $this->updateAgencyUser($this->pk_value, $user);
    }

    // 彻底删除
    public function foreverdelete ()
    {
        $id_arr = explode(',', $_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);

        $condition = array(
                'id' => array(
                        'in',
                        $id_arr
                )
        );
        $log_info = $this->get_log_info($condition);

        $rs = M(MODULE_NAME)->where($condition)->delete();
        if ($rs !== false) {
            M('agency_user')->where(array(
                    'agency_id' => array(
                            'in',
                            $id_arr
                    )
            ))->delete();

            // 删除机构图片路径记录 @by fanjingwen
            $cond = array('agency_id' => array('in', $id_arr));
            $agencyImgs = M("AgencyImage")->where($cond)->delete();

            save_log($log_info . l("FOREVER_DELETE_SUCCESS"), 1);
            $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
        } else {
            save_log($log_info . l("FOREVER_DELETE_FAILED"), 0);
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }
    }

    /* 更新担保公司用户对应信息 */
    /* function updateAgencyUser ($agency_id, $user_array)
    {
        if (empty($user_array)){
            return;
        }

        M('agency_user')->where(array('agency_id' => $agency_id))->delete();

        foreach ($info as $name) {
            $userInfo = $GLOBALS['db']->getRow(
                    "select * from " . DB_PREFIX . "user where user_name = '" .
                             $name . "'");
            if ($userInfo) {
                $data = array(
                        'user_id' => $userInfo['id'],
                        'user_name' => $userInfo['user_name'],
                        'agency_id' => $agency_id
                );

                M('agency_user')->add($data);
            }
        }
    } */

    /* 获取担保公司用户名 */
    function getAgencyUserName ($agency_id)
    {
        $info = M('agency_user')->where(array(
                'agency_id' => $agency_id
        ))->select();
        $re = array();
        foreach ($info as $v) {
            $re[] = $v['user_name'];
        }

        return $re;
    }

    /**
     * [存储机构相关的图片-JIRA#3627]
     * <fanjingwen@ucf>
     * @param int [机构id]
     * @param boolen [是否是更新操作]
     * @throw
     */
    protected function saveAgencyImg($agencyID, $isUpdate = false)
    {
        // get img
        $imgLogo = $_REQUEST['logo'];
        $imgLicense = $_REQUEST['license_img'];
        $imgBusPlaces = $_REQUEST['img_url'];
        $old_sign_img = $_REQUEST['old_sign_img'];

        // sign
        $sign_img_file = isset($_FILES['sign_img']) ? $_FILES['sign_img'] : array();
        $is_upload_sign_img_file = (!empty($sign_img_file) && UPLOAD_ERR_NO_FILE != $sign_img_file['error']);
        $sign_save_path = 'contract/sign';

        $agencyImageModul = M('AgencyImage');
        if (true == $isUpdate) {
            // 删除logo原图片
            $logo = $agencyImageModul->where(array('agency_id' => $agencyID, 'type' => AgencyImageModel::AGENCY_IMAGE_TYPE_LOGO))->find();
            if (!empty($logo) && $imgLogo != $logo['full_path']) {
                if (!Vfs::delete($logo['full_path'])) {
                    Logger::warn("ftp delete image failed!image path:" . $logo['full_path']);
                }
            }

            // 删除license_img原图片
            $license_img = $agencyImageModul->where(array('agency_id' => $agencyID, 'type' => AgencyImageModel::AGENCY_IMAGE_TYPE_LICENSE))->find();
            if (!empty($license_img['full_path']) && $imgLicense != $license_img['full_path']) {
                if (!Vfs::delete($license_img['full_path'])) {
                    Logger::warn("ftp delete image failed!image path:" . $license_img['full_path']);
                }
                if (!Vfs::delete($license_img['thumb_path'])) {
                    Logger::warn("ftp delete image failed!image path:" . $license_img['thumb_path']);
                }
            }

            // 删除business_place_state_imgs原图片
            $business_place_imgs = $agencyImageModul->where(array('agency_id' => $agencyID, 'type' => AgencyImageModel::AGENCY_IMAGE_TYPE_BUSINESS_PLACE))->findAll();
            foreach ($business_place_imgs as $business_place_img) {
                if (!in_array($business_place_img['full_path'], $imgBusPlaces)) {
                    if (!Vfs::delete($business_place_img['full_path'])) {
                        Logger::warn("ftp delete image failed!image path:" . $business_place_img['full_path']);
                    }
                    if (!Vfs::delete($business_place_img['thumb_path'])) {
                        Logger::warn("ftp delete image failed!image path:" . $business_place_img['thumb_path']);
                    }
                }
            }

            // 如果此机构不需要电子签章 或 重新上传了签章
            if (0 == $_REQUEST['need_sign_img'] || $is_upload_sign_img_file) {
                $sign = $agencyImageModul->where(array('agency_id' => $agencyID, 'type' => AgencyImageModel::AGENCY_IMAGE_TYPE_SIGN))->find();
                // 删除电子签章 img
                if (!empty($sign['full_path'])) {
                    if (!Vfs::delete($sign['full_path'])) {
                        Logger::warn("ftp delete image failed!image path:" . $logo['full_path']);
                    }
                }
            }

            // 删除相关图片数据库记录
            $img_del_res = $agencyImageModul->where(array('agency_id' => $agencyID))->delete();
            if(false === $img_del_res){
                throw new \Exception("删除原图片失败");
            }
        }

        require_once APP_ROOT_PATH . "/system/utils/es_imagecls.php";
        $imageCls = new es_imagecls();
        $agency_image_data = array();

        // logo
        $agency_image_data[] = array(
            'agency_id' => $agencyID,
            'type'      => AgencyImageModel::AGENCY_IMAGE_TYPE_LOGO,
            'thumb_path'=> '',
            'full_path' => $imgLogo,
        );

        // 营业执照
        $thumbPath = $imageCls->makeThumbOnFTP($imgLicense, '', 640, 480);
        $agency_image_data[] = array(
            'agency_id' => $agencyID,
            'type'      => AgencyImageModel::AGENCY_IMAGE_TYPE_LICENSE,
            'thumb_path'=> $thumbPath ? $thumbPath : $imgLicense,
            'full_path' => $imgLicense,
        );

        // 经营场地图
        foreach ($imgBusPlaces as $imgBusPlace) {
            $thumbPath = $imageCls->makeThumbOnFTP($imgBusPlace, '', 640, 480);
            $agency_image_data[] = array(
                'agency_id' => $agencyID,
                'type'      => AgencyImageModel::AGENCY_IMAGE_TYPE_BUSINESS_PLACE,
                'thumb_path'=> $thumbPath ? $thumbPath : $imgBusPlace,
                'full_path' => $imgBusPlace,
            );
        }

        // sign
        $sign_img = (0 == $_REQUEST['need_sign_img']) ? '' : ($is_upload_sign_img_file ? $this->saveImgFromFile($sign_img_file, $sign_save_path) : $old_sign_img);
        $agency_image_data[] = array(
            'agency_id' => $agencyID,
            'type'      => AgencyImageModel::AGENCY_IMAGE_TYPE_SIGN,
            'thumb_path'=> '',
            'full_path' => $sign_img,
        );

        foreach ($agency_image_data as $data) {
            if(false == $agencyImageModul->add($data)){
                throw new Exception("图片有误，添加失败，机构id：{$agencyID}");
            }
        }
    }

    /**
     * 根据 键名 保存 FILES 中的图片
     * @param array $file $_FILES 中某个key对应的值
     * @param string $save_path 保存的目录路径
     * @return string | false  保存图片的全路径
     */
    private function saveImgFromFile($file, $save_path)
    {
        if (empty($file) || UPLOAD_ERR_NO_FILE == $file['error']) {
            return false;
        }

        $upload_info = array(
            'file' => $file,
            'isImage' => 1,
            'savePath' => $save_path,
        );
        $result = uploadFile($upload_info);
        return (1 == $result['status']) ? VfsHelper::image($result['full_path'], $result['is_priv']) : '';
    }

    /**
     * [分配图片参数-JIRA#3627]
     * <fanjingwen@ucf>
     * @param int [机构id]
     */
    protected function assignAgencyImg($agencyID)
    {
        $cond['agency_id'] = $agencyID;
        $agencyImgs = M("AgencyImage")->where($cond)->findAll();
        foreach ($agencyImgs as $agencyImg) {
            switch ($agencyImg['type']) {
                case AgencyImageModel::AGENCY_IMAGE_TYPE_LOGO: // logo
                    $logo = $agencyImg['full_path'];
                    break;
                case AgencyImageModel::AGENCY_IMAGE_TYPE_LICENSE: // 营业执照
                    $license_img = $agencyImg['full_path'];
                    break;
                case AgencyImageModel::AGENCY_IMAGE_TYPE_BUSINESS_PLACE: // 企业场所
                    $busPlaImgs[] = $agencyImg['full_path'];
                    break;
                case AgencyImageModel::AGENCY_IMAGE_TYPE_SIGN: // 电子签章
                    $sign_img = $agencyImg['full_path'];
                    break;
            }
        }

        $this->assign('logo', $logo);
        $this->assign('license_img', $license_img);
        $this->assign('business_place_imgs', $busPlaImgs);
        $this->assign('sign_img', $sign_img);
    }

    /**
     * [swfupload：处理上传图片-Ajax-req]
     */
    public function uploadImg()
    {
        if (empty($_FILES)) {
            return;
        }

        $upDir = 'uploads'; // 上传文件的上级目录
        $savepath = $upDir . '/' . date('Ymd') . '/'; // 本次文件所存的文件夹
        Vfs::createDir($savepath); // 创建文件夹

        // 更改文件名
        $fileNameNew = 'img_' . uniqid() . '_' . $_FILES['Filedata']['name'];

        // 上传图片
        FP::import("app.upload");
        $upload = new upload($savepath, true);
        $upload->file($_FILES['Filedata']);
        $results = $upload->upload($fileNameNew);

        // 成功返回结果
        if (true == $results['status']) {
            $file_url = VfsHelper::image($savepath, false);
            $img_url = $file_url . $fileNameNew;
            print_r($img_url);
        } else {
            exit($upload->get_errors());
        }
    }

    /**
     * [swfupload：删除上传的图片-Ajax-req]
     */
    public function del()
    {
        // 未提交，不做物理删除
        // Vfs::delete($_GET['src']);
        print_r($_GET['src']);
        exit();
    }

    public function checkUserAgency(){
        $agencyUserModule = M('AgencyUser');
        $dealAgencyModule = M('DealAgency');

        $typeInfo = $GLOBALS['dict']['ORGANIZE_TYPE'];
        $result = '';
        $agency_user = trim($_GET['user']);
        if($agency_user){
            $user_filter = array_filter(explode(',', $agency_user));
            if(empty($user_filter)){
                echo $result;
            }else{
                foreach ($user_filter as $user) {
                    $agency_user_data = array();
                    $agency_user_data['user_name'] = $user;

                    $user_agency = $agencyUserModule->where($agency_user_data)->findAll();
                    if(count($user_agency) > 0){
                        $deal_agency_data = array();
                        foreach($user_agency as $agency){
                            $deal_agency_data['id'] = $agency['agency_id'];
                            $deal_agency_info = $dealAgencyModule->where($deal_agency_data)->find();
                            echo $user." 已是".$typeInfo[$deal_agency_info['type']].$deal_agency_info['name']."的确认帐号;\n";
                        }
                    }

                }
            }


        }
        exit();
    }

    private function _checkEmail($emailStr){
        if($emailStr){
            $emailStr = str_replace('，', ',', $emailStr);
            $email_arr = explode(',', $emailStr);
            foreach ($email_arr as $email) {
                if (! is_email($email)) {
                    return false;
                }
            }
        }

        return true;
    }
}
?>
