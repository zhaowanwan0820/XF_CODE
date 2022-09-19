<?php

use libs\utils\Logger;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\service\UserService;
use core\service\darkmoon\DarkMoonService;

class DarkMoonDealLoadAction extends CommonAction
{
    private $_uploadCsvTitles = array('用户姓名', '身份证号', '手机号', '银行卡号', '开户行名称', '邀请码', '认购金额');

    public function index()
    {
        //标的
        $deal_id = intval($_REQUEST['dealid']);
        if ($deal_id <= 0) {
            $this->error('获取标的信息失败');
        }
        $deal_info = DarkmoonDealModel::instance()->findViaSlave($deal_id);
        if (empty($deal_info)) {
            $this->error('获取标的信息失败');
        }
        $jys_record_number = $deal_info['jys_record_number'];

        $is_show_upload = 0;
        if (0 == $deal_info['deal_status']) {
            $is_show_upload = 1;
        }

        $model = DI('DarkmoonDealLoad');
        $where = "deal_id = {$deal_id}";
        $count = $model->where($where)->count();
        if ($count > 0) {
            // 创建分页对象
            $listRows = '';
            if (!empty($_REQUEST['listRows'])) {
                $listRows = $_REQUEST['listRows'];
            }
            $p = new Page($count, $listRows);
            // 分页查询数据
            $voList = $model->where($where)->limit($p->firstRow.','.$p->listRows)->findAll();
            // 分页显示
            $page = $p->show();

            // 模板赋值显示
            $this->assign('list', $voList);
            $this->assign('page', $page);
            $this->assign('nowPage', $p->nowPage);
        }

        $this->assign('dealid', $deal_id);
        $this->assign('is_show_upload', $is_show_upload);

        $this->assign('jys_record_number', $jys_record_number);
        $this->display();
    }

    public function edit()
    {
        $ajax = intval($_GET['ajax']);

        $id = intval($_REQUEST['id']);
        $real_name = strval($_REQUEST['real_name']);
        $idno = strval($_REQUEST['idno']);
        $mobile = strval($_REQUEST['mobile']);
        $bank_id = strval($_REQUEST['bank_id']);
        $bank_name = strval($_REQUEST['bank_name']);
        $short_alias_csv = strval($_REQUEST['short_alias_csv']);
        $money = strval($_REQUEST['money']);

        $deal_load_info = DarkmoonDealLoadModel::instance()->findViaSlave($id);
        if (empty($deal_load_info)) {
            $this->error('获取投资明细失败', $ajax);
        }

        if (empty($real_name)) {
            $this->error('真实姓名不能为空！', $ajax);
        }

        if (empty($idno)) {
            $this->error('身份证号不能为空！', $ajax);
        }

        if (empty($mobile)) {
            $this->error('手机号不能为空！', $ajax);
        }

        if (empty($bank_id)) {
            $this->error('银行卡号不能为空！', $ajax);
        }

        if (empty($bank_name)) {
            $this->error('开户行不能为空！', $ajax);
        }

        if (empty($short_alias_csv)) {
            $this->error('邀请码不能为空！', $ajax);
        }

        if (0 == floatval($money)) {
            $this->error('认购金额不能为空！', $ajax);
        }

        $deal_load_info->real_name = $real_name;
        $deal_load_info->idno = $idno;
        $deal_load_info->mobile = $mobile;
        $deal_load_info->bank_id = $bank_id;
        $deal_load_info->bank_name = $bank_name;
        $deal_load_info->short_alias_csv = $short_alias_csv;
        $deal_load_info->money = $money;
        $deal_load_info->update_time = time();

        //获取用户信息
        $userService = new UserService();
        $user_info = $userService->getUserByIdno($idno);
        if (empty($user_info)) {
//            $this->error('身份证号查询不到用户',$ajax);
        }
        $deal_load_info->user_id = $user_info['id'];
        if (false === $deal_load_info->save()) {
            $this->error('保存投资明细失败', $ajax);
        }
        //更新邀请人信息
        $darkMoonService = new \core\service\darkmoon\DarkMoonService();
        $darkMoonService->updateReferUserByDealLoadId($deal_load_info->id);
        $this->success('保存投资明细成功', $ajax);
    }

    public function invalid()
    {
        $ajax = intval($_GET['ajax']);
        $loadId = intval($_REQUEST['loadId']);
        $deal_load_info = DarkmoonDealLoadModel::instance()->findViaSlave($loadId);
        if (empty($deal_load_info)) {
            $this->error('获取投资明细失败', $ajax);
        }

        $deal_info = DarkmoonDealModel::instance()->findViaSlave($deal_load_info['deal_id']);
        if (empty($deal_info)) {
            $this->error('获取标的信息失败', $ajax);
        }

        if (4 == $deal_info->deal_status) {
            $this->error('当前状态不可置废', $ajax);
        }
        $deal_load_info->status = 3;
        $deal_load_info->update_time = time();
        if (false === $deal_load_info->save()) {
            $this->error('置废投资明细失败', $ajax);
        }

        // 触发投资人借款短信
        $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
        $count = $darkmoonDealLoadModel->getUnsignCount($deal_load_info['deal_id']);

        if (0 == intval($count)) {
            $darkmoonService = new DarkMoonService();
            $rs = $darkmoonService->sendBorrowSms($deal_load_info['deal_id']);
        }

        $this->success('置废投资明细成功', $ajax);
    }

