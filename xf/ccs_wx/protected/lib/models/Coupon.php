<?php

/**
 * This is the model class for table "dw_coupon".
 *
 * The followings are the available columns in table 'dw_coupon':
 * @property integer $id
 * @property integer $user_id
 * @property integer $source_type
 * @property string $sn
 * @property integer $status
 * @property string $src
 * @property integer $type
 * @property integer $experience_days
 * @property string $amount
 * @property string $least_invest_amount
 * @property integer $expire_time
 * @property integer $use_time
 * @property integer $new_tender_id
 * @property string $remark
 * @property int $application_id
 * @property integer $addtime
 * @property string $addip
 */
class Coupon extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $phone;//手机号
    public $status_mark;//状态，查询用

    public $application_name;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Coupon the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'dw_coupon';
    }

    //来源
    public function getSrc()
    {
        $usersrcs = CouponSlotService::getInstance()->getSrcFromCache();
        return $usersrcs;
    }

    public function StrSrc()
    {
        $usersrcs = $this->getSrc();
        if (isset($usersrcs[$this->src]))
            return $usersrcs[$this->src];
    }

    public function getStrSrc($key)
    {
        $res = $this->getSrc();

        return (array_key_exists($key, $res ? $res[$key] : ""));
    }

    //类型
    protected $_type = [1 => "优惠券", 2 => "抵现券", 3 => "限时加息券", 4 => "加息券"];

    public function getType()
    {
        return $this->_type;
    }

    public function StrType()
    {
        if (isset($this->type) && isset($this->_type[$this->type]))
            return $this->_type[$this->type];
    }

    public function getStrTYpe($key)
    {
        return (array_key_exists($key, $this->_type)) ? $this->_type[$key] : "选择类型";
    }

    //状态0未激活1是有效2已经使用3是已经过期'
    protected $_status = ['未激活', '有效', '已经使用', '已经过期'];

    public function getStatus()
    {
        return $this->_status;
    }

    public function StrStatus()
    {
        if (isset($this->status_mark) && isset($this->_status[$this->status_mark]))
            return $this->_status[$this->status_mark];
    }

    public function getStrStatus($key)
    {
        return (array_key_exists($key, $this->_status)) ? $this->_status[$key] : "选择类型";
    }


    public function getStatusTips(){
        $time = time();
        $tips = '';
        if($this->begin_time > $time) {
            $tips = '未激活';
        }
        if($this->begin_time <= $time && $this->expire_time >= $time && in_array($this->status,array(0,1) ) ) {
            $tips = '有效';
        }
        if($this->status == 2){
            $tips = '已使用';
        }
        if($this->expire_time <= $time && in_array($this->status,array(0,1,3))) {
            $tips = '已过期';
        }
        return $tips;
    }

    //延期
    protected $_delay = [0=>'无延期记录', 1=>'延期一周', 2=>'延期一个月', 3=>'延期两个月', 4=>'延期三个月',5=>'延期五天',6=>'延期三天'];

    public function getDelay()
    {
        return $this->_delay;
    }

    public function StrDelay()
    {
        if (isset($this->delay_time_type) && isset($this->_delay[$this->delay_time_type]))
            return $this->_delay[$this->delay_time_type];
    }

    public function getStrDelay($key)
    {
        return (array_key_exists($key, $this->_delay)) ? $this->_delay[$key] : "选择类型";
    }

