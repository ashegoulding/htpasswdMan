<?php
require_once 'lib/htpasswd.class.php';
require_once 'lib/htgroups.class.php';
require_once 'LocalSettings.php';
require_once 'lib/lib.php';

if(! ADMIN_FEATURE)
{
	http_response_code(400);
	exit();
}
header("Content-type: application/xml");

if(isset($_POST['id']) && isset($_POST['group']) && isset($_POST['set']))
{
	try
	{
		if(intval($_POST['set']) || strtolower(trim($_POST['set'])) == "true")
			$gp->add(trim($_POST['id']), trim($_POST['group']));
		else
			$gp->remove(trim($_POST['id']), trim($_POST['group']));
		$gp->commitOnDelete = true;
	}
	catch(Exception $e){}
}

$doc = new DOMDocument("1.0", "utf-8");
$root = $doc->createElement("htpasswdMan");
$doc->appendChild($root);
$secondRoot = $doc->createElement("groups");
$root->appendChild($secondRoot);

foreach($gp->getGroups() as $g)
{
	$group = $doc->createElement("group", $g);
	$root->appendChild($group);
}
foreach($hp->getAllUsers() as $u)
{
	$user = $doc->createElement("user");
	$user->setAttribute("id", $u);
	$secondRoot->appendChild($user);
	try
	{
		foreach($gp->getBelongingGroups($u) as $g)
		{
			$belongs = $doc->createElement("belongs", $g);
			$user->appendChild($belongs);
		}		
	}
	catch(Exception $e){}
}

echo $doc->saveXML();
?>
