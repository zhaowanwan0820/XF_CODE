<?php

use core\enum\RelatedEnum;
use libs\utils\DBDes;

class RelatedAction extends CommonAction {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 关联企业列表页
     */
    public function index_company() {
        $this->encrypt($_REQUEST);
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : 0;
        $related_name = isset($_REQUEST['related_name']) ? $_REQUEST['related_name'] : '';
        $license = isset($_REQUEST['license']) ? $_REQUEST['license'] : '';
        $enname = isset($_REQUEST['enname']) ? $_REQUEST['enname'] : '';
        $related_user = isset($_REQUEST['related_user']) ? $_REQUEST['related_user'] : '';
        $rate = isset($_REQUEST['rate']) ? floatval($_REQUEST['rate']) : 0;
        $related_type = isset($_REQUEST['related_type']) ? intval($_REQUEST['related_type']) : 0;
        $related_mode = isset($_REQUEST['related_mode']) ? intval($_REQUEST['related_mode']) : 0;
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;

        $model = M('RelatedCompany');

        if(!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->error('请正确访问该站点');
        }
        $conds = array("channel='{$channel}'");
        if ($license != '') {
            $conds[] = "license='{$license}'";
        }
        if ($related_name != '') {
            $conds[] = "related_name='{$related_name}'";
        }
        if ($enname != '') {
            $conds[] = "enname='{$enname}'";
        }
        if ($related_user != '') {
            $conds[] = "related_user='{$related_user}'";
        }
        if ($rate != 0) {
            $conds[] = "rate='{$rate}'";
        }
        if ($related_type != 0) {
            if($related_type == RelatedEnum::RELATED_TYPE_OTHER) {
                $conds[] = "related_type not in(1,2)";
            } else {
                $conds[] = "related_type='{$related_type}'";
            }
        }
        if ($related_mode != 0) {
            if($related_mode == RelatedEnum::RELATED_MODE_OTHER) {
                $conds[] = "related_mode not in(1,2)";
            }else {
                $conds[] = "related_mode='{$related_mode}'";
            }
        }
        if ($status != 0) {
            $conds[] = "status='{$status}'";
        }
        $_REQUEST['_sort'] = 1;
        $list = $this->_list($model, implode(' AND ',$conds));
        $this->assign("related_types", RelatedEnum::$RELATED_TYPES);
        $this->assign("related_modes", RelatedEnum::$RELATED_MODES);
        $this->assign("status_list", RelatedEnum::$STATUS);
        $this->assign("channels", RelatedEnum::$CHANNELS);
        $this->assign('list', $list);
        $this->assign('channel', $channel);
        $this->assign("main_title", "关联方-企业【".RelatedEnum::$CHANNELS[$channel]."】");
        $this->decrypt($_REQUEST);
        $this->display();
    }

    /**
     * 关联个人列表页
     */
    public function index_user() {
        $this->encrypt($_REQUEST);
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : 0;
        $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $related_company = isset($_REQUEST['related_company']) ? intval($_REQUEST['related_company']) : 0;
        $post = isset($_REQUEST['post']) ? intval($_REQUEST['post']) : 0;
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
        $model = M('RelatedUser');

        if(!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->error('请正确访问该站点');
        }
        $conds = array("channel='{$channel}'");
        if ($name != '') {
            $conds[] = "name='{$name}'";
        }
        if ($related_company != 0) {
            $conds[] = "related_company='{$related_company}'";
        }
        if ($post != 0) {
            $conds[] = "post='{$post}'";
        }
        if ($status != 0) {
            $conds[] = "status='{$status}'";
        }
        $_REQUEST['_sort'] = 1;
        $list = $this->_list($model, implode(' AND ',$conds));
        $this->assign("posts", RelatedEnum::$POSITIONS);
        $this->assign("related_companys", RelatedEnum::$RELATED_COMPANYS);
        $this->assign("status_list", RelatedEnum::$STATUS);

        $this->assign('list', $list);
        $this->assign("channels", RelatedEnum::$CHANNELS);
        $this->assign('channel', $channel);
        $this->assign("main_title", "关联方-个人【".RelatedEnum::$CHANNELS[$channel]."】");
        $this->decrypt($_REQUEST);
        $this->display();
    }

