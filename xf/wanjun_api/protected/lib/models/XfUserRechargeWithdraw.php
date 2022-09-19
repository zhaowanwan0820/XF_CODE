<?php


class XfUserRechargeWithdraw extends CActiveRecord
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
		return 'xf_user_recharge_withdraw';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('zx_wait_capital_frozen,ph_wait_capital_frozen,ph_zdx_wait_capital_frozen,user_id,zx_money,zx_lock_money,user_name,real_name,mobile,idno,zx_wait_capital,zx_new_wait_capital,zx_wait_interest,zx_recharge,zx_buy_debt,zx_increase,zx_withdraw,zx_sell_debt,zx_ex,zx_deduct,zx_repay,zx_reduce,zx_increase_reduce,ph_wait_capital,ph_new_wait_capital,ph_wait_interest,ph_zdx_wait_capital,ph_zdx_new_wait_capital,ph_money,ph_lock_money,ph_recharge,ph_buy_debt,ph_zdx_buy_debt,ph_increase,ph_withdraw,ph_sell_debt,ph_ex,ph_deduct,ph_repay,ph_zdx_sell_debt,ph_zdx_ex,ph_zdx_deduct,ph_zdx_repay,ph_reduce,ph_increase_reduce,update_time', 'safe', 'on'=>'search'),
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