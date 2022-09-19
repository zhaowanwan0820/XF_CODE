<?php
/**
 * @file BlackListService.php
 * @author (lufeifei@itouzi.com)
 * @date 2015/12/07
 * 后台消息系统黑名单前台查询 链接服务器为yii::app()->mongodb2
 **/

class BlackListService extends  ItzInstanceService {
	public $model;
	public $phone;
	public $username;
	public $addtime;

	public function __construct(){
		/**
		 *大坑：防止调用ItzBaseService的构造函数，导致调用死循环，E_ALL情况下报错。
		 *前提：blacklistservice根本不需要ItzBaseService的方法 
		 */
		 $this->model = UserSmsBlackList::model();
	}
    
    //去重：查看itzsmsblacklist表中是否存在这条记录
    public function isInBlackList($phone) {
		$result = $this->model->findOne(array('phone'=>(String)$phone));
		if($result==null){
			return false;
		}else{
			return true;
		}
    }
    
    //一个供其他功能调用的查询接口（传入array，传入数组，返回数组）
	public function isInBlackListArray($phoneArr)
	{
		//var_export($_GET['phoneArr']);
		//http://msg.itouzi.com/user/userSmsBlackList/select?phoneArr[1]=%2713810629967%27&phoneArr[2]=%2713668799872%27

		$resultArr = array();

		if(isset($phoneArr)){
			foreach($phoneArr as $key=>$row){
				//var_export($row);
				$result = $this->isInBlackList($row);
				//var_export($result);
				if($result==null){
					$resultArr[$row]=false; //false没有该值
				}else{
					$resultArr[$row]=true; //true存在该值
				}
			}
		}
		//var_export($resultArr);
		return $resultArr;
	}

    /**
    * 根据ID从表中删除数据
    */
    public function isDeleted($id){
		$deleteddata = $this->model->findByPk($id);
		if($deleteddata===null)
		{
			return false;
		}
		else{
			$result = $deleteddata->delete();
			if($result){
				return true;
			}else{
				return false;
			}
		}
    }

	/**
	* 根据条件进行查询
	*/
	public function isSelect(){

		$criteria=new EMongoCriteria;

		if(!empty($this->phone))
			$criteria->compare('phone',$this->phone);

		if(!empty($this->username))
			$criteria->compare('username',$this->username);
		
        if (!empty($this->addtime)) {
            $criteria->compare('addtime', array(strtotime($this->addtime), strtotime($this->addtime) + 86400));
        }

        $criteria->setSort(['addtime' => 'desc']);
		
		$result = $this->model->findAll($criteria); //$criteria,array(1=>'phone',2=>'username',3=>'addtime')

		//$phone = '13668799872';
		//$result = $this->model->findAll();
		//$result = $this->model->findOne(array('phone' => '13668799872'));
		//$result = $this->model->findAll(array('phone' => '13668799872'),array('phone','username','addtime'));
		//$result =  $this->model->find(array('deleted' => 0))->sort(array('joined' => -1))->skip(2)->limit(3);
		//var_dump($result);
		foreach($result as $key=>$row ){
			var_export($key);
			//var_export($row);
		}
		return $result;
	}

	/**
    * 数据库链接
    */
    private function getDB(){
		if($this->model->mongoaddress === 0){
			$mongo = Yii::app()->mongodb;
		}else{
			$mongo = Yii::app()->mongodb2;
		}
		return $mongo->selectCollection('itzsmsblacklist');
    }
}