     function form_index_list(&$list) {
        foreach ($list as $k => $v) {
            $this->decrypt($list[$k]);
            $list[$k]['start_time'] = empty($v['start_time']) ? '--' : date('Y-m-d',$v['start_time']);
            $list[$k]['begin_time'] = empty($v['begin_time']) ? '--' : date('Y-m-d',$v['begin_time']);
            $list[$k]['end_time'] = empty($v['end_time']) ? '--' : date('Y-m-d',$v['end_time']);
            $list[$k]['status_str'] = RelatedEnum::$STATUS[$v['status']];
            $list[$k]['post'] = RelatedEnum::$POSITIONS[$v['post']];
            $list[$k]['related_type'] = RelatedEnum::$RELATED_TYPES[$v['related_type']];
            $list[$k]['related_mode'] = RelatedEnum::$RELATED_MODES[$v['related_mode']];
            $list[$k]['related_company'] = RelatedEnum::$RELATED_COMPANYS[$v['related_company']];
        }
    }

    public function add() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : 0;
        $this->assign("user_type", $user_type);
        $this->assign('channel', $channel);
        $this->assign("related_types", RelatedEnum::$RELATED_TYPES);
        $this->assign("related_modes", RelatedEnum::$RELATED_MODES);
        $this->assign("related_companys", RelatedEnum::$RELATED_COMPANYS);
        $this->assign("posts", RelatedEnum::$POSITIONS);
        $template = 'add_company';

        if(!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->error('请正确访问该站点');
        }

