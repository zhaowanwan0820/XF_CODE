<?php

use core\enum\AgencyImageEnum;
use core\dao\agency\AgencyImageModel;
use core\dao\deal\DealAgencyModel;
use core\service\user\UserService;
use libs\vfs\Vfs;
use libs\vfs\VfsHelper;
use libs\utils\Logger;
use libs\utils\DBDes;

class DealAgencyAction extends CommonAction
{
    public function index(){
        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        $map['type'] =  array('in', array(1,2,3,4,5,6,8));
        $agency_type = isset($_REQUEST['agency_type']) ? intval($_REQUEST['agency_type']) : 0;
        if (!empty($agency_type)) {
            $map['type'] = array('eq', $agency_type);
        }
        $model = MI(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model, $map);
        }
        $list = $this->get('list');
        $userIds= array();
        foreach($list as $val){
            if($val['user_id']) {
                $userIds[$val['user_id']]= $val['user_id'];
            }
            if($val['agency_user_id']) {
                $userIds[$val['agency_user_id']] = $val['agency_user_id'] ;
            }
        }
        $userinfo = UserService::getUserInfoByIds($userIds);
        if (!isset($_REQUEST['credit_display'])){
            $_REQUEST['credit_display'] = -1;
        }
        $this->assign('userinfo',$userinfo);
        $this->assign('list',$list);
        $agency_type_map = (new DealAgencyModel)->getAgencyTypeMapCn();
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
                    $user_info = UserService::getUserIdByRealName($keywords);
                    if (!empty($user_info)) {
                        $map['user_id'] = array('eq', $user_info[0]);
                    } else {
                        $map['user_id'] = array('lt', 0);
                    }
                    break;
                case 3:
                    $user_info = UserService::getUserIdByRealName($keywords);
                    if (!empty($user_info)) {
                        $map['agency_user_id'] = array('eq', $user_info[0]);
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
        $agency_type_map = (new DealAgencyModel)->getAgencyTypeMapCn();
        $this->assign('type',  $agency_type_map);
        $this->display();
    }

