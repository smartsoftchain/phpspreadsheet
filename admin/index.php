<?php
header("Content-Type: text/html;charset=utf-8"); 
ini_set("memory_limit", "1024M");
ini_set('max_execution_time', '360000');
ini_set( 'display_errors', 1 );
ini_set('date.timezone', 'Asia/Tokyo');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set( "log_errors", "On" );
ini_set( "error_log", "./log/".date("Y-m-d")."_php.log" );

$act = (isset($_REQUEST['act'])) ? $_REQUEST['act'] : "";

session_start();
$data = array();



$path = "../inc";
	require_once($path."/conf.php");
	require_once($path."/mysqli.lib.php");
	require_once($path."/htmltemplate.inc");
	

	$mysqli = new DBLib\DBMysqli($DB_SV, $DB_USER, $DB_PASS, $DB_NAME);
define("SITE_INFO", "総合管理画面");


$data["admin"]["title"] = SITE_INFO;


define("SCRIPT_ENCODING", "UTF-8");
// データベースの漢字コード
define("DB_ENCODING", "UTF-8");
// メールの漢字コード(UTF-8かJIS)
define("MAIL_ENCODING", "JIS");



$data["title"] = "";

// --------------------------------
// 各ページの処理

$html = &htmltemplate::getInstance();

/*--------------------------------*/
if($act == "logout"){
	$_SESSION = array();
	session_destroy();
	$act = "login";
}

/*----------------------------

セッションが切れていたらログインページへ

--------------------------------*/

if(!isset($_SESSION["ADMIN_LOGIN_KE"])){
	$act = "login";
}else{
	$data["admin_name"] = $_SESSION["ADMIN_LOGIN_KE"];
}
/*----------------------------

act = login　ログイン

--------------------------------*/

if($act == "login"){
	if ($_REQUEST["id"] && $_REQUEST["passwd"]) {
		$id = htmlspecialchars($_REQUEST["id"]);
		$passwd = htmlspecialchars($_REQUEST["passwd"]);
		
		$row = $mysqli->select('admin', '*', "login_id ='".$_REQUEST["id"]."' and login_pw ='".$_REQUEST["passwd"]."'");
		if($row[0] > 0){
			
				$_SESSION["ADMIN_LOGIN_KE"] = $row[0];
				$login_id = $ret["data"][0]["login_id"];
				$login_pw = $ret["data"][0]["login_pw"];

				$act="top";
			
		}else{
			$data["message"] = "ログインできません。IDとパスワードを確認してください。";
		}
	}
	if($act == "login"){
		$html->t_include("login.html", $data);
		exit;
	}
}


/*スプレッドシート更新*/
if($act == "sheet_update"){
	$id = $_REQUEST["id"];
	$item = array();
	$row = $mysqli->select('product', '*', "id='".$id."' limit 1");
	if($row[0]){
		$item = $row[0];
	}
	//シート登録
	$pid = "";
	$match2 = array();
	preg_match("/spreadsheets\/d\/(.*?)\//", $item["sheet_url"], $match2);
	if($match2[1]){
		$pid = $match2[1];

		$get_url = "https://docs.google.com/spreadsheets/d/".$pid."/export?format=xlsx";

		$filename = $id.".xlsx";
		$f = file_get_contents($get_url);
		file_put_contents(dirname(__FILE__)."/files/".$filename, $f);

		$file_path = dirname(__FILE__)."/files/".$filename;
		if(file_exists($file_path)){

			require_once "../phpspreadsheet/vendor/autoload.php";
				
			$reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
			$spreadsheet = $reader->load($file_path);
//var_dump($spreadsheet) ;
			for($i=0;$i<=4;$i++){
				$vals = array();
				$sheet = array();
				$sheet = $spreadsheet->getSheet($i);
				$vals = $sheet->toArray(); 
$titles = $spreadsheet->getSheet($i)->getTitle() ;
				//$vals = $sheet->rangeToArray("A1:B100");
				$mysqli->delete('sheet', "uid=".$id." and no=".$i."");
				if($i==1){
					if($vals){
						foreach($vals as $key2 => $val2){
							//if($key2 > 0){
								if($val2[1]){
									$forms = array();
									$forms["uid"] = $id;
									$forms["no"] = $i;
									$forms["titles"] = $titles;
									$forms["str1"] = addslashes($val2[0]);
									$forms["str2"] = addslashes($val2[1]);
									$mysqli->insert('sheet', $forms);
								}else{
									break;
								}
							//}
						}
					}
					
				}else{
					if($vals){
						foreach($vals as $key2 => $val2){
							if($val2[1]){
								$forms = array();
								$forms["uid"] = $id;
								$forms["no"] = $i;
								$forms["titles"] = $titles;
								$forms["str1"] = addslashes($val2[0]);
								$forms["str2"] = addslashes($val2[1]);
								$mysqli->insert('sheet', $forms);
							}else{
								break;
							}
						}
					}
				}
			}
			
			
			echo "更新しました。";
		}else{
			echo "更新に失敗しました。";
		}
	}else{
		echo "更新に失敗しました。";
	}
	exit;
}


