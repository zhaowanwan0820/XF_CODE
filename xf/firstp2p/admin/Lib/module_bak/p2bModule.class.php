<?php

FP::import("app.page");

class p2bModule extends SiteBaseModule
{
    private function isSystemAdmin() {
        $userName = @$GLOBALS['user_info']['user_name'];
        FP::import("libs.common.dict");
        return in_array($userName, dict::get('P2B_ADMIN'));
    }

    private function hasBankAuth() {
        if (!$GLOBALS['user_info']) return app_redirect(url("index"));
        if ($this->isSystemAdmin()) return true;
        $userId = @$GLOBALS['user_info']['id'];
        $count_sql = "select count(*) from " . DB_PREFIX . "p2b_users where user_id = " . $userId;
        $count = $GLOBALS['db']->getOne($count_sql);
        return $count > 0;
    }

    private function warpDeal($deal) {
        $deal['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['repay_type']];
        $deal['borrow_amount'] = format_price($deal['borrow_amount'] / 10000);
        $deal['id'] = $deal['dealid'];
        return $deal;
    }
    
    private function warpJoinDeal($deal) {
        $deal['id'] = $deal['dealid'];
        return $deal;
    }

    private function warpDeals($deal_list) {
        if (is_array($deal_list) && count($deal_list) > 0) {
            foreach ($deal_list as $key => $deal) {
                $deal_list[$key] = $this->warpDeal($deal);
            }
        }
        return $deal_list;
    }

    //预览列表页面
    function index() {
        if (!$this->hasBankAuth()) {
            return app_redirect(url("index"));
        }
        //这个页面只有银行和陈仲华可以看到.
        $cate_id = intval($_REQUEST['cid']);
        $condition = "";
        $conditionC = "";
        if ($cate_id == 1) {
            //待处理
            $condition = "where t1.status = 0";
            $conditionC = "where status = 0";
        } else if ($cate_id == 2) {
            //处理中
            $condition = "where t1.status = 1";
            $conditionC = "where status = 1";
        } else if ($cate_id == 3) {
            //已完成
            $condition = "where t1.status = 2";
            $conditionC = "where status = 2";
        } else if ($cate_id == 4) {
            //未通过
            $condition = "where t1.status = 3";
            $conditionC = "where status = 3";
        } else {
            //未删除
            $condition = "where t1.status != -1";
            $conditionC = "where status != -1";
        }
        $page = intval($_REQUEST['p']);
        if($page < 1) $page = 1;
        $limit = (($page-1)*app_conf("DEAL_PAGE_SIZE")).",".app_conf("DEAL_PAGE_SIZE");
        $sql = "select *,t1.id as dealid from ". DB_PREFIX . "p2b_deal as t1 join " . DB_PREFIX . "user as t2 on t1.user_id = t2.id " . $condition . " limit " . $limit;
        $count_sql = "select count(*) from " . DB_PREFIX . "p2b_deal " . $conditionC;
        $successAmountSql = "select sum(borrow_amount) from " . DB_PREFIX . "p2b_deal where accept_user_id != 0 and status = 2";

        $deal_list = $GLOBALS['db']->getAll($sql);
        $deal_list = $this->warpDeals($deal_list);
        $count = $GLOBALS['db']->getOne($count_sql);
        $successAmount = format_price($GLOBALS['db']->getOne($successAmountSql) / 10000);
        $page_args['cid'] =  $cate_id;
        $page = new Page($count, app_conf("PAGE_SIZE"), $page_args);   //初始化分页对象
        $p =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);
        $GLOBALS['tmpl']->assign('successAmount',$successAmount);
        $GLOBALS['tmpl']->assign('isSystemAdmin', $this->isSystemAdmin());
        $GLOBALS['tmpl']->assign('deal_list',$deal_list);
        $GLOBALS['tmpl']->assign('cate_id', $cate_id);
        $GLOBALS['tmpl']->display("page/p2b_index.html");
    }

