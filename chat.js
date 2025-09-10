"use strict";

// подключаем необходимые модули
var express = require("express"),
  tasks = require("./models/tasks"), // для работы с бд
  app = express(),
  http = require("http"),
  server = http.createServer(app),
  WebSocketServer = new require('ws'),
  // подключенные клиенты
  clients = {}, users = {},
  webSocketServer = new WebSocketServer.Server({
    port: 3333
  });
  
  global.identity = {};
  

//подписываемся на событие соединения нового клиента
webSocketServer.on("connection", function(client) {
  
  var id = Math.random();
  clients[id] = client;
  client.send(JSON.stringify({id: id})); // придумал так, при открытии соединения клиенту шлём айдишник и в ответ сразу же получаем его id в бд (см. index.php script, (var login="<?php echo $handler->id; ?>";)), враг пройти не должен если что, хотя кому из прогеров этот чат впал

  //подписываемся на событие message от клиента
  client.on("message", function(message){
    
    try{
      
      message = JSON.parse(message);
    
      // здесь ловим айдишник юзера в бд
      if(message.myId){
        var myId = message.myId; // это логин юзера в базе
        global.identity[id] = myId; // для того чтобы потом его удалить при закрытии соединения
        users[id] = {};
        users[id][myId] = clients[id]; // и добавим в массив соединений "настоящих", по айди в базе
        delete clients[id];
        console.log("новое соединение с " + myId);
        
        tasks.online([1, myId]);
        
        for (var key in users) {
          for(var key2 in users[key]){
            // ты онлайн
            users[key][key2].send(JSON.stringify({online: myId}));
          }
        }
        
      }else{
        // ну тут всё понятно, обезвреживаем и лепим что хотим из присланного текста
        var name = message.name,
          text = setText(safe(message.message)),
          time = parseInt(message.time),
          file = fileFormat(message.file),
          destination = message.destination; // объект приходит, в котором перечислены айдишники кому хотим отправить  

        text += file; // добавлю ссылку на файл к тексту
        // формирую ответ сервера, что увидят люди на экране
        message.name = name;
        message.message = text;
        message.time = time;
        delete message.file;
        
        // если всем прислано, т.е. если объект пустой
        if(Object.keys(destination).length == 0){
          for (var key in users) {
            for(var key2 in users[key]){
              // отсылаю сообщение
              users[key][key2].send(JSON.stringify(message));
            }
          }
          // и ложу его в бд
          tasks.add({ name: name, text: text, time: time }, function(err, message){});
               
        }else{
          // В поле destination будет лежать или 0 по умолчанию, или строка вида " 1 2 3 ", потом пых выберет по like '% '.$this->id.' %'
          var toWho = ' ';
          for(var key in destination){
            toWho += destination[key] + ' ';
            for (var key2 in users) {
              for(var key3 in users[key2]){
                if(key3 == destination[key]){
                  users[key2][key3].send(JSON.stringify(message));
                }
              }
            }
          }
          // и не забудем себе отослать на память о подарившем
          for (var key2 in users) {
            for(var key3 in users[key2]){
              if(global.identity[id] == key3){
                users[key2][key3].send(JSON.stringify(message));
              }
            }
          }
          // и базу записать
          tasks.add({ name: name, text: text, time: time, destination: toWho }, function(err, message){});
        }
      
      }
    } catch (e) {
      console.log('Ошибка ' + e.name + ":" + e.message  + "\n" + e.stack)
    }

    
  });
  
  client.on('close', function() {
    try{
      console.log('соединение закрыто с ' + global.identity[id]);
      delete users[id][global.identity[id]]; // вот тут массивчик пригодился
      var left = 0;
      for (var key in users) {
        for(var key2 in users[key]){
          // ты не онлайн или просто на другой вкладке
          if(key2 == global.identity[id]){
            left++;
          }
        }
      }
      if(!left){
        for (var key in users) {
          for(var key2 in users[key]){
            // ты не онлайн
            users[key][key2].send(JSON.stringify({left: global.identity[id]}));
          }
        }
        tasks.online([0, global.identity[id]]);
      }
    } catch (e) {
      console.log('Ошибка ' + e.name + ":" + e.message  + "\n" + e.stack)
    } 
  });
  // дальше можешь не смотреть, там ничего интересного, обычный парсинг строк
});


