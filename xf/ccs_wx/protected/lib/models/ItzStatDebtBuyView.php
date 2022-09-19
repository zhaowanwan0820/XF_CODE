<?php

/**
 * This is the model class for table "itz_stat_debt_buy_view".
 *
 * The followings are the available columns in table 'itz_stat_debt_buy_view':
 * @property integer $id
 * @property integer $timestamp
 * @property integer $count_total
 * @property integer $profit_lt_8
 * @property integer $profit_8_9
 * @property integer $profit_9_10
 * @property integer $profit_10_11
 * @property integer $profit_11_12
 * @property integer $profit_12_13
 * @property integer $profit_13_14
 * @property integer $profit_14_15
 * @property integer $profit_15_16
 * @property integer $profit_gt_16
 * @property integer $months_lt_2
 * @property integer $months_2_3
 * @property integer $months_3_6
 * @property integer $months_6_12
 * @property integer $months_gt_12
 * @property integer $product_invest
 * @property integer $product_lease
 * @property integer $product_factoring
 * @property integer $product_art
 * @property integer $product_easy1
 * @property integer $product_easy2
 * @property integer $product_easy3
 * @property string $debt_deal_amount
 * @property string $invest_amount
 * @property string $lease_amount
 * @property string $factoring_amount
 * @property string $art_amount
 * @property string $easy1_amount
 */
class ItzStatDebtBuyView extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzStatDebtBuyView the static model class
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
		return Yii::app()->datadb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_stat_debt_buy_view';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id, timestamp, count_total, profit_lt_8, profit_8_9, profit_9_10, profit_10_11, profit_11_12, profit_12_13, profit_13_14, profit_14_15, profit_15_16, profit_gt_16, months_lt_2, months_2_3, months_3_6, months_6_12, months_gt_12, product_invest, product_lease, product_factoring, product_art, product_easy1, product_easy2, product_easy3', 'numerical', 'integerOnly'=>true),
			array('debt_deal_amount, invest_amount, lease_amount, factoring_amount, art_amount, easy1_amount', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, timestamp, count_total, profit_lt_8, profit_8_9, profit_9_10, profit_10_11, profit_11_12, profit_12_13, profit_13_14, profit_14_15, profit_15_16, profit_gt_16, months_lt_2, months_2_3, months_3_6, months_6_12, months_gt_12, product_invest, product_lease, product_factoring, product_art, product_easy1, product_easy2, product_easy3, debt_deal_amount, invest_amount, lease_amount, factoring_amount, art_amount, easy1_amount', 'safe', 'on'=>'search'),
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
			'timestamp' => 'Timestamp',
			'count_total' => 'Count Total',
			'profit_lt_8' => 'Profit Lt 8',
			'profit_8_9' => 'Profit 8 9',
			'profit_9_10' => 'Profit 9 10',
			'profit_10_11' => 'Profit 10 11',
			'profit_11_12' => 'Profit 11 12',
			'profit_12_13' => 'Profit 12 13',
			'profit_13_14' => 'Profit 13 14',
			'profit_14_15' => 'Profit 14 15',
			'profit_15_16' => 'Profit 15 16',
			'profit_gt_16' => 'Profit Gt 16',
			'months_lt_2' => 'Months Lt 2',
			'months_2_3' => 'Months 2 3',
			'months_3_6' => 'Months 3 6',
			'months_6_12' => 'Months 6 12',
			'months_gt_12' => 'Months Gt 12',
			'product_invest' => 'Product Invest',
			'product_lease' => 'Product Lease',
			'product_factoring' => 'Product Factoring',
			'product_art' => 'Product Art',
			'product_easy1' => 'Product Easy1',
			'product_easy2' => 'Product Easy2',
			'product_easy3' => 'Product Easy3',
			'debt_deal_amount' => 'Debt Deal Amount',
			'invest_amount' => 'Invest Amount',
			'lease_amount' => 'Lease Amount',
			'factoring_amount' => 'Factoring Amount',
			'art_amount' => 'Art Amount',
			'easy1_amount' => 'Easy1 Amount',
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
		$criteria->compare('timestamp',$this->timestamp);
		$criteria->compare('count_total',$this->count_total);
		$criteria->compare('profit_lt_8',$this->profit_lt_8);
		$criteria->compare('profit_8_9',$this->profit_8_9);
		$criteria->compare('profit_9_10',$this->profit_9_10);
		$criteria->compare('profit_10_11',$this->profit_10_11);
		$criteria->compare('profit_11_12',$this->profit_11_12);
		$criteria->compare('profit_12_13',$this->profit_12_13);
		$criteria->compare('profit_13_14',$this->profit_13_14);
		$criteria->compare('profit_14_15',$this->profit_14_15);
		$criteria->compare('profit_15_16',$this->profit_15_16);
		$criteria->compare('profit_gt_16',$this->profit_gt_16);
		$criteria->compare('months_lt_2',$this->months_lt_2);
		$criteria->compare('months_2_3',$this->months_2_3);
		$criteria->compare('months_3_6',$this->months_3_6);
		$criteria->compare('months_6_12',$this->months_6_12);
		$criteria->compare('months_gt_12',$this->months_gt_12);
		$criteria->compare('product_invest',$this->product_invest);
		$criteria->compare('product_lease',$this->product_lease);
		$criteria->compare('product_factoring',$this->product_factoring);
		$criteria->compare('product_art',$this->product_art);
		$criteria->compare('product_easy1',$this->product_easy1);
		$criteria->compare('product_easy2',$this->product_easy2);
		$criteria->compare('product_easy3',$this->product_easy3);
		$criteria->compare('debt_deal_amount',$this->debt_deal_amount,true);
		$criteria->compare('invest_amount',$this->invest_amount,true);
		$criteria->compare('lease_amount',$this->lease_amount,true);
		$criteria->compare('factoring_amount',$this->factoring_amount,true);
		$criteria->compare('art_amount',$this->art_amount,true);
		$criteria->compare('easy1_amount',$this->easy1_amount,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}