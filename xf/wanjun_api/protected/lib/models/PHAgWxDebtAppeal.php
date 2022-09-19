<?php

/**
 * This is the model class for table "ag_wx_debt_appeal".
 *
 * The followings are the available columns in table 'ag_wx_debt_appeal':
 * @property integer $id
 * @property integer $products
 * @property integer $debt_id
 * @property integer $debt_tender_id
 * @property integer $type
 * @property integer $status
 * @property integer $decision_time
 * @property integer $decision_maker
 * @property string $decision_outcomes
 * @property integer $addtime
 * @property string $addip
 */
class PHAgWxDebtAppeal extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxDebtAppeal the static model class
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
		return Yii::app()->phdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_wx_debt_appeal';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('products, debt_id, debt_tender_id, type, status, decision_time, decision_maker, addtime', 'numerical', 'integerOnly'=>true),
			array('decision_outcomes', 'length', 'max'=>255),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, products, debt_id, debt_tender_id, type, status, decision_time, decision_maker, decision_outcomes, addtime, addip', 'safe', 'on'=>'search'),
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
			'products' => 'Products',
			'debt_id' => 'Debt',
			'debt_tender_id' => 'Debt Tender',
			'type' => 'Type',
			'status' => 'Status',
			'decision_time' => 'Decision Time',
			'decision_maker' => 'Decision Maker',
			'decision_outcomes' => 'Decision Outcomes',
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
		$criteria->compare('products',$this->products);
		$criteria->compare('debt_id',$this->debt_id);
		$criteria->compare('debt_tender_id',$this->debt_tender_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('decision_time',$this->decision_time);
		$criteria->compare('decision_maker',$this->decision_maker);
		$criteria->compare('decision_outcomes',$this->decision_outcomes,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}