<?php
/**
 * ApiMonitorService.php
 * @date 2018-11-30
 * @author zhangyao1<zhangyao1@ucfgroup.com>
 */

namespace core\service;

use core\dao\ApiCallLogModel;
use core\dao\ApiConfigModel;
use core\dao\ApiHourStatisticsModel;
use core\dao\ApiDayStatisticsModel;
use core\dao\ApiWeekStatisticsModel;
use core\dao\BaseModel;

class ApiMonitorService extends BaseService
{
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    private static $TIME_RANGE = [2000, 1000, 750, 500, 250, 0];

    //增加接口请求记录、统计数据入库
    public function addApiLogByUri($data)
    {
        $data['uri'] = BaseModel::escape_string($data['uri']);
        $data['module'] = BaseModel::escape_string($data['module']);

        if(empty($data['api_id'])){
            $apiConfigModel = new ApiConfigModel();
            $apiId = $apiConfigModel->getApiIdByUM($data['uri'], $data['module']);
            if(empty($apiId)){
                $apiId = $apiConfigModel->insertApiData($data);
            }
            $data['api_id'] = $apiId;

        }

        //增加接口请求记录
        $apiCallLogModel = new ApiCallLogModel();
        $res = $apiCallLogModel->insertApiLog($data);
        if(!$res){
            return false;
        }

        //各种维度统计数据
        $this->numStatics($data);
        $this->timeStatics($data);
        $this->codeStatics($data);
        return true;
    }

    public function getUserAgent($start = 0, $lenth = -1) {
        return substr($_SERVER['HTTP_USER_AGENT'], $start, $lenth);
    }

    public function getClientTerminal() {
        $userAgent = self::getUserAgent();

        $from = 'unknown';
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent,0,4))) {
            $from = 'mobile';
        } else {
            $from = 'web';
        }

        if (stripos($userAgent, 'MicroMessenger') !== false) {
            $from = 'weixin';
        }

        $os = 'unknown';
        if (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = 'ios';
        } elseif (preg_match('/Android|Linux/i', $userAgent)) {
            $os = 'android';
        }

        return array('from' => $from, 'os' => $os);
    }
    public function getip() {
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $res =  preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
        return $res;
    }

    //统计类型：频次
    public function numStatics($data)
    {
        $data['type'] = 0;
        $this->_dealStaticsData($data);

    }

    //统计类型：响应时间
    public function timeStatics($data)
    {
        $data['type'] = 2;
        $resTime = $data['response_time'];

        foreach(self::$TIME_RANGE as $item){
            if($resTime >= $item){
                $value = $item;
                break;
            }
        }

        $data['value'] = $value;
        $this->_dealStaticsData($data);
    }

    //统计类型：接口响应码
    public function codeStatics($data)
    {
        $data['type'] = 1;
        $data['value'] = $data['result_code'];
        $this->_dealStaticsData($data);
    }

    //从小时、日、周三个时间维度统计
    private function _dealStaticsData($data)
    {
        $countTime = $this->_getCountTime();
        $this->_addNumData(new ApiHourStatisticsModel(), self::HOUR, $countTime['hour'], $data);
        $this->_addNumData(new ApiDayStatisticsModel(), self::DAY, $countTime['day'], $data);
        $this->_addNumData(new ApiWeekStatisticsModel(), self::WEEK, $countTime['week'], $data);

    }

    //统计数据入库
    private function _addNumData($obj, $time_diff, $countTime, $data)
    {
        $res = $obj->getLastTime($data);
        $lastTime = $res['count_time'];
        if(time() < ($lastTime + $time_diff) && $lastTime){
            $data['count_time'] = $lastTime;
            $data['id'] = $res['id'];
            $obj->updateLogById($data);
        }else{
            $data['count_time'] = $countTime;
            $obj->insertLog($data);
        }
    }

    //获取这周、当日、当前小时的开始时间
    private function _getCountTime()
    {
        $hourCountTime = time() - time()%3600;
        $dayCountTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $weekCountTime = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
        return array('hour' => $hourCountTime, 'day' => $dayCountTime, 'week' => $weekCountTime);
    }
}

