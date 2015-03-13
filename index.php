<?php
require_once 'lib/htgroups.class.php';
require_once 'lib/htpasswd.class.php';

require_once 'LocalSettings.php';
require_once 'lib/lib.php';

if(isset($_POST['id']) && isset($_POST['pw']))
{
	$ID = trim($_POST['id']);
	$PW = $_POST['pw'];
	
	if((!ADMIN_FEATURE) && THE_USER != $ID)
		dieWith("당신은 관리자가 아니야!");

	if(empty($ID))
		dieWith("ID가 비었습니다.");
	else if(strlen($ID) > MAX_ID_LENGTH)
		dieWith("ID가 너무 깁니다. " . MAX_ID_LENGTH . "자 이하로 입력하세요.");
	else if(strlen($PW) === 0)
		dieWith("빈 PW는 허용되지 않습니다.");

	if($hp->user_exists($ID, $PW))
		$hp->user_delete($ID, $PW);
	$hp->user_add($ID, $PW);
	redir("./");
}
else if(isset($_GET['del']))
{
	$ID = trim($_GET['del']);
	if((!ADMIN_FEATURE) && THE_USER != $ID)
		dieWith("당신은 관리자가 아니야!");
	
	try
	{
		$gp->removeFromAll($ID);
		$gp->commitOnDelete = true;
	}
	catch(Exception $e){}
	$hp->user_delete($ID);
	if(ADMIN_FEATURE)
		redir("./#groupGovernorHeading");
	else
		redir("./");
}
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-language" content="ko" />
<meta charset=utf-8 />
<title>htaccess Integration</title>
<script type=application/javascript>
<?php
if(ADMIN_FEATURE)
{
	echo "const ADMIN_FEATURE = true;\r\n";
	echo "const THE_ADMIN_GROUP = \"" . THE_ADMIN_GROUP . "\";\r\n";
}
else
	echo "const ADMIN_FEATURE = false;\r\n";
echo "const THE_USER = \"" . THE_USER . "\";\r\n";
?>

var groupGovernor = null;
var groupMap = null;
var groupNameMap = null;

String.prototype.trim = function() {
	    return this.replace(/(^\s*)|(\s*$)/gi, "");
}
var groupAjax = new XMLHttpRequest();
groupAjax.onreadystatechange = function()
{
	if(this.readyState != 4 || this.status != 200)
		return;
	var doc = this.responseXML;
	var i;
	
	while(groupGovernor.firstChild)
		groupGovernor.removeChild(groupGovernor.firstChild);
	
	var root = doc.getElementsByTagName("htpasswdMan")[0];
	var groups = root.getElementsByTagName("group");
	var users = root.getElementsByTagName("groups")[0].getElementsByTagName("user");
	
	// Heading
	var headingLine = document.createElement("tr");
	headingLine.className = "heading";
	headingLine.style.background = "violet";
	groupGovernor.appendChild(headingLine);
	{
		var delHead = document.createElement("th");
		delHead.className = "cell";
		delHead.innerHTML = "삭제";
		headingLine.appendChild(delHead);
		var idHead = document.createElement("th");
		idHead.className = "cell";
		idHead.innerHTML = "ID";
		headingLine.appendChild(idHead);
		for(i=0; i<groups.length; ++i)
		{
			var groupHead = document.createElement("td");
			groupHead.className = "cell";
			groupHead.style.textAlign = "center";
			groupHead.innerHTML = groups[i].firstChild.nodeValue;
			headingLine.appendChild(groupHead); 
		}
		initGroupMap();
		
		var createHead = document.createElement("th");
		createHead.className = "cell";
		createHead.innerHTML = "생성";
		headingLine.appendChild(createHead);
	}
	
	// User lines
	for(i = 0; i<users.length; ++i)
	{
		var arr = [];
		var j;
		for(j in groupMap)
			arr.push(false);

		var userLine = document.createElement("tr");
		userLine.className = "userLine";
		groupGovernor.appendChild(userLine);

		var delCell = document.createElement("td");
		delCell.className = "cell";
		delCell.style.textAlign = "center;";
		userLine.appendChild(delCell);
		if(THE_USER == users[i].getAttribute("id"))
		{
			delCell.innerHTML = "&nbsp;";
		}
		else
		{
			var delLink = document.createElement("a");
			delLink.innerHTML = "삭제";
			delLink.href = "./?del=" + users[i].getAttribute("id");
			delCell.appendChild(delLink);
		}

		var idTag = document.createElement("td");
		idTag.innerHTML = users[i].getAttribute("id");
		idTag.className = "cell";
		userLine.appendChild(idTag);

		var belongs = users[i].getElementsByTagName("belongs");
		for(j=0; j<belongs.length; ++j)
			arr[groupMap[belongs[j].firstChild.nodeValue]] = true;
		for(j=0; j<arr.length; ++j)
		{
			var groupCell = document.createElement("td");
			groupCell.className = "cell";
			groupCell.style.cursor = "pointer";
			groupCell.style.textAlign = "center";
			if(arr[j])
			{
				groupCell.style.background = "cyan";
				groupCell.innerHTML = "O";
				groupCell.belongs = true;
			}
			else
				groupCell.belongs = false;
			groupCell.groupName = groupNameMap[j];
			groupCell.userID = users[i].getAttribute("id");
			groupCell.onclick = onGroupClick;
			userLine.appendChild(groupCell);
		}

		var createCell = document.createElement("td");
		createCell.className = "cell";
		userLine.appendChild(createCell);
		{
			var group = document.createElement("input");
			group.type = "text";
			group.className = "createGroupText";
			group.style.width = "120px";
			group.onkeyup = function(e)
			{
				if(! (e.keyCode == 13 || e.keyCode == 10))
					return;
				var toInvoke = e.target.parentNode.getElementsByClassName("createGroupButton");
				if(toInvoke[0])
					toInvoke[0].click();
			};
			createCell.appendChild(group);

			var createBtn = document.createElement("input");
			createBtn.type = "button";
			createBtn.className = "createGroupButton";
			createBtn.value = "적용";
			createBtn.onclick = onGroupEditClick;
			createBtn.userID = users[i].getAttribute("id");
			createCell.appendChild(createBtn);
		}
	}

	try
	{
		document.getElementById("groupRefreshBtn").disabled = false;
	}
	catch(e){}		
}

