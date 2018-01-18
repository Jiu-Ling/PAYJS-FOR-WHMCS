<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1'); 
use WHMCS\Database\Capsule;
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "wepay"; # Enter your gateway module name here replacing template
$GATEWAY       = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"])
    die("Module Not Activated"); # Checks gateway module is active before accepting callback

$order_data                  = $_POST;
$gatewaySECURITY_CODE        = $GATEWAY['key'];

$status    = $order_data['return_code'];         //获取传递过来的交易状态
$invoiceid = $order_data['out_trade_no'];     //订单号
$transid   = $order_data['payjs_order_id'];       //转账交易号
$amount    = $order_data['total_fee'];          //获取递过来的总价格
$fee=0;
if(!da_checksign($_POST,$gatewaySECURITY_CODE)){
    die(json_encode(array('errcode'=>2333)));
 
}

if ($status == '1') {
    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]);
    checkCbTransID($transid);
    addInvoicePayment($invoiceid, $transid,Capsule::table('tblinvoices')->where('id',$invoiceid)->get()->total,0,$gatewaymodule);//Capsule::table('tblinvoices')->where('id',$invoiceid)->update(['status'=>'Paid']);
    logTransaction($GATEWAY["name"], $_POST, "Successful");
    echo json_encode(['errcode'=>0]);
} else {
    echo 'faild';
}
