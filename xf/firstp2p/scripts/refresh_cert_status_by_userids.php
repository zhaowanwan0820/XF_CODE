<?php
/**
 * 刷新用户绑卡认证类型
 * author: weiwei12@ucfgroup.com
 */

$user_ids = array('6118', '7804', '6385', '7343', '2168', '26498', '2285', '7026', '22460', '10163', '3746', '10092', '14268', '9801', '13596', '10912', '17985', '27388', '7490', '5360', '6280', '14776', '13425', '10636', '10634', '8289', '25740', '10170', '15838', '9281', '9225', '18816', '6996', '1465', '119', '23568', '839', '9411', '2174', '12939', '11706', '24955', '6311', '19783', '13942', '4783', '4162', '19141', '20012', '8317', '532', '8628', '11596', '5269', '13905', '21693', '11481', '18109', '3903', '27161', '1988', '5132', '4502', '8128', '12851', '13589', '10348', '20709', '20113', '13450', '19057', '19345', '7984', '1116', '6116', '3850', '18094', '4732', '1226', '21793', '12897', '5801', '300', '16036', '4576', '9986', '8005', '2591', '8892', '16321', '829', '3533', '5488', '12038', '4176', '5826', '13043', '9713', '10215', '18930', '8835', '17050', '4032', '14449', '10611', '4285', '16726', '6137', '18751', '16085', '5800', '501', '13223', '30990', '26039', '5116', '1197', '7453', '4638', '14790', '6127', '13059', '24791', '331', '10549', '4850', '25949', '14249', '22348', '1411', '18701', '4934', '329', '3924', '12147', '29362', '7511', '27366', '25154', '24108', '5463', '6569', '4798', '1126', '8373', '8340', '16766', '853', '5460', '3516', '3327', '16154', '6017', '20880', '27319', '23785', '5568', '16256', '12019', '16120', '16202', '22069', '11505', '874', '355', '14772', '5764', '5866', '18586', '21218', '14918', '11677', '7376', '20409', '17738', '15717', '5960', '5782', '9126', '6215', '8011', '4844', '15236', '4149', '12868', '21051', '641', '5584', '6360', '30711', '15461', '12991', '11995', '80', '16663', '16370', '13555', '5079', '20257', '18582', '13750', '19569', '6758', '5977', '10359', '1790', '12753', '4633', '4896', '8348', '4982', '742', '20126', '6999', '5814', '20902', '8087', '31497', '12670', '18647', '18830', '10208', '16205', '8502', '15961', '15936', '18920', '1155', '19955', '1363', '2136', '7499', '4720', '553', '319', '27445', '6484', '5702', '5525', '4854', '14119', '61', '4903', '6830', '14035', '4945', '21522', '31365', '5019', '17226', '15812', '7417', '5341', '759', '1564', '9595', '27524', '10268', '9361', '19035', '13808', '5395', '11590', '29727', '27058', '8235', '13164', '8342', '28994', '27996', '23144', '3130', '4618', '5447', '10319', '18325', '27790', '5789', '7811', '21138', '13987', '22425', '7030', '27455', '18198', '8850', '19779', '7478', '16128', '21380', '4834', '6549', '13890', '11473', '23588', '4628', '6450', '9077', '8646', '16033', '3325', '6216', '15324', '18525', '628', '18679', '288', '6044', '1909', '5013', '25737', '5005', '11997', '11433', '13422', '13469', '9777', '2425', '7070', '13376', '6938', '5362', '14955', '4533', '7535', '16226', '13288', '14459', '430', '11301', '12842', '15559', '13237', '979', '9426', '17665', '4925', '1255', '252', '7159', '18527', '23450', '15160', '24792', '10080', '16648', '469', '10368', '19774', '13365', '19588', '16218', '4118', '14444', '8546', '24505', '9481', '10310', '10318', '19059', '6506', '4966', '5962', '7982', '6083', '5526', '8135', '1937', '1129', '10664', '7263', '6971', '5643', '12880', '5729', '16288', '15528', '18321', '10770', '13785', '12113', '13675', '9926', '16570', '23363', '11191', '12193', '17927', '30378', '18652', '17638', '15990', '6905', '5059', '6174', '14864', '4234', '11524', '10372', '1177', '24770', '12841', '25103', '19405', '22291', '5071', '6125', '13054', '2811', '19023', '18989', '14604', '20317', '13168', '1389', '19576', '28077', '5353', '7447', '26681', '13370', '15222', '23601', '4937', '26149', '18728', '7452', '1251', '3492', '1189', '7630', '6577', '1844', '26903', '6070', '7932', '11435', '8328', '5442', '5751', '9335', '4735', '12925', '10555', '5598', '28761', '15719', '3536', '25386', '6637', '19659', '11802', '605', '4202', '7569', '20884', '14059', '1468', '115', '18288', '18898', '4608', '11592', '7268', '15292', '17262', '14676', '28662', '13229', '13201', '14708', '5029', '526', '8860', '1558', '21477', '8247', '27642', '15710', '14049', '16375', '5604', '1214', '11639', '16870', '2869', '7427', '16295', '15041', '4817', '12697', '4405', '12115', '25459', '4913', '11707', '29753', '4146', '9787', '21466', '26505', '18367', '7116', '28417', '316', '2899', '10029', '1024', '24596', '13569', '19692', '11675', '5287', '15702', '26887', '4793', '9098', '13613', '15379', '18785', '989', '13253', '13262', '15885', '5520', '29856', '5450', '5410', '31275', '5387', '15660', '24110', '29707', '29686', '29697', '31280', '29642', '31266', '29665', '29715', '29628', '29652', '28589', '31218', '28549', '28587', '29494', '29614', '31194', '28553', '27213', '17270', '12930', '21062', '30586', '6685', '31628', '30527', '31618', '30520', '28902', '30851', '30880', '29126', '31795', '11832', '28115', '28156', '29157', '29113', '29112', '28146', '19320', '15148', '5976', '28258', '28244', '5979', '17255', '20242', '474', '4171', '24761', '9719', '31085', '28379', '1570', '14950', '21605', '10153', '6795', '24219', '1172', '19186', '29376', '13859', '13578', '15973', '8357', '11969', '16529', '10784', '11071', '30190', '21148', '5027', '1985', '22097', '8258', '30607', '7704', '8475', '4506', '29901', '7431', '14584', '15722', '13282', '15868', '8996', '36', '19369', '29625', '12775', '2848', '8625', '2082', '20795', '24366', '5305', '7466', '30272', '544', '6967', '7501', '1489', '15228', '4962', '16274', '21074', '3784', '7457', '14332', '1113', '18733', '6943', '8419', '3895', '5118', '15690', '10632', '1127', '5851', '3792', '30429', '20042', '14284', '20599', '672', '17461', '11756', '7586', '393', '3744', '7981', '19093', '14898', '7937', '5516', '30333', '5836', '5053', '12182', '341', '24940', '16642', '3541', '7884', '725', '28038', '19834', '12205', '20000', '5868', '7006', '4985', '13489', '7623', '11514', '15223', '14232', '10732', '12885', '1474', '12443', '11292', '29800', '128', '3989', '5541', '1464', '4346', '30462', '6012', '750', '5406', '7712', '5768', '15883', '18023', '23854', '1215', '16249', '15395', '26343', '26388', '10281', '3830', '4718', '30993', '12863', '8739', '5332', '11320', '17025', '8500', '1967', '987', '5529', '11281', '4597', '4765', '8823', '5936', '388', '24005', '5157', '11508', '14700', '12155', '4258', '19401', '31568', '22073', '14189', '1906', '12613', '26126', '4954', '7010', '20511', '6995', '17886', '23265', '939', '2725', '31161', '4204', '5086', '7480', '11003', '3905', '13303', '5100', '12552', '7617', '17005', '10172', '10003', '12997', '10443', '12769', '11378', '12116', '29075', '6522', '6536', '10288', '19271', '6753', '8199', '13736', '18850', '14383', '6568', '13743', '19964', '18256', '5599', '20563', '20445', '19608', '4623', '5124', '1294', '1858', '27115', '1007', '18458', '19215', '5716', '21710', '7215', '25561', '7836', '6582', '7592', '477', '19464', '26521', '4935', '25153', '7160', '957', '5761', '978', '19566', '20218', '321', '5334', '21742', '5735', '17986', '13633', '17961', '9647', '6179', '7717', '4548', '5162', '15982', '14706', '25683', '24823', '12296', '6757', '5338', '6084', '5819', '982', '9036', '25059', '10324', '4909', '26753', '18381', '9673', '4752', '6989', '18308', '29891', '13486', '1624', '5531', '2386', '5983', '15022', '13542', '30696', '7357', '10388', '12699', '9733', '15592', '15694', '13454', '15661', '19049', '25826', '24616', '20644', '19342', '22408', '12836', '21230', '26161', '19641', '28957', '30455', '30446', '29326', '6038', '13157', '20458', '20438', '14617', '18129', '21404', '24000', '8686', '8101', '7768', '7616', '7634', '10004', '9790', '25500', '10762', '10265', '29814', '13636', '16756', '32043', '27143', '27174', '5556', '13505', '11021', '11076', '10964', '11048', '19377', '31705', '28981', '14043', '13908', '19963', '13516', '5752', '31699', '16638', '15708', '12387', '11239', '12325', '17772', '5560', '460', '10096', '13330', '5757', '6', '25843', '16230', '4641', '17641', '5508', '171', '13543', '13523', '14836', '11096', '23782', '8765', '7560', '12515', '11257', '5267', '19152', '8917', '17849', '12979', '13599', '12523', '21305', '1038', '14527', '9495', '1332', '13023', '1902', '28017', '13235', '7173', '7703', '10205', '2885', '6187', '5155', '10726', '3476', '4980', '30179', '10878', '26514', '26337', '1905', '15901', '3751', '25056', '17331', '837', '6119', '23778', '1106', '5427', '17622', '537', '18886', '19621', '4940', '13099', '12736', '512', '503', '2413', '1811', '2839', '6977', '287', '28698', '24716', '27696', '5414', '20538', '9972', '9228', '4799', '16094', '21741', '4490', '18346', '5448', '320', '9047', '6802', '5855', '7356', '19809', '408', '21264', '17407', '6833', '21120', '10250', '1309', '13562', '5948', '15364', '951', '6244', '13575', '21536', '6468', '10503', '18871', '12833', '4727', '31470', '12461', '11029', '20641', '5839', '555', '16712', '3947', '5424', '5436', '13682', '3969', '8980', '17145', '7915', '5432', '12601', '376', '10204', '721', '14212', '14824', '17186', '18662', '11182', '18545', '1157', '21153', '12630', '18063', '11687', '21117', '30628', '11987', '16407', '16069', '12629', '16519', '9478', '31369', '6936', '5774', '10666', '4810', '12369', '974', '14181', '25272', '22091', '10360', '20775', '20742', '17661', '10739', '15003', '10727', '24446', '31036', '12067', '10627', '13063', '985', '11831', '7278', '13354', '3736', '9123', '6654', '18088', '19159', '442', '24636', '8094', '8610', '12848', '16323', '6942', '10975', '7039', '15827', '28704', '9343', '16754', '5445', '13470', '6098', '18401', '8662', '9670', '15801', '14013', '6697', '1941', '13193', '1169', '30040', '16645', '8733', '25892', '30250', '8703', '21777', '12923', '10902', '13245', '12761', '1200', '20560', '24046', '13055', '22310', '26244', '7351', '13092', '557', '11731', '22446', '5527', '15501', '26414', '14080', '14061', '17254', '5842', '7740', '14010', '26386', '5943', '14241', '12785', '1206', '20652', '16167', '13250', '12989', '830', '29732', '4050', '6041', '13251', '861', '16376', '8820', '118', '30468', '5318', '1771', '8370', '22082', '19223', '369', '11333', '293', '13406', '11664', '827', '6219', '8155', '8376', '4949', '30162', '4753', '6036', '8838', '12120', '7201', '165', '9028', '20032', '392', '9373', '30661', '11490', '15593', '8907', '546', '7894', '13946', '7130', '31002', '4887', '1882', '11536', '6867', '14132', '31550', '5562', '7105', '8406', '19240', '5685', '9610', '4809', '12987', '7686', '2967', '29349', '16314', '27', '5050', '22178', '4771', '10554', '13071', '1290', '13207', '9571', '13522', '11010', '73', '10352', '22322', '522', '5249', '9082', '14850', '1768', '17268', '20195', '26785', '10476', '13397', '1146', '17342', '14319', '4486', '1230', '12714', '23522', '10519', '3752', '3065', '17783', '20413', '2054', '11034', '4186', '466', '2188', '12522', '18531', '25304', '15981', '1522', '514', '18405', '11321', '24441', '5509', '5480', '13246', '395', '1060', '15433', '5289', '7870', '19652', '1168', '3220', '10757', '5212', '368', '4918', '8066', '1385', '4612', '14643', '23690', '5899', '2600', '15210', '23532', '16133', '3640', '13781', '11683', '5741', '13309', '2669', '19260', '13014', '19568', '16038', '19703', '20514', '14244', '4334', '10681', '14631', '5780', '13655', '11559', '24353', '619', '18044', '5987', '8687', '27255', '14169', '12363', '5187', '20764', '12918', '13461', '10336', '27592', '13266', '13057', '16254', '19849', '10362', '26458', '9711', '29870', '1176', '5285', '20756', '20557', '18528', '12175', '1441', '16063', '5616', '30156', '4635', '21338', '5712', '5536', '28020', '14895', '4590', '13199', '7972', '28066', '13661', '19287', '6402', '365', '13449', '25727', '7966', '13295', '1218', '14663', '496', '7758', '7989', '18524', '12680', '10255', '14874', '7987', '14387', '5428', '15688', '13165', '7978', '6652', '789', '12750', '24630', '22266', '5876', '11644', '9960', '13419', '16258', '27761', '1975', '12560', '438', '500', '6316', '12251', '12640', '4928', '4282', '24270', '8125', '7085', '18870', '8764', '3939', '20211', '7983', '13829', '4112', '14213', '4063', '4961', '8074', '22153', '26954', '9314', '4343', '15711', '4302', '5144', '1371', '1117', '16296', '26918', '18712', '16304', '13981', '15050', '12000', '26171', '16285', '6861', '4840', '6027', '916', '21656', '338', '5551', '2843', '7101', '1369', '19831', '26125', '6634', '20911', '9045', '4068', '5904', '7926', '22371', '22430', '5612', '12194', '4744', '8581', '20702', '1003', '15234', '8713', '10236', '16235', '18805', '4856', '18070', '3150', '434', '16153', '12608', '8054', '846', '20878', '13208', '3968', '21325', '12849', '2955', '11299', '10718', '15807', '14716', '15146', '11670', '10342', '12315', '20829', '11806', '16243', '7940', '15434', '21046', '26188', '13685', '6940', '18121', '16864', '25048', '11310', '24725', '4199', '11754', '29899', '12212', '12126', '22427', '4194', '19055', '4268', '11646', '29868', '16244', '9477', '7094', '7276', '3535');

