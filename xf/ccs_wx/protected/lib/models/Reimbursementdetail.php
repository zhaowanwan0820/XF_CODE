<?php

/**
 * This is the model class for table "reimbursementdetail".
 *
 * The followings are the available columns in table 'reimbursementdetail':
 * @property integer $id
 * @property string $monthlyinterest
 * @property double $capital
 * @property double $interest
 * @property string $plannedtime
 * @property double $estimatedtotal
 * @property string $actualtime
 * @property double $actualamount
 * @property integer $project_projectid
 */
class Reimbursementdetail extends CActiveRecord
{
	public $enterprisename;
	public $projectname;
	public $cooperativeorganization;
	public $borrowamount;
	public $projectstatus;
	public $repaybody;
	public $is_maturity;
	
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzStatRepayDetail the static model class
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
		return Yii::app()->reportdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'reimbursementdetail';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id, projectname', 'required'),
			array('id, project_projectid', 'numerical', 'integerOnly'=>true),
			array('capital, interest, estimatedtotal, actualamount', 'numerical'),
			array('plannedtime, actualtime', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, enterprise_enterpriseid,borrowamount,projectname,enterprisename,cooperativeorganization,monthlyinterest, repayment_status, capital, interest, plannedtime, estimatedtotal, actualtime, actualamount, project_projectid', 'safe', 'on'=>'search'),
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
			"projectInfo"   =>  array(self::BELONGS_TO, 'Project', 'project_projectid'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'projectid' => '项目id',
			'projectname' => '项目名称',
			'projecttype' => '项目类型',
			'repaymentmethod' => '还款方式',
			'projectstatus' => '还款类型',
			'borrowamount' => '借款金额',
			'releasetime' => '项目发布时间',
			'numberofinvestment' => '项目投资人数',
			'interestrate' => '利率',
			'fulltime' => '项目清标时间',
			'cooperativeorganization' => '合作金融机构',
			'finalpayback' => '最后偿付',
			'enterprisename' => '企业名称',
			'guarantorname'=> '合作金融机构',
			'monthlyinterest' => '每月起息时间',
			'capital' => '本金',
			'interest' => '利息',
			'plannedtime' => '还款时间',
			'estimatedtotal' => '还款总额',
			'actualtime' => '实际还款时间',
			'actualamount' => '实际还款总额',
			'is_maturity'=>'是否到期',				
			'id' => 'ID',
			'project_projectid' => 'Project Projectid',
			'repaybody'=>'还款主体',
			'repayment_status' => '还款状态',
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
		$criteria->with = 'projectInfo';
		$criteria->addCondition("projectInfo.projectstatus = 1003");
		$criteria->compare('id',$this->id);
		$criteria->compare('capital',$this->capital);
		$criteria->compare('interest',$this->interest);
		$criteria->compare('estimatedtotal',$this->estimatedtotal);
		$criteria->compare('actualtime',$this->actualtime);
		$criteria->compare('actualamount',$this->actualamount);
		$criteria->compare('project_projectid',$this->project_projectid);
		$criteria->compare('repayment_status',$this->repayment_status);
		if(!empty($this->monthlyinterest))
			$criteria->addBetweenCondition('monthlyinterest', strtotime($this->monthlyinterest), (strtotime($this->monthlyinterest)+86399));
		else
			$criteria->compare('monthlyinterest', $this->monthlyinterest);

		if(!empty($this->plannedtime))
			$criteria->addBetweenCondition('plannedtime', strtotime($this->plannedtime), (strtotime($this->plannedtime) + 86399));
		else
			$criteria->compare('plannedtime', $this->plannedtime);
		//项目名称搜索
		if(!empty($this->projectname)){
			$criteria->addCondition("projectInfo.projectname = '{$this->projectname}'");
		}
		if(!empty($this->cooperativeorganization)){
			$criteria->addCondition("projectInfo.cooperativeorganization = '{$this->cooperativeorganization}'");
		}
		if(!empty($this->enterprisename)){
			$enterpriseInfo = Enterprise::model()->find("enterprisename = :enterprisename",array(':enterprisename'=>trim($this->enterprisename)));
			$enterpriseid = count($enterpriseInfo)>0 ? $enterpriseInfo->enterpriseid : -1;
			$criteria->addCondition("projectInfo.enterprise_enterpriseid = {$enterpriseid}");
		}
		if(!empty($this->borrowamount)){
			$criteria->addCondition("projectInfo.borrowamount = {$this->borrowamount}");
		}
		//	每月起息时间默认按照从低到高排序
		$criteria->order = 'monthlyinterest asc';
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}