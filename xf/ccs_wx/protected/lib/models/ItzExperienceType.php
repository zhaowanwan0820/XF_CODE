<?php

/**
 * This is the model class for table "itz_experience_type".
 *
 * The followings are the available columns in table 'itz_experience_type':
 * @property integer $id
 * @property string $name
 * @property string $nid
 * @property integer $status
 * @property integer $optype
 * @property integer $value
 * @property integer $cycle
 * @property integer $award_times
 * @property integer $interval
 * @property string $remark
 * @property integer $op_user
 * @property integer $addtime
 * @property string $addip
 * @property integer $updatetime
 * @property string $updateip
 */
class ItzExperienceType extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzExperienceType the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_experience_type';
	}
	
	/**
	 * 奖励次数
	 */	 
	protected $_awardType=array('不限','一次');
	
	public function getMarryType(){
		return $this->_awardType;
	}
	public function StrMarryType(){
		return $this->_awardType[$this->award_times];
	}
	public function getStrMarryType($key){
		return (array_key_exists($key, $this->_awardType))?$this->_awardType[$key]:"";
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, optype, value, cycle, award_times, interval, op_user, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('name, nid', 'length', 'max'=>50),
			array('remark', 'length', 'max'=>256),
			array('addip, updateip', 'length', 'max'=>30),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, nid, status, optype, value, cycle, award_times, interval, remark, op_user, addtime, addip, updatetime, updateip', 'safe', 'on'=>'search'),
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
			'name' => 'Name',
			'nid' => 'Nid',
			'status' => 'Status',
			'optype' => 'Optype',
			'value' => 'Value',
			'cycle' => 'Cycle',
			'award_times' => 'Award Times',
			'interval' => 'Interval',
			'remark' => 'Remark',
			'op_user' => 'Op User',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'updatetime' => 'Updatetime',
			'updateip' => 'Updateip',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('nid',$this->nid,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('optype',$this->optype);
		$criteria->compare('value',$this->value);
		$criteria->compare('cycle',$this->cycle);
		$criteria->compare('award_times',$this->award_times);
		$criteria->compare('interval',$this->interval);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('op_user',$this->op_user);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('updateip',$this->updateip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}