//有效期
    protected $_expire_time = ["一个月", "二个月", "三个月", "四个月", "五个月", "六个月", "七个月", "八个月", "九个月", "十个月", "十一个月", "十二个月"];

    public function getExpireTime()
    {
        return $this->_expire_time;
    }

    public function StrExpireTime()
    {
        if (isset($this->expire_time) && isset($this->_expire_time[$this->expire_time]))
            return $this->_expire_time[$this->expire_time];
    }

    public function getStrExpireTime($key)
    {
        return (array_key_exists($key, $this->_expire_time)) ? $this->_expire_time[$key] : "请选择";
    }
    public function getEncryptStr($str='',$length=0)
    {
        $strlen = mb_strlen($str, 'UTF-8');
        if ($strlen>0) {
            return $this->substr_replace_cn($str,'*',0,$strlen-$length);
        }
        return '-';
    }
     public function substr_replace_cn($string, $repalce = '*',$start = 0,$len = 0) {
        $count = mb_strlen($string, 'UTF-8'); //此处传入编码，建议使用utf-8。此处编码要与下面mb_substr()所使用的一致
        if(!$count) { 
            return $string; 
        }
        if($len == 0){
            $end = $count;  //传入0则替换到最后
        }else{
            $end = $start + $len;       //传入指定长度则为开始长度+指定长度
        }
        $i = 0;
        $returnString = '';
        while ($i < $count) {        //循环该字符串
            $tmpString = mb_substr($string, $i, 1, 'UTF-8'); // 与mb_strlen编码一致
            if ($start <= $i && $i < $end) {
                $returnString .= $repalce;
            } else {
                $returnString .= $tmpString;
            }
            $i ++;
        }
        return $returnString;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['src, expire_time,  addtime,amount,least_invest_amount,type',
             'required',
             'message' => Yii::t('luben', '{attribute}不能为空')],
            ['amount, least_invest_amount',
             'numerical',
             'min' => 0.0000000001,
             'tooSmall' => Yii::t('luben', '{attribute}必须大于0')],
            ['status, type, expire_time, use_time, new_tender_id, addtime', 'numerical', 'integerOnly' => true],
            ['sn', 'length', 'max' => 255],
            ['src', 'length', 'max' => 20],
            ['amount, user_id, delay_time_type, begin_time, borrow_type, borrow_id', 'length', 'max' => 11],
            ['remark', 'length', 'max' => 200],
            ['addip', 'length', 'max' => 15],
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            [
                'phone,borrow_id,interest_max_money,delay_time_type,delay_remark,realname,username,id, user_id, sn, status,
                src, type, source_type, amount, experience_days, expire_time, use_time, new_tender_id, remark, addtime, addip,
                borrow_type, application_name, status_mark',
                'safe',
                'on' => 'search'
            ],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            "userInfo" => [self::BELONGS_TO, 'User', 'user_id'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_type' => '来源类型',
            'user_id' => '用户ID',
            'sn' => '序列号',
            'status' => '状态',
            'status_mark'=>'状态',
            'src' => '来源',
            'phone' => '手机号',
            'type' => '类型',
            'experience_days'=>'加息返还时间',
            'amount' => '面额/加息收益率',
            'borrow_id' => '项目ID',
            'borrow_type' => '产品类型',
            'least_invest_amount' => '可用最少投资金额',
            'expire_time' => '过期时间',
            'use_time' => '使用时间',
            'new_tender_id' => 'New Tender',
            'remark' => '备注',
            'application_id' => '发送任务 ID',
            'application_name' => '发送任务',
            'addtime' => '添加时间',
            'addip' => 'IP',
            'delay_time_type' => '延期信息',
            'delay_remark' => '延期备注',
            'realname' => '真实姓名',
            'username' => '用户名',
            'interest_max_money'=> '最高加息本金'
        ];
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.
        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('source_type', $this->source_type);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('sn', $this->sn);
        $criteria->compare('status', $this->status);
        $criteria->compare('borrow_id', $this->borrow_id);
        $criteria->compare('src', $this->src);
        $criteria->compare('type', $this->type);
        $criteria->compare('delay_time_type', $this->delay_time_type);
        $criteria->compare('delay_remark', $this->delay_remark);
        $criteria->compare('amount', $this->amount);
        $criteria->compare('experience_days', $this->experience_days);
        $criteria->compare('least_invest_amount', $this->least_invest_amount);
        $criteria->compare('interest_max_money', $this->interest_max_money);
        $criteria->compare('borrow_type', $this->borrow_type);
        /*根据状态进行查询*///状态0未激活1是有效2已经使用3是已经过期'
        if ($this->status_mark!='') {
            switch($this->status_mark){
                case 0:
                    $criteria->addCondition('begin_time > '.time());
                    break;
                case 1:
                    $criteria->addCondition('begin_time <= '.time().' and expire_time >= '.time().' and status in (0,1)');
                    break;
                case 2:
                    $criteria->addCondition('status = 2');
                    break;
                case 3:
                    $criteria->addCondition('expire_time < '.time().' and status in (0,1,3)');
                    break;
            }
        }

        /* 根据传入的 string app name 查询相应的 id  */
        if (!empty($this->application_name)) {
            $appCriteria = new CDbCriteria;
            $appCriteria->select = 'id';
            $appCriteria->condition = 'application_name = :name';
            $appCriteria->params = ['name' => $this->application_name];
            $appId = CouponApplication::model()->find($appCriteria)['id'];
            if (!$appId) {
                $appId = PHP_INT_MAX;
            }
            $criteria->compare('application_id', $appId);
        }

        if (!empty($this->expire_time))
            $criteria->addBetweenCondition('expire_time', strtotime($this->expire_time), (strtotime($this->expire_time) + 86399));
        else
            $criteria->compare('expire_time', $this->expire_time);
        $criteria->compare('new_tender_id', $this->new_tender_id);
        $criteria->compare('remark', $this->remark);
        if (!empty($this->addtime))
            $criteria->addBetweenCondition('addtime', strtotime($this->addtime), (strtotime($this->addtime) + 86399));
        else
            $criteria->compare('addtime', $this->addtime);
        if (!empty($this->use_time))
            $criteria->addBetweenCondition('use_time', strtotime($this->use_time), (strtotime($this->use_time) + 86399));
        else
            $criteria->compare('use_time', $this->use_time);
        $criteria->compare('addip', $this->addip);
        return new CActiveDataProvider($this, [
            'sort' => [
                'defaultOrder' => 'id DESC',
            ],
            'criteria' => $criteria,
        ]);
    }

    /**
     * 检测是否注册放松 50 元
     * Enter description here ...
     * @param int $user_id
     */
    public function checkSend($where_array)
    {

        $returnResult = $this->findByAttributes($where_array);
        if (!empty($returnResult->id)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getInterestMaxMoney(){
    	if($this->type==1 || $this->type==2){
    		return '-';
    	}else{
    		$interest_max_money = intval($this->interest_max_money);
    		if(empty($interest_max_money)){
    			return '无限制';
    		}else{
    			return $this->interest_max_money;
    		}
    	}
    }

    public function getBorrowType()
    {
        $type = $this->borrow_type;
        $typeArr = Yii::app()->params['borrow_type'];
        if($type<200){
	        if ($type == 0) {
	            return '以下项目可用:全部直投项目';
	        } else {
	            return '以下项目可用:'.$typeArr[$type];
	        }
    	}else{
    		$coupon_type_model = new ItzCouponType();
    		return $coupon_type_model->strBorrowType($type);
    	}
    }

    public function getApplicationName($appModelId = false)
    {
        $appModelId = $appModelId ?: $this->application_id;
        if ($appModelId == 0) {
            return '';
        } else {
            $criteria = new CDbCriteria;
            $criteria->select = 'application_name';
            $criteria->condition = 'id =' . $appModelId;

            return CouponApplication::model()->find($criteria)['application_name'];
        }

    }

    public function setApplication_name($value)
    {
        $this->application_name  = $value;
    }

    public function getBorrowId()
    {
        $id = $this->borrow_id;
        if ($id == 0) {
            return '全部';
        } else {
            return $id;
        }
    }

    public function getProduction()
    {
        $arr = Yii::app()->params['borrow_type'];

        return $arr;
    }

    public function strAmount()
    {
        $amount = $this->amount;
        /* 大于 2 为加息（体验）券，去尾 0 ，加百分号 */
        if ($this->type > 2) {
            $amount = preg_replace('/\.?0+$/', '', $amount);
            return $amount . '%';
        } else {
            return $amount;
        }
    }
}
