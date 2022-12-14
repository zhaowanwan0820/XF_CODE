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
			'projectid' => '??????id',
			'projectname' => '????????????',
			'projecttype' => '????????????',
			'repaymentmethod' => '????????????',
			'projectstatus' => '????????????',
			'borrowamount' => '????????????',
			'releasetime' => '??????????????????',
			'numberofinvestment' => '??????????????????',
			'interestrate' => '??????',
			'fulltime' => '??????????????????',
			'cooperativeorganization' => '??????????????????',
			'finalpayback' => '????????????',
			'enterprisename' => '????????????',
			'guarantorname'=> '??????????????????',
			'monthlyinterest' => '??????????????????',
			'capital' => '??????',
			'interest' => '??????',
			'plannedtime' => '????????????',
			'estimatedtotal' => '????????????',
			'actualtime' => '??????????????????',
			'actualamount' => '??????????????????',
			'is_maturity'=>'????????????',				
			'id' => 'ID',
			'project_projectid' => 'Project Projectid',
			'repaybody'=>'????????????',
			'repayment_status' => '????????????',
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
		//??????????????????
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
		//	????????????????????????????????????????????????
		$criteria->order = 'monthlyinterest asc';
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}