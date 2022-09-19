<?php 
/**
 * 贷款信息-用户关联信息
 * @author  caolong
 * @date    2014-1-9
 */
class LoanLnfoAction extends CommonAction{
    public  $rsData = array('total'=>0,'rows'=>array());
    public  $ajaxData = array('code'=>'0000','message'=>'操作成功');
    public  $allowPostfix = array('jpg','jpeg','pjpeg');
    
    
    public function index() {
        
        $id     = !empty($_REQUEST['id'])   ? intval($_REQUEST['id']) : '';
        $did    = !empty($_REQUEST['did'])  ? intval($_REQUEST['did']) : '';
        if(!empty($id)) {
        	$result = M('PreDealInfo')->where('id='.$id)->find();
        }
        if(!empty($did)) {
            $result = M('PreDealInfo')->where('did='.$did)->find();
        }
        
        $result['image_data']            = !empty($result['images_url'])         ? json_decode($result['images_url'],true) : '';
        $result['licence_image_url']     = !empty($result['licence_image'])      ? get_attr($result['licence_image'],1) : '';
        $result['organization_iamge_url']= !empty($result['organization_iamge']) ? get_attr($result['organization_iamge'],1) : '';
        $result['taxation_image_url']    = !empty($result['taxation_image'])     ? get_attr($result['taxation_image'],1) : '';
        $result['bank_iamge_url']        = !empty($result['bank_iamge'])         ? get_attr($result['bank_iamge'],1) : '';
      
        $this->assign('company',$result);
        $this->assign('did',$did);
        $this->display();
    }
    
    //获取财务情况 列表
    public function getCompanyFinance() {
        $id = intval($_REQUEST['id']);
        if(!empty($id)) {
            $sql = "SELECT finance_info FROM firstp2p_pre_deal_info WHERE id = ".$id;
            $list= $GLOBALS['db']->getOne($sql);
            $list= json_decode($list,true);
            $num = 0;
            if(!empty($list)) {
                foreach ($list as $key=>$val) {
                    $num++;
                    $val['remarks'] = urldecode($val['remarks']);
                    $this->rsData['rows'][] = $val;
                }
            }
            $this->rsData['total'] = $num;
        }
        echo json_encode($this->rsData);
    }
    
    //保存用户关联数据
    public function saveCompany() {
        $id                         = $_POST['id'];
        $data['name']               =$_POST['name'];
        $data['address']            =$_POST['address'];
        $data['license']            =$_POST['license'];
        $data['project_area']       =$_POST['project_area'];
        $data['description']        =$_POST['description'];
        $data['top_credit']         =$_POST['top_credit'];
        $data['is_important_enterprise'] =$_POST['is_important_enterprise'];
        $data['mangage_condition']  =$_POST['mangage_condition'];
        $data['complain_condition'] =$_POST['complain_condition'];
        $data['trustworthiness']    =$_POST['trustworthiness'];
        $data['policy']             =$_POST['policy'];
        $data['source']             =$_POST['source'];
        $data['marketplace']        =$_POST['marketplace'];
        //图片
        $data['licence_image']      =intval($_POST['licence_image']);
        $data['organization_iamge'] =intval($_POST['organization_iamge']);
        $data['taxation_image']     =intval($_POST['taxation_image']);
        $data['bank_iamge']         =intval($_POST['bank_iamge']);
        //其他图片信息
        $data['images_url']         =json_encode($this->getImageJson( $_POST['image_name'],$id));
        //贷款表id
        $data['did']                = $_POST['did'];
     //   print_r($data);exit;   
        if(!empty($data)) {
            if(!empty($data['name']) ) {
                if(!empty($id)) { //修改
                    $sql = $this->organizationSql($data,'update',$id);
                    $GLOBALS['db']->query($sql);
                }else{      //新增
                    $sql = $this->organizationSql($data,'insert');
                    $GLOBALS['db']->query($sql);
                    $id  = $GLOBALS['db']->getOne('select @@IDENTITY');
                }
                $this->ajaxData['message'] = $id;
            }else{
                $this->ajaxData = array('code'=>'4000','message'=>'必填参数为空!');
            }
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        }
        echo json_encode($this->ajaxData);exit;
    }
    
