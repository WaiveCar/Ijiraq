<?php
include('../AdDaemon/lib/lib.php');
include('../AdDaemon/lib/accounting.php');


if($_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( authenticate_user($_POST) ) {
		$redir_url = $_SESSION['after_login_url'] ?? '/';
    unset($_SESSION['after_login_url']);
    header('Location: ' . $redir_url);
    die();
	} else {
    $flash_error = 'Username / Password: Incorrect';
  }
}

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/css/sb-admin-2.min.css" rel="stylesheet">
		<link rel=stylesheet href=https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css>
    <link href="/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <title>Screen Admin - Login</title>
  </head>
  <style>
  </style>
  <body id="page-top">

		<section class="content">
			<header>
				
			<h1>Log In</h1>

			</header>
			
			<div class="text-danger"><?=$flash_error?></div>
			<form method="post">
				<label for="email">Email</label>
				<input name="email" id="email" required>
				<label for="password">Password</label>
				<input type="password" name="password" id="password" required>
				<input type="submit" value="Log In">
			</form>

		</section>

  <script
    src="https://code.jquery.com/jquery-3.4.1.min.js"
    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
    crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="/js/jquery.easing.min.js"></script>
    <script src="/js/sb-admin-2.min.js"></script>
    <script src="/js/jquery.dataTables.min.js"></script>
    <script src="/js/dataTables.bootstrap4.min.js"></script>
    <script src="/Admin/script.js?1"></script>
  </body>
</html>

