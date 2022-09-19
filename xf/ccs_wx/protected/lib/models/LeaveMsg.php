<?php

/**
 * This is the model class for table "dw_leave_msg".
 *
 * The followings are the available columns in table 'dw_leave_msg':
 * @property integer $id
 * @property integer $user_id
 * @property string $username
 * @property integer $msg_type
 * @property integer $status
 * @property string $msg
 * @property string $comment
 * @property integer $addtime
 * @property string $addip
 * @property integer $enabled
 */
class LeaveMsg extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return LeaveMsg the static model class
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
		return 'dw_leave_msg';
	}

    /**
     * 留言类型
     */
     protected $_guarantorStatus=array(1=>'新版吐槽');
    
     public function getGuarantorStatus(){
         return $this->_guarantorStatus;
     }
     public function StrGuarantorStatus(){
         if(isset($this->msg_type))
            return $this->_guarantorStatus[$this->msg_type];
     }
     public function getStrGuarantorStatus($key){
         return (array_key_exists($key, $this->_guarantorStatus))?$this->_guarantorStatus[$key]:"";
     }

     /**
     * 状态
     */
     protected $_guarantorType=array(1=>'新添加',2=>"已处理");
     public function getType(){
         return $this->_guarantorType;
     }
     public function StrType(){
         $usersrcs = $this->getType();
         if(isset($usersrcs[$this->status]))
            return $usersrcs[$this->status];
     }
     public function getStrType($key){
         $res  = $this->getType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('msg, addtime, addip', 'required'),
			array('user_id, msg_type, status, addtime, enabled', 'numerical', 'integerOnly'=>true),
			array('username', 'length', 'max'=>127),
			array('msg', 'length', 'max'=>1024),
			array('comment', 'length', 'max'=>255),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, username, msg_type, status, msg, comment, addtime, addip, enabled', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => '用户ID',
			'username' => '用户名',
			'msg_type' => '留言类型',
			'status' => '留言状态',
			'msg' => '留言内容',
			'comment' => '注释',
			'addtime' => '留言时间',
			'addip' => '添加IP',
			'enabled' => '是否有效',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('username',$this->username);
		$criteria->compare('msg_type',$this->msg_type);
		$criteria->compare('status',$this->status);
		$criteria->compare('msg',$this->msg);
		$criteria->compare('comment',$this->comment);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);
		$criteria->compare('enabled',$this->enabled);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}