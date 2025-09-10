<?php


// обработчик чата
class Handler {
  
  function __construct() {
    try{

      mb_internal_encoding('UTF-8');
      // читаем ini файл
      $ini = parse_ini_file(ROOT."/config/db_conf.ini");
      $this->host = $ini['host'];
      $this->dbname = $ini['dbname'];
      $this->user = $ini['user'];
      $this->password = $ini['password'];
      $this->chatTable = $ini['chatTable'];
      
      $this->db = new \PDO('mysql: host='.$this->host.'; dbname='. $this->dbname, $this->user, $this->password);
      
      $this->db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
      $this->db->exec("SET NAMES 'utf8'"); 
      $this->db->exec("SET CHARACTER SET 'utf8'");
      $this->db->exec("SET SESSION collation_connection = 'utf8_general_ci'");
      
      
      if(empty($_COOKIE['login'])){
        header ("Location: /login.php");
      }else{
        $this->login = $_COOKIE['login'];
        $this->id = $this->selectUserId($this->login);
        $_SESSION['login'] = $this->login;
        if(!$this->id){
          header ("Location: /login.php");
        }
        $this->setOnline();
      }
      
   }catch(\PDOException $err) { 
      echo 'Ошибка при соединении с БД ' . $err->getMessage(). '<br> 
            в файле '.$err->getFile().", строка ".$err->getLine() . "<br><br>Стэк вызовов: " . preg_replace('/#\d+/', '<br>$0', $err->getTraceAsString()); 
      exit;  
   }
  }
  
  
  
  
  
  function setOnline($online=1){
    $this->query("update `users` set `online`=? where `login`=?", [$online, $this->login], 1);
  }
  
  
  
  
  
