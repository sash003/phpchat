<?php

ob_start();

session_start();

header ("Content-Type:text/html; charset=UTF-8");

// Общие настройки
ini_set('display_errors',1);
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

define("ROOT", __DIR__);

require 'models/ChatHandler.php';

$handler = new Handler();

if(!empty($_POST['func'])){
  $func = $_POST['func'];
  $handler->$func();
  exit;
}

?>


<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Хенаро">
  <meta name="robots" content="noindex,nofollow" />

  <title>Чат</title>
  
  <link rel="shortcut icon" href="/template/images/favicon.png" type="image/x-icon" />

  <link href="/template/css/style.css" type="text/css" rel="stylesheet">
  <link href="/template/css/adaptive.css" type="text/css" rel="stylesheet">
  <script type="text/javascript">
    
  </script>
  <script src="/template/js/jquery-1.11.1.min.js"></script>
  <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.2/socket.io.js"></script>-->
  <script type="text/javascript">
    var id="<?php echo $handler->id; ?>";
    var socket = new WebSocket("ws://sockets:3333");
    //var socket = new WebSocket("ws://test.webkey.net.ua/websocket");
  </script>
  <script src="/template/js/prefixfree.js"></script>
  <script src="/template/js/editor.js"></script>
  <script src="/template/js/detect.js"></script>
  <script src="/template/js/chat.js"></script>
</head>

<body>
<h1 style="left: 100px; position: relative; display: inline-block;" id="status"></h1>
  <div id="chat">
    <div id="messWrap">
      <img src="/template/images/buttonTop.png" id="top" alt="" title="Предыдущие 10"/>
      <ul id="messages" type="square">
        <?php
          echo $handler->listPrev();
        ?>
      </ul>
    </div>

    <div id="form">
      <!--<div id="keydown">
        <img src="/template/images/keydown.png" alt="" /><span id="nicks"></span><span id="do"></span>
      </div>-->
      <form action="/" enctype="multipart/form-data">
        <span id="nick" name="nick"><?php echo $handler->login; ?></span>
        
        <div id='code'>
          <span title='Жирный' class='code' data-rel='B'>B</span>
          <span title='Наклонный' class='code' data-rel='I'><i>I</i></span>
          <span title='Подчёркнутый' class='code' style='text-decoration:underline' data-rel='U'>U</span>
          <span title='Перечёркнутый' class='code' style='text-decoration: line-through;' data-rel='S'>S</span>
          <span style='font-size: 33px;' title='Цитата' class='code' data-rel='QUOTE'>&Prime;</span>
          <span style='font-size: 22px; display: inline-block; padding: 3px; line-height: 60%;' title='Код' class='code' data-rel='CODE'>CODE</span>
          <span style="position: relative; height: 28px;width: 28px; overflow: visible;">
            <input type="file" name="file" id="file" />
            <img id="fileImg" src="/template/images/attach2.png" alt=""/>
          </span>
        </div>
        <img id="Annulment" src="/template/images/Annulment.png" alt="" />
        <textarea name="message_text" id="message_text"></textarea>
        <input id="message_btn" type="image" name="submit" src="/template/images/button.png" />
      </form>
    </div>
  </div>
  <audio style="opacity: 0; display: none;" controls="controls" id="audio">
    <source src="/template/audio/call_init.mp3" />
    <source src="/template/audio/call_init.wav" />
  </audio>
  
  <!-- Для открытия картинок --> 
  <div id="wrapImg">
    <img src="" alt="" style="transform: scale(1);"/>
  </div>
  <div id="users">
  <span>Users</span>
    <ul>
      <?php
        echo $handler->listUsers();
      ?>
    </ul>
  </div>
  
  
  
</body>

</html>

<?php

ob_end_flush();

?>