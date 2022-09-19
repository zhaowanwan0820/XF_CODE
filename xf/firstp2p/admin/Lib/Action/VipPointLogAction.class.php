<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class VipPointLogAction extends CommonAction {

    public function __construct()
    {
        parent::__construct();
        $this->model = MI('VipPointLog', 'vip', 'slave');
        $this->assign('sourceTypes', MI('VipSourceWeightConf', 'vip', 'slave')->where()->getField('id,name'));
        //$this->assign('sourceTypes', ['0' => '全部', '1' => '投资P2P', '2' => '投资专享', '3' => '签到']);
    }

    public function index()
    {
        $where = [];
        if ($_REQUEST['user_id']) {
            $where['user_id'] = intval($_REQUEST['user_id']);
        }
        if ($_REQUEST['user_mobile']) {
            $user = MI('User')->where("mobile='{$_REQUEST['user_mobile']}'")->find();
            $where['user_id'] = intval($user['id']);
        }
        if ($_REQUEST['source_type']) {
            $where['source_type'] = intval($_REQUEST['source_type']);
        }

        $this->_list($this->model, $where);

        $this->display();
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            $user = MI('User')->where("id={$item['user_id']}")->find();
            $item['user_name'] = $user['real_name'];
            $item['user_mobile'] = substr_replace($user['mobile'], '****', 3, 4);
            $item['expire_time'] = $item['expire_time'] ? date('Y-m-d H:i:s', $item['expire_time']) : '-';
        }
    }

    public function export_csv()
    {
        ini_set('memory_limit', '1024M');

        $map = [];
        if (empty($_REQUEST['ids'])) {
            if ($_REQUEST['user_id']) {
                $map['user_id'] = intval($_REQUEST['user_id']);
            }
            if ($_REQUEST['user_mobile']) {
                $user = MI('User')->map("mobile='{$_REQUEST['user_mobile']}'")->find();
                $map['user_id'] = intval($user['id']);
            }
            if ($_REQUEST['source_type']) {
                $map['source_type'] = intval($_REQUEST['source_type']);
            }
        } else {
            $ids = $_REQUEST['ids'];
            $map = "id IN($ids)";
        }

        $data = $this->model->where($map)->select();
        $list = $this->form_index_list($data);
        $list = $this->formatCSV($list);

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=红包任务列表.csv");
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        foreach ($list as $line) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, $line);
        }
    }

    private function formatCSV($list)
    {
        $csvTitle = ['编号', '用户ID', '用户名称', '用户手机号', '经验值变动', '有效期至', '详情'];
        $formatList = [$csvTitle];
        foreach ($list as $item) {
            $formatList[] = [
                $item['id'], $item['user_id'], $item['user_name'], $item['user_mobile'],
                $item['point'], $item['expire_time'], $item['info']
            ];
        }
        array_walk_recursive($formatList, function(&$item) {
            $item = mb_convert_encoding($item, 'gbk', 'utf8');
        });
        return $formatList;
    }

}
