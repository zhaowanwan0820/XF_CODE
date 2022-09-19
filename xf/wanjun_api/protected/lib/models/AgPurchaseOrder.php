<?php

/**
 * This is the model class for table "ag_purchase_order".
 *
 * The followings are the available columns in table 'ag_purchase_order':
 * @property string $id
 * @property integer $user_id
 * @property integer $platform_id
 * @property string $project_types
 * @property string $project_ids
 * @property string $money
 * @property string $discount
 * @property string $acquired_money
 * @property integer $addtime
 * @property integer $expiry_time
 * @property integer $status
 * @property integer $successtime
 * @property string $addip
 */
class AgPurchaseOrder extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgPurchaseOrder the static model class
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
		return 'ag_purchase_order';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, platform_id, addtime, expiry_time, status, successtime', 'numerical', 'integerOnly'=>true),
			array('money, discount, acquired_money', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>31),
			array('project_types, project_ids', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, platform_id, project_types, project_ids, money, discount, acquired_money, addtime, expiry_time, status, successtime, addip', 'safe', 'on'=>'search'),
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
			'platform_id' => 'Platform',
			'project_types' => 'Project Types',
			'project_ids' => 'Project Ids',
			'money' => 'Money',
			'discount' => 'Discount',
			'acquired_money' => 'Acquired Money',
			'addtime' => 'Addtime',
			'expiry_time' => 'Expiry Time',
			'status' => 'Status',
			'successtime' => 'Successtime',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('project_types',$this->project_types,true);
		$criteria->compare('project_ids',$this->project_ids,true);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('discount',$this->discount,true);
		$criteria->compare('acquired_money',$this->acquired_money,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('expiry_time',$this->expiry_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('successtime',$this->successtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}