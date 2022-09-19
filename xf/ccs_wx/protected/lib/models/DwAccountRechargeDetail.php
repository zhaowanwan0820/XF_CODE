<?php

/**
 * This is the model class for table "dw_account_recharge_detail".
 *
 * The followings are the available columns in table 'dw_account_recharge_detail':
 * @property string $id
 * @property integer $recharge_id
 * @property integer $user_id
 * @property string $recharge_total
 * @property string $use_recharge_money
 * @property string $no_use_recharge_money
 * @property string $invest_recharge_money
 * @property string $current_recharge_money
 * @property string $cash_money
 * @property integer $addtime
 */
class DwAccountRechargeDetail extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwAccountRechargeDetail the static model class
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
		return 'dw_account_recharge_detail';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('recharge_id, user_id, addtime', 'numerical', 'integerOnly'=>true),
			array('recharge_total, use_recharge_money, no_use_recharge_money, invest_recharge_money, current_recharge_money, cash_money', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, recharge_id, user_id, recharge_total, use_recharge_money, no_use_recharge_money, invest_recharge_money, current_recharge_money, cash_money, addtime', 'safe', 'on'=>'search'),
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
			'recharge_id' => 'Recharge',
			'user_id' => 'User',
			'recharge_total' => 'Recharge Total',
			'use_recharge_money' => 'Use Recharge Money',
			'no_use_recharge_money' => 'No Use Recharge Money',
			'invest_recharge_money' => 'Invest Recharge Money',
			'current_recharge_money' => 'Current Recharge Money',
			'cash_money' => 'Cash Money',
			'addtime' => 'Addtime',
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
		$criteria->compare('recharge_id',$this->recharge_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('recharge_total',$this->recharge_total,true);
		$criteria->compare('use_recharge_money',$this->use_recharge_money,true);
		$criteria->compare('no_use_recharge_money',$this->no_use_recharge_money,true);
		$criteria->compare('invest_recharge_money',$this->invest_recharge_money,true);
		$criteria->compare('current_recharge_money',$this->current_recharge_money,true);
		$criteria->compare('cash_money',$this->cash_money,true);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}