    //拼装图片json数据
    private function getImageJson($data = array() ,$id='') {
        $result = $list = array();
        if(!empty($data)) {
            //组合
            if(empty($id)) { //add
                $sql = 'select images_url from firstp2p_pre_deal_info where id ='.intval($id);
                $r   = $GLOBALS['db']->getOne($sql);
                $result = json_decode($r,true);
                foreach($data as $key=>$val)
                    $result[] = array('image_id'=>$key,'image_name'=>urlencode($val));
            }else{          //editor
                $sql = 'select images_url from firstp2p_pre_deal_info where id ='.intval($id);
                $r   = $GLOBALS['db']->getOne($sql);
                $result = json_decode($r,true);
              //  if(!empty($result)) {
                    foreach($data as $key=>$val)
                        $list[] = array('image_id'=>$key,'image_name'=>urlencode($val));
               // }
                if(!empty($list)) {
                    foreach ($list as $k=>$v)
                        $result[$k] = $list[$k];
                }
            }
        	return $result;
        }else{
            return false;
        } 
    }
    
    //组织sql
    private function organizationSql($data = array() ,$method = 'update',$id='') {
        $command = $method == 'update' ? 'UPDATE' : 'INSERT INTO';
        $sql = $command.' firstp2p_pre_deal_info  SET';
        if(!empty($data)) {
            foreach ($data as $key=>$val ) {
                $sql.= "`{$key}` = '{$val}', ";
            }
            $sql = rtrim($sql,", ");
        }else{
            return false;
        }
        $sql.= !empty($id) ? " WHERE id = '".intval($id)."'" : '';
        return $sql;
    }
    
    //保存财务情况数据
    public function saveData() {
        if(!empty($_POST['data'])) {
            $data = (array)json_decode($_POST['data']);
            $post['loanId']        = $data['loanId'];
            $post['year']          = intval($data['year']);
            $post['master_income'] = intval($data['master_income']);
            $post['gross_profit']  = intval($data['gross_profit']);
            $post['total_assets']  = intval($data['total_assets']);
            $post['net_asset']     = intval($data['net_asset']);
            $post['remarks']       = urlencode($data['remarks']);
            $post['id']            = $data['id'];
            if(!empty($post['loanId'])) {
                $list = $this->getFinance_infoJson($post,$post['loanId']);
                $str  = json_encode($list);
                $sql  = "UPDATE firstp2p_pre_deal_info SET finance_info='".$str."' WHERE id=".$post['loanId'];
                $GLOBALS['db']->query($sql);
            }else
                $this->ajaxData = array('code'=>'4000','message'=>'loanId丢失');
        }else
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        echo json_encode($this->ajaxData);
    }
    
    //拼装财务json数据
    private function getFinance_infoJson($data = array() ,$id='') {
        $result = $list = array();
        if(!empty($data)) {
            $sql = 'select finance_info from firstp2p_pre_deal_info where id ='.intval($id);
            $r   = $GLOBALS['db']->getOne($sql);
            $result = json_decode($r,true);
            //组合
            if($data['id'] == 99999999) { //add
                if(!empty($result)) { //追加
                    $data['id'] = count($result) + 1;
                    $result[] = $data;
                }else{
                    $data['id'] = 1;
                    $result[0] = $data;
                }
            }else{          //editor
                foreach ($result as $key=>$val ) {
                   if($data['id'] == $val['id']) {
                       $result[$key] = $data;
                   }    	
                }
            }
            return $result;
        }else
            return FALSE;
         
    }
    
    //删除财务数据
    public function delCompanyFinance() {
        $loanId = intval($_POST['loanId']);
        $id     = intval($_POST['id']);
        if(!empty($loanId) && !empty($id)) {
            $sql = 'select finance_info from firstp2p_pre_deal_info where id ='.$loanId;
            $result = $GLOBALS['db']->getOne($sql);
            $jsonData = json_decode($result,true);
            if(!empty($jsonData)) {
                foreach($jsonData as $key=>$val) {
                     if($id == $val['id']){
                     	unset($jsonData[$key]);
                     }
                }
               $sql = "UPDATE firstp2p_pre_deal_info SET finance_info='".json_encode($jsonData)."' WHERE id = ".$loanId;
               $GLOBALS['db']->query($sql);
            }
        }else 
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        echo json_encode($this->ajaxData);
    }
    
