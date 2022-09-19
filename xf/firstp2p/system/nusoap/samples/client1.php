<?php
/*
 *	$Id: client1.php,v 1.3 2007/11/06 14:48:24 snichol Exp $
 *
 *	Client sample that should get a fault response.
 *
 *	Service: SOAP endpoint
 *	Payload: rpc/encoded
 *	Transport: http
 *	Authentication: none
 */

set_time_limit(0);
require_once('../lib/nusoap.php');
require_once('../lib/des.php');

$client = new nusoap_client('https://gboss.id5.cn/services/QueryValidatorServices?wsdl', 'wsdl');
$client->soap_defencoding = 'gbk'; 
$err = $client->getError();
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
	echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
	exit();
}

$DES = new DES(12345678, 12345678);


$partner = $DES->encrypt ('dzwxjr123');
$partnerPW = $DES->encrypt ('dzwxjr123_5_8C!K!S');
$type = $DES->encrypt ('1A020201');

$param = mb_convert_encoding ('ÀîÐ¡Ã÷,333081196410100010', "GBK", "UTF-8" );
$params = array ("userName_" => $partner, "password_" => $partnerPW, "type_" => $type, "param_" => $param );
$result = $client->call('querySingle', $params);
var_dump($result);
exit;

/*
$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
$useCURL = isset($_POST['usecurl']) ? $_POST['usecurl'] : '0';
$client = new nusoap_client("https://gboss.id5.cn/services/QueryValidatorServices?wsdl", 'wsdl',
						$proxyhost, $proxyport, $proxyusername, $proxypassword);
$err = $client->getError();
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
	echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
	exit();
}
$client->setUseCurl($useCURL);
// This is an archaic parameter list
$params = array(
    'manufacturer' => "O'Reilly",
    'page'         => '1',
    'mode'         => 'books',
    'tag'          => 'trachtenberg-20',
    'type'         => 'lite',
    'devtag'       => 'Your tag here',
    'sort'         => '+title'
);
$result = $client->call('ManufacturerSearchRequest', $params, 'http://soap.amazon.com', 'http://soap.amazon.com');
if ($client->fault) {
	echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
} else {
	$err = $client->getError();
	if ($err) {
		echo '<h2>Error</h2><pre>' . $err . '</pre>';
	} else {
		echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
	}
}
echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
*/

?>