    //添加新单的页面
    function add() {
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }
        //只有陈仲华可以看
        $GLOBALS['tmpl']->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);
        $GLOBALS['tmpl']->display("page/p2b_add.html");
    }

    function del() {
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }
        
        if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) <= 0){
        	return app_redirect(url("index"));
        }
        
        $p2b_deal = $GLOBALS['db']->getRow("select * from ". DB_PREFIX . "p2b_deal where id = ".intval($_REQUEST['id']));
        $p2b_deal['status'] = -1;
        $GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal", $p2b_deal, 'UPDATE', 'id = '.intval($_REQUEST['id']));
        
        //删除附件
        $attach_list = $GLOBALS['db']->getAll("select * from ". DB_PREFIX . "p2b_deal_attachment where bank_deal_id = ".intval($_REQUEST['id']));
        if($attach_list){
        	foreach($attach_list as $aval){
        		@unlink(APP_ROOT_PATH.$GLOBALS['dict']['P2B_ATTACHMENT_PATH'].$aval['filename']);
        	}
        	$GLOBALS['db']->query("delete from ".DB_PREFIX."p2b_deal_attachment where bank_deal_id =".intval($_REQUEST['id']));
        }
        
        return showSuccess('操作成功', 0, url("index", "p2b#deal", array("id"=>$_REQUEST['id'])));
    }
    
    function delatt(){
    	
    	if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) <= 0){
    		return app_redirect(url("index"));
    	}
    	
    	//删除附件
    	$attach = $GLOBALS['db']->getRow("select * from ". DB_PREFIX . "p2b_deal_attachment where id = ".intval($_REQUEST['id']));
    	if($attach){
    		if($attach['filename'])  @unlink(APP_ROOT_PATH.$GLOBALS['dict']['P2B_ATTACHMENT_PATH'].$attach['filename']);
    		$GLOBALS['db']->query("delete from ".DB_PREFIX."p2b_deal_attachment where id =".intval($_REQUEST['id']));
    	}
    	
    	return showSuccess('操作成功', 0, url("index", "p2b#edit", array("id"=>$attach['bank_deal_id'])));
    }

    //详情页面
    function deal() {
        if (!$this->hasBankAuth()) {
            return app_redirect(url("index"));
        }
        $dealId = intval($_REQUEST['id']);
        $sql = "select *,t1.id as dealid from ". DB_PREFIX . "p2b_deal as t1 join " . DB_PREFIX . "user as t2 on t1.user_id = t2.id where t1.status != -1 and t1.id = " . $dealId;
        $deal = $GLOBALS['db']->getAll($sql);
        if(empty($deal)){
        	return app_redirect(url("index","p2b"));
        }
        
        $deal = $this->warpJoinDeal($deal[0]);
        
        $attach_list = $GLOBALS['db']->getAll("select * from ". DB_PREFIX . "p2b_deal_attachment where bank_deal_id = ".$dealId);

        $GLOBALS['tmpl']->assign('att_path', $GLOBALS['dict']['P2B_ATTACHMENT_PATH']);
        $GLOBALS['tmpl']->assign('attach_list', $attach_list);
        $GLOBALS['tmpl']->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);
        $GLOBALS['tmpl']->assign('deal', $deal);
        $GLOBALS['tmpl']->assign('isSystemAdmin', $this->isSystemAdmin());
        $GLOBALS['tmpl']->display("page/p2b_deal.html");
    }

    function support() {
        if (!$this->hasBankAuth()) {
            return app_redirect(url("index"));
        }
        $p2b_deal = $GLOBALS['db']->getRow("select * from ". DB_PREFIX . "p2b_deal where id = ".intval($_REQUEST['id']));
        if ($p2b_deal['status'] != 0) {
            showErr('该项目不可以操作', 0, url("index", "p2b#deal", array("id"=>$_REQUEST['id'])));
        }
        $p2b_deal['status'] = 1;
        $p2b_deal['accept_user_id'] = @$GLOBALS['user_info']['id'];
        $p2b_deal['accept_time'] = get_gmtime();
        $GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal", $p2b_deal, 'UPDATE', 'id = '.intval($_REQUEST['id']));
        return showSuccess('操作成功', 0, url("index", "p2b#deal", array("id"=>$_REQUEST['id'])));
    }

    //添加一个项目
    function saveadd() {
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }
        
        if(empty($_POST)){
        	return app_redirect(url("index"));
        }
        
        $data = array();
        $data['title'] = trim($_REQUEST['borrowtitle']);
        $data['description'] = trim($_REQUEST['borrowdescription']);
        $data['borrow_amount'] = trim($_REQUEST['borrowamount']);
        $data['repay_type'] = trim($_REQUEST['loantype']);
        $data['repay_period'] = trim($_REQUEST['repaytime']);
        $data['rate'] = trim($_REQUEST['apr']);
        $data['user_id'] = trim($_REQUEST['username']);
        $data['create_time'] = get_gmtime();
        $data['status'] = intval(trim($_REQUEST['status']));
        
        $GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal", $data);
        $insert_id = $GLOBALS['db']->insert_id();
        
        //处理附件
	    if($_FILES['attachment'] && $insert_id){
	    	
	    	$dir = APP_ROOT_PATH.$GLOBALS['dict']['P2B_ATTACHMENT_PATH'];
	    	if (!is_dir($dir)) {
	    		@mkdir($dir);
	    		@chmod($dir, 0777);
	    	}
	    	
	    	$att_arr = $_FILES['attachment'];
	    	foreach($att_arr['error'] as $akey => $error){
	    		
	    		if($error == 0){
	    			
	    			$att_name = explode('.',$att_arr['name'][$akey]);
	    			$att_type_key = count($att_name) - 1;
	    			$att_type = $att_name[$att_type_key];
	    			unset($att_name[$att_type_key]);
	    			$att_title = implode('.', $att_name);
	    			$filename = md5(time().rand());
	    			
	    			$attach = array();
	    			$attach['bank_deal_id'] = $insert_id;
	    			$attach['title'] = $att_title;
	    			$attach['filename'] = $filename.'.'.$att_type;
	    			$attach['type'] = $att_type;
	    			$attach['description'] = htmlspecialchars(trim($_REQUEST['att_desc'][$akey]));
	    			$attach['create_time'] = get_gmtime();
	    			
	    			$GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal_attachment", $attach);
	    			// TODO vfs requirement check
				require_once APP_ROOT_PATH.'/libs/vfs/Vfs.php';
				require_once APP_ROOT_PATH.'/libs/vfs/VfsException.php';
				try {
					libs\vfs\Vfs::write($dir.$filename.'.'.$att_type, $att_arr['tmp_name'][$akey]);
				} catch (VfsException $e) {
					// nothing to do reffered the origin code snap
				}
	    			// move_uploaded_file($att_arr['tmp_name'][$akey], $dir.$filename.'.'.$att_type);
	    		}
	    	}
	    }
        
        return app_redirect(url("index","p2b"));
    }

    //修改新单的页面
    function edit() {
        
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }

        if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) <= 0){
            return app_redirect(url("index","p2b#index"));
        }
    
        $p2b_deal = $GLOBALS['db']->getRow("select * from ". DB_PREFIX . "p2b_deal where status != -1 and id = ".intval($_REQUEST['id']));
        if(empty($p2b_deal)){
            return app_redirect(url("index","p2b#index"));
        }
         
        $GLOBALS['tmpl']->assign('att_path', $GLOBALS['dict']['P2B_ATTACHMENT_PATH']);
        $GLOBALS['tmpl']->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);
        $GLOBALS['tmpl']->assign('p2b_deal', $p2b_deal);

        $attach_list = $GLOBALS['db']->getAll("select * from ". DB_PREFIX . "p2b_deal_attachment where bank_deal_id = ".intval($_REQUEST['id']));
        
        $GLOBALS['tmpl']->assign('attach_list', $attach_list);

        $GLOBALS['tmpl']->display("page/p2b_edit.html");
    }
    
    //保存修改
    function saveedit() {
        //只有admin可以修改
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }
        foreach($_REQUEST['p2b'] as &$v){
            $v = htmlspecialchars(trim($v));
        }
        if(intval($_REQUEST['id']) > 0){
            $GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal", $_REQUEST['p2b'], 'UPDATE', 'id = '.intval($_REQUEST['id']));
            
            //处理旧的附件说明
            if($_REQUEST['att_desc_edit']){
            	foreach($_REQUEST['att_desc_edit'] as $att_id => $att_desc){
            		if(intval($att_id) > 0){
            			$update_arr = array('description' => htmlspecialchars(trim($att_desc)));
            			$GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal_attachment", $update_arr, 'UPDATE', 'id = '.intval($att_id));
            		}
            	}
            }
            
            //上传附件
            if($_FILES['attachment']){
            
            	$dir = APP_ROOT_PATH.$GLOBALS['dict']['P2B_ATTACHMENT_PATH'];
            	if (!is_dir($dir)) {
            		@mkdir($dir);
            		@chmod($dir, 0777);
            	}
            
            	$att_arr = $_FILES['attachment'];
            	foreach($att_arr['error'] as $akey => $error){
            		 
            		if($error == 0){
            
            			$att_name = explode('.',$att_arr['name'][$akey]);
            			$att_type_key = count($att_name) - 1;
            			$att_type = $att_name[$att_type_key];
            			unset($att_name[$att_type_key]);
            			$att_title = implode('.', $att_name);
            			$filename = md5(time().rand());
            
            			$attach = array();
            			$attach['bank_deal_id'] = intval($_REQUEST['id']);
            			$attach['title'] = $att_title;
            			$attach['filename'] = $filename.'.'.$att_type;
            			$attach['type'] = $att_type;
            			$attach['description'] = htmlspecialchars(trim($_REQUEST['att_desc'][$akey]));
            			$attach['create_time'] = get_gmtime();
            
            			$GLOBALS['db']->autoExecute(DB_PREFIX."p2b_deal_attachment", $attach);
            			//TODO vfs requirement check?
				require_once APP_ROOT_PATH.'/libs/vfs/Vfs.php';
				require_once APP_ROOT_PATH.'/libs/vfs/VfsException.php';
				try {
					libs\vfs\Vfs::write($dir.$filename.'.'.$att_type, $att_arr['tmp_name'][$akey]);
				} catch (VfsException $e) {
					// nothing to do reffered the origin code snap
				}		
            			// move_uploaded_file($att_arr['tmp_name'][$akey], $dir.$filename.'.'.$att_type);
            		}
            	}
            }
            
            return showSuccess('操作成功', 0, url("index", "p2b#deal", array("id"=>$_REQUEST['id'])));
        }
        return app_redirect(url("index","p2b"));
    }

    //管理界面
    function manage() {
        if (!$this->isSystemAdmin()) {
            return app_redirect(url("index"));
        }
        //只有陈仲华可以看
        $cate_id = intval($_REQUEST['cid']);
        $condition = "";
        $conditionC = "";
        if ($cate_id == 1) {
            //待处理
            $condition = "where t1.status = 0";
            $conditionC = "where status = 0";
        } else if ($cate_id == 2) {
            //处理中
            $condition = "where t1.status = 1";
            $conditionC = "where status = 1";
        } else if ($cate_id == 3) {
            //已完成
            $condition = "where t1.status = 2";
            $conditionC = "where status = 2";
        } else if ($cate_id == 4) {
            //未通过
            $condition = "where t1.status = 3";
            $conditionC = "where status = 3";
        }
        $page = intval($_REQUEST['p']);
        if($page < 1) $page = 1;
        $limit = (($page-1)*app_conf("DEAL_PAGE_SIZE")).",".app_conf("DEAL_PAGE_SIZE");
        $sql = "select * from ". DB_PREFIX . "p2b_deal as t1 join " . DB_PREFIX . "user as t2 on t1.user_id = t2.id " . $condition . " limit " . $limit;
        $count_sql = "select count(*) from " . DB_PREFIX . "p2b_deal " . $conditionC;
        $successAmountSql = "select sum(borrow_amount) from " . DB_PREFIX . "p2b_deal where accept_user_id != 0 and status = 2";

        $deal_list = $GLOBALS['db']->getAll($sql);
        $deal_list = $this->warpDeals($deal_list);
        $count = $GLOBALS['db']->getOne($count_sql);
        $successAmount = format_price($GLOBALS['db']->getOne($successAmountSql) / 10000);
        $page_args['cid'] =  $cate_id;
        $page = new Page($count, app_conf("PAGE_SIZE"), $page_args);   //初始化分页对象
        $p  =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);
        $GLOBALS['tmpl']->assign('successAmount',$successAmount);
        $GLOBALS['tmpl']->assign('deal_list',$deal_list);
        $GLOBALS['tmpl']->assign('cate_id', $cate_id);
        $GLOBALS['tmpl']->display("page/p2b_manage.html");
    }
}
?>