        $main_title = "新增-企业【".RelatedEnum::$CHANNELS[$channel]."】";
        if($user_type == RelatedEnum::USER_TYPE_USER) {
            $template = 'add_user';
            $main_title = "新增-个人【".RelatedEnum::$CHANNELS[$channel]."】";
        }
        $this->assign("channels", RelatedEnum::$CHANNELS);
        $this->assign('channel', $channel);
        $this->assign("main_title",$main_title );
        $this->display($template);
    }

    public function edit() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        if($user_type == RelatedEnum::USER_TYPE_COMPANY) {
            $model = D('RelatedCompany');
        } else {
            $model = D('RelatedUser');
        }

        $id = intval($_REQUEST['id']);
        $condition['id'] = $id;
        $data = $model->where($condition)->find();
        if (!$data) {
            $this->error('获取信息失败');
        }

        $this->decrypt($data);

        $channel = $data['channel'];
        if(isset($data['start_time'])) {
            $data['start_time'] = date('Y-m-d',$data['start_time']);
        }
        if(isset($data['begin_time'])) {
            $data['begin_time'] = date('Y-m-d',$data['begin_time']);
        }
        if(isset($data['end_time'])) {
            $data['end_time'] = date('Y-m-d',$data['end_time']);
        }
        $template = 'edit_company';
        $main_title = "编辑-企业【".RelatedEnum::$CHANNELS[$channel]."】";
        if($user_type == RelatedEnum::USER_TYPE_USER) {
            $template = 'edit_user';
            $main_title = "编辑-个人【".RelatedEnum::$CHANNELS[$channel]."】";
        }

        $this->assign("data", $data);
        $this->assign("related_types", RelatedEnum::$RELATED_TYPES);
        $this->assign("related_modes", RelatedEnum::$RELATED_MODES);
        $this->assign("related_companys", RelatedEnum::$RELATED_COMPANYS);
        $this->assign("posts", RelatedEnum::$POSITIONS);
        $this->assign("user_type", $user_type);
        $this->assign("channels", RelatedEnum::$CHANNELS);
        $this->assign('channel', $channel);
        $this->assign("main_title",$main_title );
        $this->display($template);
    }

    /**
     * 添加
     */
    public function insert() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : 0;
        if(!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->error('请正确访问该站点');
        }
        if($user_type == RelatedEnum::USER_TYPE_COMPANY) {
            $index= 'index_company&channel='.$channel;
            $model = D('RelatedCompany');
        } else {
            $index= 'index_user&channel='.$channel;
            $model = D('RelatedUser');
        }

        // 字段校验
        $data = $model->create();
        if (!$data) {
            $this->error($model->getError());
        }

        $data['start_time'] = trim($data['start_time'])==''?0:strtotime($data['start_time']);
        $data['begin_time'] = trim($data['begin_time'])==''?0:strtotime($data['begin_time']);
        $data['end_time'] = trim($data['end_time'])==''?0:strtotime($data['end_time']);
        $data['create_time'] = time();
        $data['update_time'] = time();

        //添加显示编号
        $orderno = $model->where("channel={$channel}")->max("CAST(orderno AS SIGNED)")+1;
        $data['orderno'] = $orderno;

        $this->encrypt($data);
        // 保存
        $result = $model->add($data);
        //日志信息
        $log_info = "[" . $model->getLastInsID() . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";
        if ($result) {
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0);
        }

        $this->assign("jumpUrl", u(MODULE_NAME . "/{$index}"));
        $this->success(L("INSERT_SUCCESS"));
    }

    /**
     * 更新
     */
    function update() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : '1';
        if($user_type == RelatedEnum::USER_TYPE_COMPANY) {
            $model = D('RelatedCompany');
        } else {
            $model = D('RelatedUser');
        }

        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }

        if(isset($data['start_time'])) {
            $data['start_time'] = trim($data['start_time'])==''?0:strtotime($data['start_time']);
        }
        if(isset($data['begin_time'])) {
            $data['begin_time'] = trim($data['begin_time'])==''?0:strtotime($data['begin_time']);
        }
        if(isset($data['end_time'])) {
            $data['end_time'] = trim($data['end_time'])==''?0:strtotime($data['end_time']);
        }
        $data['update_time'] = time();

        $this->encrypt($data);

        // 更新数据
        $list = $model->save($data);
        $id = $data[$model->getPk()];
        if (false !== $list) {
            //成功提示
            $this->success(L('UPDATE_SUCCESS'));
        } else {
            //错误提示
            $this->error(L('UPDATE_FAILED'));
        }
    }

    /**
     * 启停开关
     */
    function do_switch() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        if($user_type == RelatedEnum::USER_TYPE_COMPANY) {
            $model = D('RelatedCompany');
        } else {
            $model = D('RelatedUser');
        }
        $id = intval($_REQUEST['id']);
        $condition['id'] = $id;
        $data = $model->where($condition)->find();
        if (!$data) {
            $this->error('获取信息失败');
        }

        if($data['status'] == RelatedEnum::STATUS_USELESS) {
            $data['status'] = RelatedEnum::STATUS_USEFUL;
        } else {
            $data['status'] = RelatedEnum::STATUS_USELESS;
        }
        // 更新数据
        $list = $model->save($data);
        $id = $data[$model->getPk()];
        if (false !== $list) {
            //成功提示
            $this->success(L('UPDATE_SUCCESS'));
        } else {
            //错误提示
            $this->error(L('UPDATE_FAILED'));
        }
    }

    /**
     * 删除关联方数据
     */
    function do_delete() {
        $user_type = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : '0';
        if($user_type == RelatedEnum::USER_TYPE_COMPANY) {
            $model = D('RelatedCompany');
        } else {
            $model = D('RelatedUser');
        }
        $id = intval($_REQUEST['id']);
        $condition['id'] = $id;
        $data = $model->where($condition)->find();
        if (!$data) {
            $this->error('获取信息失败');
        }

        //删除数据
        $r = $model->delete($id);
        if (empty($r)){
            //失败提示
            $this->error(L('DELETE_FAILED'));
        } else {
            //成功提示
            $this->success(L('DELETE_SUCCESS'));
        }
    }

    private function encrypt(&$data) {
        foreach (RelatedEnum::$DBDES_FIELDS as $field) {
            if(isset($data[$field])) {
                $data[$field] = DBDes::encryptOneValue($data[$field]);
            }
        }
    }

    private function decrypt(&$data) {
        foreach (RelatedEnum::$DBDES_FIELDS as $field) {
            if(isset($data[$field])) {
                $data[$field] = DBDes::decryptOneValue($data[$field]);
            }
        }
    }

    public function import_company() {
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : '1';
        $this->assign('channel', $channel);
        $this->display();
    }


    /**
     * csv数据导入
     */
    public function do_import_company()
    {
        $channel = isset($_REQUEST['channel']) ? intval($_REQUEST['channel']) : '1';
        if(!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->error('请正确访问该站点');
        }
        $model = D('RelatedCompany');

        //文件检查
        if ($_FILES['upfile']['error'] == 4) {
            $this->error("请选择文件！");
            exit;
        }
        if ($_FILES['upfile']['type'] != 'text/csv' && $_FILES['upfile']['type'] != 'application/vnd.ms-excel') {
            $this->error("请上传csv格式的文件！");
            exit;
        }
        set_time_limit(0);
        $max_line_num = 10000;
        ini_set('memory_limit', '2G');
        $file_line_num = count(file($_FILES['upfile']['tmp_name']));
        if ($file_line_num > $max_line_num + 1) {
            $this->error("处理的数据不能超过{$max_line_num}行");
        }

        //读取csv数据
        $row_no = 1;
        $row_head_array = array('序号','关联方名称','英文名称','注册地','注册/营业执照号','关联人','关联形式','持股比例','关联关系','关联开始时间','备注','是否启用');
        $list = array();
        $errorMsg = []; //记录错误行
        $now = time();
        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== FALSE) {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if ($row_no == 1) { //第一行标题，检查标题行
                    if (count($row) != count($row_head_array)) {
                        $this->error("第一行标题不正确！");
                        exit;
                    }
                    for ($i = 0; $i < count($row_head_array); $i++) {
                        $row[$i] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$i])));
                        if ($row[$i] != $row_head_array[$i]) {
                            $this->error("第" . ($i + 1) . "列标题不正确！应为'{$row_head_array[$i]}'");
                            exit;
                        }
                    }
                } else { //数据
                    $item = array();
                    $col = 0;
                    $item['serialno']       = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//序号
                    $item['related_name']   = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//关联方名称
                    $item['enname']         = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//英文名称
                    $item['address']        = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//注册地
                    $item['license']        = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//注册/营业执照号
                    $item['related_user']   = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//关联人
                    $related_type           = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));
                    $item['related_type']   = in_array($related_type,RelatedEnum::$RELATED_TYPES) ? array_flip(RelatedEnum::$RELATED_TYPES)[$related_type] : RelatedEnum::RELATED_TYPE_OTHER;//关联形式
                    $item['rate']           = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//持股比例
                    $related_mode           = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));
                    $item['related_mode']   = in_array($related_mode,RelatedEnum::$RELATED_MODES) ? array_flip(RelatedEnum::$RELATED_MODES)[$related_mode] : RelatedEnum::RELATED_MODE_OTHER;//关联关系
                    $start_time             = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));
                    $item['start_time']     = empty($start_time)? 0 : strtotime($start_time);//关联开始时间
                    $item['remark']         = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++])));//备注
                    $item['status']         = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$col++]))) == '停用' ? RelatedEnum::STATUS_USELESS : RelatedEnum::STATUS_USEFUL;//是否启用
                    $item['channel']        = $channel;//渠道
                    $item['create_time']    = $now;//创建时间
                    $item['update_time']    = $now;//修改时间
                    $this->encrypt($item);
                    //注掉检查策略
                    //$item['id']             = $this->_checkCompanyData($item,$channel);
                    $list[] = $item;
                }
                $row_no++;
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        if (empty($list)) {
            $this->error("导入数据为空！");
            exit;
        }

        //导入
        $row_no = 2;
        $GLOBALS['db']->startTrans();

        //全部删除旧数据
        $model->where("channel={$channel}")->delete();
        $failRows = array();
        $total_num = count($list);
        $orderno = 1;
        foreach ($list as $key =>$item) {
            $item['orderno'] = $orderno++;
            //注掉检查策略，统一新增
            //if($item['id'] == -1) { //多条重复报错
            //    $rs = false;
            //} else if($item['id'] == 0) { //新插入
            //    $rs = $model->add($item);
            //} else{// 更新数据
            //    $rs = $model->save($item);
            //}
            $rs = $model->add($item);
            if(!$rs) {
                $failRows[] = $row_no;
            }
            $row_no++;
        }
        $rs = $GLOBALS['db']->commit();
        if ($rs) {
            if (!empty($errorMsg)) {
                $this->error("导入失败！<br />" . implode('<br />', $errorMsg));
                exit;
            }
            $total_fail = count($failRows);
            $total_succ = $total_num - $total_fail;
            if($total_fail >0) {
                $this->assign('waitSecond', 600);
            } else {
                $this->assign('waitSecond', 3);
            }
            $this->success("共{$total_num}条数据，成功导入{$total_succ} 条！,失败{$total_fail}条！（第 ".implode('、',$failRows)." 条）");

        } else {
            $GLOBALS['db']->rollback();
            $this->error("导入失败！");
        }
    }

    /**
     * 检查企业信息处理方式
     * 1)   按照“注册/营业执照号”字段在系统中筛选对应，若找到唯一对应值，更新本条数据，若找到多条对应值，跳过并记录本条信息位置；若无对应值，进入2）。
     * 2)   按照“关联方名称”字段在A中筛选对应，若找到唯一对应值，更新本条数据，若找到多条对应值，跳过并记录本条信息位置；若无对应值，进入3）。
     * 3)   按照“英文名称”字段在A中筛选对应，若找到唯一对应值，更新本条数据，若找到多条对应值，跳过并记录本条信息位置；若不存在，插入本条数据。
     * 4)   最终输出结果：共XXX条数据，成功导入XXX条，失败XXX条（第X条、第XX条……）。
     * @param $data 企业数据
     * @param $channel 渠道
     * @return bool
     */
    private function _checkCompanyData($data,$channel) {
        $cond_tpl = "%s='%s' AND channel={$channel}";
        $relatedCompany = D('RelatedCompany');
        $license_same_list = $relatedCompany->where(sprintf($cond_tpl,'license',$data['license']))->findAll();
        if(count($license_same_list) == 1) {
            return $license_same_list[0]['id'];
        }
        $license_related_name_list = $relatedCompany->where(sprintf($cond_tpl,'related_name',$data['related_name']))->findAll();
        if(count($license_related_name_list) == 1) {
            return $license_related_name_list[0]['id'];
        }
        $license_enname_list = $relatedCompany->where(sprintf($cond_tpl,'enname',$data['enname']))->findAll();
        if(count($license_enname_list) == 1) {
            return $license_enname_list[0]['id'];
        }

        if((count($license_same_list)>1) || (count($license_same_list)>1) || (count($license_same_list)>1)) {
            return -1; //找到多条，报错
        }
        return 0; //没找到，插入
    }


    /**
     * 刷新关联方用户数据
     */
    function refresh_user_orderno() {
        $relatedUserModel = D('RelatedUser');

        $channels = array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH);
        foreach ($channels as $channel) {
            $orderno = 1;
            $list = $relatedUserModel->where("channel={$channel}")->order("id ASC")->findAll();
            foreach ($list as $relatedUser) {
                $condition['id'] = $relatedUser['id'];
                $data = $relatedUserModel->where($condition)->find();
                if (!$data) {
                    $this->error('获取信息失败');
                }

                $data['orderno'] = $orderno++;
                // 更新数据
                $res = $relatedUserModel->save($data);
                if (false === $res) {
                    $this->error(L('UPDATE_FAILED'));
                }
            }
        }
        $this->success('刷新成功！');
    }
}
?>