function initGroupMap()
{
	var heading = groupGovernor.getElementsByClassName("heading")[0];
	var groups = heading.getElementsByTagName("td");
	groupMap = new Object();
	groupNameMap = new Object();
	
	var i;
	for(i=0; i<groups.length; ++i)
	{
		groupMap[groups[i].firstChild.nodeValue] = i;
		groupNameMap[i] = groups[i].firstChild.nodeValue;
	}
}

function onLoad()
{
	groupGovernor = document.getElementById("groupGovernor");
	loadGroup();
}

function loadGroup(t)
{
	groupAjax.open("GET", "./group_ajax.php", true);
	groupAjax.send();
	if(t)
		document.getElementById("groupRefreshBtn").disabled = true;
}

function validateName(x)
{
	x = x.trim();
	if(x.search(/^[0-9]/i) >= 0)
		throw "이름은 숫자로 시작할 수 없습니다.";
	else if(x.search(/[^a-z0-9_]/i) >= 0)
		throw "허용되지 않는 문자가 있습니다.";
}

function onGroupEditClick(e)
{
	var self = e.target;
	var group = self.parentNode.getElementsByClassName("createGroupText")[0].value;
	try
	{
		validateName(group);
		
		groupAjax.open("POST", "./group_ajax.php", true);
	 	groupAjax.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	 	groupAjax.send("id=" + encodeURIComponent(self.userID) + "&group=" + encodeURIComponent(group) + "&set=1");
	}
	catch(e)
	{
		alert(e);
	}
}

function onGroupClick(e)
{
	var self = e.target;
	if(ADMIN_FEATURE)
	{
		if(THE_ADMIN_GROUP == self.groupName && THE_USER == self.userID)
		{
			if(! confirm("귀하의 관리자 권한을 박탈합니다. 계속 진행합니까?"))
				return;
		}
	}
 	groupAjax.open("POST", "./group_ajax.php", true);
 	groupAjax.setRequestHeader("Content-type","application/x-www-form-urlencoded");
 	groupAjax.send("id=" + encodeURIComponent(self.userID) + "&group=" + encodeURIComponent(self.groupName) + "&set=" + (self.belongs? "0" : "1"));
}

function onSubmit(self)
{
	if(typeof(self) == "string")
		self = document.getElementById(self);
	
	var id = self.id.value.trim();
	try
	{
		validateName(id);

		if(! id.length)
			throw "ID를 입력해 주세요.";
		else if(id.length > <?php echo MAX_ID_LENGTH; ?>)
			throw "ID가 너무 깁니다. <?php echo MAX_ID_LENGTH; ?>자 이하로 입력하세요.";
		else if(! self.pw.value.length)
			throw "빈 PW는 허용되지 않습니다.";
		else if(self.pw.value != self.confirm.value)
			throw "두 암호가 일치하지 않습니다. 다시 입력하세요.";

		return true;
	}
	catch(e)
	{
		alert(e);
	}
	return false;
}
function onChange(self, target)
{
	if(typeof(self) == "string")
		self = document.getElementById(self);
	if(typeof(target) == "string")
		target = document.getElementById(target);

	if(self.value == target.value)
		self.style.backgroundColor = target.style.backgroundColor = "SpringGreen";
	else
		self.style.backgroundColor = target.style.backgroundColor = "plum";
}
function onQuit()
{
	if(confirm("정말 탈퇴합니까?"))
		location.replace("./?del=<?php echo THE_USER; ?>");
}
</script>
<style type=text/css>
td.cell
{
	border-collapse: collapse;
	border: 1px solid black;
	padding: 5px;
}
th.cell
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
th.longer
{
	border-collapse: collapse;
	border: 1px solid black;
	padding: 5px;
	min-width: 100px;
}
article.adminFeature
{
	display: inline-block; border: 1px violet solid; margin: 5px; padding: 10px;
}
article.userFeature
{
	display: inline-block; border: 1px green solid; margin: 5px; padding: 15px;
}
</style>
</head>
<body style="line-height: 1.4em;" onload="onLoad()">

