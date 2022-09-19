<?php


class XfDataStatistics extends CActiveRecord
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
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_data_statistics';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id,handle_time,add_time,zx_user_total,ph_user_total,distinct_user_total,zx_capital_total,zx_interest_total,ph_capital_total,ph_interest_total,shop_debt_money_total,cash_repayment_total,offline_debt_money_total,shop_debt_money,cash_repayment,offline_debt_money,shop_debt_user,cash_repayment_user,offline_debt_user,repayment_capital_total,repayment_interest_total,repayment_capital,repayment_interest,repayment_user,repayment_clear_user,repayment_clear_user_total,company,guarantee_company,cooperation_company', 'safe', 'on'=>'search'),
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


}