/*商品コードチェック*/
if($act == "code_chk"){
	$codes = $_REQUEST["codes"];
	$row = $mysqli->select('product', '*', "code='".$codes."' limit 1");
	if($row[0]){
		echo "ng";
	}else{
		echo "ok";
	}
	exit;
}
if($act == "code_chk2"){
	$codes = $_REQUEST["codes"];
	$id = $_REQUEST["id"];
	$row = $mysqli->select('product', '*', "code='".$codes."' and id<>".$id." limit 1");
	if($row[0]){
		echo "ng";
	}else{
		echo "ok";
	}
	exit;
}
/*----------------------------

act = 管理者情報更新

--------------------------------*/

if($act == "setup"){
	//$inst = DBConnection::getConnection($DB_URI);
	$data["titles"] = "ログイン情報変更";
	if($_REQUEST["mode"] == "update"){
		$form=$_REQUEST["form"];
		
		$mysqli->update('admin', $form,"id=1");
		$data["error"] = "更新しました。";
	}
	
	$list = array();
	$row = $mysqli->select('admin', '*', "1 limit 1");
	if($row[0]){
		$list = $row[0];
	}
	$data["list"] = $list;
	
	$html->t_include("setup.html", $data);
	exit;
}



/*----------------------------

act = 商品更新

--------------------------------*/

if($act == "item_update"){
	$id = $_REQUEST["id"];
	$data["id"] = $id;
	$data["titles"] = "商品更新";
	if($_REQUEST["mode"] == "update"){
		$form = $_REQUEST["form"];
		$form["update_at"] = date("Y-m-d H:i:s");
		$mysqli->update('product', $form,"id=".$id);
		$data["message"] = "更新しました。";
	}
	
	$list = array();
	$row = $mysqli->select('product', '*', "id=".$id);
	if($row[0]){
		$list = $row[0];
	}
	$data["list"] = $list;
	
	//クライアント一覧
	$user = array();
	$row = $mysqli->select('user', '*', "1 order by `id` desc");
	if($row[0]){
		foreach($row as $key => $val){
			if($list["uid"]==$val["id"]){$sel="selected";}else{$sel="";}
			$user[] = array("key"=>$val["id"],"value"=>$val["name"],"sel"=>$sel);
		}
	}
	$data["user"] = $user;
	

	$html->t_include("item_update.html", $data);
	exit;
}

/*----------------------------

act = 商品登録

--------------------------------*/

if($act == "item_edit"){
	//$inst = DBConnection::getConnection($DB_URI);
	$data["titles"] = "商品登録";
	if($_REQUEST["mode"] == "edit"){
		$form = $_REQUEST["form"];
		$form["regist_at"] = date("Y-m-d H:i:s");
		$mysqli->insert('product', $form);
		$data["message"] = "登録しました。";
	}
	
	//クライアント一覧
	$user = array();
	$row = $mysqli->select('user', '*', "1 order by `id` desc");
	if($row[0]){
		foreach($row as $key => $val){
			$user[] = array("key"=>$val["id"],"value"=>$val["name"]);
		}
	}
	$data["user"] = $user;

	$html->t_include("item_edit.html", $data);
	exit;
}


/*----------------------------

act = 商品管理

--------------------------------*/

if($act == "item"){
	
	$data["titles"] = "商品管理";
	$search = $_REQUEST["search"];
	
	if($_REQUEST["mode"] == "del"){
		$did = $_REQUEST["did"];
		$mysqli->delete('product', "id in (".$did.")");
		
		$data["message"] = "削除しました。";
	}
	
	$user = array();
	$row = $mysqli->select('user', '*', "1 order by `id` desc");
	if($row[0]){
		foreach($row as $key => $val){
			$user[$val["id"]] = $val["name"];
		}
	}
	
	
	$maxpage = 20;
	$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
	$data["page"] = $page;
	$start = ($page-1)*$maxpage;
	if($start<0){$start=0;}
	$limit = " limit ".$start.",".$maxpage;
	
	$where = "1";
	if($search["name"]){
		$uids = array();
		$rows = $mysqli->select('user', '*', "name LIKE '%".$search["name"]."%' order by `id` desc");
		if($rows){
			foreach($rows as $keys => $vals){
				$uids[] = $vals["id"];
			}
		}
		$where .= " and `uid` in ('".implode("','",$uids)."')";
	}
	if($search["code"]){
		$where .= " and `code` LIKE '%".$search["code"]."%'";
	}
	if($search["title"]){
		$where .= " and `title` LIKE '%".$search["title"]."%'";
	}
	$data["search"] = $search;
	
	
	$list = array();
	$row = $mysqli->select('product', '*', $where." order by `id` desc");
	if($row){
		foreach($row as $key => $val){
			$val["name"] = $user[$val["uid"]];
			$list[] = $val;
		}
	}
	$data["list"] = $list;


	$data_count = 0;
	$row = $mysqli->select('product');
	//全データ件数取得
	if($row){
		$data_count = count($row);
	}else{
		$data_count = 0;
	}
	$data["cnt"] = number_format($data_count);

	$page_count = ceil($data_count / $maxpage);

	$data["pagingstring"] = Paging ((int)$page,(int)$page_count);
	



	$html->t_include("item.html", $data);
	exit;
}





