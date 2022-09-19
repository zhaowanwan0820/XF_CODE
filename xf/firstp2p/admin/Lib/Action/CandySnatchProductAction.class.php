<?php

use libs\db\Db;
use core\service\AddressService;

/**
 * Created by PhpStorm.
 * User: wangpeipei
 * Date: 2018/10/22
 * Time: 17:31
 */
class CandySnatchProductAction extends CommonAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $productName = !empty($_REQUEST['product_name']) ? addslashes(trim($_REQUEST['product_name'])) : '';
        $codeTotal = !empty($_REQUEST['code_total']) ? intval($_REQUEST['code_total']) : '';

        $model = M('snatch_product', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('snatch_product');

        $condition = "1";
        if (!empty($productName)) {
            $condition .= " AND short_title LIKE '%{$productName}%'";
        }

        if (!empty($codeTotal)) {
            $condition .= " AND price = '{$codeTotal}'";
        }

        $this->_list($model, $condition);
        $list = $this->get('list');
        $list = $this->getUsedProduct($list);
        foreach ($list as $key => $value) {
            $list[$key]['status'] = $value['status'] == core\service\candy\CandySnatchService::PRODUCT_STATUS_PROCESS ? '上线' : '下线';
        }
        $this->assign('list', $list);
        $this->assign('productName', $productName);
        $this->assign('codeTotal', $codeTotal);
        $this->display();
    }

    /**
     * 获得当日商品消耗量
     */
    private function getUsedProduct(array $list)
    {
        $startTime = strtotime(date('Ymd'));
        foreach ($list as $key => $value) {
            $list[$key]['number'] = $value['stock'] - Db::getInstance('candy')->
                getOne("SELECT count(*) FROM snatch_period WHERE create_time >= '{$startTime}' AND product_id = '{$value['id']}'");
        }
        return $list;
    }

    /**
     * 新增商品
     */
    public function add()
    {
        $this->display();
    }

    /**
     * 保存数据（修改或更新）
     */
    public function save()
    {
        $newdb = Db::getInstance('candy');
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $productInfo = array(
            'title' => isset($_REQUEST['title']) ? addslashes(trim($_REQUEST['title'])) : '',
            'short_title' => isset($_REQUEST['short_title']) ? addslashes(trim($_REQUEST['short_title'])) : '',
            'price' => isset($_REQUEST['price']) ? intval($_REQUEST['price']) : 0,
            'images' => isset($_REQUEST['images']) ? json_encode($_REQUEST['images']) : '',
            'detail' => isset($_REQUEST['detail']) ? addslashes(trim($_REQUEST['detail'])) : '',
            'create_time' => time(),
            'stock' => isset($_REQUEST['stock']) ? intval($_REQUEST['stock']) : 0,
            'type' => isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0,
            'sort' => isset($_REQUEST['sort']) ? intval($_REQUEST['sort']) : 0,
            'status' => isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0,
        );
        if ($id != 0) {//对商品进行更新操作
            $newdb->update('snatch_product', $productInfo, 'id=' . $id);
            if ($newdb->affected_rows() < 1) {
                throw new \Exception('商品更新失败');
            }
            $this->success('操作成功', 0, '?m=CandySnatchProduct&a=index');
        }
        $insertId = $newdb->insert('snatch_product', $productInfo);
        if (empty($insertId)) {
            throw new \Exception('添加商品失败');
        }
        $this->success('操作成功', 0, '?m=CandySnatchProduct&a=add');
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $db = Db::getInstance('candy');
        $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $periods = $db->getAll("SELECT id FROM snatch_period WHERE product_id = {$id}");
        $ids = array_column($periods, 'id');
        $robotPutAmount = 0;
        foreach ($ids as $item) {
            $robotPutAmount += $db->getOne("SELECT sum(code_count) FROM snatch_order WHERE period_id = {$item} AND user_id > 1000000000 ");
        }

        $snatchInfo = $db->getRow("SELECT count(id) as snatchAmount, sum(code_used) as candyAmount FROM snatch_period WHERE product_id = {$id}");
        $productInfo = $this->getProductInfo($id);
        $images = json_decode($productInfo['images']);
        $this->assign('productInfo', $productInfo);
        $this->assign('snatchInfo', $snatchInfo);
        $this->assign('images', $images);
        if ($snatchInfo['candyAmount'] == 0) {
            $this->assign('robotPercent', 0 .'%' );
        }else{
            $this->assign('robotPercent', bcdiv($robotPutAmount * 100, $snatchInfo['candyAmount'], 2) . '%');
        }
        $this->assign('userPutAmount', $snatchInfo['candyAmount'] - $robotPutAmount);
        $this->display();
    }

    /**
     * 修改商品
     */
    public function edit()
    {
        $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (empty($id)) {
            throw new \Exception('您所修改的商品id为空');
        }

        $productInfo = $this->getProductInfo($id);
        $images = json_decode($productInfo['images']);

        $this->assign('productInfo', $productInfo);
        $this->assign('images', $images);
        $this->display();
    }

    /**
     * 获取商品信息
     */
    private function getProductInfo($id)
    {
        return Db::getInstance('candy')->getRow("SELECT * FROM snatch_product WHERE id = '{$id}'");
    }

}