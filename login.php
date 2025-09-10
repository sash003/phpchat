<?php

require_once('models/ChatHandler.php');

 if(!empty($_POST['login'])){
   setcookie('login', $_POST['login'], time()+11*30*24*3600, '/');
   header("Location: /");
 }
?>

<form action="" method="POST">
    Логин: <input type="text" name="login" value="Lazy_Den" />
</form>