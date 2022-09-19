<?php

/**
 * This is the model class for table "dw_user_id5_log".
 *
 * The followings are the available columns in table 'dw_user_id5_log':
 * @property string $id
 * @property integer $user_id
 * @property string $username
 * @property string $realname
 * @property string $card_id
 * @property string $result
 * @property string $result_string
 * @property string $addtime
 * @property string $addip
 * @property string $remark
 */
class UserId5Log extends DwActiveRecord
{
    public $dbname = 'dwdb';
	public $phone;//电话
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserId5Log the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_user_id5_log';
	}
     /**
     * 认证状态
     */
     protected $_result=array(
                   10 => '一致',
                   11 => '不一致',
                   12 => '库中无此号',
                   13 => '网络错误'
                );
    
     public function getCodelocal(){
         return $this->_result;
     }
     public function StrCodelocal(){
         if($this->code_local)
            return $this->_result[$this->code_local];
     }
     public function getStrCodelocal($key){
         return (array_key_exists($key, $this->_result))?$this->_result[$key]:"选择类型";
     }
     

     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, username, realname, card_id, result, result_string, remark', 'required'),
			array('user_id, addtime', 'numerical', 'integerOnly'=>true),
			array('username, realname, card_id', 'length', 'max'=>100),
			array('result', 'length', 'max'=>10),
			array('result_string', 'length', 'max'=>200),
			array('addip', 'length', 'max'=>50),
			array('remark', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('code_local,real_from,phone,id, user_id, username, realname, card_id, result, result_string, addtime, addip, remark', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		'userInfo' => array(self::BELONGS_TO,'User','user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'phone' => '电话号码',
			'user_id' => '用户ID',
			'username' => '用户名',
			'realname' => '真实姓名',
			'card_id' => '提交身份证号',
			'code_local' => '认证状态',
			'result_string' => '认证信息',
			'addtime' => '提交时间',
			'addip' => 'IP',
			'remark' => '人工调查备注',
			'real_from'=>'实名通道'
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;
        
        $order = 'id DESC';
        if(!empty($this->username)){
            $criteria->select = "* ,case when username like '%".$this->username."%' then (length(username)-length('".$this->username."')) end as rn ";
            $order      = ' rn ';
        }
        $criteria->compare('id',$this->id);
		$criteria->compare('code_local',$this->code_local);
        $criteria->compare('user_id',$this->user_id);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('realname',$this->realname,true);
        $criteria->compare('real_from',$this->real_from);
		$criteria->compare('card_id',$this->card_id);
        if(!empty($this->result) && $this->result ==5){
            $criteria->addCondition('result=""');
        }else{
            $criteria->compare('result',$this->result);
        }
		$criteria->compare('result_string',$this->result_string,true);
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
        
		$criteria->compare('addip',$this->addip);
		$criteria->compare('remark',$this->remark,true);

		return new CActiveDataProvider($this, array(
            'sort'=>array(
                'defaultOrder'=>$order, 
            ),
			'criteria'=>$criteria,
		));
	}
}
