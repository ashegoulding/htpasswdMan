<?php
require_once 'htpasswd.class.php';
// 스크립트가 실행될 때 아래의 파일이 반드시 존재해야 작동함.
// htpasswd -c 'THE_FILE' '계정명' 명령으로 만드세요. 
const THE_FILE = "htpasswd";
const THE_GROUP_FILE = "groups";
const MAX_ID_LENGTH = 64;

function dieWith($reason)
{
	http_response_code(400);
	header("Content-type: text/html; charset=utf-8");
	?>
<script type="application/javascript">alert("<?php echo $reason; ?>");location.replace('./');</script>
	<?php
	exit();
}

function redir()
{?>
<script type=application/javascript>location.replace("./");</script>
<?php
	exit();
}

$hp = new htpasswd(THE_FILE);
if(isset($_POST['id']) && isset($_POST['pw']))
{
	$ID = trim($_POST['id']);
	$PW = $_POST['pw'];

	if(empty($ID))
		dieWith("ID가 비었습니다.");
	else if(strlen($ID) > MAX_ID_LENGTH)
		dieWith("ID가 너무 깁니다. " . MAX_ID_LENGTH . "자 이하로 입력하세요.");
	else if(strlen($PW) === 0)
		dieWith("빈 PW는 허용되지 않습니다.");

	if($hp->user_exists($ID, $PW))
		$hp->user_delete($ID, $PW);
	$hp->user_add($ID, $PW);
	redir();
}
else if(isset($_GET['del']))
{
	$hp->user_delete($_GET['del']);
	redir();
}
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-language" content="ko" />
<meta charset=utf-8 />
<title>htpasswd 관리기</title>
<script type=application/javascript>
String.prototype.trim = function() {
	    return this.replace(/(^\s*)|(\s*$)/gi, "");
}
function onSubmit()
{
	var f = document.user;
	var id = f.id.value.trim();
	if(! id.length)
	{
		alert("ID를 입력해 주세요.");
		return false;
	}
	else if(id.length > <?php echo MAX_ID_LENGTH; ?>)
	{
		alert("ID가 너무 깁니다. <?php echo MAX_ID_LENGTH; ?>자 이하로 입력하세요.");
		return false;
	}
	else if(! f.pw.value.length)
	{
		alert("빈 PW는 허용되지 않습니다.");
		return false;
	}
	else if(f.pw.value != f.confirm.value)
	{
		alert("두 암호가 일치하지 않습니다. 다시 입력하세요.");
		return false;
	}

	return true;
}
function onChange()
{
	var pw = document.user.pw;
	var cfrm = document.user.confirm;

	if(pw.value == cfrm.value)
		pw.style.backgroundColor = cfrm.style.backgroundColor = "SpringGreen";
	else
		pw.style.backgroundColor = cfrm.style.backgroundColor = "plum";
}
</script>
<style type=text/css>
td.cell
{
	border-collapse: collapse;
	border: 1px solid black;
	padding: 5px;
}
td.longer
{
	border-collapse: collapse;
	border: 1px solid black;
	padding: 5px;
	min-width: 100px;
}
</style>
</head>
<body style="line-height: 1.4em;">

<header>
	<h1 style="text-align: center;">htpasswd 관리기</h1>
	<div>설정할 htpasswd 파일은 <u><?php echo THE_FILE; ?></u> 입니다.</div>
</header>
<br/>

<section>
<h2>유저</h2>
<article>
<div>
<h3>유저를 생성하거나 변경합니다.</h3>
</div>
<form name=user method=post action=./ onsubmit="return onSubmit()">
<table>
<tr>
<td>ID</td><td><input type=text name=id style="width: 150px;" /></td>
</tr>
<tr>
<td>암호</td><td><input type=password name=pw style="width: 150px;" /></td>
</tr>
<tr>
<td>확인</td><td><input type=password name=confirm style="width: 150px;" onkeyup="onChange()" /></td>
</tr>
<tr>
<td colspan=2 style="text-align: center;"><input type=submit value="적용" /></td>
</tr>
</table>
</form>
</article>
<br/>
<article>
<h3>유저 목록</h3>
<table style="border-collapse: collapse;"><?php
try
{
	$fh = fopen(THE_FILE, "r");
	if(! $fh)
		throw new Exception();
	$cnt = 0;
	while(($line = fgets($fh)) !== false)
	{
		$id = explode(":", $line)[0];
		echo "<tr><td class=longer>" . $id . "</td>";
		echo "<td class=cell><a href=./?del=" . $id . ">삭제</a></td></tr>";
		++$cnt;
	}
	fclose($fh);

	if($cnt)
		echo "<tr><td colspan=2 class=cell>총 <b>" . $cnt . "</b>개의 레코드</td></tr>";
}
catch(Exception $e){}
?></table>
</article>
</section>

</body>
</html>