  function selectUserId($login){
    $sql = "select `id` from `users` where `login`=?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(array($login));
    $data = $stmt->fetchAll();
    if($data){
      return $data[0]['id'];
    }
    return 0;
  }
  
  
  
  
  
  
  function listUsers(){
    $result = '';
    $stmt = $this->db->prepare("SELECT `id`, `login`, `online` FROM `users` where `id`!=?");
    $stmt->execute(array($this->id));
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
      if($row['online']){
        $online = "<img src='/template/images/online.png'/>";
      }else $online = "";
      $result .= "<li data-id='".$row['id']."'>".$row['login'].$online."</li>";
    }
    return $result;
  }
  
  
  
  
  
  
  function listPrev(){
    $result = '';
    $stmt = $this->db->prepare("SELECT * FROM `$this->chatTable` where `destination`='0' or `destination` like ? or `name`=(select `login` from `users` where `id`=?) order by `id` desc limit 10");
    $stmt->execute(array('% '.$this->id.' %', $this->id));
    $arrayPosts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $len = count($arrayPosts);
    for ($i = $len - 1; $i >= 0; $i--) {
      $result .= "<li class='msg' id='".$arrayPosts[$i]['id']."'>"
        ."<a class='nick'>".$arrayPosts[$i]['name']."</a><br>"
        .$arrayPosts[$i]['text']
        ."<span class='time'>".$arrayPosts[$i]['time']."</span>"
        ."</li>";
    }
    return $result;
  }
  
  
  
  
  
  
  
  function getPrev(){
    $id = intval($_POST['id']);
    if($id < 2) return;
    $id--;
    $firstId = $id - 10;
    $result = '';
    $stmt = $this->db->prepare("SELECT * FROM `$this->chatTable` where `id` between ? and ? and (`destination`='0' or `destination` like ? or `name`=(select `login` from `users` where `id`=?)) order by `id`");
    $stmt->execute(array($firstId, $id, '% '.$this->id.' %', $this->id));
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
      $result .= "<li class='msg' id='".$row['id']."'>"
        ."<a class='nick'>".$row['name']."</a><br>"
        .$row['text']
        ."<span class='time'>".$row['time']."</span>"
        ."</li>";
    }
    echo $result;
  }
  
  
  
  
  
  
  
  function uploadFile(){
    if(is_uploaded_file($_FILES['file']['tmp_name'])){
      $tmp = $_FILES['file']['tmp_name'];
      $size = $_FILES['file']['size'];
      $type = $this->get_mimeType($tmp);
      //echo $type;
      $supportMimeTypes = array(
        "image/jpg",
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/x-ms-bmp",
        "image/vnd.djvu",
        "image/vnd.adobe.photoshop",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "text/plain",
        "text/rtf",
        "application/pdf",
        "application/msword",
        "application/x-rar",
        //"application/octet-stream",
        "application/zip",
        "application/xml",
        "audio/mpeg"
      );
      
      if(!in_array($type, $supportMimeTypes) || $size > 10 * 1024 * 1024){
        return("<div style='height:7px;'></div><div>Файл слишком большой или имеет неподдерживаемый формат</div>");
      }
      
      $dir = './template/attachments';
      $namefile = $_FILES['file']['name'];
      $namefile = mb_convert_encoding($namefile, "UTF-8");
      $namefile = preg_replace('/[^\wа-яё.]/iu', '_', $namefile);
      $namefile = $this->translit($namefile);
      $path = $dir.'/'.$namefile;
      
      if(move_uploaded_file($_FILES['file']['tmp_name'], $path)){
        if(preg_match("/image\/(jpg|jpeg|png|bmp|gif)/",$type)){
          $path = "<div style='height:7px;'></div><img src='$path' class='attachImg' alt=''/>";
        }elseif(preg_match("/image\/gif/",$type)){
          $path = "<div style='height:7px;'></div><img src='$path' class='gif' alt=''/>";
        }else{
         switch($type){
          case 'application/x-rar': $img = "<img class='filesimg' src='/template/images/filesimg/rar.png' alt=''> ";
          break;
          case 'application/zip': $img = "<img class='filesimg' src='/template/images/filesimg/rar.png' alt=''> ";
          break;
          case 'image/vnd.adobe.photoshop': $img = "<img class='filesimg' src='/template/images/filesimg/psd.png' alt=''> ";
          break;
          case 'application/pdf': $img = "<img class='filesimg' src='/template/images/filesimg/pdf.png' alt=''> ";
          break;
          case 'image/vnd.djvu': $img = "<img class='filesimg' src='/template/images/filesimg/djvu.png' alt=''> ";
          break;
          case 'text/plain': $img = "<img class='filesimg' src='/template/images/filesimg/txt.png' alt=''> ";
          break;
          case 'application/xml': $img = "<img class='filesimg' src='/template/images/filesimg/xml.png' alt=''> ";
          break;
          case 'audio/mpeg': $img = '<audio controls preload="metadata">
		<source src="'.$path.'" type="audio/mpeg">
	</audio>';
          break;
          default: $img = "<img class='filesimg' src='/template/images/filesimg/word.png' alt=''>";
          break;
        }
        
        $path = $type == "audio/mpeg" ? $img : "<div style='height:7px;'></div><a class='a' href='$path' download>$img $namefile</a>";
      }
        return $path;
      }else{
        return("<div style='height:7px;'></div><div>Файл слишком большой или имеет неподдерживаемый формат</div>");
      }
    }else{
      return("<div style='height:7px;'></div><div>Файл слишком большой или имеет неподдерживаемый формат</div>");
    }
  }
  
  
  
  
  
  
  function translit($str) {
    $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
    return str_replace($rus, $lat, $str);
  }

    
    
    
    
    
  function get_mimeType($filename){
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $mime;
  }
  
  
  
  
  
  
  function setToDB(){
    $login = $_SESSION['login'];
    if($login != $_POST['name']){
      echo "Да шо ты мутишь";
      exit;
    }
    $file = "";
    if(is_uploaded_file($_FILES['file']['tmp_name'])){
      $file = $this->uploadFile();
    }
    $text = $this->escapeStr($_POST['message_text'], 3333).$file;
    $text = $this->setCode($text);
    $da = $this->query("insert into `chat` (`name`, `text`, `time`) values (?,?,?)", [$login, $text, time()], 1);
    echo $text;
  }
  
  
  
  
  
  
  function setCode($text)
  {
    
    $text = preg_replace('/\[NICK\]([\S\s]*?)\[\\\\NICK\]/usi', '<a class="nick2">$1</a>', $text);
    $text = preg_replace('/\[QUOTE\]([\S\s]*?)\[\\\\QUOTE\]/usi', "&laquo;$1&raquo;", $text);
    $text = preg_replace('/\[B\]([\S\s]*?)\[\\\\B\]/usi', "<b>$1</b>", $text);
    $text = preg_replace('/\[I\]([\S\s]*?)\[\\\\I\]/usi', "<i>$1</i>", $text);
    $text = preg_replace('/\[U\]([\S\s]*?)\[\\\\U\]/usi', '<span style="text-decoration: underline;">$1</span>', $text);
    $text = preg_replace('/\[S\]([\S\s]*?)\[\\\\S\]/usi',
      '<span style="text-decoration: line-through;">$1</span>', $text);
    $text = preg_replace('/:\)/usi', " <img src='/template/images/smiles/1.gif' alt=''> ", $text);
    $text = preg_replace('/;\)/usi', " <img src='/template/images/smiles/2.gif' alt=''> ", $text);
    $text = preg_replace('/:\(/usi', " <img src='/template/images/smiles/3.gif' alt=''> ", $text);
    $text = preg_replace('/:D/usi', " <img src='/template/images/smiles/4.gif' alt=''> ", $text);
    
    $text = preg_replace_callback(
      '/\b(https?:\/\/[\wа-яё\/.\?\+%&=#:\-;]+)/uis',
      function ($matches)
      {
        if(preg_match('/https:\/(\/www\.)?youtube\.com\/watch/i', $matches[1])){
          $frame = $matches[1]; //\?v=([a-z0-9\-_]+?)
          $frame = preg_replace("/watch\?v=/", "embed/", $frame);
          $frame = preg_replace("/&.+$/", "", $frame);
          return "<br><iframe width='560' height='315' src='$frame' frameborder='0' allowfullscreen></iframe><br>"; 
        }else{
          $href = $matches[1];
          return "<a class='a' target='_blank' href='$href'>$href</a>";
        }   
      },
      $text
    );
    
    $text = preg_replace_callback(
      '#\[CODE\](.+?)\[\\\\CODE\]#uis',
      function ($matches)
      {
        return "<div class='blackCode'>".$matches[1]."</div>";
      },
      $text
    );
    return $text;
  }
  
  
  
  
  
    public function query($query, array $values = array(
    ), $param = false)
  {
    try
    {
      $stmt       = $this->db->prepare($query);
      $values_len = count($values);

      for($i = 0; $i < $values_len; $i++)
      {
        $value = trim($values[$i]);
        if(preg_match('/^\d+$/', $value))
        {
          $stmt->bindValue($i + 1, $value, \PDO::PARAM_INT);
        }
        else
        {
          $stmt->bindValue($i + 1, $value, \PDO::PARAM_STR);
        }
      }
      $stmt->execute($values);
      if(!$param)
      {
        return $stmt->fetchAll();
      }
      else
      {
        return $stmt->rowCount();
      }
    } catch(\PDOException $err)
    {
      echo 'Ошибка при выборке из БД ' . $err->getMessage(). '<br>
      в файле '.$err->getFile().", строка ".$err->getLine() . "<br><br>Стэк вызовов: " . preg_replace('/#\d+/', '<br>$0', $err->getTraceAsString());
      exit;
    }

  }




  public function escapeStr($str, $size = 0)
  {
    $str = trim($str);
    //$str = preg_replace('/[`\'\"\(\)\[\]]/', '', $str);
    $str = htmlentities($str, ENT_QUOTES, "UTF-8");
    if($size)$str = mb_substr($str, 0, $size, "UTF-8");
    return $str;
  }
  
}