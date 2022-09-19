<?php
/**
 * 存管对账
 */
ini_set('memory_limit', '4096M');
set_time_limit(0);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/../app/init.php');
use core\service\SupervisionAccountService;
use core\service\SupervisionBaseService;
use libs\utils\PaymentApi;
use libs\db\Db;

// 同步手机号资料

$users = [
4307, 7625, 9486, 18469, 23359, 46330, 68996, 70043, 82111, 138434, 146332, 338591, 357842, 378396, 428426, 828678, 935265, 1116187, 1203176, 1335262, 1381437, 1384655, 1391034, 1442548, 1445014, 1446988, 1453011, 1474296, 1517529, 1807297, 1857616, 1965653, 2000645, 2081795, 2255836, 2348204, 2404468, 2475807, 2502983, 2542670, 2571859, 2583891, 2583908, 2583919, 2583970, 2686719, 2697338, 2912627, 2950939, 3023977, 3033570, 3052960, 3064070, 3081994, 3099652, 3100162, 3106118, 3120716, 3218055, 3221310, 3322091, 3416109, 3514951, 3515460, 3556411, 3558835, 3563470, 3588872, 3624553, 3660343, 3690865, 3743011, 3768529, 3799811, 3852249, 3936305, 3951218, 3986560, 3999191, 4050053, 4077914, 4091169, 4250827, 4254769, 4272166, 4305205, 4329213, 4405082, 4461096, 4475806, 4741245, 4759573, 4760877, 4782543, 4899157, 4962546, 5054106, 5055246, 5124008, 5161225, 5223710, 5326005, 5584419, 5607118, 5612274, 5703609, 5850739, 5867799, 5901457, 5994343, 6035036, 6127058, 6147669, 6147861, 6148049, 6165412, 6223726, 6228391, 6233079, 6288749, 6311565, 6349840, 6351712, 6372893, 6393271, 6401800, 6478781, 6483565, 6526563, 6619476, 6638837, 6640314, 6750414, 6750883, 6756630, 6766318, 6766353, 6776394, 6776502, 6776563, 6776608, 6776690, 6776721, 6776850, 6776889, 6776927, 6776989, 6777021, 6777055, 6777110, 6777164, 6813654, 6840057, 6859282, 6894200, 6922066, 6931285, 6931349, 6931371, 6931696, 6931711, 6931788, 6931841, 6931872, 6931886, 6966611, 6977102, 7015107, 7016374, 7017870, 7033496, 7033677, 7058074, 7063108, 7063138, 7063144, 7063154, 7063161, 7063172, 7063185, 7063195, 7063219, 7081296, 7093877, 7107903, 7128084, 7162512, 7163033, 7163087, 7163158, 7163184, 7165157, 7178449, 7178846, 7198775, 7206680, 7212342, 7214139, 7226954, 7230191, 7230259, 7235925, 7252901, 7253069, 7253128, 7277616, 7298119, 7309151, 7314693
];
$db = Db::getInstance('firstp2p', 'slave');
$accountSerivce = new SupervisionAccountService();
foreach ($users as $userId) {
    PaymentApi::log('Supervision update userMobile success, userId:'.$userId);
    $mobile = $db->getOne('SELECT mobile FROM firstp2p_user WHERE id = '.intval($userId));
    $result = $accountSerivce->memberPhoneUpdate($userId, $mobile);
    if (isset($result['respCode']) && $result['respCode'] == SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
        PaymentApi::log('Supervision update userMobile success, userId:'.$userId);
    } else {
        PaymentApi::log('Supervision update userMobile fail, userId:'.$userId . 'err:'.$result['respMsg']);
    }
}