    //文件上传
    public function upload() {
        $tmp     = $_REQUEST['tmp_name'];
        $file    = $_FILES[$tmp];
        $flag    = $_REQUEST['flag'];
        $imageId = $_REQUEST['imageId'];
        $prefix  = $this->getImagePostFix($file['tmp_name']); 
        if(in_array($prefix, $this->allowPostfix)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'asPrivate' => true,
            );
            $result  = uploadFile($uploadFileInfo);
            if(!empty($result['status']) &&   intval($result['status']) === 1) {
                $id = $result['aid'];
                $this->ajaxData = array('code'=>'0000','message'=>'','content'=> array('imageId'=>$id,'path'=>get_attr($result['aid'],1)));
            }else{
                $this->ajaxData = array('code'=>'4000','message'=>$result['errors'][0]);
            }
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'图片格式不正确,系统只支持jpg,jpeg格式');
        }
        echo json_encode($this->ajaxData); 
    }
    
    //通过二进制流 读取文件后缀信息
    private function getImagePostFix($filename) {
        $file     = fopen($filename, "rb");
        $bin      = fread($file, 2); //只读2字节
        fclose($file);
        $strinfo  = @unpack("c2chars", $bin);
        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
        $filetype = "";
        switch ($typecode) {
        	case 7790:
        	    $filetype = 'exe';
        	    break;
        	case 7784:
        	    $filetype = 'midi';
        	    break;
        	case 8297:
        	    $filetype = 'rar';
        	    break;
        	case 255216:
        	    $filetype = 'jpg';
        	    break;
        	case 7173:
        	    $filetype = 'gif';
        	    break;
        	case 6677:
        	    $filetype = 'bmp';
        	    break;
        	case 13780:
        	    $filetype = 'png';
        	    break;
        	default:
        	    $filetype = 'unknown'.$typecode;
        }
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
            return 'jpg';
        }
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
            return 'png';
        }
        return $filetype;
    }
    
    //删除单独的图片
    public function delImage() {
        $image      = $_POST['imageFile'];
        $id         = $_POST['company_id'];
        $field      = $_POST['iamgeName'];
        if(!empty($image) && !empty($id )) {
            $sql = "UPDATE firstp2p_pre_deal_info SET `{$field}`=0 WHERE id = '{$id}'";
            $r   = $GLOBALS['db']->query($sql);
            $result =  del_attr($image);
            if(!$result || !$r)
                $this->ajaxData = array('code'=>'4001','message'=>'图片删除失败');
        }else
            $this->ajaxData = array('code'=>'4000','message'=>'参数丢失');
        echo json_encode($this->ajaxData);
    }
    
    //删除json数据的图片
    public function delJsonImage() {
        $imageId    = intval($_POST['imageId']);
        $id         = intval($_POST['id']);
        if(!empty($imageId) && !empty($id )) {
            $sql = 'select images_url from firstp2p_pre_deal_info where id ='.intval($id);
            $r   = $GLOBALS['db']->getOne($sql);
            $result = json_decode($r,true);
            foreach($result as $key=>$val) {
                if($val['image_id'] == $imageId) {
                	unset($result[$key]);
                }
            }
            if(!empty($result)) {
            	$data = json_encode($result);
            }else{
                $data = '';
            }
            $sql = "UPDATE firstp2p_pre_deal_info SET images_url = '".$data."' WHERE id = ".$id;      
            $return = $GLOBALS['db']->query($sql);
            $bool =  del_attr($imageId);
            if(!$return || !$bool)
                $this->ajaxData = array('code'=>'4001','message'=>'图片删除失败');
        }else
            $this->ajaxData = array('code'=>'4000','message'=>'参数丢失');
        echo json_encode($this->ajaxData);
    }
}
?>
