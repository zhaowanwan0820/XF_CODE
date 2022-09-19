<?php

/**
 * 商城化债管理-订单相关
 */
class ShopOrderController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    /**
     * 列表
     */
    public function actionIndex()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'appid'=>Yii::app()->request->getParam('appid'),
                'total_num'=>Yii::app()->request->getParam('action_num'),
                'status'=>Yii::app()->request->getParam('auth_status'),
                'action_user_name'=>Yii::app()->request->getParam('action_name'),
                'auth_user_name'=>Yii::app()->request->getParam('auth_name'),
                'action_start'=>Yii::app()->request->getParam('action_start'),
                'action_end'=>Yii::app()->request->getParam('action_end'),
                'auth_start'=>Yii::app()->request->getParam('auth_start'),
                'auth_end'=>Yii::app()->request->getParam('auth_end'),
               
            ];
            //获取用户列表
            $importFileInfo         = (new XfUserShoppingImportFile())->getImportList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/shop/ShopOrder/Auth')) || empty($authList)) {
            $can_auth = 1;
        }
        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);
      
        return $this->renderPartial('index', ['can_auth'=>$can_auth,'shopList'=>$platform['list']]);
    }

    /**
     * 明细
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionDetail()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'upload_id'=>Yii::app()->request->getParam('upload_id')?:-1,
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'order_no'=>Yii::app()->request->getParam('order_no'),
                'order_start'=>Yii::app()->request->getParam('order_start'),
                'order_end'=>Yii::app()->request->getParam('order_end'),
                'order_amount'=>Yii::app()->request->getParam('order_amount'),
                'delivery_no'=>Yii::app()->request->getParam('delivery_no'),
                'goods_name'=>Yii::app()->request->getParam('goods_name'),
                'goods_price'=>Yii::app()->request->getParam('goods_price'),
                'send_start'=>Yii::app()->request->getParam('send_start'),
                'send_end'=>Yii::app()->request->getParam('send_end'),
                'exchange_no'=>Yii::app()->request->getParam('exchange_no'),
                
            ];
            if ($deal_id = Yii::app()->request->getParam('deal_id')) {
                $params['deal_id'] = $deal_id;
            }
            if ($name = Yii::app()->request->getParam('name')) {
                $params['name'] = $name;
            }
            //获取用户列表
            $importFileInfo         = (new XfUserShoppingInfo())->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }

        return $this->renderPartial('detail', ['upload_id'=>Yii::app()->request->getParam('upload_id')]);
    }
    /**
     *上传
     */
    public function actionUpload()
    {
        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);

        if (\Yii::app()->request->isPostRequest) {
            $appid = $_POST['appid'];

            try {
                $return = $this->uploadOffline($appid);

                return $this->renderPartial(
                    'importFile',
                    [
                        'end'             => 1,
                        'total_amount'    => $return['total_amount'],
                        'total_num'       => $return['total_num'],
                        'total_integral'  => $return['total_integral'],
                        
                    ]
                );
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage(),'shopList' => $platform['list']]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0, 'shopList' => $platform['list']]);
    }

    /**
     * 审核
     */
    public function actionAuth()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'status' => 1,
              
            ];
            XfUserShoppingImportFile::authImportFile($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 取消
     */
    public function actionCancel()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'status' => 3,
                
            ];
            XfUserShoppingImportFile::authImportFile($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }


    private function uploadOffline($appid)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');


        try {
            Yii::app()->fdb->beginTransaction();

            if (empty($_FILES['offline']['name'])) {
                throw new Exception('请选择文件上传');
            }

            // 读取数据
            $file = CUploadedFile::getInstanceByName(key($_FILES));
            if ($file->getHasError()) {
                $error = [
                    1 => '上传文件超过了服务器限制',
                    2 => '上传文件超过了脚本限制',
                    3 => '文件只有部分被上传',
                    4 => '没有文件被上传',
                    6 => '找不到临时文件夹',
                    7 => '文件写入失败',
                ];
                throw new Exception(isset($error[$file->getError()]) ? $error[$file->getError()] : '未知错误');
            }

            Yii::$enableIncludePath = false;
            Yii::import('application.extensions.phpexcel.PHPExcel', 1);
            $excelFile     = $file->getTempName();
            $inputFileType = PHPExcel_IOFactory::identify($excelFile);
            $objReader     = PHPExcel_IOFactory::createReader($inputFileType);
            $excelReader   = $objReader->load($excelFile);
            $phpexcel      = $excelReader->getSheet(0);
            $total_line    = $phpexcel->getHighestRow();
            $execlData     = $phpexcel->toArray();

            // 验证模板
            if ($total_line > 10001) {
                throw new Exception('数据量过大');
            }
            $title = implode(',', $execlData[0]);
           
            if ($title !== '先锋数据中心openid*,购物订单号*,下单时间*,订单金额*,订单使用债权积分*,订单使用商城赠送积分,商品名称*,商品价格*,商品可使用积分数*,兑换积分流水号,*发货时间,*物流单号,快递公司') {
                throw new Exception('请使用指定模板导入');
            }
            

            array_shift($execlData);

            $admin_id        = \Yii::app()->user->id;
            $time            = time();

            $dir      = 'upload/shop_order/' . date('Ym', time()) . '/';
            $file_dir = $dir . time().mt_rand(1000, 999). '.' . $file->getExtensionName();
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                throw new Exception('创建目录失败');
            }
            if (!$file->saveAs($file_dir)) {
                throw new Exception('上传失败');
            }

            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $total_num = 0;
            $total_amount = 0;
            $total_integral = 0;
            foreach ($execlData as $line => $content) {
                $user_id = $content[0];
                if (empty($user_id)) {
                    continue;
                }
                $order_no = $content[1];
                $order_time = strtotime($content[2]);
                $order_amount = $content[3];
                $debt_integral_amount = $content[4];
                $shop_integral_amount = $content[5];
                $goods_name = $content[6];
                $goods_price = $content[7];
                $goods_use_integral = $content[8];
                $exchange_no = $content[9];
                $send_time = strtotime($content[10]);
                $delivery_no = $content[11];
                $delivery_name = $content[12];

                if (empty($order_no)) {
                    throw new Exception('购物订单号不能为空');
                }
                
                if (empty($order_amount)) {
                    throw new Exception('订单金额不能为空');
                }
                if (empty($order_time)) {
                    throw new Exception('下单时间不能为空');
                }
                if (empty($debt_integral_amount)) {
                    throw new Exception('订单使用债权积分不能为空');
                }
                if (empty($goods_name)) {
                    throw new Exception('商品名称不能为空');
                }
                if (empty($goods_price)) {
                    throw new Exception('商品价格不能为空');
                }
                if (empty($goods_use_integral)) {
                    throw new Exception('商品可使用积分数不能为空');
                }
                if (empty($send_time)) {
                    throw new Exception('发货时间不能为空');
                }
                if (empty($delivery_no)) {
                    throw new Exception('物流单号不能为空');
                }
               
                $order_no_arr[] = "'".$content[1]."'";
                $total_num ++;
                $total_amount+=$content[3];
                $total_integral+=$content[4];
            }
            $check_sql = "select order_no from xf_user_shopping_info where order_no in (".implode(',', $order_no_arr).") and status in (0,1) ";
            $res = Yii::app()->fdb->createCommand($check_sql)->queryAll();
            if ($res) {
                $order_no = ArrayUntil::array_column($res, 'order_no');
                throw new Exception('以下商品订单号重复:'.implode(',', $order_no));
            }
           
            $fileModel                   = new XfUserShoppingImportFile();
            $fileModel->file_name        = $file->getName();
            $fileModel->file_path        = $file_dir;
            $fileModel->platform_id      = $appid;
            $fileModel->action_admin_id  = $admin_id;
            $fileModel->action_user_name = $username;
            $fileModel->addtime          = $time;
            $fileModel->update_time      = $time;
            $fileModel->total_num        = $total_num;
            $fileModel->total_amount     = $total_amount;
            $fileModel->total_integral   = $total_integral;
            if (false === $fileModel->save()) {
                throw new Exception('导入记录表失败');
            }
            $file_id = $fileModel->id;

            $insert_sql = "INSERT INTO xf_user_shopping_info (`platform_id`,`user_id`,`order_no`,`order_time`,`order_amount`,`debt_integral_amount`,`shop_integral_amount`,`goods_name`,`goods_price`,`goods_use_integral`,`exchange_no`,`delivery_no`,`send_time`,`delivery_name`,`upload_id`,`add_time`)". ' values ';
       
            foreach ($execlData as $line => $content) {
                $user_id = $content[0];
                $order_no = $content[1]?:'';
                $order_time = strtotime($content[2])?:0;
                $order_amount = $content[3]?:0;
                $debt_integral_amount = $content[4]?:0;
                $shop_integral_amount = $content[5]?:0;
                $goods_name = $content[6]?:'';
                $goods_price = $content[7]?:0;
                $goods_use_integral = $content[8]?:0;
                $exchange_no = $content[9]?:'';
                $send_time = strtotime($content[10])?:0;
                $delivery_no = $content[11]?:'';
                $delivery_name = $content[12]?:'';

                $insert_sql .= " (".$appid.",". $user_id.",'".$order_no."',$order_time,$order_amount,$debt_integral_amount, $shop_integral_amount,'".$goods_name."',$goods_price,$goods_use_integral,'".$exchange_no."','".$delivery_no."',$send_time ,'".$delivery_name ."',$file_id,$time),";
            }
            $insert_sql = rtrim($insert_sql, ',');
            
            if (false === Yii::app()->fdb->createCommand($insert_sql)->execute()) {
                throw new Exception('数据导入失败');
            }

           
            if (false === $fileModel->save()) {
                throw new Exception('统计数据保存失败');
            }
            Yii::app()->fdb->commit();
            return [
                'total_num'         => $total_num,
                'total_amount'         => $total_amount,
                'total_integral'         => $total_integral,
                
            ];
        } catch (Exception $e) {
            Yii::app()->fdb->rollback();
            throw $e;
        }
    }
}
