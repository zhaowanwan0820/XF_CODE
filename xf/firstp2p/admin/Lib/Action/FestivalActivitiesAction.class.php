<?php
/**
 * APP节庆活动后台
 *
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @version
 * @copyright 2016.11
 */

class FestivalActivitiesAction extends CommonAction
{

    private static $activity_type = array('0' => '物品掉落','1' => '物品上升');
    /**
     * 首页
     * @access public
     */
    public function index()
    {
        if ($_REQUEST['is_effect']) {
            if ($_REQUEST['is_effect'] == 2) {
                $this->assign('is_effect',intval($_REQUEST['is_effect']));
                unset($_REQUEST['is_effect']);
            } else {
                $_REQUEST['is_effect'] = intval($_REQUEST['is_effect']);
                $this->assign('is_effect',intval($_REQUEST['is_effect']));
            }
        }
        if (!isset($_REQUEST['is_effect'])) {
            $this->assign('is_effect',2);
        }
        if ($_REQUEST['name']) {
            $name = htmlspecialchars('%'.$_REQUEST['name'].'%');
            $condition['name'] = array('like',$name);
            if (isset($_REQUEST['is_effect']))
            $condition['is_effect'] = $_REQUEST['is_effect'];
            $list = M(MODULE_NAME)->where($condition)->findall();
            $this->assign('search_name',htmlspecialchars($_REQUEST['name']));
        } else {
            $model = M(MODULE_NAME);
            $condition = $this->_search();
            if (!empty($model)) {
                $this->_list($model,$condition);
            }
            $list = $this->get('list');
        }
        foreach ($list as $key=>$value) {
            $list[$key]['img_conf'] = json_decode($list[$key]['img_conf'],true);
            $list[$key]['prize_conf'] = json_decode($list[$key]['prize_conf'],true);
            //将库存剩余个数置为总库存数-消耗的
            foreach ($list[$key]['prize_conf'] as $key_conf => $value) {
                if ($list[$key]['prize_conf'][$key_conf]['count'] > 0)
                    $list[$key]['prize_conf'][$key_conf]['remainder_count'] = $list[$key]['prize_conf'][$key_conf]['count'] - $list[$key]['prize_conf'][$key_conf]['use_count'];
            }
            $list[$key]['take_limit'] = array('count_limit_day'=>$list[$key]['count_limit_day'],
                                              'count_limit'=>$list[$key]['count_limit'],
                                          );
            $start_time = !empty($list[$key]['start_time']) ? date('Y-m-d H:i:s',$list[$key]['start_time']) : '';
            $end_time = !empty($list[$key]['end_time']) ? date('Y-m-d H:i:s',$list[$key]['end_time']) : '';
            $list[$key]['expiry_date'] = array('start_time' => $start_time,
                                               'end_time' => $end_time,
                                              );
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 创建活动
     * @access public
     */
    public function insert()
    {
        //校验传入参数
        $form = D(MODULE_NAME);

        if (!empty($form)) {
            $this->_list($form, $condition);
        }
         $list = $this->get('list');
         // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }
        adddeepslashes($data);//过滤参数
        if (mb_strlen($data['name']) > 10) {
            $this->error(L("活动名称不能大于10字"),0);
        }
        $ret = $this->checkActivityName($data['name'],0);
        if ($ret) {
            $this->error(L("活动名称已经存在请更换别的名称"),0);
        }
        if (stripos($data['img_conf']['activity_pull']['activity_pull_src'],'http') !== 0) {
            $this->error(L("首页下拉配置图不能为空"),0);
        }
        if (mb_strlen($data['img_conf']['activity_pull']['pull_text']) > 10) {
            $this->error(L("下拉文案不能大于10字"),0);
        }
        if (mb_strlen($data['img_conf']['activity_pull']['release_text']) > 10) {
            $this->error(L("释放文案不能大于10字"),0);
        }
        if (stripos($data['img_conf']['activity_home']['activity_home_src'],'http') !== 0 || stripos($data['img_conf']['activity_home']['activity_in_button_src'],'http') !== 0) {
            $this->error(L("活动首页配置不能为空"),0);
        }
        if (stripos($data['img_conf']['activity_prize']['activity_prize_src'],'http') !== 0 || stripos($data['img_conf']['activity_prize']['prize_button_src'],'http') !== 0) {
            $this->error(L("活动获奖页配置不能为空"),0);
        }
        $this->checkActivityDropImgConf($data['img_conf']['drop_01'],'01');
        $this->checkActivityDropImgConf($data['img_conf']['drop_02'],'02');
        $this->checkActivityDropImgConf($data['img_conf']['drop_03'],'03');
        $this->checkActivityPrizeConf($data['prize_conf']);
        //将库存剩余个数置为总库存数-消耗的
        foreach ($data['prize_conf'] as $key => $value) {
            if ($data['prize_conf'][$key]['count'] > 0)
                $data['prize_conf'][$key]['use_count'] = 0;
        }
        $data['img_conf'] = json_encode($data['img_conf']);
        $data['prize_conf'] = json_encode($data['prize_conf']);
        $data['start_time'] = !empty($data['start_time']) ? strtotime($data['start_time']) : time();
        $data['end_time'] = !empty($data['end_time']) ? strtotime($data['end_time']) : 2145888000;
        $data['count_limit_day'] = !empty($data['count_limit_day']) ? $data['count_limit_day'] : 0;
        $data['count_limit'] = !empty($data['count_limit']) ? $data['count_limit'] : 0;
        $data['create_time'] = time();
        if ($data['count_limit'] && $data['count_limit_day'] && ($data['count_limit_day'] > $data['count_limit'])) {
            $this->error(L("用户参与次数上限不能小于用户每日参与次数"),0);
        }
        if ($data['end_time'] && $data['start_time'] >= $data['end_time']) {
            $this->error(L("活动开始时间不能大于结束时间"),0);
        }
        if ($data['end_time'] && $data['end_time'] <= time) {
            $this->error(L("活动结束时间不能小于当前时间"),0);
        }

        // 保存
        $result = $form->add($data);

        //日志信息
        $log_info = "[" . $form->getLastInsID() . "]";
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
        $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
        $this->success(L("INSERT_SUCCESS"));
    }

    public function add()
    {
        $this->display();
    }
    /**
     * 编辑页面.
     *
     * @access public
     */
    public function update()
    {
        B('FilterString');
        $form = D(MODULE_NAME);
        // 字段校验
        $data = $form->create();
        $fields = $form->getDbFields();
        //设置update_time
        if(in_array('update_time',$fields)){
            $form->__set('update_time',get_gmtime());
        }
        if (!$data) {
            $this->error($form->getError());
        }
        adddeepslashes($data);//过滤参数
        if (mb_strlen($data['name']) > 10) {
            $this->error(L("活动名称不能大于10字"),0);
        }
        //检查是否有重复的活动名称
        $ret = $this->checkActivityIdName($data['name'],0,$data['id']);
        if ($ret) {
            $this->error(L("活动名称已经存在请更换别的名称"),0);
        }
        if (stripos($data['img_conf']['activity_pull']['activity_pull_src'],'http') !== 0) {
            $this->error(L("首页下拉配置图不能为空"),0);
        }
        if (mb_strlen($data['img_conf']['activity_pull']['pull_text']) > 10) {
            $this->error(L("下拉文案不能大于10字"),0);
        }
        if (mb_strlen($data['img_conf']['activity_pull']['release_text']) > 10) {
            $this->error(L("释放文案不能大于10字"),0);
        }
        if (stripos($data['img_conf']['activity_home']['activity_home_src'],'http') !== 0 || stripos($data['img_conf']['activity_home']['activity_in_button_src'],'http') !== 0) {
            $this->error(L("活动首页配置不能为空"),0);
        }
        if (stripos($data['img_conf']['activity_prize']['activity_prize_src'],'http') !== 0 || stripos($data['img_conf']['activity_prize']['prize_button_src'],'http') !== 0) {
            $this->error(L("活动获奖页配置不能为空"),0);
        }
        $this->checkActivityDropImgConf($data['img_conf']['drop_01'],'01');
        $this->checkActivityDropImgConf($data['img_conf']['drop_02'],'02');
        $this->checkActivityDropImgConf($data['img_conf']['drop_03'],'03');
        $this->checkActivityPrizeConf($data['prize_conf']);
        $data['img_conf'] = json_encode($data['img_conf']);
        $data['start_time'] = !empty($data['start_time']) ? strtotime($data['start_time']) : time();
        $data['end_time'] = !empty($data['end_time']) ? strtotime($data['end_time']) : 2145888000;
        $data['count_limit_day'] = !empty($data['count_limit_day']) ? $data['count_limit_day'] : 0;
        $data['count_limit'] = !empty($data['count_limit']) ? $data['count_limit'] : 0;
        $data['create_time'] = time();
        if ($data['count_limit'] && $data['count_limit_day'] && ($data['count_limit_day'] > $data['count_limit'])) {
            $this->error(L("用户参与次数上限不能小于用户每日参与次数"),0);
        }
        if ($data['end_time'] && $data['start_time'] >= $data['end_time']) {
            $this->error(L("活动结束时间不能小于开始时间"),0);
        }
        //查询要更新的数据，将用户更新的数据里的库存使用量更新到新的奖励配置中，防止更新后会刷新已使用库存
        $condition['id'] = $data['id'];
        $oldData = M(MODULE_NAME)->where($condition)->find();
        $oldData['prize_conf'] = json_decode($oldData['prize_conf'],true);
        $data['prize_conf'] = $this->updateStockConf($oldData['prize_conf'],$data['prize_conf']);
        $data['prize_conf'] = json_encode($data['prize_conf']);
        //日志信息
        $log_info = "[" . $data[$this->pk_name] . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";
        $this->pk_value = $data[$this->pk_name];
        $old_data = '';
        $new_data = '';
        if ($this->is_log_for_fields_detail) { // 记录字段更新详细日志
            $condition[$this->pk_name] = $this->pk_value;
            $old_data = M(MODULE_NAME)->where($condition)->find();
            $new_data = $data;
        }
        // 保存
        $result = $form->save($data);
        if ($result !== false) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1, $old_data, $new_data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }

    /**
     * 编辑
     *
     * @access public
     */
    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $vo['img_conf'] = json_decode($vo['img_conf'],true);
        $vo['prize_conf'] = json_decode($vo['prize_conf'],true);
        $vo['start_time'] = !empty($vo['start_time']) ? date("Y-m-d H:i:s",$vo['start_time']) : '';
        $vo['end_time'] = !empty($vo['end_time']) ? date("Y-m-d H:i:s",$vo['end_time']) : '';
        $this->assign('vo',$vo);
        $this->assign('img_conf',$vo['img_conf']);
        $this->assign('prize_conf',$vo['prize_conf']);
        $this->display();
    }

    //上传图片
    public function loadFile() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }
    
