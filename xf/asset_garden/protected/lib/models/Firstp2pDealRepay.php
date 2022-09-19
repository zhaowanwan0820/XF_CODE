<?php


class Firstp2pDealRepay extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Deal the static model class
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
		return 'firstp2p_deal_repay';
	}

    public function getDbConnection()
    {
        return Yii::app()->rcms;
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('distribution_id, paid_type, company_id,id,deal_id,user_id,repay_money,manage_money,impose_money,repay_time,true_repay_time,status,principal,interest,loan_fee,consult_fee,guarantee_fee,pay_fee,management_fee,canal_fee,create_time,update_time,deal_type,repay_type,part_repay_money,new_principal,new_interest,last_yop_requestno,last_yop_repay_time,last_yop_repay_money,last_yop_repay_status,last_yop_repay_remark,paid_principal,paid_interest,paid_principal_time,paid_interest_time,last_yop_return_remark', 'safe', 'on'=>'search'),
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