// 同步银行卡资料
$cardUsers = [
4348, 4935, 5828, 6217, 7271, 7823, 7916, 8763, 11067, 12506, 12943, 14784, 15161, 16447, 17620, 19588, 28666, 40209, 41339, 44513, 44730, 47871, 50257, 51525, 53166, 62118, 63557, 83383, 92945, 103872, 121989, 125292, 168658, 169334, 179321, 412466, 587197, 588118, 658427, 703487, 759015, 761629, 785343, 816218, 965368, 1001549, 1034560, 1073955, 1130961, 1190913, 1230095, 1281707, 1300117, 1360065, 1368791, 1389669, 1394536, 1400515, 1402595, 1404568, 1415345, 1450933, 1455520, 1494469, 1496559, 1497563, 1506142, 1510226, 1516504, 1517529, 1524973, 1787865, 1813230, 1893731, 1944999, 2109892, 2110710, 2119703, 2154517, 2164258, 2280073, 2346882, 2351221, 2354862, 2433877, 2554678, 2601073, 2630145, 2641465, 2648407, 2652210, 2689710, 2836382, 2839705, 2867692, 2950939, 2966697, 2984706, 3011800, 3045046, 3089644, 3111134, 3117686, 3127765, 3158995, 3251559, 3348887, 3385584, 3433337, 3457618, 3468636, 3529812, 3567406, 3578635, 3600879, 3628814, 3644872, 3657073, 3657553, 3670215, 3674363, 3678859, 3759284, 3806002, 3813133, 3819560, 3835273, 3846775, 3920040, 3921616, 3991587, 4009718, 4023311, 4062642, 4071814, 4124596, 4131039, 4152893, 4212069, 4266772, 4289824, 4291442, 4294457, 4343325, 4384871, 4394589, 4406310, 4419543, 4434421, 4440434, 4449199, 4641038, 4714057, 4727491, 4777678, 4848516, 4966557, 5052846, 5102445, 5136910, 5176810, 5204731, 5220174, 5223710, 5327910, 5566750, 5703609, 5713273, 5715502, 5720301, 5745247, 5844951, 5857501, 5941808, 5945096, 5991109, 6013542, 6035390, 6092658, 6099497, 6171269, 6220240, 6254306, 6265334, 6269879, 6275696, 6286970, 6289527, 6303679, 6326636, 6332004, 6335222, 6353013, 6374674, 6386143, 6434192, 6459783, 6463671, 6483373, 6486853, 6517915, 6626778, 6680763, 6698825, 6771248, 6778139, 6825503, 6838996, 6851364, 6867209, 6914308, 6925730, 6949994, 6952632, 6976077, 6983489, 6984779, 7015524, 7022340, 7026873, 7027026, 7032940, 7041435, 7051372, 7074712, 7107903, 7134582, 7145146, 7150999, 7174426, 7188651, 7213006, 7232022, 7248057, 7259850, 7271937, 7273078, 7277374, 7288833, 7289181, 7308693, 7317593
];
foreach ($cardUsers as $userId) {
    try {
        $userId = intval($userId);
        $bankcardInfo = $db->getRow("SELECT bank_id,bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
        if (empty($bankcardInfo)) {
            throw new \Exception('empty user bankcard record');
        }
        $bankInfo = $db->getRow("SELECT name,short_name FROM firstp2p_bank WHERE id = '{$bankcardInfo['bank_id']}'");
        if (empty($bankInfo)) {
            throw new \Exception('empty bank info');
        }
        $cardInfo = [];
        $cardInfo['bank_bankcard'] = $bankcardInfo['bankcard'];
        $cardInfo['bank_name'] = $bankInfo['name'];
        $cardInfo['short_name'] = $bankInfo['short_name'];
        $result = $accountSerivce->memberCardUpdate($userId, $cardInfo);
        if (!isset($result['respCode']) || $result['respCode'] != SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
            throw new \Exception('request interface fail');
        }
        PaymentApi::log('Supervision update userCard success, userId:'.$userId);
    } catch(\Exception $e) {
        PaymentApi::log('Supervision update userCard fail, userId:'.$userId.', err:'.$e->getMessage());
    }
}
