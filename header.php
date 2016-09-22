<!doctype html>
<html><head>
<meta charset="utf-8">
<link rel=stylesheet href=css/bootstrap.min.css>
<title><?= $docTitle ?> LDAP Account Management</title>
</head><body>
<nav class="navbar navbar-default navbar-static-top">
<div class="container"><ul class="nav navbar-nav">
<li <?=CONTROLLER=="userinfo.php"?'class=active':''?>><a href=userinfo.php>My Account</a></li>
<li <?=CONTROLLER=="change_passwd.php"?'class=active':''?>><a href=change_passwd.php>Change Password</a></li>
<li <?=CONTROLLER=="listusers.php"?'class=active':''?>><a href=listusers.php>User List</a></li>
<li <?=CONTROLLER=="birthdays.php"?'class=active':''?>><a href=birthdays.php>Birthdays</a></li>
</ul><ul class="nav navbar-nav pull-right">
<li><a href=/>GLaDOS Home</a></li>
</ul></div></nav>

<div class=container>

