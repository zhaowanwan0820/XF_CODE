<?php


class XfDebtLiquidationStatistics extends CActiveRecord
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
		return Yii::app()->phdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_debt_liquidation_statistics';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			//array('add_time,gift_id,liquidation_user_day,liquidation_user,debt_total_day,debt_total,yr_debt_total_day,yr_debt_total,liquidation_cost_day,liquidation_cost,liquidation_cost_fluctuation_day,liquidation_cost_fluctuation', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,handel_time,add_time,gift_id,liquidation_user_day,liquidation_user,debt_total_day,debt_total,yr_debt_total_day,yr_debt_total,liquidation_cost_day,liquidation_cost,liquidation_cost_fluctuation_day,liquidation_cost_fluctuation', 'safe', 'on'=>'search'),
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