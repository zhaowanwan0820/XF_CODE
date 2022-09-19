<?php

/**
 * This is the model class for table "dw_user_sms_log".
 * The followings are the available columns in table 'dw_user_sms_log':
 * @property string $id
 * @property string $type
 * @property integer $user_id
 * @property string $mobile
 * @property string $content
 * @property integer $gateway
 * @property string $ret
 * @property integer $status
 * @property string $addtime
 * @property string $addip
 * @property string $remark
 */
class UserSmsLogOld extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $phone_status;//手机认证状态
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return UserSmsLog the static model class
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
        return 'dw_user_sms_log';
    }
     /**
     * 短信类别
     */
     public function getType(){
         $usersrcs = Yii::app()->params['type'];
         return $usersrcs;
     }
     public function StrType(){
         $usersrcs = $this->getType();
         if(isset($usersrcs[$this->type]))
            return $usersrcs[$this->type];
     }
     public function getStrType($key){
         $res  = $this->getType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
     /**
     * 审核状态
     */
     protected $_guarantorStatus=array('失败',"成功");
    
     public function getGuarantorStatus(){
         return $this->_guarantorStatus;
     }
     public function StrGuarantorStatus(){
         return $this->_guarantorStatus[$this->status];
     }
     public function getStrGuarantorStatus($key){
         return (array_key_exists($key, $this->_guarantorStatus))?$this->_guarantorStatus[$key]:"";
     } 
     
    /**
     * 手机认证状态
     */
     protected $_phone_status=array("未认证","已认证",'待验证');
    
     public function getPhoneStatus(){
         return $this->_phone_status;
     }
     public function StrPhoneStatus(){
         if(isset($this->phone_status) && isset($this->_phone_status[$this->phone_status]))
            return $this->_phone_status[$this->phone_status];
     }
     public function getStrPhoneStatus($key){
         return (array_key_exists($key, $this->_phone_status))?$this->_phone_status[$key]:"选择类型";
     }
      
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, mobile, content, remark', 'required'),
            array('user_id, gateway, status', 'numerical', 'integerOnly'=>true),
            array('type', 'length', 'max'=>50),
            array('mobile', 'length', 'max'=>12),
            array('content', 'length', 'max'=>200),
            array('ret, addtime, addip', 'length', 'max'=>50),
            array('remark', 'length', 'max'=>500),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
			array('remark,realname,phone_status,username,id, type, user_id, mobile, content, gateway, ret, status, addtime, addip, remark', 'safe', 'on'=>'search'),
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
              "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id','select'=>'userInfo.user_id,userInfo.username,userInfo.realname,userInfo.phone_status'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'type' => '类型',
            'user_id' => '用户ID',
            'mobile' => '手机号',
            'content' => '内容',
            'gateway' => '短信通道',
            'ret' => '短信通道返回结果',
            'status' => '发送结果',
            'addtime' => '发送时间',
            'addip' => 'IP',
            'remark' => '备注内容',
            'realname' => '真实姓名',
            'username' => '用户名',
            'phone_status' => '手机认证状态',
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
        $criteria->compare('t.id',$this->id);
        $criteria->compare('t.type',$this->type);
        $criteria->compare('t.user_id',$this->user_id);
        $criteria->compare('t.mobile',$this->mobile);
        $criteria->compare('t.content',$this->content);
        $criteria->compare('t.gateway',$this->gateway);
        $criteria->compare('t.ret',$this->ret);
        $criteria->compare('t.status',$this->status);
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('t.addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('t.addtime',$this->addtime);
        
        $criteria->compare('t.addip',$this->addip);
        $criteria->compare('t.remark',$this->remark,true);

        //$totalItemCount = UserSmsLog::model()->count($criteria);
        //$criteria->with = 'userInfo';
        
        /*if(!empty($this->phone_status) || $this->phone_status==='0'){
            if($this->phone_status ==2){
                $criteria->addNotInCondition('userInfo.phone_status',array(0,1,3,4));
            }else{
                $criteria->addCondition("userInfo.phone_status=".$this->phone_status); 
            }
            //$totalItemCount = UserSmsLog::model()->with('userInfo')->count($criteria);
        }*/

        return new CActiveDataProvider($this, array(
           'sort'=>array(
                'defaultOrder'=>'t.id DESC', 
            ),
            //'totalItemCount'=>$totalItemCount,
            'criteria'=>$criteria,
        ));
    }

    public function afterFind(){
        $this->phone_status = isset($this->userInfo->phone_status)?$this->userInfo->phone_status:"";
    }
}