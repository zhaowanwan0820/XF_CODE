<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/10
 * Time: 17:03
 */
class IndexController extends CController {
    /**
     * CErrorHandler::error:
     * code: HTTP 状态码（比如 403, 500）；
     * type: 错误类型（比如 CHttpException, PHP Error）；
     * message: 错误信息；
     * file: 发生错误的PHP文件名；
     * line: 错误所在的行；
     * trace: 错误的调用栈信息；
     * source: 发生错误的代码的上下文。
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {

            unset($error['traces']);
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            Yii::log("itouzi error: HTTP_REFERER:".$referer.", REQUEST_URI".$_SERVER["REQUEST_URI"].", ERROR_INFO:".print_r($error,true),"error");

            if(Yii::app()->request->isAjaxRequest){
                $this->echoJson($error['message'],100,"server error");
            }else{
                echo json_encode($error);
               //$this->render('error', array("error"=>$error));
            }

        }
    }


}