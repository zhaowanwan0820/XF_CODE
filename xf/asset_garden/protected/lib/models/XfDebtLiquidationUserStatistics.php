<?php


class XfDebtLiquidationUserStatistics extends CActiveRecord
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
		return 'xf_debt_liquidation_user_statistics';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			//array('add_time,gift_id,liquidation_user_day,liquidation_user,kpi_1_user_day,kpi_1_user,kpi_2_user_day,kpi_2_user,kpi_3_user_day,kpi_3_user', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,add_time,handel_time,gift_id,liquidation_user_day,liquidation_user,kpi_1_user_day,kpi_1_user,kpi_2_user_day,kpi_2_user,kpi_3_user_day,kpi_3_user', 'safe', 'on'=>'search'),
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