<?php

/**
 * This is the model class for table "itz_user_rank_experience".
 *
 * The followings are the available columns in table 'itz_user_rank_experience':
 * @property integer $id
 * @property integer $user_id
 * @property integer $value
 * @property integer $op_user
 * @property integer $addtime
 * @property string $addip
 * @property string $updatetime
 * @property string $updateip
 * @property integer $used_value
 */
class ItzUserRankExperience extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzUserRankExperience the static model class
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
		return 'itz_user_rank_experience';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, value, op_user, addtime, used_value', 'numerical', 'integerOnly'=>true),
			array('addip, updateip', 'length', 'max'=>30),
			array('updatetime', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, value, op_user, addtime, addip, updatetime, updateip, used_value', 'safe', 'on'=>'search'),
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
			'value' => 'Value',
			'op_user' => 'Op User',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'updatetime' => 'Updatetime',
			'updateip' => 'Updateip',
			'used_value' => 'Used Value',
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
		$criteria->compare('value',$this->value);
		$criteria->compare('op_user',$this->op_user);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('updatetime',$this->updatetime,true);
		$criteria->compare('updateip',$this->updateip,true);
		$criteria->compare('used_value',$this->used_value);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}