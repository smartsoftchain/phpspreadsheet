<?php
header("Content-Type: text/html;charset=utf-8"); 
ini_set("memory_limit", "1024M");
ini_set('max_execution_time', '360000');
ini_set('display_errors', 1 );
ini_set('session.gc_maxlifetime', 86400 );
ini_set('upload_max_filesize','50M');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);

	$path = "./inc";
	require_once($path."/conf.php");
	require_once($path."/mysqli.lib.php");
	require_once($path."/htmltemplate.inc");
	require_once($path."/mail.php");
	
	$mysqli = new DBLib\DBMysqli($DB_SV, $DB_USER, $DB_PASS, $DB_NAME);
	
	ini_set( "log_errors", "On" );
	ini_set( "error_log", "./log/".date("Y-m-d")."_php.log" );
	session_start();


	$data = array();

	$max_page = 20;

	define("SCRIPT_ENCODING", "UTF-8");
	// データベースの漢字コード
	define("DB_ENCODING", "UTF-8");
	// メールの漢字コード(UTF-8かJIS)
	define("MAIL_ENCODING", "JIS");


$act = (isset($_REQUEST['act'])) ? $_REQUEST['act'] : "";

$data["sitename"] = "";


//ドキュメントルートURl設定
$url = "http://".$_SERVER["HTTP_HOST"]."/";


// --------------------------------
// 各ページの処理

$html = &htmltemplate::getInstance();

/*--------------------------------*/

if($act == "no"){
	$code = $_REQUEST["code"];
	$id = $_REQUEST["id"];
	
	$row = $mysqli->select('product', '*', "code = '".$code."'");
	
	if($row[0]){
		$rows = $mysqli->select('sheet', '*', "uid = '".$row[0]["id"]."' and no=".$id." order by rand() limit 1");
		if($rows[0]){
			echo $rows[0]["str2"];
		}
	}
	
	
	exit;
}
if($act == "no1"){
	$code = $_REQUEST["code"];
	$ids = $_REQUEST["ids"];
	
		$rows = $mysqli->select('sheet', '*', "id = '".$ids."'");
		if($rows[0]){
			echo $rows[0]["str2"];
		}
	
	
	
	exit;
}



$data["code"] = $_REQUEST["code"];

$row = $mysqli->select('product', '*', "code = '".$data["code"]."'");
	$list = array();
	if($row[0]){
		//更新ID
		$data["pid"] = $row[0]["id"];
		$rows = $mysqli->select('sheet', '*', "uid = '".$row[0]["id"]."' and no=1 order by id");
		if($rows[0]){
			foreach($rows as $key => $val){
				$list[] = array("key"=>$val["id"],"value"=>$val["str1"]);
			}
		}
		$data["list"] = $list;
		//ボタン名取得
		$titles = array();
		$rows = $mysqli->select('sheet', array("no","titles","str1"), "uid = '".$row[0]["id"]."' group by no order by no");
		if($rows[0]){
			foreach($rows as $key => $val){
				$data["title".$val["no"]] = $val["titles"];
				$data["titles".$val["no"]] = $val["str1"];
			}
		}
		
	}



	
$html->t_include("top.html", $data);
exit;


function Paging ($page,$page_count){

	$pagingstring = "";
	if ($page > 1) {
		$pagingstring .= "<li><a href=\"javascript:void(0);\" class=\"pageBtn\" rel=\"".strval($page - 1)."\">Prev</a></li>";
		for ($i = 5; $i >= 1; $i--) {
			if ($page - $i >= 1) {
				$pagingstring .= "<li><a href=\"javascript:void(0);\" class=\"pageBtn\" rel=\"".strval($page - $i)."\">".strval($page - $i)."</a></li>";
			}
		}
	}
	$pagingstring .= "<li class=\"active\">".strval($page)."</li>";
	if ($page < $page_count) {
		for ($i = 1; $i <= 5; $i++) {
			if ($page + $i <= $page_count) {
				$pagingstring .= "<li><a href=\"javascript:void(0);\" class=\"pageBtn\" rel=\"".strval($page + $i)."\">".strval($page + $i)."</a></li>";
			}
		}
		$pagingstring .= "<li><a href=\"javascript:void(0);\" class=\"pageBtn\" rel=\"".strval($page + 1)."\">Next</a></li>";
	}
	return $pagingstring;
}





?>