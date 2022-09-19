<?php
/**
 * 借款的代理机构信息
 *
 * @author pengchanglu@ucfgroup.com
 * @modify caolong 2013-12-19
 */

class UserCompanyAction extends CommonAction{
    public  $rsData = array('total'=>0,'rows'=>array()); 
    public  $ajaxData = array('code'=>'0000','message'=>'操作成功');
    public  $allowPostfix = array('jpg','jpeg','pjpeg');

    /**
     * 用户机构 列表
     */
    public function index() {
 
        
        $condition['is_delete'] = 0;
        $this->assign("default_map",$condition);
        //parent::index();
        //列表过滤器，生成查询Map对象
        $map = $this->_search ();
        //追加默认参数
        if($this->get("default_map"))
            $map = array_merge($this->get("default_map"),$map);

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ();
        return;
    }

    //获取财务情况 列表
    public function getCompanyFinance() {
        $cid = intval($_REQUEST['cid']);
        if(!empty($cid)) {
            $list = M("CompanyFinance")->where("cid=".$cid." and status = 0")->field('*')->select();
            $num = 0;
            if(!empty($list)) {
                foreach ($list as $key=>$val) {
                    $num++;
                    $this->rsData['rows'][] = $val;
                }
            }
            $this->rsData['total'] = $num;
        }
        echo json_encode($this->rsData);exit;
    }
    
    //保存用户关联数据
    public function saveCompany() {
        $id                         = $_POST['id'];
        $imageIds   = ltrim($_POST['image_ids'],",");
        $imageNames = $_POST['image_name'];
        $data = M(MODULE_NAME)->create ();
        $model = M('UserCompany');
        $data['is_html'] = 1;   // add caolong 2014-3-27
    	if(!empty($data)) {
    	    if(!empty($data['name']) && !empty($data['address']) && !empty($data['legal_person']) && !empty($data['tel']) && !empty($data['license']) ) {
                if(!empty($id)) { //修改
                    $sql = $this->organizationSql($data,'update',$id);
                    $GLOBALS['db']->query($sql);
                }else{      //新增
                    $sql = $this->organizationSql($data,'insert');
                    $GLOBALS['db']->query($sql);
                    $id  = $GLOBALS['db']->getOne('select @@IDENTITY');
                }
                if(!empty($imageIds)) {
                    M('CompanyImage')->where("id in({$imageIds})")->save(array('cid'=>$id));
                }
                if(!empty($imageNames)) {
                	foreach ($imageNames as $key=>$val) {
                	    if(!empty($key)) 
                	        M('CompanyImage')->where("id = ".$key)->save(array('name'=>$val));
                	}
                }
            }else
                $this->error('必填参数为空!');
    	}else 
    	    $this->error('参数丢失!');
    	$this->success('关联公司信息录入成功，请继续添加财务情况');
    }
    
