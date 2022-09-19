<?php

class ItzUserController extends DController
{
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('admin','delete','index','view','create','update','resetPwd'),
				'roles'=>array('admin'), 
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
    /**
     * 后台用户列表
     * @return mixed|string
     * @throws CException
     */
    public function actionIndex()
    {
        $res = User::model()->getList();
        var_dump($res);die;
        $result = Yii::app()->db->createCommand()
            ->select('id,username,addtime,phone,email,status')
            ->from('itz_user')
            ->limit()
            ->queryAll();

        return $this->renderPartial('adminlist');
    }

}