<header>
	<h1 style="text-align: center;">htaccess Integration</h1>
	<p class=userFullLine>환영합니다, <span class=userLine style="font-weight: bold;"><?php echo (ADMIN_FEATURE? "<span style='color: violet;'>관리자 </span>" : "") . THE_USER; ?></span>님.</p>
	<p>
		<div class=htpasswdFullLine>설정할 htpasswd 파일은 <u class=htpasswdLine><?php echo THE_FILE; ?></u> 입니다.</div>
		<div class=htgroupsFullLine>설정할 htgroups 파일은 <u class=htgorupsLine><?php echo THE_GROUP_FILE; ?></u> 입니다.</div>
	</p>
	<p>
		<div>관리자 권한에 의해 표시된 부분은 <span style="color: violet;">바이올렛</span>으로 표시됩니다.</div>
		<div>일반 사용자의 기능은 <span style="color: green;">녹색</span>으로 표시됩니다.</div>
	</p>
</header>
<br/>

<section>
<h2>유저</h2>
<article class=userFeature>
<div>
<h3 style="text-align: center;">내 계정</h3>
</div>
<form id=frmMine name=mine method=post action=./ onsubmit="return onSubmit('frmMine')">
<table>
<tr>
<th>ID</th><td><?php echo THE_USER; ?></td>
</tr>
<tr>
<th>암호</th><td><input type=password name=pw style="width: 150px;" id=inputMyPw /></td>
</tr>
<tr>
<th>확인</th><td><input type=password name=confirm style="width: 150px;" onkeyup="onChange(this, 'inputMyPw')" /></td>
</tr>
<tr>
<th>소속</th>
<td><?php
try
{
	$arr = $gp->getBelongingGroups(THE_USER);
	echo "<ul>";
	foreach($arr as $v)
		echo "<li>$v</li>";
	echo "</ul>";
}
catch(Exception $e)
{
	echo "<div style='text-align: center; font-style: italic;'>(무소속)</div>";
}
?></td>
</tr>
<tr>
<td colspan=2>&nbsp;</td>
</tr>
<tr>
<td><a onclick="onQuit()" style="color: red; font-style: italic; cursor: pointer;">탈퇴</a></td>
<td style="text-align: right;">
<input type=submit value=적용 style="margin-left: auto;" />
</td>
</tr>
</table>
<input type=hidden name=id value=<?php echo THE_USER; ?> />
</form>
</article>
<?php
if(ADMIN_FEATURE)
{?>
<article class=adminFeature>
<div>
<h3 style="text-align: center;">유저 생성 / 변경</h3>
</div>
<form id=frmUser name=user method=post action=./ onsubmit="return onSubmit('frmUser')">
<table>
<tr>
<th>ID</th><td><input type=text name=id style="width: 150px;" /></td>
</tr>
<tr>
<th>암호</th><td><input type=password name=pw style="width: 150px;" id=userPw /></td>
</tr>
<tr>
<th>확인</th><td><input type=password name=confirm style="width: 150px;" onkeyup="onChange(this, 'userPw')" /></td>
</tr>
<tr>
<td colspan=2 style="text-align: center;"><input type=submit value="적용" /></td>
</tr>
</table>
</form>
</article>

<?php }
?>
</section>
<br/>

<section>
<h2 id=groupGovernorHeading>그룹</h2>
<?php 
if(ADMIN_FEATURE)
{?>
<article class=adminFeature>
<h3 style="text-align: center;">그룹 관리</h3>
<p style="text-align: center;"><input type=button value=새로고침 onclick="loadGroup(this)" id=groupRefreshBtn /></p>
<table style="border-collapse: collapse;" id=groupGovernor></table>
</article>
<?php }?>

<article class=userFeature>
<h3 style="text-align: center;">소속 그룹</h3><?php 
try
{
	$belongs = $gp->getBelongingGroups(THE_USER);
	$groupTable = array();
	?><table style="border-collapse: collapse;">
<tr style="background: yellow;">
<th class=longer style="text-align: center; font-weight: bold;">ID</th><?php
foreach($belongs as $v)
{
	foreach($gp->getGroup($v) as $other)
	{
		if(! isset($groupTable[$other]))
			$groupTable[$other] = array();
		array_push($groupTable[$other], $v);
	}
	echo "<td class=cell style='text-align: center;'>$v</td>";
}
?></tr><?php 
foreach($groupTable as $k=>$v)
{
	if($k == THE_USER)
		echo "<tr style='background: wheat;'>";
	else
		echo "<tr>";
	echo "<td class=longer>$k</td>";
	
	foreach($belongs as $b)
	{
		if(array_search($b, $v) !== false)
			echo "<td class=cell style='text-align: center;'>O</td>";
		else
			echo "<td class=cell style='text-align: center;'>&nbsp;</td>";
	}
	echo "</tr>";
}
?></table><?php
}
catch(Exception $e)
{?>

<i>(소속 그룹이 없습니다)</i>
<?php }?>
</article>
</section>
</body>
</html>