    //组织sql
    private function organizationSql($data = array() ,$method = 'update',$id='') {
        $command = $method == 'update' ? 'UPDATE' : 'INSERT INTO';
        $sql = $command.' firstp2p_user_company  SET';
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
            //print_r($data);
            $model = M("CompanyFinance");
            $post['cid']           = $data['cid'];
            $post['year']          = $data['year'];
            $post['master_income'] = $data['master_income'];
            $post['gross_profit']   = $data['gross_profit'];
            $post['total_assets']  = $data['total_assets'];
            $post['net_asset']     = $data['net_asset'];
            $post['remarks']       = $data['remarks'];
            if(!empty($post['cid'])) {
                if(!empty($post['year']) && !empty($post['master_income'])) {
                    if(intval($data['id']) === 99999999) { //添加
                        $post['create_time'] = time();
                        if(!$model->add($post)) {
                            $this->ajaxData = array('code'=>'4004','message'=>'添加失败');
                        }
                    }else{//编辑
                        if(!$model->where('id = '.$data['id'])->save($post)) {
                            $this->ajaxData = array('code'=>'4002','message'=>'更新失败');
                        }
                    }
                }else {
                    $this->ajaxData = array('code'=>'4002','message'=>'参数不能为空');
                }
              	
            }else{
            	$this->ajaxData = array('code'=>'4000','message'=>'cid丢失');
            }
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        }
        echo json_encode($this->ajaxData);
    }
    
    //删除财务数据
    public function delCompanyFinance() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
        	$model = M("CompanyFinance");
        	$data['status'] = 1;
        	$model->where('id = '.$id)->save($data);
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        }
        echo json_encode($this->ajaxData);
    }
    
    //文件上传
    public function upload() {
        $tmp     = $_REQUEST['tmp_name'];
        $file    = $_FILES[$tmp];
        $flag    = $_REQUEST['flag'];
        $imageId = intval($_REQUEST['imageId']);
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
                $id = '';
                if(!$flag) {
                    $id = $this->saveImage(array('name'=>$_REQUEST['name'],'pic_path'=>$result['aid'],'create_time'=>time(),'status'=>0, ),$imageId);
                }else{
                    $id = $result['aid'];
                }
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
        switch ($typecode)
        {
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
        //fix
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
            return 'jpg';
        }
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
            return 'png';
        }
        return $filetype;
        
    }
    
    
    //保存图片数据
    public function saveCompanyImageData() {
    	$id    = $_REQUEST['id'];
    	$name  = $_REQUEST['name'];
    	if(!empty($id) && !empty($name)) {
             $model = M('CompanyImage');
             $model->where('id = '.$id)->save(array('name'=>$name));
    	}else{
    		$this->ajaxData = array('code'=>'4000','message'=>'参数丢失');
    	}
    	echo json_encode($this->ajaxData);
    }
    
    //删除图片
    public function saveCompanyImageDel() {
        $id    = intval($_REQUEST['id']);
        if(!empty($id)) {
            $result =  del_attr($id);
            if(!$result)
                $this->ajaxData = array('code'=>'4001','message'=>'图片删除失败');
            M('CompanyImage')->where('id = '.$id)->save(array('status'=>'1'));
        }else{
            $this->ajaxData = array('code'=>'4000','message'=>'参数丢失');
        }
        echo json_encode($this->ajaxData);
    }
    //删除单独的图片
    public function delImage() {
        $image      = $_POST['imageFile'];
        $id         = $_POST['company_id'];
        $field      = $_POST['iamgeName'];
        if(!empty($image) && !empty($id )) {
           $sql = "UPDATE firstp2p_user_company SET `{$field}`=0 WHERE id = '{$id}'";
           $r   = $GLOBALS['db']->query($sql);
           $result =  del_attr($image);
           if(!$result || !$r) 
               $this->ajaxData = array('code'=>'4001','message'=>'图片删除失败');
        }else
            $this->ajaxData = array('code'=>'4000','message'=>'参数丢失');
        echo json_encode($this->ajaxData);    	
    }
    
    //修改图片
    private function saveImage($data=array(),$id='') {
        $model = M('CompanyImage');
        if(!empty($id)) {
        	$model->where('id = '.$id)->save($data);
        }else{
        	$id = $model->add($data);
        }      
        return $id;  
    }
    
    //获取目录
    public function getPath() {
        $rootPath = APP_ROOT_PATH.'public/attachment/';
        $savePath = 'company/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $rootPath.= $savePath;
        $path = explode('/', $rootPath);
        foreach((array)$path as $key=>$val) {
            $dir.=$val.'/';
            if(!file_exists($dir)) {
                @mkdir($dir,0777);
            }
        }
        return array('path'=>$rootPath,'savePath'=>$savePath);
    }
    
	/**
	 * 用户机构 修改 弹框
	 */
	public function companyShow(){
		$id = intval($_REQUEST['id']);
		$user_id = intval($_REQUEST['user_id']);
		if(!$user_id && !$id){
			$this->error("参数错误！");
		}
		if(!empty($user_id)){
			$company = M("UserCompany")->where("user_id = ".$user_id)->find();
			$user = M("User")->where("id=".$user_id)->field('user_name,real_name')->find();
			$company['image_data']  = M('CompanyImage')->where('cid = '.$company['id'].' and status= 0')->select();
		}
		if(!empty($id) && !empty($user_id)){
			$company = M("UserCompany")->where("id = ".$id)->find();
			$user = M("User")->where("id= ".$user_id)->field('user_name,real_name')->find();
			$company['image_data']  = M('CompanyImage')->where('cid = '.$id.' and status= 0')->select();
		}
		if(!empty($user)) {
		    $company['user_name'] = $user['user_name'];
		    $company['real_name'] = $user['real_name'];
		    $company['user_id'] = $user_id;
		}
		$list = $r = array();
		if(!empty($company)) {
		    $company['licence_image_url']     = !empty($company['licence_image'])      ? get_attr($company['licence_image'],1) : '';
		    $company['organization_iamge_url']= !empty($company['organization_iamge']) ? get_attr($company['organization_iamge'],1) : '';
		    $company['taxation_image_url']    = !empty($company['taxation_image'])     ? get_attr($company['taxation_image'],1) : '';
		    $company['bank_iamge_url']        = !empty($company['bank_iamge'])         ? get_attr($company['bank_iamge'],1) : ''; 
		}
		
		
		$image_count = count($company['image_data']);
		$this->assign("company",$company);
		$this->assign("image_count",$image_count);
		$this->assign('image_domain',get_www_url());
		$this->display();
	}

	/**
	 * 用户机构修改
	 */
	public function companyEdit(){
		$user_id = intval($_REQUEST['user_id']);
		if(!$user_id){
			$this->error("参数错误！");
		}
		B('FilterString');
		$data = array();
		$data = $_POST;
		$company = M("UserCompany")->where("user_id = ".$user_id)->select();
		if($company){//编辑
			$data['update_time'] = get_gmtime();
			$company_id = M("UserCompany")->where("user_id = ".$user_id)->save($data);
		}else{//添加
			$data['create_time'] = get_gmtime();
			M("UserCompany")->add($data);
			$company_id = M("UserCompany")->getLastInsID();
		}
		if($company_id){
			$this->ajaxReturn();
		}
		$this->error("操作失败！");
	}
}
