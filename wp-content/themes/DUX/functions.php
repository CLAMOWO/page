<?php
// Require theme functions
require get_stylesheet_directory() . '/functions-theme.php';

// Customize your functions
//添加背景按钮
add_theme_support( 'custom-background');
function custom_login_head(){
$imgurl = 'https://api.ixiaowai.cn/api/api.php';
if($imgurl){
    echo'
'; }} add_action('login_head', 'custom_login_head'); add_filter('login_headerurl', create_function(false,"return get_bloginfo('url');")); add_filter('login_headertitle', create_function(false,"return get_bloginfo('name');"));

function get_ip(){
//判断服务器是否允许$_SERVER
if(isset($_SERVER)){
if(isset($_SERVER[HTTP_X_FORWARDED_FOR])){
$realip = $_SERVER[HTTP_X_FORWARDED_FOR];
}elseif(isset($_SERVER[HTTP_CLIENT_IP])) {
$realip = $_SERVER[HTTP_CLIENT_IP];
}else{
$realip = $_SERVER[REMOTE_ADDR];
}
}else{
//不允许就使用getenv获取
if(getenv("HTTP_X_FORWARDED_FOR")){
$realip = getenv( "HTTP_X_FORWARDED_FOR");
}elseif(getenv("HTTP_CLIENT_IP")) {
$realip = getenv("HTTP_CLIENT_IP");
}else{
$realip = getenv("REMOTE_ADDR");
}
}
return $realip;
}
function convertip($ip) {
$dat_path = "qqwry.dat"; //*数据库路径*//
if(!$fd = @fopen($dat_path, 'rb')){
return 'IP date file not exists or access denied';
}
$ip = explode('.', $ip);
$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
$DataBegin = fread($fd, 4);
$DataEnd = fread($fd, 4);
$ipbegin = implode('', unpack('L', $DataBegin));
if($ipbegin < 0) $ipbegin += pow(2, 32);
$ipend = implode('', unpack('L', $DataEnd));
if($ipend < 0) $ipend += pow(2, 32);
$ipAllNum = ($ipend - $ipbegin) / 7 + 1;
$BeginNum = 0;
$EndNum = $ipAllNum;
while($ip1num>$ipNum || $ip2num<$ipNum) {
$Middle= intval(($EndNum + $BeginNum) / 2);
fseek($fd, $ipbegin + 7 * $Middle);
$ipData1 = fread($fd, 4);
if(strlen($ipData1) < 4) {
fclose($fd);
return 'System Error';
}
$ip1num = implode('', unpack('L', $ipData1));
if($ip1num < 0) $ip1num += pow(2, 32);
if($ip1num > $ipNum) {
$EndNum = $Middle;
continue;
}
$DataSeek = fread($fd, 3);
if(strlen($DataSeek) < 3) {
fclose($fd);
return 'System Error';
}
$DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
fseek($fd, $DataSeek);
$ipData2 = fread($fd, 4);
if(strlen($ipData2) < 4) {
fclose($fd);
return 'System Error';
}
$ip2num = implode('', unpack('L', $ipData2));
if($ip2num < 0) $ip2num += pow(2, 32);
if($ip2num < $ipNum) {
if($Middle == $BeginNum) {
fclose($fd);
return 'Unknown';
}
$BeginNum = $Middle;
}
}
$ipFlag = fread($fd, 1);
if($ipFlag == chr(1)) {
$ipSeek = fread($fd, 3);
if(strlen($ipSeek) < 3) {
fclose($fd);
return 'System Error';
}
$ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
fseek($fd, $ipSeek);
$ipFlag = fread($fd, 1);
}
if($ipFlag == chr(2)) {
$AddrSeek = fread($fd, 3);
if(strlen($AddrSeek) < 3) {
fclose($fd);
return 'System Error';
}
$ipFlag = fread($fd, 1);
if($ipFlag == chr(2)) {
$AddrSeek2 = fread($fd, 3);
if(strlen($AddrSeek2) < 3) {
fclose($fd);
return 'System Error';
}
$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
fseek($fd, $AddrSeek2);
} else {
fseek($fd, -1, SEEK_CUR);
}
while(($char = fread($fd, 1)) != chr(0))
$ipAddr2 .= $char;
$AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
fseek($fd, $AddrSeek);
while(($char = fread($fd, 1)) != chr(0))
$ipAddr1 .= $char;
} else {
fseek($fd, -1, SEEK_CUR);
while(($char = fread($fd, 1)) != chr(0))
$ipAddr1 .= $char;
$ipFlag = fread($fd, 1);
if($ipFlag == chr(2)) {
$AddrSeek2 = fread($fd, 3);
if(strlen($AddrSeek2) < 3) {
fclose($fd);
return 'System Error';
}
$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
fseek($fd, $AddrSeek2);
} else {
fseek($fd, -1, SEEK_CUR);
}
while(($char = fread($fd, 1)) != chr(0)){
$ipAddr2 .= $char;
}
}
fclose($fd);
if(preg_match('/http/i', $ipAddr2)) {
$ipAddr2 = '';
}
$ipaddr = "$ipAddr1 $ipAddr2";
$ipaddr = preg_replace('/CZ88.Net/is', '', $ipaddr);
$ipaddr = preg_replace('/^s*/is', '', $ipaddr);
$ipaddr = preg_replace('/s*$/is', '', $ipaddr);
if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
$ipaddr = 'Unknown';
}
$ipaddr = iconv('gbk', 'utf-8//IGNORE', $ipaddr);
if( $ipaddr != ' ' )
return $ipaddr;
else
$ipaddr = '火星来客';
return $ipaddr;
}