<?php

/**
 * This is the model class for table "ag_account_log".
 *
 * The followings are the available columns in table 'ag_account_log':
 * @property string $id
 * @property integer $related_id
 * @property integer $user_id
 * @property integer $platform_id
 * @property integer $project_id
 * @property string $log_type
 * @property integer $direction
 * @property string $money
 * @property string $use_money
 * @property string $lock_money
 * @property string $withdraw_free
 * @property integer $to_user
 * @property integer $addtime
 * @property string $addip
 */
class AgAccountLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgAccountLog the static model class
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
		return Yii::app()->agdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_account_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('related_id, user_id, platform_id, project_id, direction, to_user, addtime', 'numerical', 'integerOnly'=>true),
			array('log_type', 'length', 'max'=>63),
			array('money, use_money, lock_money', 'length', 'max'=>20),
			array('withdraw_free', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, serial_number,related_id, user_id, project_type_id, platform_id, remark, project_id, log_type, direction, money, use_money, lock_money, withdraw_free, to_user, addtime, addip', 'safe', 'on'=>'search'),
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
			'related_id' => 'Related',
			'user_id' => 'User',
			'platform_id' => 'Platform',
			'project_id' => 'Project',
			'log_type' => 'Log Type',
			'direction' => 'Direction',
			'money' => 'Money',
			'use_money' => 'Use Money',
			'lock_money' => 'Lock Money',
			'withdraw_free' => 'Withdraw Free',
			'to_user' => 'To User',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('related_id',$this->related_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('project_id',$this->project_id);
		$criteria->compare('log_type',$this->log_type,true);
		$criteria->compare('direction',$this->direction);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('use_money',$this->use_money,true);
		$criteria->compare('lock_money',$this->lock_money,true);
		$criteria->compare('withdraw_free',$this->withdraw_free,true);
		$criteria->compare('to_user',$this->to_user);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}