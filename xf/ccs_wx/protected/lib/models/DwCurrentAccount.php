<?php

/**
 * This is the model class for table "dw_current_account".
 *
 * The followings are the available columns in table 'dw_current_account':
 * @property string $id
 * @property integer $user_id
 * @property string $total
 * @property string $invested_money
 * @property string $interest_money
 * @property string $recharge_amount
 * @property string $redeeming_money
 */
class DwCurrentAccount extends DwActiveRecord
{
	public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwCurrentAccount the static model class
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
		return 'dw_current_account';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id', 'numerical', 'integerOnly'=>true),
			array('total, invested_money, interest_money, recharge_amount, redeeming_money', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, total, invested_money, interest_money, recharge_amount, redeeming_money', 'safe', 'on'=>'search'),
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
			'total' => 'Total',
			'invested_money' => 'Invested Money',
			'interest_money' => 'Interest Money',
			'recharge_amount' => 'Recharge Amount',
			'redeeming_money' => 'Redeeming Money',
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
		$criteria->compare('total',$this->total,true);
		$criteria->compare('invested_money',$this->invested_money,true);
		$criteria->compare('interest_money',$this->interest_money,true);
		$criteria->compare('recharge_amount',$this->recharge_amount,true);
		$criteria->compare('redeeming_money',$this->redeeming_money,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}