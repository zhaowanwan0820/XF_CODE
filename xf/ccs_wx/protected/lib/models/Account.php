<?php

/**
 * This is the model class for table "dw_account".
 *
 * The followings are the available columns in table 'dw_account':
 * @property string $id
 * @property integer $user_id
 * @property integer $account_type
 * @property string $total
 * @property string $use_money
 * @property string $no_use_money
 * @property string $collection
 * @property string $withdraw_free
 * @property string $use_virtual_money
 * @property string $no_use_virtual_money
 * @property string $invested_money
 * @property string $recharge_amount
 */
class Account extends DwActiveRecord
{
	public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Account the static model class
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
		return 'dw_account';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, account_type', 'numerical', 'integerOnly'=>true),
			array('total, account_type ,use_money, no_use_money, collection, withdraw_free, use_virtual_money, no_use_virtual_money, invested_money, recharge_amount', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, account_type, user_id, total, use_money, no_use_money, collection, withdraw_free, use_virtual_money, no_use_virtual_money, invested_money, recharge_amount', 'safe', 'on'=>'search'),
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
            'account_type' => 'account_type',
			'total' => 'Total',
			'use_money' => 'Use Money',
			'no_use_money' => 'No Use Money',
			'collection' => 'Collection',
			'withdraw_free' => 'Withdraw Free',
			'use_virtual_money' => 'Use Virtual Money',
			'no_use_virtual_money' => 'No Use Virtual Money',
			'invested_money' => 'Invested Money',
			'recharge_amount' => 'Recharge Amount',
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
        $criteria->compare('account_type',$this->account_type);
		$criteria->compare('total',$this->total,true);
		$criteria->compare('use_money',$this->use_money,true);
		$criteria->compare('no_use_money',$this->no_use_money,true);
		$criteria->compare('collection',$this->collection,true);
		$criteria->compare('withdraw_free',$this->withdraw_free,true);
		$criteria->compare('use_virtual_money',$this->use_virtual_money,true);
		$criteria->compare('no_use_virtual_money',$this->no_use_virtual_money,true);
		$criteria->compare('invested_money',$this->invested_money,true);
		$criteria->compare('recharge_amount',$this->recharge_amount,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}