<?php
/**
 * @file BlackListService.php
 * @author (lufeifei@itouzi.com)
 * @date 2015/12/07
 * ��̨��Ϣϵͳ������ǰ̨��ѯ ���ӷ�����Ϊyii::app()->mongodb2
 **/

class BlackListService extends  ItzInstanceService {
	public $model;
	public $phone;
	public $username;
	public $addtime;

	public function __construct(){
		/**
		 *��ӣ���ֹ����ItzBaseService�Ĺ��캯�������µ�����ѭ����E_ALL����±���
		 *ǰ�᣺blacklistservice��������ҪItzBaseService�ķ��� 
		 */
		 $this->model = UserSmsBlackList::model();
	}
    
    //ȥ�أ��鿴itzsmsblacklist�����Ƿ����������¼
    public function isInBlackList($phone) {
		$result = $this->model->findOne(array('phone'=>(String)$phone));
		if($result==null){
			return false;
		}else{
			return true;
		}
    }
    
    //һ�����������ܵ��õĲ�ѯ�ӿڣ�����array���������飬�������飩
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
					$resultArr[$row]=false; //falseû�и�ֵ
				}else{
					$resultArr[$row]=true; //true���ڸ�ֵ
				}
			}
		}
		//var_export($resultArr);
		return $resultArr;
	}

    /**
    * ����ID�ӱ���ɾ������
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
	* �����������в�ѯ
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
    * ���ݿ�����
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