    /**
     * 下载模板
     */
    public function get_upload_tpl()
    {
        header('Content-Type: text/csv;charset=utf8');
        header('Content-Disposition: attachment; filename=load_detail_upload_tpl.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        $title = $this->_uploadCsvTitles;
        foreach ($title as &$item) {
            $item = iconv('utf-8', 'gbk//IGNORE', $item);
        }
        fputcsv($fp, $title);
        exit;
    }

    /**
     * 执行投资记录上传.
     */
    public function do_upload()
    {
        //标的
        $deal_id = intval($_REQUEST['dealid']);
        $deal_info = DarkmoonDealModel::instance()->findViaSlave($deal_id);
        if (empty($deal_info)) {
            $this->error('获取标的信息失败');
        }

        if (0 != $deal_info['deal_status']) {
            $this->error('当前标的状态不可上传！');
        }

        try {
            $GLOBALS['db']->startTrans();
            // 判断是否为 csv
            $mimes = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv');
            if (!in_array($_FILES['batch_update_file']['type'], $mimes)) {
                throw new \Exception('上传文件需为 csv 格式！');
            }

            //文件是否打开成功
            if (false === ($handle = fopen($_FILES['batch_update_file']['tmp_name'], 'r'))) {
                throw new \Exception('csv 文件打开失败！');
            }

            $userService = new UserService();
            $success_collection = array();
            $fail_collection = array();

            $is_header = true;
            $totalColumn = count($this->_uploadCsvTitles); //总列数
            $model = DI('DarkmoonDealLoad');
            $i = 2;
            while (false !== ($data = fgetcsv($handle))) {
                // 列数校验
                if ($totalColumn != count($data)) {
                    throw new \Exception("数据格式错误：此 csv 只能是{$totalColumn}列！");
                }
                // 约定第一行为表头，跳过
                if ($is_header) {
                    $is_header = false;
                    continue;
                }

                list($new_data['real_name'],
                    $new_data['idno'],
                    $new_data['mobile'], // 授信额度
                    $new_data['bank_id'],
                    $new_data['bank_name'],
                    $new_data['short_alias_csv'],
                    $new_data['money']) = $data; // 赋值给对应项

                $now = time();
                $new_data['real_name'] = iconv('gbk', 'utf-8', trim($new_data['real_name']));
                $new_data['bank_name'] = iconv('gbk', 'utf-8', trim($new_data['bank_name']));
                $new_data['bank_id'] = strval($new_data['bank_id']);
                $new_data['idno'] = strval($new_data['idno']);

                if (empty($new_data['real_name'])) {
                    throw new \Exception("第{$i}行数据格式错误：真实姓名不能为空！");
                }

                if (empty($new_data['idno'])) {
                    throw new \Exception("第{$i}行数据格式错误：身份证号不能为空！");
                }

                if (empty($new_data['mobile'])) {
                    throw new \Exception("第{$i}行数据格式错误：手机号不能为空！");
                }

                if (empty($new_data['bank_id'])) {
                    throw new \Exception("第{$i}行数据格式错误：银行卡号不能为空！");
                }

                if (empty($new_data['bank_name'])) {
                    throw new \Exception("第{$i}行数据格式错误：开户行不能为空！");
                }

                if (empty($new_data['short_alias_csv'])) {
                    throw new \Exception("第{$i}行数据格式错误：邀请码不能为空！");
                }

                if (0 == floatval($new_data['money'])) {
                    throw new \Exception("第{$i}行数据格式错误：认购金额不能为空！");
                }

                //获取用户信息
                $user_info = $userService->getUserByIdno($new_data['idno']);
                $new_data['user_id'] = $user_info['id'];
                $new_data['deal_id'] = $deal_id;
                $new_data['user_name'] = $user_info['user_name'];
                $new_data['status'] = 1;
                $new_data['create_time'] = $now;
                $new_data['update_time'] = $now;

                //var_dump($new_data);exit;

                //$lcsUserInfo = $userService->getUserByInviteCode($new_data['short_alias']);
                //$new_data['refer_user_id'] = $lcsUserInfo['id'];
                $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
                foreach ($new_data as $key => $value) {
                    if ('' === $value || is_null($value)) {
                        $value = '';
                    }
                    $darkmoonDealLoadModel->$key = addslashes($value);
                }
                if (false === $darkmoonDealLoadModel->save()) {
                    $fail_collection[] = array(
                        'id' => count($fail_collection) + 1,
                        'project_id' => $new_data['id'],
                        'fail_msg' => '用户投资明细添加失败',
                    );

                    throw new \Exception('用户投资明细添加失败！');
                } else {
                    //更新邀请人信息
                    $darkMoonService = new \core\service\darkmoon\DarkMoonService();
                    $darkMoonService->updateReferUserByDealLoadId($darkmoonDealLoadModel->id);
                    $success_collection[] = $new_data['id'];
                    Logger::info(sprintf('用户投资明细添加成功，更新内容：%s', json_encode($new_data)));
                }
                ++$i;
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('%s，上传文件名：%s，file：%s, line:%s', $e->getMessage(), $_FILES['batch_update_file']['name'], __FILE__, __LINE__));
            $this->error($e->getMessage());

            return;
        }
        $this->redirect(u(MODULE_NAME."/index?dealid={$deal_id}"));
        exit;
    }
}
