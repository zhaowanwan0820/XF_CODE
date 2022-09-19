<?php

/**
 * This is the model class for table "itz_user_rank_experience_logs".
 *
 * The followings are the available columns in table 'itz_user_rank_experience_logs':
 * @property integer $id
 * @property integer $user_id
 * @property integer $type_id
 * @property integer $status
 * @property integer $op
 * @property integer $value
 * @property string $remark
 * @property integer $op_user
 * @property integer $addtime
 * @property string $addip
 */
class ItzUserRankExperienceLogs extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzUserRankExperienceLogs the static model class
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
		return 'itz_user_rank_experience_logs';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, type_id, status, op, value, op_user, addtime', 'numerical', 'integerOnly'=>true),
			array('remark', 'length', 'max'=>256),
			array('addip', 'length', 'max'=>30),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, type_id, status, op, value, remark, op_user, addtime, addip', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'type_id' => 'Type',
			'status' => 'Status',
			'op' => 'Op',
			'value' => 'Value',
			'remark' => 'Remark',
			'op_user' => 'Op User',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('type_id',$this->type_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('op',$this->op);
		$criteria->compare('value',$this->value);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('op_user',$this->op_user);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}