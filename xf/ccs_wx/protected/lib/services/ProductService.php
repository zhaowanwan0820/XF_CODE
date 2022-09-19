<?php
/**
 * @file ProductService.php
 * @author (zhanglei@itouzi.com)
 * @date 2017/02/10
 * 项目相关
 **/
class ProductService extends ItzInstanceService {
	
	public $_opmp_host = ''; //opmp对应url
	public $_passport_host = ''; //passport对应url
	
    public function __construct( ){
        parent::__construct();
        $this->_opmp_host = Yii::app()->c->opmpUrl;
        $this->_passport_host = Yii::app()->c->ipassportUrl;
        
    }
    /**
     * 获取列表
     * @param array $data
     */
    public function getList($data=array(),$page=1,$limit=10){
    	$result = array();
    	$_url = $this->_opmp_host.'/products';
    	$params = $data;
    	$params['page_num'] = !empty($params['page']) ? intval($params['page']) : $page;
    	$params['page_size'] = !empty($params['limit']) ? intval($params['limit']) : $limit;
    	unset($params['page']);
    	unset($params['limit']);
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
    	if($info['data']['total']){
    		$list = $info['data']['list'];
    		$product_type = array(1=>'信托',2=>'证券',3=>'股权',4=>'房地产',5=>'风险');
    		$product_status = array( 1=>'待发布',2=>'募集中',3=>'已募满',4=>'封闭期',5=>'已清盘',6=>'提前清盘',7=>'已取消',8=>'未募满', 9=>'募集失败');
    		$review_status = array(0=>'待审核',1=>'通过',2=>'不通过');
    		foreach ($list as $k=>$val){
    			$list[$k]['product_type'] = isset($product_type[$val['product_type']]) ? $product_type[$val['product_type']] : '--';
    			$list[$k]['product_status'] = isset($product_status[$val['product_status']]) ? $product_status[$val['product_status']] : '--';
    			$list[$k]['review_status'] = isset($review_status[$val['review_status']]) ? $review_status[$val['review_status']] : '--';
    		}
	    	$result['data']['listTotal'] = $info['data']['total'];
	    	$result['data']['listInfo'] = $list;
    	}else{
    		$result['data'] = array();
    	}
    	$result['code'] = $info['code'];
    	$result['info'] = $info['info'];
    	return $result;
    }
    
    /**
     * 详情信息
     * @param number $id
     * @return multitype:|unknown
     */
    public function getDetail($id=0){
    	$id = intval($id);
    	if(empty($id)){
    		return array(
    			'code'=>100,
    			'info'=>'参数错误',
    			'data'=>array()
    		);
    	}
    	$params = array();
    	$_url = $this->_opmp_host.'/product/'.$id;
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
    	$result['data']['listInfo'] = $info['data'];
    	$result['code'] = $info['code'];
    	$result['info'] = $info['info'];
    	return $result;
    }
    
    /**
     * 获取基金管理机构来源
     */
    public function getCustodianSrc($data=array()){
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    
    	$params = array();
    	$_url = $this->_passport_host.'/funds?reviewer_status=1&cooperation_status=1';
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
    	 
    	foreach ($info['data']['list'] as $key=>$val){
    		$src_k[]=base64_decode($val['login_id']);
    		$src_v[]=$val['cn_name'];
    	}
    	 
    	$src = array_combine($src_k, $src_v);
    	$returnResult['code'] = 0;
    	$returnResult['info'] = '获取基金管理机构列表成功';
    	$returnResult['data']['info'] = $src;
    
    	return $returnResult;
    }
    
    /**
     * 获取托管方来源
     */
    public function getTrusteeSrc($data=array()){
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    
    	$params = array();
    	$_url = $this->_passport_host.'/trustees?reviewer_status=1&cooperation_status=1';
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
    	 
    	foreach ($info['data']['list'] as $key=>$val){
    		$src_k[]=base64_decode($val['login_id']);
    		$src_v[]=$val['cn_name'];
    	}
    	 
    	$src = array_combine($src_k, $src_v);
    	$returnResult['code'] = 0;
    	$returnResult['info'] = '获取托管方列表成功';
    	$returnResult['data']['info'] = $src;
    
    	return $returnResult;
    }
    
}