        if (!empty($file)) {
            $uploadFileInfo = array(
                    'file' => $file,
                    'isImage' => 1,
                    'asAttachment' => 1,
                    'limitSizeInMB' => round(200 / 1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
        }
        if(!empty($result['aid']) && empty($result['errors'])){
            $imgUrl = get_attr($result['aid'],1,false);
            $rel = array("code" => 1,"imgUrl" => $imgUrl);
        }else if(!empty($result['errors'])){
            $rel = array("code" => 0,"message" => end($result['errors']));
        }else{
            $rel = array("code" => 0,"message" => "图片上传失败");
        }
        echo  json_encode($rel);
    }

    //检查活动名称是否存在
    public function checkActivityName($name,$ajax = 1) {
        if ($ajax == 1) {
            $name = addslashes($_REQUEST['name']);
        } else {
            $name = addslashes($name);
        }
        $condition['name'] = $name;
        $ret = M(MODULE_NAME)->where($condition)->find();
        if ($ret['name']) {
            if ($ajax == 1)
            echo json_encode(array('errno'=>0));
            else
            return true;
        } else {
            if ($ajax == 1)
            echo json_encode(array('errno'=>1));
            else
            return false;
        }
    }
    //检查除当前id外是否还有同名的活动
    public function checkActivityIdName($name,$ajax = 1,$activeId =0) {
        if ($ajax == 1) {
            $name = addslashes($_REQUEST['name']);
            $activeId = $_REQUEST['active_id'] ? intval($_REQUEST['active_id']) : 0;
        } else {
            $name = addslashes($name);
            $activeId = intval($activeId);
        }
        $condition['name'] = $name;
        $ret = M(MODULE_NAME)->where($condition)->find();
        if ($ret['name'] && $ret['id'] != $activeId) {
            if ($ajax == 1)
            echo json_encode(array('errno'=>0));
            else
            return true;
        } else {
            if ($ajax == 1)
            echo json_encode(array('errno'=>1));
            else
            return false;
        }
    }
    //检查图片配置配置参数，不可单项为空
    public function checkActivityDropImgConf($conf,$id) {
        $count = count(array_keys ( $conf, "" ));
        if ($count == 0) {
            if (!preg_match("/^[1-9][0-9]*$/",$conf['drop_img_count_'.$id]) || !preg_match("/^[1-9][0-9]*$/",$conf['drop_img_score_'.$id]) || !preg_match("/^[1-9][0-9]*$/",$conf['drop_img_speed_'.$id])) {
                $this->error(L("已配置的掉落物品出现个数、得分、掉落速度必须为正整数"),0);
                return false;
            }
        } elseif ($count != 5) {
            $this->error(L("已配置的掉落物品有空值"),0);
            return false;
        }
        return true;
    }
    //检查奖励配置参数，不可单项为空
    public function checkActivityPrizeConf($conf) {
        foreach ($conf as $value) {
            $count = count(array_keys ($value, "" ));
            if ($count == 0) {
                if (!preg_match("/^[0-9]*$/",$value['type']) || !preg_match("/^[1-9][0-9]*$/",$value['prize_id']) || !preg_match("/^[1-9][0-9]*$/",$value['count']) || !preg_match("/^[1-9][0-9]*$/",$value['low']) || !preg_match("/^[1-9][0-9]*$/",$value['high'])) {
                    $this->error(L("已配置的奖励配置券组id、库存数量、得分区间必须为正整数"),0);
                    break;
                }
            } elseif ($count != 4) {
                $this->error(L("已配置的奖励配置不能有空值"),0);
                break;
            }
        }
        return true;
    }
    //更新库存使用量
    public function updateStockConf($oldConf,$newConf) {
        foreach ($newConf as $key=>$value) {
            $count = count(array_keys ($value, "" ));
            if ($count == 0) {
                $use_count = isset($oldConf[$key]['use_count']) ? $oldConf[$key]['use_count'] : 0;
                if (intval($newConf[$key]['count']) > 0) {
                    $newConf[$key]['use_count'] = $use_count;
                }
            }
        }
        return $newConf;
    }

    //春节活动-找福袋
    public function findLuckyBag() {
        $form = D('ApiConf');
        $options['where'] = "`name`='saveLuckyBag' AND `site_id`='1'";
        $result = $form->find($options);
        $data = json_decode($result['value'],true);
        $data['is_effect'] = $result['is_effect'];
        $this->assign('data',$data);
        $this->display();
    }
    //春节活动-找福袋,保存数据
    public function saveFindLuckyBag() {
        $dataPre['start_time'] = $_REQUEST['start_time'];
        $dataPre['end_time'] = $_REQUEST['end_time'];
        $dataPre['coupons_type'] = $_REQUEST['coupons_type'];
        $dataPre['coupons_id'] = intval($_REQUEST['coupons_id']);
        $dataPre['coupons_count'] = intval($_REQUEST['coupons_count']);
        $data['is_effect'] = intval($_REQUEST['is_effect']);
        adddeepslashes($dataPre);//过滤参数
        $data['value'] = json_encode($dataPre);
        $data['title'] = '春节任务找福袋';
        $data['name'] = 'saveLuckyBag';
        $data['update_time'] = time();
        $data['site_id'] = 1;
        $data['conf_type'] =5;
        // 保存
        $form = D('ApiConf');
        $options['where'] = "`name`='saveLuckyBag'";
        $result = $form->save($data,$options);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            $this->error('Redis连接异常');
        }
        //如果将活动值为无效则删除缓存，
        if ($data['is_effect'] == 1) {
            $redis->setex('AdminFindLuckyBagInfoCache',432000,$data['value']);
        } else {
            $redis->del('AdminFindLuckyBagInfoCache');
        }

        if (!$result) {
            $data['create_time'] = time();
            $result = $form->add($data);
        }
        if ($result !== false) {
            //成功提示
            save_log($log_info . '找红包后台更新成功', 1, '', $data);

             $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . '找红包后台更新失败', 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }
}
