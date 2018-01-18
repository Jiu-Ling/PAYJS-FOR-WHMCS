<?php 
use WHMCS\Database\Capsule;

function wepay_config() {
    $configarray = array(
        "FriendlyName"  => array(
            "Type"  => "System",
            "Value" => "Pay.js微信支付"
        ),
        "key" => array(
            "FriendlyName" => "密钥",
            "Type"         => "password",
            "Size"         => "32",
        ),
        "mchid" => array(
            "FriendlyName" => "商户号",
            "Type"         => "text",
            "Size"         => "32",
        ),
        "callback" => array(
            "FriendlyName" => "通知地址(Callback)",
            "Type"         => "text"
        ),
    );

    return $configarray;
}

function wepay_form($params) {
    $n1 = $_SERVER['PHP_SELF'];
    if(stristr($n1,'viewinvoice')){
    }else{
        return '<img style="width: 150px" src="'.$systemurl.'/modules/gateways/wepay/wechat.png" alt="微信支付"  />';
    }
    $systemurl          = $params['systemurl'];
    $invoiceid          = $params['invoiceid'];
    list($price,$rate)= [$params['amount']*100,1];
    $mchid              = $params['mchid'];
    $asd=[
        'out_trade_no'=>$invoiceid,
        'total_fee'=>$price,
        'mchid'=>$mchid,
        "body"=>$params['description'],
        "notify_url"=>$params['callback']
        ];
	$asd=da_sign($asd,$params['key']);
	$result=da_post("https://payjs.cn/api/native",$asd);
	$a=$result;
	$result=json_decode($result,true);
	if($result['return_code']!=1){
	    
	    return "API调用失败".json_encode($result);
	}
	$code = '<div class="wepay"><center><div id="wepayimg" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;margin-bottom: 5px;width: 202px;"><img class="img-responsive pad" src="'.$result['qrcode'].'" style="width: 250px; height: 200px;"></div>';
	$code_ajax = '<a href="#" target="_blank" id="wepayDiv" class="btn btn-success" style="width: auto; ">使用手机微信扫描上面二维码进行支付<br>
	</a><br><span class="hidden-lg hidden-md">'.$result['code_url'].'</span></center></div>';
	$code_ajax = $code_ajax.'
<!--微信支付ajax跳转-->
	<script>	
    //设置每隔 2000 毫秒执行一次 load() 方法
    setInterval(function(){load()}, 2000);
    function load(){
        var xmlhttp;
        if (window.XMLHttpRequest){
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp=new XMLHttpRequest();
        }else{
            // code for IE6, IE5
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange=function(){
            if (xmlhttp.readyState==4 && xmlhttp.status==200){
                trade_state=xmlhttp.responseText;
                if(trade_state=="SUCCESS"){
                    document.getElementById("wepayimg").style.display="none";
                    document.getElementById("wepayDiv").innerHTML="支付成功";
                    //延迟 2 秒执行 tz() 方法
                    setTimeout(function(){tz()}, 2000);
                    function tz(){
                        window.location.href="'.$systemurl.'/viewinvoice.php?id='.$invoiceid.'";
                    }
                }
            }
        }
        //invoice_status.php 文件返回订单状态，通过订单状态确定支付状态
        xmlhttp.open("get","'.$systemurl.'/modules/gateways/wepay/invoice_status.php?invoiceid='.$invoiceid.'",true);
        //下面这句话必须有
        //把标签/值对添加到要发送的头文件。
        //xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        //xmlhttp.send("out_trade_no=002111");
        xmlhttp.send();
    }
</script>';
	
	$code = $code.$code_ajax;
    $n1 = $_SERVER['PHP_SELF'];
    if(stristr($n1,'viewinvoice')){
        return $code;
    }else{
        return '<img style="width: 150px" src="'.$systemurl.'/modules/gateways/wepay/wechat.png" alt="微信支付"  />';
    }

}

function wepay_link($params) {
    return wepay_form($params);
}
if(!function_exists("da_sort")){
function da_sort(&$array){
    ksort($array);
}
}
if(!function_exists("da_getsign")){
#签名
function da_getsign($array,$key){
    unset($array['sign']);
    ksort($array);
    $sign = strtoupper(md5(urldecode(http_build_query($array)).'&key='.$key));
    return $sign;
}}
if(!function_exists("da_sign")){
function da_sign($array,$key){
    $array['sign']=da_getSign($array,$key);
    return $array;
}}
if(!function_exists("da_checksign")){
function da_checksign($array,$key){
    $new = $array;
    $new=da_sign($new,$key);
    if(!isset($array['sign'])){
        return false;
    }
    return $array['sign']==$new['sign'];
}}
if(!function_exists("da_post")){
function da_post($url, $data = null){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($data)){
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);
	curl_close($curl);
	return $output;
}}

if(!function_exists("autogetamount")){
function autogetamount($params){
    $amount=$params['amount'];
    $currencyId=$params['currencyId'];
    $currencys=localAPI("GetCurrencies", [], wepay_getAdminname());
    if($currencys['result']=='success' and $currencys['totalresults']>=1){
        
    }else{
        var_dump($currencys);
        throw new \Exception('货币设置错误、API请求错误');
        //如果api请求错误或者货币数量小于1
    }
    //获取货币。
    $currencys=$currencys['currencies']['currency'];
    foreach($currencys as $currency){
        if($currencyId==$currency['id']){
            $from=$currency;
            break;
        }
    }
    if(!$from){
        throw new \Exception("货币错误，找不到起始货币。");
    }
    foreach($currencys as $currency){
        $hb=strtoupper($currency['code']);
        if($hb=='CNY' or $hb=='RMB'){
            $cny=$currency;
            break;
        }
    }
    if(!$cny){
        throw new \Exception("找不到人民币货币，请确认后台货币中存在货币代码为CNY的货币！");
    }
    $rate=$cny['rate']/$from['rate'];
    return [round((double)$rate*$amount,2),round((double)$rate,2)];
}
}
if(!function_exists("wepay_getAdminname")){
function wepay_getAdminname(){
    $admin = Capsule::table('tbladmins')->first();
    return $admin->username;
}
}