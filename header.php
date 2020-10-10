<!doctype html>
<html><head>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<?php if ($baseHref): ?> <base href="<?= E($baseHref) ?>"> <?php endif; ?>
<link rel=stylesheet href=css/bootstrap.min.css>
<title><?= $docTitle ?> LDAP Account Management</title>
</head><body>
<nav class="navbar navbar-default navbar-static-top">
<div class="container"><ul class="nav navbar-nav">
<li <?=CONTROLLER=="userinfo.php"?'class=active':''?>><a href=userinfo.php>My Account</a></li>
<li <?=CONTROLLER=="manage_pubkeys.php"?'class=active':''?>><a href=manage_pubkeys.php>SSH Public Keys</a></li>
<li <?=CONTROLLER=="change_passwd.php"?'class=active':''?>><a href=change_passwd.php>Change Password</a></li>
<li <?=CONTROLLER=="listusers.php"?'class=active':''?>><a href=listusers.php>User List</a></li>
<li <?=CONTROLLER=="birthdays.php"?'class=active':''?>><a href=birthdays.php>Birthdays</a></li>
<li <?=CONTROLLER=="ldapsync.php"?'class=active':''?>><a href=ldapsync.php>ldapsync</a></li>
</ul><ul class="nav navbar-nav pull-right">
<li><a href=/>GLaDOS Home</a></li>
</ul></div></nav>

<?php if($globalWarning):?>
<div class="alert alert-warning" style="margin:0;"><div class="container"><?=$globalWarning?></div></div>
<?php endif;?>
<?php if($isAdmin && $editDN && $editDN != $boundUserDN):?>
<div class="alert alert-info infobar" style="margin:-20px 0 0;border-radius:0;"><div class="container" style="margin-top:0">Editing user <b><?=$editDN?></b></div></div>
<?php endif;?>
<div class=container>

