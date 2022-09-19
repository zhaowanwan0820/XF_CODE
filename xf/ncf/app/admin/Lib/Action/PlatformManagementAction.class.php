<?php
/**
 * 平台用款管理(交易平台用款预警)
 *
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @version
 * @copyright 2017.03
 */

class PlatformManagementAction extends CommonAction
{

    /**
     * 平台用款管理首页
     * @access public
    */
    public function index()
    {
        $form = D(MODULE_NAME);
        $condition = array();
        $condition = $this->_search();
        $condition['is_delete'] = 0;
        $isEffect = isset($condition['is_effect']) && ($condition['is_effect'] === '0' || $condition['is_effect'] == 1) ? $condition['is_effect'] : -1;
        if ($condition['is_effect'] == -1) {
            unset($condition['is_effect']);
        }
        if (empty($condition['advisory_name'])) {
            unset($condition['advisory_name']);
        } else {
            $advisory_name = addslashes($condition['advisory_name']);
            $condition['advisory_name'] = array('like','%'.$advisory_name.'%');
        }
        if (!empty($form)) {
            $this->_list($form,$condition);
        }

        $list = $this->get('list');
        $this->assign('list', $list);
        $this->assign('is_effect', $isEffect);
        $this->assign('advisory_name', $advisory_name);
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
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['money_effect_term_start'] = strtotime($data['money_effect_term_start']);
        $data['money_effect_term_end'] = strtotime($data['money_effect_term_end']);
        $data['operate_person'] = $adm_session['adm_name'];
        $data['create_time'] = time();
        $data['update_time'] = $data['create_time'];
        $isExistPlatform = $form->where(array('advisory_name' => $data['advisory_name']))->find();
        if(!empty($isExistPlatform)) {
            //错误提示
            save_log($data['advisory_name']."--该该咨询机构已经存在", 0);
            $this->error("该该咨询机构已经存在", 0);
        }
        $sumMoney = $this->getSumMoneyByAdvisory($data['advisory_id']);
        if (!empty($sumMoney['money']) && $data['money_limit'] <= $sumMoney['money']) {
            //错误提示
            save_log($data['advisory_name']."--产品合作限额超出平台合作限额，请检查。", 0);
            $this->error("产品合作限额超出平台合作限额，请检查。", 0);
        }
        if ($data['money_effect_term_start'] > $data['money_effect_term_end']) {
            //错误提示
            $this->error("有效期不正确，请重新检查。", 0);
        }
        if ((!empty($sumMoney['min_start']) && !empty($sumMoney['max_end'])) && ($sumMoney['min_start'] < $data['money_effect_term_start'] || $sumMoney['max_end'] > $data['money_effect_term_end'])) {
            //错误提示
            $this->error("产品合作限额有效期超出平台合作限额有效期，请检查。", 0);
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
            save_log($log_info . L("INSERT_SUCCESS"), 1 ,'',$data);
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
        $module = D('DealAgency');
        $result = $module->where(array('type' => 2,'is_effect'=>1))->findAll();
        $this->assign("advisory_list", $result);
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
        $data['money_effect_term_start'] = strtotime($data['money_effect_term_start']);
        $data['money_effect_term_end'] = strtotime($data['money_effect_term_end']);
        $data['update_time'] = time();
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['operate_person'] = $adm_session['adm_name'];
        $isExistPlatform = $form->where(array('id' => array('NEQ',$data['id']),'advisory_name' =>$data['advisory_name']))->find();
        if(!empty($isExistPlatform)) {
            //错误提示
            save_log($data['advisory_name']."--该该咨询机构已经存在", 0);
            $this->error("该该咨询机构已经存在", 0);
        }
        $sumMoney = $this->getSumMoneyByAdvisory($data['advisory_id']);
        if (!empty($sumMoney['money']) && $data['money_limit'] <= $sumMoney['money']) {
            //错误提示
            save_log($data['advisory_name']."--产品合作限额超出平台合作限额，请检查。", 0);
            $this->error("产品合作限额超出平台合作限额，请检查。", 0);
        }
        if ($data['money_effect_term_start'] > $data['money_effect_term_end']) {
            //错误提示
            $this->error("有效期不正确，请重新检查。", 0);
        }
        if ((!empty($sumMoney['min_start']) && !empty($sumMoney['max_end'])) && ($sumMoney['min_start'] < $data['money_effect_term_start'] || $sumMoney['max_end'] > $data['money_effect_term_end'])) {
            //错误提示
            $this->error("产品合作限额有效期超出平台合作限额有效期，请检查。", 0);
        }
        //日志信息
        $log_info = "[" . $data[$this->pk_name] . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";
        $this->pk_value = $data[$this->pk_name];
        $old_data = '';
        $new_data = '';
        // 记录字段更新详细日志
        $condition[$this->pk_name] = $this->pk_value;
        $old_data = M(MODULE_NAME)->where($condition)->find();

        // 保存
        $result = $form->save($data);
        if ($result !== false) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1, $old_data, $data);
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
        $module = D('DealAgency');
        $result = $module->where(array('type' => 2,'is_effect'=>1))->findAll();
        $condition['id'] = intval($_REQUEST['id']);
        $vo = M(MODULE_NAME)->where($condition)->find();
        $vo['money_effect_term_start'] = format_date($vo['money_effect_term_start']);
        $vo['money_effect_term_end'] = format_date($vo['money_effect_term_end']);
        $this->assign('vo',$vo);
        $this->assign("advisory_list", $result);
        $this->display();
    }

    /**
     * 导出所有记录
     */
    public function export_csv()
    {
        $content = implode(',', array('编号','咨询机构名称','用款限额','用款有效期','状态','操作人','操作时间'))."\n";
        $module = M(MODULE_NAME);
        $condition['is_delete'] = 0;
        $list = $this->_list($module,$condition);
        $condition['is_delete'] = 0;
        foreach ($list as $item) {
            $item['is_effect'] = $item['is_effect'] == 1? '有效' : '无效';
            $content .= implode(',', array($item['id'],$item['advisory_name'],$item['money_limit'],format_date($item['money_effect_term_end']),$item['is_effect'],$item['operate_person'],format_date($item['update_time'])))."\n";
        }
        header('Content-Disposition: attachment; filename=platform_management_'.date('Ymd_His').'.csv');
        echo iconv('utf-8', 'gbk//ignore', $content);
        return;
    }
    /**
     * 获取某个咨询机构下的所有产品限额之和
     * @param $id
     * @return float
     */
    public function getSumMoneyByAdvisory($id){
        if (empty($id)) {
            return false;
        }
        $module = D('ProductManagement');
        $res = array();
        $sumMoney = $module->where(array('advisory_id' => intval($id),'is_effect' => 1,'is_delete' => 0))->field('sum(`money_limit`) as money,min(`money_effect_term_start`) as min_start,max(`money_effect_term_end`) as max_end')->findAll();
        $res = array_shift($sumMoney);
        return $res;
    }
}

