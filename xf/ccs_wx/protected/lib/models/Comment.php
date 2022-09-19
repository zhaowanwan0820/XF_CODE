<?php

/**
 * This is the model class for table "dw_comment".
 *
 * The followings are the available columns in table 'dw_comment':
 * @property integer $id
 * @property integer $pid
 * @property integer $user_id
 * @property string $module_code
 * @property integer $article_id
 * @property string $comment
 * @property string $flag
 * @property integer $order
 * @property integer $status
 * @property string $addtime
 * @property string $addip
 */
class Comment extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Comment the static model class
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
		return 'dw_comment';
	}

    /**
     * 状态
     */
     protected $_status=array('隐藏','显示','待审核','审核未通过');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->status]))
            return $this->_status[$this->status];
         else 
            return '未知';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     
     /**
      * 是否回复
      */
     protected $_statusmsg=array('未回复','已回复');
    
     public function getStatusmsg(){
         return $this->_statusmsg;
     }
    public function getProvince($id){
         $commentModel = new Comment;
         $provinces = $commentModel->findAllByAttributes(array('pid'=>$id));
         if(count($provinces)>=1){
             $status = 1;
         }else{
             $status = 0;
         }
         return $status;
     }
     public function StrProvince(){
         $statuss = $this->getProvince($this->id);
         return $this->_statusmsg[$statuss];
     }
     public function getStrProvince($key){
         return (array_key_exists($key, $this->_statusmsg))?$this->_statusmsg[$key]:"";
     }
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('pid, user_id, article_id, order, status', 'numerical', 'integerOnly'=>true),
			array('module_code', 'length', 'max'=>50),
			array('flag, addtime, addip', 'length', 'max'=>30),
			array('comment', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('audit_id,realname,username,id, pid, user_id, module_code, article_id, comment, flag, order, status, addtime, addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
              "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
              "borrowInfo"   =>  array(self::BELONGS_TO, 'Borrow', 'article_id'),
              "itzUserInfo"   =>  array(self::BELONGS_TO, 'ItzUser', 'audit_id'),
        );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'pid' => '是否回复',
			'user_id' => '评论人ID',
			'audit_id' => '审核人',
			'realname' => '评论人真实姓名',
            'username' => '评论人用户名',
			'module_code' => 'Module Code',
			'article_id' => '评论文章',
			'comment' => '评论内容',
			'flag' => 'Flag',
			'order' => '排序',
			'status' => '状态',
			'addtime' => '评论日期',
			'addip' => 'IP',
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
		$criteria->compare('pid',$this->pid);
		$criteria->compare('user_id',$this->user_id);
        $criteria->compare('audit_id',$this->audit_id);
		$criteria->compare('module_code',$this->module_code,true);
		$criteria->compare('article_id',$this->article_id);
		$criteria->compare('comment',$this->comment,true);
		$criteria->compare('flag',$this->flag,true);
		$criteria->compare('order',$this->order);
		$criteria->compare('status',$this->status);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86399));
        else
            $criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
        $criteria->addCondition("pid=0");
		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}