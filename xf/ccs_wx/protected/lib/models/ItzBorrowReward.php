<?php

/**
 * This is the model class for table "itz_borrow_reward".
 *
 * The followings are the available columns in table 'itz_borrow_reward':
 * @property string $id
 * @property integer $user_id
 * @property string $tender_id
 * @property integer $type
 * @property string $account
 * @property integer $novice_project
 * @property integer $value_date
 * @property integer $repay_time
 * @property integer $status
 * @property integer $addtime
 * @property integer $success_time
 */
class ItzBorrowReward extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzBorrowReward the static model class
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
		return 'itz_borrow_reward';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, reserve_id, type, novice_project, value_date, repay_time, status, addtime, success_time', 'numerical', 'integerOnly'=>true),
			array('tender_id', 'length', 'max'=>50),
			array('account', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, reserve_id, tender_id, type, account, novice_project, value_date, repay_time, status, addtime, success_time', 'safe', 'on'=>'search'),
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
			'reserve_id' => 'Reserve Id',
			'tender_id' => 'Tender',
			'type' => 'Type',
			'account' => 'Account',
			'novice_project' => 'Novice Project',
			'value_date' => 'Value Date',
			'repay_time' => 'Repay Time',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'success_time' => 'Success Time',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('reserve_id',$this->reserve_id);
		$criteria->compare('tender_id',$this->tender_id,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('account',$this->account,true);
		$criteria->compare('novice_project',$this->novice_project);
		$criteria->compare('value_date',$this->value_date);
		$criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('success_time',$this->success_time);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}