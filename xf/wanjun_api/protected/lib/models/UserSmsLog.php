<?php

class UserSmsLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ContractTask the static model class
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
		return 'xf_user_sms_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			//array('exchange_min,exchange_max,plan_liquidation_user,liquidation_user,plan_yr_debt_total,yr_debt_total,plan_debt_total,debt_total,plan_liquidation_cost,liquidation_cost,avg_proportion,avg_debt,avg_liquidation_cost,kpi_1_min,kpi_1_max,kpi_1_plan_user,kpi_2_min,kpi_2_max,kpi_2_plan_user,kpi_3_min,kpi_3_max,kpi_3_plan_user', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,msg_id,type,user_id,send_mobile,receive_mobile,content,add_time,add_user_id,status', 'safe', 'on'=>'search'),
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