    public function insert ()
    {
        B('FilterString');
        // 当[关联用户ID]是[企业用户]时，检查选择的机构是否在该企业用户的用途中
        $type = intval($_POST['type']);
     if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 && $type > 0) {
            // 获取企业用户信息
           $userId= (int)$_POST['user_id'];
            $enterpriseBaseInfo = UserService::getEnterpriseInfo($userId);
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
        $dealAgencyModule = M('DealAgency');
        $agencyUserModule = M('AgencyUser');
        $data = $dealAgencyModule->create();
        $data['bankcard'] = DBDes::encryptOneValue($data['bankcard']);
        $data['mobile'] = DBDes::encryptOneValue($data['mobile']);
        if($data['repay_inform_email']){
            $data['repay_inform_email'] = str_replace('，', ',', $data['repay_inform_email']);
            $email_arr = explode(',', $data['repay_inform_email']);
            foreach ($email_arr as $email) {
                if (! is_email($email)) {
                    $this->error("到期还款通知邮箱 格式有误！");
                }
            }
        }
        $dealAgencyModule->startTrans();
        try {
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
                    $user_info = UserService::getUserByName($user,'id');
                    if(empty($user_info)){
                        throw new Exception("机构确认账号：{$user}，用户不存在");
                    }
                    $agency_user_data = array();
                    $agency_user_data['user_id'] = $user_info['id'];
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
        $agency_type_map = (new DealAgencyModel)->getAgencyTypeMapCn();
        $this->assign('type',$agency_type_map);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $info = $this->getAgencyUserName($id);
        $vo['user'] = implode(',', $info);

        $vo['bankcard'] = DBDes::decryptOneValue($vo['bankcard']);
        $vo['mobile'] = DBDes::decryptOneValue($vo['mobile']);

        $this->assign('vo', $vo);
        $this->assignAgencyImg($id); // JIRA#3627 1+N资产端 by fanjingwen@
        $this->display();
    }

    public function update ()
    {
        B('FilterString');

        // 当[关联用户ID]是[企业用户]时，检查选择的机构是否在该企业用户的用途中
        $type = intval($_POST['type']);
        if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 && $type > 0) {
            $userId = (int)$_POST['user_id'] ;
            // 获取企业用户信息
            $enterpriseBaseInfo = UserService::getEnterpriseInfo($userId);
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

        $data['bankcard'] = DBDes::encryptOneValue($data['bankcard']);
        $data['mobile'] = DBDes::encryptOneValue($data['mobile']);

        $dealAgencyModule->startTrans();
        try {
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
                    $user_info = UserService::getUserByName($user,'id');
                    if(empty($user_info)){
                        throw new Exception("机构确认账号：{$user}，用户不存在");
                    }
                    $agency_user_data = array();
                    $agency_user_data['user_id'] = $user_info['id'];
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
    protected function saveAgencyImg($agencyID, $isUpdate = false){
        // get img
        $imgLogo = $_REQUEST['logo'];
        $imgLicense = $_REQUEST['license_img'];
        $old_sign_img = $_REQUEST['old_sign_img'];
        // sign
        $sign_img_file = isset($_FILES['sign_img']) ? $_FILES['sign_img'] : array();
        $is_upload_sign_img_file = (!empty($sign_img_file) && UPLOAD_ERR_NO_FILE != $sign_img_file['error']);
        $sign_save_path = 'contract/sign';

        $agencyImageModul = M('AgencyImage');
        $agency_image_data = array();
        if($imgLogo) {
            $logoType =  AgencyImageEnum::AGENCY_IMAGE_TYPE_LOGO;
            $agency_image_data[] = $this->getImgData($agencyID,$logoType,$imgLogo,$isUpdate);
        }
        if($imgLicense) {
            $imgLicenseType =  AgencyImageEnum::AGENCY_IMAGE_TYPE_LICENSE;
            $agency_image_data[] = $this->getImgData($agencyID,$imgLicenseType,$imgLicense,$isUpdate);
        }
        if (0 == $_REQUEST['need_sign_img'] || $is_upload_sign_img_file) {
            $sign_img = (0 == $_REQUEST['need_sign_img']) ? '' : ($is_upload_sign_img_file ? $this->saveImgFromFile($sign_img_file, $sign_save_path) : $old_sign_img);
            $uploadSignType =  AgencyImageEnum::AGENCY_IMAGE_TYPE_SIGN;
            $agency_image_data[] = $this->getImgData($agencyID,$uploadSignType,$sign_img,$isUpdate);
        }
            // 删除相关图片数据库记录
        if (true == $isUpdate) {
            $img_del_res = $agencyImageModul->where(array('agency_id' => $agencyID))->delete();
            if (false === $img_del_res) {
                throw new \Exception("删除原图片失败");
            }
        }
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
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_LOGO: // logo
                    $logo = $agencyImg['full_path'];
                    break;
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_LICENSE: // 营业执照
                    $license_img = $agencyImg['full_path'];
                    break;
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_SIGN: // 电子签章
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
    /**
     * [swfupload：Ajax-req]
     */
    protected function getImgData($agencyID,$type,$newPath,$isUpdate = false){
        $agencyImageModul = M('AgencyImage');
        if($isUpdate) {
            $path = $agencyImageModul->where(array('agency_id' => $agencyID, 'type' => $type))->find();
            if (!empty($path) && $newPath != $path['full_path']) {
                if (!Vfs::delete($path['full_path'])) {
                    Logger::warn("ftp delete image failed!image path:" . $path['full_path']);
                }
                if ($type == AgencyImageEnum::AGENCY_IMAGE_TYPE_LICENSE) {
                    if (!Vfs::delete($license_img['thumb_path'])) {
                        Logger::warn("ftp delete image failed!image path:" . $license_img['thumb_path']);
                    }
                }
            }
        }
        $agency_image_data= array(
            'agency_id' => $agencyID,
            'type'      => $type,
            'thumb_path'      => '',
            'full_path' => $newPath,
        );
        if($type == AgencyImageEnum::AGENCY_IMAGE_TYPE_LICENSE){
            require_once APP_ROOT_PATH . "/system/utils/es_imagecls.php";
            $imageCls = new es_imagecls();
            $thumbPath = $imageCls->makeThumbOnFTP($newPath, '', 640, 480);
            $agency_image_data['thumb_path'] =  $thumbPath ? $thumbPath : $newPath;
        }

        return $agency_image_data;
    }
}
?>
