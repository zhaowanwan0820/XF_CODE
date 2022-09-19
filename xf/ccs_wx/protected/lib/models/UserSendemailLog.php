<?php

/**
 * This is the model class for table "itzemail".
 *
 * The followings are the available columns in table 'itzemail':
 * @property string $id
 * @property integer $status
 * @property string $title
 * @property string $stype
 * @property string $email
 * @property integer $user_id
 * @property string $content
 * @property string $createtime
 * @property string $lasttime
 */
class UserSendemailLog extends EMongoDocument
{
    /**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserSendemailLog the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	/*public function behaviors()
	{
		return array(
			'EMongoTimestampBehaviour'
		);
	}*/

	public function collectionName()
	{
		return 'itzemail';
	}

     /**
     * 发送状态
     */
     protected $_guarantorStatus=array(0=>'not send',1=>'success',3=>'fail');

     public function getGuarantorStatus(){
         return $this->_guarantorStatus;
     }
     public function StrGuarantorStatus(){
         if(isset($this->status))
            return $this->_guarantorStatus[$this->status];
     }
     public function getStrGuarantorStatus($key){
         return (array_key_exists($key, $this->_guarantorStatus))?$this->_guarantorStatus[$key]:"";
     }

     /**
     * 类别
     */
	public function getType()
	{
		$result = Yii::app()->dwdb->createCommand()->select('name, code')->from('itz_trigger_point')->queryAll();
		$temp = [];
		foreach($result as $key => $value) {
			$temp[$value['code']] = $value['name'];
		}
		return $temp;
	}
	public function StrType()
	{
		$usersrcs = $this->getType();
		if (isset($usersrcs[$this->stype])) {
			return $usersrcs[$this->stype];
		} else {
			$usersrcs = Yii::app()->params['type'];
		}
		return $usersrcs[$this->stype] ? $usersrcs[$this->stype] : $this->stype;

	}
	public function getStrType($key)
	{
		$res = $this->getType();
		return array_key_exists($key, $res ? $res[$key] : '');
	}
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, sync', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>250),
			array('stype, email, createtime', 'length', 'max'=>50),
			array('title, email, content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.

			array('user_id, title, stype, email, content, status, createtime, lasttime', 'safe', 'on'=>'search'),//realname,username,
		);
	}

	/**
	 * @return array relational rules.
	 */
	/*public function relations()
	{
		return array(
              "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
        );
	}*/

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'_id' => 'ID',
			'user_id' => '用户ID',
			'title' => '标题',
			'stype' => '类型',
			'email' => '接受邮箱',
			'content' => '邮件内容',
			'status' => '发送结果',
			'createtime' => '发送时间',
			//'lasttime' => '更新时间',
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

		$criteria=new EMongoCriteria;
		if(($this->status!=NULL &&$this->status!='')||is_int($this->status))
		{
			$criteria->compare('status',$this->status);
		}
		if(!empty($this->title))
			$criteria->compare('title',$this->title);

		if(!empty($this->stype))
			$criteria->compare('stype',$this->stype);

		if(!empty($this->email))
			$criteria->compare('email',$this->email);//'lvfujun@itouzi.com'

		if(($this->user_id!=NULL &&$this->user_id!='')||is_int($this->user_id))
		{
			$criteria->compare('user_id',(String) $this->user_id);
		}

		if(!empty($this->content))
			$criteria->compare('content',$this->content,true);

		if(!empty($this->createtime))
        {
			$criteria->compare('createtime',array(strtotime($this->createtime),(strtotime($this->createtime)+86400)));
		}

		$criteria->setSort(array('createtime'=> 'desc'));
		return new EMongoDataProvider($this, array(
			'criteria' =>$criteria,
			'slaveOkay' => true,
		));
	}

	public function scopes()
	{
		return array(
			'programmers' => array(
				'condition' => array('user_id' => '12'),
				'sort' => array('stype' => 1),
				'skip' => 1,
				'limit' => 3
			)
		);
	}


}