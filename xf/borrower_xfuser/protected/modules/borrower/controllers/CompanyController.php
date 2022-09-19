<?php

class CompanyController extends \iauth\components\IAuthController
{
    public $pageSize = 10;
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success','Error'
        );
    }
    /**
     * 第三方公司列表
     * @return mixed
     */
    public function actionList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 企业名称
            if (!empty($_POST['company_id'])) {
                $company_id = intval($_POST['company_id']);
                $where .= " AND id = {$company_id} ";
            }
            // 税号
            if (!empty($_POST['tax_number'])) {
                $t      = trim($_POST['tax_number']);
                $where .= " AND tax_number = '{$t}' ";
            }

            // 企业联系人
            if (!empty($_POST['contract_name'])) {
                $t      = trim($_POST['contract_name']);
                $where .= " AND contract_name = '{$t}' ";
            }
            // 联系电话
            if (!empty($_POST['contract_mobile'])) {
                $t      = trim($_POST['contract_mobile']);
                $where .= " AND contract_mobile = '{$t}' ";
            }
            // 联系邮箱
            if (!empty($_POST['contract_email'])) {
                $t      = trim($_POST['contract_email']);
                $where .= " AND contract_email = '{$t}' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM firstp2p_cs_company {$where} ";
            $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM firstp2p_cs_company {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['add_time']    = $value['add_time'] ? date('Y-m-d H:i:s' , $value['add_time']) : '——';
                $value['update_time']    = $value['update_time'] ? date('Y-m-d H:i:s' , $value['update_time']) : '——';;
                if ($value['business_license']) {
                    $value['business_license'] = "<a href='/{$value['business_license']}' download><button class='layui-btn layui-btn-primary'>下载</button></a>";
                } else {
                    $value['business_license'] = '——';
                }
                $listInfo[] = $value;
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();
        return $this->renderPartial('list',array( 'company_list'=>$company_list));
    }


    /**
     * 第三方公司 - 添加
     */
    public function actionAddCompany()
    {


        if (!empty($_POST)) {
            if (empty($_POST['name'])  ) {
                return $this->actionError('公司名称不能为空', 5);
            }
            $company_ids = $this->getCompanyIds($_POST['name']);
            if(!empty($company_ids)){
                return $this->actionError('公司名称已存在', 5);
            }
            if (empty($_POST['tax_number'])  ) {
                return $this->actionError('税号不能为空', 5);
            }
            if (empty($_FILES['business_license'])) {
                return $this->actionError('请上传营业执照扫描件' , 5);
            }
            if (empty($_POST['contract_name'])  ) {
                return $this->actionError('企业联系人不能为空', 5);
            }
            if (empty($_POST['contract_mobile']) || !FunctionUtil::IsMobile($_POST['contract_mobile']) ) {
                return $this->actionError('联系电话有误', 5);
            }
            if (empty($_POST['contract_email']) || !preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',$_POST['contract_email']) ) {
                return $this->actionError('联系邮箱有误', 5);
            }
            //营业执照扫描件
            $file = $this->upload_rar('business_license');
            if ($file['code'] !== 0) {
                return $this->actionError($file['info'], 5);
            }
            $business_license = $file['data'];

            /*
            $upload_oss = $this->upload_oss('./'.$file['data'], 'company_info/'.$file['data']);
            if ($upload_oss === false) {
                return $this->actionError('营业执照扫描件上传至OSS失败', 5);
            }
            unlink('./'.$file['data']);
            */
            //写入
            //$business_license_url = 'company_info/'.$file['data'];
            $time = time();
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $op_user_name = Yii::app()->user->name ? Yii::app()->user->name : '' ;
            $ip = Yii::app()->request->userHostAddress;
            $sql = "INSERT INTO firstp2p_cs_company (name,tax_number,business_license,contract_name,contract_mobile,contract_email,add_user_id,add_user_name,add_ip,add_time) 
                VALUES ('{$_POST['name']}' , '{$_POST['tax_number']}' , '{$business_license}' , '{$_POST['contract_name']}' , '{$_POST['contract_mobile']}', '{$_POST['contract_email']}'  , {$op_user_id}, '{$op_user_name}' , '{$ip}' , {$time} ) ";
            $add_company_info = Yii::app()->cmsdb->createCommand($sql)->execute();
            if (!$add_company_info) {
                return $this->actionError('添加第三方公司失败', 5);
            }
            return $this->actionSuccess('添加第三方公司成功', 3);
        }

        return $this->renderPartial('AddCompany', array());
    }

    /**
     * 第三方公司 - 编辑
     */
    public function actionEditCompany()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入ID', 5);
            }
            if (empty($_POST['contract_name'])  ) {
                return $this->actionError('企业联系人不能为空', 5);
            }
            if (empty($_POST['contract_mobile']) || !FunctionUtil::IsMobile($_POST['contract_mobile']) ) {
                return $this->actionError('联系电话有误', 5);
            }
            if (empty($_POST['contract_email']) || !preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',$_POST['contract_email']) ) {
                return $this->actionError('联系邮箱有误', 5);
            }
            if (empty($_POST['name']) || empty($_POST['tax_number']) || empty($_POST['business_license']) ) {
                return $this->actionError('异常数据', 5);
            }


            $time = time();
            $id   = intval($_POST['id']);
            $sql  = "SELECT * from firstp2p_cs_company where id={$id} ";
            $res = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }

            //无需修改
            if($_POST['contract_mobile'] == $res['contract_mobile'] && $_POST['contract_name'] == $res['contract_name'] && $_POST['contract_email'] == $res['contract_email']){
                return $this->actionError('无数据修改，无需保存', 5);
            }

            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip = Yii::app()->request->userHostAddress;
            $edit_sql = " , update_user_id = {$op_user_id} , update_ip = '{$ip}' , update_time = {$time} ";
            $sql = "UPDATE firstp2p_cs_company SET contract_name = '{$_POST['contract_name']}' , contract_mobile = '{$_POST['contract_mobile']}', contract_email = '{$_POST['contract_email']}' $edit_sql  WHERE id = {$res['id']} ";
            $update_company_info = Yii::app()->cmsdb->createCommand($sql)->execute();
            if (!$update_company_info ) {
                return $this->actionError('保存失败', 5);
            }
            return $this->actionSuccess('保存成功', 3);
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * from  firstp2p_cs_company  where  id = {$id} ";
            $res = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }

            $agreement_url = explode('/', $res['business_license']);
            $res['business_license'] = Yii::app()->c->baseUrl.'/'.$res['business_license'];
            if ($agreement_url[0] == 'upload') {
                $res['business_license_name'] = $agreement_url[2];
            } elseif ($agreement_url[0] == 'company_info') {
                $res['business_license_name'] = $agreement_url[3];
            }
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('EditCompany', array('res' => $res));
    }

    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z', 'pdf', 'jpg', 'png');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的压缩文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的压缩文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '压缩文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有压缩文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '压缩文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '压缩文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }

    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    public function actionError($msg = '失败', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    public function actionSuccess($msg = '成功', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }


    /**
     * 优惠政策管理
     * @return mixed
     */
    public function actionFavouredPolicy(){
        if(!empty($_POST)){
            if(!is_numeric($_POST['capital_policy']) || $_POST['capital_policy'] > 100 || $_POST['capital_policy'] < 0){
                return $this->actionError('本金优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['interest_policy']) || $_POST['interest_policy'] > 100 || $_POST['interest_policy'] < 0){
                return $this->actionError('利息优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['late_fee_policy']) || $_POST['late_fee_policy'] > 100 || $_POST['late_fee_policy'] < 0){
                return $this->actionError('滞纳金优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['penalty_interest_policy']) || $_POST['penalty_interest_policy'] > 100 || $_POST['penalty_interest_policy'] < 0){
                return $this->actionError('罚息优惠政策填写有误', 5);
            }

            $data = [];
            $data['id'] = 1;
            $data['capital_policy'] = $_POST['capital_policy'];
            $data['interest_policy'] = $_POST['interest_policy'];
            $data['late_fee_policy'] = $_POST['late_fee_policy'];
            $data['penalty_interest_policy'] = $_POST['penalty_interest_policy'];
            $data['last_edit_time'] = time();
            $data['last_edit_uid'] = Yii::app()->user->id;
            $ret = BaseCrudService::getInstance()->add('Firstp2pFavouredPolicy', $data);
            if (false == $ret) {
                return $this->actionError('保存失败', 5);
            }
            return $this->actionSuccess('保存成功', 3);
        }

        $tpl_name = 'favouredPolicy_add';
        $sql = "SELECT * from  firstp2p_favoured_policy  where  id = 1 ";
        $res = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if ($res) {
            $tpl_name = 'favouredPolicy_view';
        }
        return $this->renderPartial($tpl_name, array('info' => $res));

    }

    /**
     * 编辑优惠政策管理
     * @return mixed
     */
    public function actionFavouredPolicyEdit(){
        //优惠政策数据
        $sql = "SELECT * from  firstp2p_favoured_policy  where  id = 1 ";
        $res = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        //编辑更新
        if (\Yii::app()->request->isPostRequest) {
            if(!is_numeric($_POST['capital_policy']) || $_POST['capital_policy'] > 100 || $_POST['capital_policy'] < 0){
                return $this->actionError('本金优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['interest_policy']) || $_POST['interest_policy'] > 100 || $_POST['interest_policy'] < 0){
                return $this->actionError('利息优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['late_fee_policy']) || $_POST['late_fee_policy'] > 100 || $_POST['late_fee_policy'] < 0){
                return $this->actionError('滞纳金优惠政策填写有误', 5);
            }
            if(!is_numeric($_POST['penalty_interest_policy']) || $_POST['penalty_interest_policy'] > 100 || $_POST['penalty_interest_policy'] < 0){
                return $this->actionError('罚息优惠政策填写有误', 5);
            }

            //save模型更新
            $saveModel = Firstp2pFavouredPolicy::model();
            $saveModel->id = 1;
            $saveModel->capital_policy = $_POST['capital_policy'];
            $saveModel->interest_policy = $_POST['interest_policy'];
            $saveModel->late_fee_policy = $_POST['late_fee_policy'];
            $saveModel->penalty_interest_policy = $_POST['penalty_interest_policy'];
            $saveModel->last_edit_time = time();
            $saveModel->last_edit_uid = Yii::app()->user->id;

            //编辑
            if ($saveModel->save(false)) {
                return $this->actionSuccess('编辑成功', 3);
            }
            return $this->actionError('编辑失败', 5);
        }

        return $this->renderPartial('favouredPolicy_edit', array('info' => $res));
    }
    public function getCompanyIds($company_name){
        $sql   = "SELECT id FROM firstp2p_cs_company where name='$company_name' ";
        $company_ids = \Yii::app()->cmsdb->createCommand($sql)->queryColumn();
        return $company_ids;
    }
    /**
     * 第三方公司分配列表
     * @return mixed
     */
    public function actionDistributionList()
    {

        // 条件筛选
        $where = " WHERE 1 = 1 ";

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        /*
        $is_item_id =0 ;
        $distribution_info = \Yii::app()->db->createCommand()
            ->select('item_id')
            ->from('itz_auth_assignment')
            ->where(" user_id=$op_user_id")
            ->queryRow();
        if($distribution_info && $distribution_info['item_id'] == \Yii::app()->c->xf_config['borrower_distribution_itemid']){
            $where .= " and company_user_id ={$op_user_id} ";
            $is_item_id = 1;
        }*/


        // 企业名称
        /*
        if (!empty($_GET['company_name'])) {
            $company_name = trim($_GET['company_name']);
            $company_ids = $this->getCompanyIds($company_name);
            $where .= $company_ids ? " and company_id in (".implode(',', $company_ids).")" : ' and company_id=-1 ';
        }*/

        if(!empty($_GET['company_id'])){
            $company_id = !is_numeric($_GET['company_id']) ? -1 : $_GET['company_id'];
            $where .=  " and company_id =$company_id";
        }

        if (isset($_GET['status']) && in_array($_GET['status'], [0,1,2,3])) {
            $where .= " and status ={$_GET['status']} ";
        }

        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }


        $sql   = "SELECT count(id) AS count FROM firstp2p_borrower_distribution {$where} ";
        $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
        if ($count > 0) {
            // 查询数据
            $sql = "SELECT * FROM firstp2p_borrower_distribution {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            $status_tips = ['待审核','合作中','审核拒绝','停止合作'];
            foreach ($list as $key => $value) {
                $value['addtime']    = $value['addtime'] ? date('Y-m-d H:i:s' , $value['addtime']) : '——';
                $value['auth_time']    = $value['auth_time'] ? date('Y-m-d H:i:s' , $value['auth_time']) : '——';
                $value['start_time_tips']    = $value['start_time'] ? date('Y-m-d H:i:s' , $value['start_time']) : '——';
                $value['end_time_tips']    = $value['end_time'] ? date('Y-m-d H:i:s' , $value['end_time']) : '——';
                $value['company_name'] = $this->getCompanyName($value['company_id']);
                if ($value['file_path']) {
                    $value['file_path'] = "<a href='/{$value['file_path']}' download><button class='layui-btn layui-btn-primary'>下载</button></a>";
                } else {
                    $value['file_path'] = '——';
                }
                $value['status_tips'] =  $status_tips[$value['status']];
                $listInfo[] = $value;
            }


            $criteria = new CDbCriteria();
            $pages    = new CPagination($count);
            $pages->pageSize = $limit;
            $pages->applyLimit($criteria);
            $pages = $this->widget('CLinkPager', array(
                'header'=>'',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '末页',
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'pages' => $pages,
                'maxButtonCount'=>8,
                'cssFile'=>false,
                'htmlOptions' =>array("class"=>"pagination"),
                'selectedPageCssClass'=>"active"
            ), true);
        }

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();

        return $this->renderPartial('distributionList', array('listInfo' => $listInfo, 'pages' => $pages , 'count' => $count , 'limit' => $limit , 'page' => $page,'company_list'=>$company_list));
    }

    public function getCompanyName($company_id)
    {
        $itemData = \Yii::app()->cmsdb->createCommand()
            ->select('name')
            ->from('firstp2p_cs_company')
            ->where("id = $company_id")
            ->queryRow();
        return $itemData['name'];
    }

    public function actionAuth()
    {
        // 校验记录ID
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $new_status  = intval($_POST['status']);
            if(!in_array($new_status, [1,2,3])){
                $this->echoJson(array(), 1002, '操作异常');
            }
            $sql = "SELECT * FROM firstp2p_borrower_distribution WHERE id = {$id} ";
            $old = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 1000, 'ID输入错误');
            }
            if($new_status == $old['status']){
                $this->echoJson(array(), 1002, '状态无变更，无需操作');
            }

            if($old['status'] != 0 && in_array($new_status, [1,2])){
                $this->echoJson(array(), 1002, '非待审核状态，操作异常');
            }

            if($old['status'] != 1 && $new_status == 3){
                $this->echoJson(array(), 1002, '非合作中状态，无需终止操作');
            }

            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $auth_user_name = Yii::app()->user->name ? Yii::app()->user->name : '' ;
            $time = time();
            $sql = "UPDATE firstp2p_borrower_distribution SET status = {$new_status} , auth_admin_id = {$op_user_id} , auth_user_name = '{$auth_user_name}' , auth_time = {$time} WHERE id = {$old['id']} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1002, '操作失败');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }


    /**
     * 分配借款人录入
     */
    public function actionAddDistribution()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '分配借款人模板 '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {

            //角色校验
            /*
            $op_user_id = \Yii::app()->user->id;
            $distribution_info = \Yii::app()->db->createCommand()
                ->select('item_id')
                ->from('itz_auth_assignment')
                ->where(" user_id=$op_user_id")
                ->queryRow();
            if(!$distribution_info || $distribution_info['item_id'] != \Yii::app()->c->xf_config['borrower_distribution_itemid']){
                return $this->actionError('请使用归属第三方公司登陆账号添加分配', 5);
            }*/

            // 企业名称
            if (empty($_POST['company_id']) || empty($_POST['start_time']) || empty($_POST['end_time']) || empty($_POST['s_type']) || !is_numeric($_POST['company_id'])) {
                return $this->actionError('必填项不能为空', 5);
            }
            /*
            $company_ids = $this->getCompanyIds($_POST['company_name']);
            if( count($company_ids) == 0){
                return $this->actionError('归属第三方公司不存在', 5);
            }
            if(count($company_ids) > 1 ){
                return $this->actionError('归属第三方公司数据异常', 5);
            }*/

            $check_company = $this->getCompanyName($_POST['company_id']);
            if(!$check_company){
                $this->echoJson("", 0, "归属第三方公司不存在");
            }

            //第三方公司
            /*
            $user_info = \Yii::app()->db->createCommand()
                ->select('company_id, type')
                ->from('itz_user')
                ->where(" id=$op_user_id")
                ->queryRow();
            if(!$user_info || empty($user_info['company_id']) || $user_info['type'] != 1){
                return $this->actionError('账号所属公司数据异常', 5);
            }

            if($user_info['company_id'] != $company_ids[0]){
                return $this->actionError('请填写自己账号所属的第三方公司名称', 5);
            }*/
            if(!in_array($_POST['s_type'], [1,2])){
                return $this->actionError('分配借款人方式数据错误', 5);
            }

            if($_POST['s_type'] == 1 && (empty($_POST['user_id']) || !is_numeric($_POST['user_id']))){
                return $this->actionError('借款人ID填写有误', 5);
            }

            if($_POST['s_type'] == 2 && empty($_FILES['file_path'])){
                return $this->actionError('请选择要上传的数据文件', 5);
            }

            $_POST['start_time'] = $start_time = strtotime($_POST['start_time']);
            $_POST['end_time'] = $end_time = strtotime($_POST['end_time']);
            $time = time();
            if($start_time <= $time || $end_time<= $time || $end_time<= $time){
                return $this->actionError('请填写有效且正确的时间', 5);
            }

            $data = [];
            $template_url = '';
            if($_POST['s_type'] == 2){
                $upload_xls = $this->upload_xls('file_path');
                if ($upload_xls['code'] != 0) {
                    return $this->actionError($upload_xls['info'], 5);
                }
                $template_url = $upload_xls['data'];
                include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
                include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
                $xlsPath   = './'.$template_url;
                $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
                $xlsReader->setReadDataOnly(true);
                $xlsReader->setLoadSheetsOnly(true);
                $Sheets = $xlsReader->load($xlsPath);
                $Rows   = $Sheets->getSheet(0)->getHighestRow();
                $data   = $Sheets->getSheet(0)->toArray();
                if ($Rows < 2) {
                    return $this->actionError('分配信息中无数据', 5);
                }
                if ($Rows > 20001) {
                    return $this->actionError('分配信息中的数据超过2万行', 5);
                }
                unset($data[0]);
            }else{
                $data[][] = $_POST['user_id'];
            }

            //数据入库
            $result = PartialService::getInstance()->add_borrower_distribution($_POST, $template_url, $data);
            if ($result['code'] != 0) {
                unlink('./'.$template_url);
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess($result['info'], 3);
        }

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();

        return $this->renderPartial('AddDistribution', array('company_list'=>$company_list));
    }

    private function upload_xls($name)
    {
        $file  = $_FILES[$name];
        $types = array('xlsx' , 'xls');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的xls文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的xls文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => 'xls文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有xls文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => 'xls文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => 'xls文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => 'xls文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建xls文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存xls文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存xls文件失败' , 'data' => '');
        }
    }


    /**
     * 分配详情
     * @return mixed
     */
    public function actionDistributionView(){
        if(empty($_GET['id']) || !is_numeric($_GET['id']) ){
            $_GET['id'] = -1;
        }
        // 条件筛选
        $where = " WHERE distribution_id = {$_GET['id']} ";

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        $distribution_info = \Yii::app()->db->createCommand()
            ->select('item_id')
            ->from('itz_auth_assignment')
            ->where(" user_id=$op_user_id")
            ->queryRow();
        if($distribution_info && $distribution_info['item_id'] == \Yii::app()->c->xf_config['borrower_distribution_itemid']){
            $where .= " and company_user_id ={$op_user_id} ";
        }



        if (isset($_GET['status']) && in_array($_GET['status'], [ 1,2 ])) {
            $where .= " and status ={$_GET['status']} ";
        }

        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }

        $sql   = "SELECT count(id) AS count FROM firstp2p_borrower_distribution_detail {$where} ";
        $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
        if ($count > 0) {
            // 查询数据
            $sql = "SELECT * FROM firstp2p_borrower_distribution_detail {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            $status_tips = [1=>'导入成功',2=>'导入失败'];
            foreach ($list as $key => $value) {
                $value['addtime']    = $value['addtime'] ? date('Y-m-d H:i:s' , $value['addtime']) : '——';
                $value['status_tips'] =  $status_tips[$value['status']];
                $listInfo[] = $value;
            }


            $criteria = new CDbCriteria();
            $pages    = new CPagination($count);
            $pages->pageSize = $limit;
            $pages->applyLimit($criteria);
            $pages = $this->widget('CLinkPager', array(
                'header'=>'',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '末页',
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'pages' => $pages,
                'maxButtonCount'=>8,
                'cssFile'=>false,
                'htmlOptions' =>array("class"=>"pagination"),
                'selectedPageCssClass'=>"active"
            ), true);
        }
        return $this->renderPartial('distribution_view', array('listInfo' => $listInfo, 'pages' => $pages , 'count' => $count , 'limit' => $limit , 'page' => $page ));

    }

}
