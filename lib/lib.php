<?php
if(! isset($_SERVER['PHP_AUTH_USER']))
	dieWith("Authentication not enabled. No way to service.");
else if(empty(trim($_SERVER['PHP_AUTH_USER'])))
	dieWith("Authentication not enabled. No way to service.");

define("THE_USER", $_SERVER['PHP_AUTH_USER']);
header("Cache-Control: no-cache, no-store, max-age=0");

$hp = new htpasswd(THE_FILE);
$gp = new Ashe\Groups(THE_GROUP_FILE);

try
{
	define("ADMIN_FEATURE", $gp->isInGroup(THE_USER, THE_ADMIN_GROUP));
}
catch(Exception $e)
{
	define("ADMIN_FEATURE", false);
}

function dieWith($reason)
{
	http_response_code(400);
	header("Content-type: text/html; charset=utf-8");
	?>
<script type="application/javascript">alert("<?php echo $reason; ?>");location.replace('./');</script>
	<?php
	exit();
}

function redir($to)
{?>
<script type=application/javascript>location.replace("<?php echo $to; ?>");</script>
<?php
	exit();
}

?>