require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\LOGGER;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

function formatCardNoForLog($params)
{
    if (isset($params[0]) && !empty($params[0]))
    {
        foreach ($params as $key => $item)
        {
            $params[$key] = formatCardNoForLog($item);
        }
        return $params;
    }
    if (!empty($params['cardNo'])) {
        $params['cardNo'] = formatBankcard($params['cardNo']);
    }
    if (!empty($params['bankcard'])) {
        $params['bankcard'] = formatBankcard($params['bankcard']);
    }
    return $params;
}

$cert_status_map = array(
    'EXTERNAL_CERT' => 1, //IVR语音认证
    'FASTPAY_CERT'  => 2, //快捷认证(四要素认证)
    'TRANSFER_CERT' => 3, //转账认证
    'WHITELIST_CERT' => 4, //白名单
    'REMIT_CERT'    => 5, //打款认证
    'ONLY_CARD'    => 6, //卡密认证
    'AUDIT_CERT'    => 7, //人工认证
    'NO_CERT'    => 8, //未认证
);

PaymentApi::log("begin refresh cert status by userids", LOGGER::INFO);

$userBankCardObj = new \core\service\UserBankcardService();

foreach ($user_ids as $user_id) {

    $select_sql = "SELECT `id`, `cert_status`, `user_id`, `bankcard` FROM `firstp2p_user_bankcard` WHERE `user_id` = '{$user_id}'";
    $result = \libs\db\Db::getInstance('firstp2p')->getAll($select_sql);
    if (empty($result)) {
        PaymentApi::log("refresh cert status failed, bankcard is empty from db, user_id:{$user_id}", LOGGER::WARN);
    }
    if (count($result) > 1) {
        PaymentApi::log("more than one bank card data, user: {$user_id}", LOGGER::INFO);
    }
    foreach ($result as $ret) {
        // 获取支付系统所有银行卡列表
        $bank_info = $userBankCardObj->queryBankCardsList($ret['user_id']);
        if (empty($bank_info['list'])) {
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, bankCards is empty, user_id: {$ret['user_id']}", LOGGER::WARN);
            continue;
        }
        //查找bank card
        $bank_cards = $bank_info['list'];
        $card = array();
        foreach ($bank_cards as $bank_card) {
            if ($ret['bankcard'] == $bank_card['cardNo']) {
                $card = $bank_card;
                break;
            }
        }
        if (empty($card)) {
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, card not match, user_id: {$ret['user_id']}, ret: " . json_encode(formatCardNoForLog($ret)) . ', searchbankcards: ' . json_encode(formatCardNoForLog($bank_cards)), LOGGER::WARN);
            continue;
        }
        if (empty($card['certStatus'])) {
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, certStatus is empty, user_id: {$ret['user_id']}, card: " . json_encode(formatCardNoForLog($card)), LOGGER::WARN);
            continue;
        }

        $cert_status = isset($cert_status_map[$card['certStatus']]) ? $cert_status_map[$card['certStatus']] : 0;
        $update_sql = "UPDATE `firstp2p_user_bankcard` set `cert_status` = '{$cert_status}' where `id` = '{$ret['id']}'";
        $update =  \libs\db\Db::getInstance('firstp2p')->query($update_sql);
        if (!$update) {
            PaymentApi::log("refresh cert status failed, update failed, id: {$ret['id']}, user_id: {$user_id}, cert_status: {$cert_status}", LOGGER::WARN);
        } else {
            PaymentApi::log("refresh cert status success, id: {$ret['id']}, user_id: {$user_id}, cert_status: {$cert_status}", LOGGER::INFO);
        }
    }

}

PaymentApi::log("end refresh cert status by userids", LOGGER::INFO);

exit(0);