/*----------------------------

act = データ更新

--------------------------------*/

if($act == "datas_update"){
	//$inst = DBConnection::getConnection($DB_URI);
	$id = $_REQUEST["id"];
	$data["id"] = $id;
	$data["titles"] = "データ更新";
	if($_REQUEST["mode"] == "update"){
		$form = $_REQUEST["form"];
		$user["update_at"] = date("Y-m-d H:i:s");
    	$mysqli->update('user', $form,"id in (".$id.")");
		$data["message"] = "更新しました。";
		
	}
	$list = array();
	$row = $mysqli->select('user', '*', "id=".$id." order by `id` desc");
	if($row[0]){
		$list = $row[0];
	}
	$data["list"] = $list;
	
	$data["status".$list["status"]] = "checked";

	$html->t_include("datas_update.html", $data);
	exit;
}



/*----------------------------

act = データ管理

--------------------------------*/

if($act == "datas_edit"){
	//$inst = DBConnection::getConnection($DB_URI);
	$data["titles"] = "クライアント新規登録";
	if($_REQUEST["mode"] == "edit"){
		$user = $_REQUEST["form"];
		$user["regist_at"] = date("Y-m-d H:i:s");
		$mysqli->insert('user', $user);
		$data["message"] = "登録しました。";
	}
	


	$html->t_include("datas_edit.html", $data);
	exit;
}


/*----------------------------

act = データ管理

--------------------------------*/

if($act == "datas"){
	
	$data["titles"] = "顧客情報";
	$search = $_REQUEST["search"];
	
	if($_REQUEST["mode"] == "del"){
		$did = $_REQUEST["did"];
		$mysqli->delete('user', "id in (".$did.")");
		exit;
	}
	
	$maxpage = 20;
	$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
	$data["page"] = $page;
	$start = ($page-1)*$maxpage;
	if($start<0){$start=0;}
	$limit = " limit ".$start.",".$maxpage;
	
	$where = "1";
	if($search["name"]){
		$where .= " and `name` LIKE '%".$search["name"]."%'";
	}
	if($search["type"]){
		$where .= " and `type`= '".$search["type"]."'";
	}

	$data["search"] = $search;

	
	$list = array();
	$row = $mysqli->select('user', '*', $where." order by `id` desc");
	if($row){
		foreach($row as $key => $val){
			$item = array();
			//ユーザー別商品ページ
			$rows = $mysqli->select('product', '*', "uid=".$val["id"]." order by `id` desc");
			if($rows[0]){
				foreach($rows as $keys => $vals){
					$item[] = array("code"=>$vals["code"],"title"=>$vals["title"]);
				}
				$val["item"] = $item;
			}
			$list[] = $val;
		}
	}
	$data["list"] = $list;


	$data_count = 0;
	$row = $mysqli->select('user');
	//全データ件数取得
	if($row){
		$data_count = count($row);
	}else{
		$data_count = 0;
	}
	$data["cnt"] = number_format($data_count);

	$page_count = ceil($data_count / $maxpage);

	$data["pagingstring"] = Paging ((int)$page,(int)$page_count);



	$html->t_include("datas.html", $data);
	exit;
}



/*----------------------------

act =  TOP一覧画面

--------------------------------*/


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
	$pagingstring .= "<li class=\"active\"><a href=\"javascript:void(0);\">".strval($page)."</a></li>";
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


function download_csv2($data, $filename,$top){
	header("Content-disposition: attachment; filename=" . $filename);
	header("Content-type: text/x-csv; charset=Shift_JIS");
	echo $fp,mb_convert_encoding(implode(",", $top), "Shift_Jis", "utf-8") . "\r\n";
	foreach ($data as $val) {
		$csv = array();
		foreach ($val as $item) {
			array_push($csv, $item);
		}
		echo mb_convert_encoding(implode(",", $csv), "Shift_Jis", "utf-8") . "\r\n";
	}
	exit;
}








?>