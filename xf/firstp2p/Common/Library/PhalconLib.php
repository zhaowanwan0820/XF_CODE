<?php
namespace NCFGroup\Common\Library;


use Phalcon\Exception;

class PhalconLib
{
    /**
     * 打印出Model的错误信息
     *
     * @param Model $model
     */
    public static function printModelError($model)
    {
        foreach ($model->getMessages() as $message) {
            echo $message, "\n";
        }
    }

    /**
     * 获得Model的错误信息
     *
     * @param Model $model
     */
    public static function getModelError($model)
    {
        $finalMessage = "";
        foreach ($model->getMessages() as $message) {
            $finalMessage .= $message->__toString() . "\n";
        }
        return $finalMessage;
    }

    public static function redirectToErrorPage($controller, $errorMsg, $redirectUrl = "", $redirectSleepTime = 3)
    {
        $controller->session->set("error-msg", $errorMsg);
        $controller->session->set("error-redirect-url", $redirectUrl);
        $controller->session->set("error-reidrect-sleep-time", $redirectSleepTime);
        $controller->response->redirect("errorpage/index");
    }

    /**
     * Output debug infomation
     *
     * @param Phalcon\Logger\Adapter $logger
     * @param string $msg
     */
    public static function debug($logger, $msg, $debugLevel = 0)
    {
        $msg = var_export($msg, true);
        $trace = debug_backtrace();
        if (count($trace) >= $debugLevel + 2) {
            $lineno = $trace[$debugLevel]['line'];
            $class = $trace[$debugLevel + 1]['class'];
            $line = "[$class:$lineno] ";
        }
        $logger->debug($line . $msg);
    }

    /**
     * Output error infomation
     *
     * @param Phalcon\Logger\Adapter $logger
     * @param string $msg
     */
    public static function error($logger, $msg, $debugLevel = 0)
    {
        $msg = var_export($msg, true);
        $trace = debug_backtrace();
        if (count($trace) >= $debugLevel + 2) {
            $lineno = $trace[$debugLevel]['line'];
            $class = $trace[$debugLevel + 1]['class'];
            $line = "[$class:$lineno] ";
        }
        $logger->error($line . $msg);
    }

    /**
     * Output info infomation
     *
     * @param Phalcon\Logger\Adapter $logger
     * @param string $msg
     */
    public static function info($logger, $msg, $debugLevel = 0)
    {
        $msg = var_export($msg, true);
        $trace = debug_backtrace();
        if (count($trace) >= $debugLevel + 2) {
            $lineno = $trace[$debugLevel]['line'];
            $class = $trace[$debugLevel + 1]['class'];
            $line = "[$class:$lineno] ";
        }
        $logger->info($line . $msg);
    }

    /**
     * 通过Phalcon的Response对象发送内容到浏览器
     *
     * @param Phalcon\Http\Response $responseHandler
     * @param string $content
     * @param string $contentType
     */
    public static function outputResponse(\Phalcon\Http\Response $responseHandler, $content,
                                          $contentType = "application/json")
    {
        $responseHandler->setContentType($contentType);
        $responseHandler->sendHeaders();
        $responseHandler->setContent($content);
        $responseHandler->send();
    }

    public static function flashJson(\Phalcon\Http\Response $responseHandler, $content)
    {
        $responseHandler->setContentType('application/json', 'UTF-8');
        $responseHandler->setJsonContent($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $responseHandler->send();
    }
}
