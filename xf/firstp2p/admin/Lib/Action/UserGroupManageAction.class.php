<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

vendor("phpexcel.PHPExcel");
class UserGroupManageAction extends CommonAction {

    /**
     * 根据身份证号变更组信息（集团员工与主站客户）
     */
    public function index() {
        if (empty($_FILES['file'])) {
            $this->display('index');
            exit;
        }
        if (empty($_FILES['file']['tmp_name'])){
            $this->error('上传的文件不能为空');
        }

        $data = $this->get_data_from_excel();
        if (empty($data)) {
            $this->error('输入的文件内容为空');
        }

        $error_number = $success_number = 0;
        $user_group_staff = M('UserGroup')->where(array('name' => '集团普通员工'))->field('id')->find();
        if (!$user_group_staff['id']) {
            $this->error('集团普通员工组不存在');
        }
        $user_group_normal = M('UserGroup')->where(array('name' => '主站客户'))->field('id')->find();
        if (!$user_group_normal['id']) {
            $this->error('主站客户组不存在');
        }
        $coupon_level_staff = M('CouponLevel')->where(array('group_id' => $user_group_staff['id']))->field('id')->find();
        if (!$coupon_level_staff['id']) {
            $this->error('优惠码等级集团普通员工组不存在');
        }
        $coupon_level_normal = M('CouponLevel')->where(array('group_id' => $user_group_normal['id']))->field('id')->find();
        if (!$coupon_level_normal['id']) {
            $this->error('优惠码等级主站客户组不存在');
        }
        foreach ($data as $idno => $status) {
            // 身份证号采用加密存储，统一使用大写的X后缀
            $idno = strtoupper(addslashes(trim($idno)));
            if ($status == '在职') {
                $user = M('User')->where("idno = '$idno'")->field('group_id')->find();
                if ($user['group_id'] != $user_group_normal[id]) {
                    $error_number++;
                    continue;
                }
                $change_group = $user_group_staff['id'];
                $coupon_level_id = $coupon_level_staff['id'];
            } else {
                $change_group = $user_group_normal['id'];
                $coupon_level_id = $coupon_level_normal['id'];
            }
            if (M('User')->where("idno = '$idno'")->limit(1)->save(array('group_id' => $change_group, 'coupon_level_id' =>$coupon_level_id))) {

                $success_number++;
            } else {
                $error_number++;
            }
        }
        $this->assign('waitSecond', 6);
        $this->success("{$success_number}个变更成功，{$error_number}个变更失败。");
    }

    /**
     * 导入模板下载
     */
    public function download() {
        Header("Location: static/admin/Common/user_group_update_template.xlsx");
        exit;
    }

    /**
     * 获取excel数据
     */
    public function get_data_from_excel() {

        try {
            $fileType = PHPExcel_IOFactory::identify($_FILES['file']['tmp_name']);
            $reader = PHPExcel_IOFactory::createReader($fileType);
            $excel = $reader->load($_FILES['file']['tmp_name']);

            $sheet = $excel->getSheet(0); //第一个工作簿
            $rowCount = $sheet->getHighestRow(); //行数
            $data = array();
            for($currentRow = 2; $currentRow <= $rowCount; $currentRow++) {
                $idno = trim((string)$sheet->getCell('A'.$currentRow)->getValue());
                $status = trim((string)$sheet->getCell('B'.$currentRow)->getValue());
                if (!$idno) {
                    break;
                }
                $data[$idno] = $status;
            }
            return $data;
        } catch (Exception $e) {
           return array();
        }
    }

}
