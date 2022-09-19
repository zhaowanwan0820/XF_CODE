<?php

/**
 * This is the model class for table "itz_landing_page".
 *
 * The followings are the available columns in table 'itz_landing_page':
 * @property string $id
 * @property string $name
 * @property string $class
 * @property integer $addtime
 * @property integer $status
 */
class ItzLandingPage extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzLandingPage the static model class
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
		return 'itz_landing_page';
	}
     //状态
     protected $_Status=array('无效','有效');
    
     public function getStatus(){
         return $this->_Status;
     }
     public function StrStatus(){
         if($this->status)
            return $this->_Status[$this->status];
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_Status))?$this->_Status[$key]:"选择类型";
     }
     //分类
    
    
     public function getClass(){
         return Yii::app()->params['landing_class'];
     }
     public function StrClass(){
         $res = $this->getClass();
         if($this->class && $res)
            return $res[$this->class];
     }
     public function getStrClass($key){
         $res = $this->getClass();
         return (array_key_exists($key, $res))?$res[$key]:"选择类型";
     }
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, class,url', 'required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('addtime, status', 'numerical', 'integerOnly'=>true),
			array('name, class', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, class, addtime, status,url', 'safe'),
			array('id, name, class, addtime, status,url', 'safe', 'on'=>'search'),
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
			'name' => '着陆页名称',
			'class' => '着陆页分类',
			'addtime' => '生成时间',
			'status' => '状态',
			'url'=>'着陆页地址'
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
		$criteria->compare('name',$this->name);
		$criteria->compare('class',$this->class);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
		$criteria->compare('status',$this->status);

		return new CActiveDataProvider($this, array(
		   'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}