/*Функции*/
function fileFormat(file){
  var res = String(file);
  res = safe(res);
  res = res.replace(/[^\wа-яё\-\.\/]/gi, '');
  if(!res){
    file = '';
  }else if(res == '2'){
    file = "<div style='height:7px;'></div><div>Файл слишком большой или имеет неподдерживаемый формат</div>";
  }else{
    if (/\.(jpg|jpeg|png|bmp)$/i.test(res)) {
      file = "<div style='height:7px;'></div><img src='" + res + "' class='attachImg' alt=''/>";
    }else if(/\.gif$/i.test(res)){
      file = "<div style='height:7px;'></div><img src='" + res + "' class='gif' alt=''/>";
    }else{
      var filename = res.replace("./template/attachments/", ""),
      parts = filename.split('.'),
      ext = parts.pop(), img;
      switch(ext){
        case 'rar': img = "<img class='filesimg' src='/template/images/filesimg/rar.png' alt=''> ";
          break;
        case 'zip': img = "<img class='filesimg' src='/template/images/filesimg/rar.png' alt=''> ";
          break;
        case 'psd': img = "<img class='filesimg' src='/template/images/filesimg/psd.png' alt=''> ";
          break;
        case 'pdf': img = "<img class='filesimg' src='/template/images/filesimg/pdf.png' alt=''> ";
          break;
        case 'djvu': img = "<img class='filesimg' src='/template/images/filesimg/djvu.png' alt=''> "
          break;
        case 'txt': img = "<img class='filesimg' src='/template/images/filesimg/txt.png' alt=''> "
          break;
        case 'fb2': img = "<img class='filesimg' src='/template/images/filesimg/fb2.png' alt=''> "
          break;
        case 'xml': img = "<img class='filesimg' src='/template/images/filesimg/xml.png' alt=''> "
          break;
        case 'mp3': img = '<div class="mp3"><object type="application/x-shockwave-flash" data="http://flv-mp3.com/i/pic/ump3player_500x70.swf" height="60" width="400"><param name="wmode" value="transparent" /><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><param name="movie" value="http://flv-mp3.com/i/pic/ump3player_500x70.swf" /><param name="FlashVars" value="way='+res+'&amp;swf=http://flv-mp3.com/i/pic/ump3player_500x70.swf&amp;w=400&amp;h=60&amp;time_seconds=0&amp;autoplay=0&amp;q=&amp;skin=blue&amp;volume=70&amp;comment=" /></object></div>';
          break;
        default: img = "<img class='filesimg' src='/template/images/filesimg/word.png' alt=''> "
          break;
      }
      file = ext == "mp3" ? img : "<div style='height:7px;'></div><a class='a' href='" + res + "' download>"+img+" "+filename+"</a>";
    }
  }
  return file;
}





function setText(str){
  str = String(str);
  str = str
    .replace(/\b(https?:\/\/[\wа-яё\/.\?\+%&=#:\-;]+)/gi, function(
      match,
      value
    ) {
      if (/https:\/(\/www\.)?youtube\.com\/watch/i.test(value)) {
        var frame = value.replace("watch?v=", "embed/");
        return (
          "<br><iframe width='560' height='315' src='" +
          frame +
          "' frameborder='0' allowfullscreen></iframe><br>"
        );
      } else {
        return (
          "<a class='a' target='_blank' href='" +
          value +
          "'>" +
          decodeURIComponent(value) +
          "</a>"
        );
      }
    })
    .replace(/\[NICK\]([\S\s]*?)\[\\NICK\]/gi, '<a class="nick2">$1</a>')
    .replace(/\[B\]([\S\s]*?)\[\\B\]/gi, "<b>$1</b>")
    .replace(
      /\[I\]([\S\s]*?)\[\\I\]/gi,
      '<span style="font-style: italic;">$1</span>'
    )
    .replace(
      /\[U\]([\S\s]*?)\[\\U\]/gi,
      '<span style="text-decoration: underline;">$1</span>'
    )
    .replace(
      /\[S\]([\S\s]*?)\[\\S\]/gi,
      '<span style="text-decoration: line-through;">$1</span>'
    )
    .replace(/\[QUOTE\]([\S\s]*?)\[\\QUOTE\]/gi, "&laquo;$1&raquo;")
    .replace(/:\)/g, "<img src='/template/images/smiles/1.gif' alt=''>")
    .replace(/;\)/g, "<img src='/template/images/smiles/2.gif' alt=''>")
    .replace(/:\(/g, "<img src='/template/images/smiles/3.gif' alt=''>")
    .replace(/:D/g, "<img src='/template/images/smiles/4.gif' alt=''>");
    
  return str;
}

function escapefile(filename) {
  var name = String(filename);
  return name.replace(/[^\w\-а-яё\. ]/gi, "");
}

function safe(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function getRightTime(ms) {
  var date = new Date(),
    difference = date.getTimezoneOffset() / 60000,
    msn = ms + difference,
    date = new Date(msn),
    day = String(date.getDate()),
    month = String(date.getMonth() + 1);
  if (day.length < 2) {
    day = "0" + day;
  }
  if (month.length < 2) {
    month = "0" + month;
  }
  date =
    day +
    "." +
    month +
    "." +
    date.getFullYear() +
    " " +
    date.getHours() +
    ":" +
    date.getMinutes();
  return date;
}
