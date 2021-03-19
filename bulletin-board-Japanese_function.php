<?php
$LogTable = "logfile6_2_1";//ログを記録するテーブル
$D_Prevent = "rand";//二重送信防止用のテーブル
$imagefile = "picture/";

//データベースへ接続
$dsn = 'mysql:dbname=********;host=********';//Data Source Name
$user = '*******';//ユーザー名
$password = '********';
$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));//PHP Data Objects

//コメント用のテーブルの作成
$sql = "CREATE TABLE IF NOT EXISTS {$LogTable} (id INT AUTO_INCREMENT PRIMARY KEY, name char(32), dat char(32), editdat char(32), comment TEXT, imagepath char(70), pass char(32));";
$state = $pdo->query($sql);//コメントid,ユーザーname,投稿した日付,最後に編集した日付,コメント内容,画像のパス,パスワード


//コメントidのリンクから、GETでidを取得(show関数)
  if(isset($_GET["getid"])&&is_numeric($_GET["getid"])){
    $editid = $_GET["getid"];
  }else{//初回実行時などは空欄
    $editid = "";
  }

/*--------------------ここから主な処理の開始--------------------*/
//フォームの表示変更
  /*$configの値に対して分岐する。$configはeditdataから変数を受け取る。
    フォーム欄の表示内容、実行ボタンの内容$submit_valueの値を決める。
    $configに想定していない値が入っていた場合は不正なアクセスがあったものとして扱う
    処理中に発生したメッセージは、全て配列$messageに入れ、フォーム欄の下で表示する。*/

    if(isset($_POST["config"])){//初回実行時、リンクのクリック時はとばす
      $config = $_POST["config"];//値の受け取り
      $message = array();//値の初期化

      if($config=="投稿"||$config=="キャンセル"){//投稿、キャンセルボタンを押した場合、フォームには何も表示しない
        //ただの投稿に設定、その他の設定は使用しない
        $submit_value = "投稿";
        $editid = "";

      }elseif($config=="編集"||$config=="削除"){//編集・削除の申請があった場合、フォームには何も表示しない
        //フォーム表示の変更
        //値の取得
        $inputid = $_POST["editid"];//編集番号
        $inputpass = $_POST["inputpass"];//入力されたパスワード
        $pass_auth = pass_auth($pdo, $LogTable, $inputpass, $inputid);//パスワード判定

        if($pass_auth["result"]){//パスワードが合致した場合
          $message[] = $pass_auth["message"];//メッセージを追加
          //値の取得
          $sql = $pdo->prepare("SELECT * FROM {$LogTable} WHERE id=:editid");
          $sql->bindParam(":editid", $inputid, PDO::PARAM_INT);
          $sql->execute();
          $result = $sql->fetch();
          //値の差し替え、投稿内容を配列に入れる。
          $display = [  "id" => $result["id"],
                        "name" => $result["name"],
                        "pass" => $result["pass"],
                        "comment" => $result["comment"],
                        "imagepath" => $result["imagepath"], ];
          $submit_value = $config;

        }else{//パスワードが合致しなかった場合
          $message[] =  $pass_auth["message"];//メッセージを追加
          $message[] =  "もう一度入力してください。<br>";
          $submit_value = "投稿";
        }

      }else{//想定していない値の場合
        $message[] =  "不正なアクセスが発生しました。(config)";
        $submit_value = "投稿";
        $editid = "";
      }
    }else $submit_value = "投稿";


/*--------------------ユーザー定義関数--------------------*/

//二重登録防止用の関数
function reload($pdo, $table){//PDOインスタンス、テーブル名
  /*乱数の値はフォーム欄のPOSTにname=reloadで入れる(hidden)
    乱数を作りテーブルに挿入し、その値の変化でリロードの有無を判別する。
    リロードの有無を論理型(reload)で、次の乱数の値を整数型(rand)で与え、連想配列にして返す。
    <input type="hidden" name="reload" value="<?=array[rand]?>">*/

  //二重登録対策のテーブルの作成
  $sql = "CREATE TABLE IF NOT EXISTS {$table} (rand INT);";
  $state = $pdo->query($sql);

  //値の取得
  $sql = $pdo->prepare("SELECT rand FROM ".$table);
  $sql -> execute();
  $results = $sql -> fetch();
  $reload = false;

  //リロードの検出
  if((isset($_POST["reload"]))&&(isset($results["rand"]))){//初回起動時は実行しない
    if($_POST["reload"]==$results["rand"]){//リロードされていない場合
      $reload = false;
    }else $reload = true;//リロードされた場合
  }

    //テーブル内をリセット
    $sql = $pdo->prepare("DELETE FROM ".$table);
    $sql -> execute();
    //新しい値を挿入
    $rand = rand();
    $sql = $pdo->prepare("INSERT INTO {$table} (rand) VALUES (:rand)");
    $sql -> bindParam(":rand", $rand, PDO::PARAM_INT);
    $sql -> execute();

    //値を配列して返す
    $array = [ "reload" => $reload, "rand" => $rand,];
    return $array;
}


//パスワードの正誤判定用関数
function pass_auth($pdo, $table, $pass, $editid){//PDOインスタンス、テーブル名、入力されたパスワード、申請されたid
  /*idとパスワード内容から正誤判定
  *引数以外のメインでの処理なし
    判定結果とメッセージを論理型と文字列の配列に入れて返す
    この関数は他の関数からのみ呼び出される*/
  $sql = $pdo->prepare("SELECT * FROM {$table} WHERE id=:editid");//idの一覧
  $sql->bindParam(":editid", $editid, PDO::PARAM_INT);
  $sql->execute();
  $data = $sql->fetch();
  if($data["id"]==$editid){//idが存在するかどうかの判別
    if($data["pass"]==$pass){//パスワードの正誤判定
      $array = [ "result" => true, "message" => "パスワード認証に成功しました。<br>",];//パスワードが合致した場合
    }else $array = [ "result" => false, "message" => "パスワードが異なります。<br>",];//異なるパスワードだった場合
  }else $array = [ "result" => false, "message" => "存在しないidです。<br>",];//存在しないidだった場合

  return $array;
}


//データレコードの変更用関数
function editdata($pdo, $table, $imagefile){//PDOインスタンス、テーブル名
  /*$_POST["submit"]の値にによって分岐する
    入力された値に対してバリデーションを行う
    空欄があった場合は処理をキャンセルし、もう一度入力させる
    *申請時のパスワードを実行時にPOST,hidden,inputpassで送信する
    編集・削除の前にもパスワードの判定を行う。パスワードは実行時にPOST,hidden,inputpassで送信し、この値が合致しなかった場合、不正な値が入ったとして処理する
    この関数に返り値はない*/

  if(isset($_POST["submit"])){//初回の実行時,リンククリック時以外は実行
    $submit = $_POST["submit"];//データレコードの変更設定
    $date = date("d/m/y H:i:s(D)");//日付の取得
    $input = false;//正しく入力したかを確認するパラメータの初期化

    //画像のパスの作成
    if(isset($_POST["imageedit"])){
      if($_POST["imageedit"]=="変更"){
        if(!empty($_FILES["image"]["name"])){//ファイルが選択されていれば処理を行う

          $imagename = uniqid(mt_rand(), true);//名前の生成
          $imagename .= '.' . substr(strrchr($_FILES['image']['name'], '.'), 1);//ファイルの拡張子を取得
          $imagepath = $imagefile.$imagename;

          move_uploaded_file($_FILES['image']['tmp_name'], $imagepath);//imagesディレクトリにファイル保存
          if(file_exists($imagepath)){
            if (exif_imagetype($imagepath)) {//画像ファイルかのチェック
                echo '画像をアップロードしました<br>';
            } else {
                echo '画像ファイルではありません、表示できません。<br>';
                $imagepath = "";
            }
          }
        }else $imagepath = "";
      }else $imagepath = $_POST["posted_image"];
    }else $imagepath = "";

    //$submitの値に対する分岐
    if($submit!="申請"){//申請時以外の時、入力した値を受け取り、分岐する

      //値のバリデーション(全角スペースを削除)
      if(trim($_POST["name"], " \n\r\t\v\0　")==""){//名前の取得(空欄の時名無しに変更)
          $name = "名無しさん";
      }else $name = $_POST["name"];

      if(trim($_POST["pass"], " \n\r\t\v\0　")==""){//パスワード(空欄の時キャンセル)
        echo "パスワードが入力されていません。<br>";
        $input = true;
      }else $pass = $_POST["pass"];//前後のスペース等は消去

      if(trim($_POST["comment"], " \n\r\t\v\0　")==""){//コメント内容(空欄の時キャンセル)
        if($imagepath==""){//画像の投稿がない場合
          echo "コメントが入力されていません。<br>";
          $input = true;
        }else $comment = rtrim($_POST["comment"], " \n\r\t\v\0　");//画像の登録があった場合は空欄を許可する

      }else $comment = rtrim($_POST["comment"], " \n\r\t\v\0　");

      if($input){//正しく入力されていなかった場合、最初から入力させる。
        echo "もう一度正しく入力してください。<br>";//エラーメッセージを表示しそれ以外の処理は行わない

      }else{//正しく入力されていた場合

        //データレコードの変更
        if($submit=="投稿"){//投稿ボタンを押したとき、ただの書き込み
          $sql = $pdo -> prepare("INSERT INTO {$table} (name, dat, comment, imagepath, pass) VALUES (:name, :dat, :comment, :imagepath, :pass)");
          $sql->bindParam(':name', $name, PDO::PARAM_STR);
          $sql->bindParam(':dat', $date, PDO::PARAM_STR);
          $sql->bindParam(':comment', $comment, PDO::PARAM_STR);
          $sql->bindParam(':imagepath', $imagepath, PDO::PARAM_STR);
          $sql->bindParam(':pass', $pass, PDO::PARAM_STR);
          $sql->execute();
          echo "投稿しました。<br>";

        }elseif($submit=="編集"){//編集ボタンを押したとき、内容の更新
          $editid = $_POST["editid"];//編集番号
          $inputpass = $_POST["inputpass"];//入力されたパスワード
          $pass_auth = pass_auth($pdo, $table, $inputpass, $editid);//パスワード判定
          if($pass_auth["result"]){//結果で分岐
            $sql = $pdo->prepare("UPDATE {$table} SET name=:name, editdat=:editdat, comment=:comment, imagepath=:imagepath, pass=:pass WHERE id=:editid;");
            $sql->bindParam(':name', $name, PDO::PARAM_STR);
            $sql->bindParam(':editdat', $date, PDO::PARAM_STR);
            $sql->bindParam(':comment', $comment, PDO::PARAM_STR);
            $sql->bindParam(':imagepath', $imagepath, PDO::PARAM_STR);
            $sql->bindParam(':pass', $pass, PDO::PARAM_STR);
            $sql->bindParam(':editid', $editid, PDO::PARAM_INT);
            $sql->execute();
            echo "{$editid}番のコメントを編集しました。<br>";
          }else echo "不正な値が入力されました。(pass)<br>";

        }elseif($submit=="削除"){//削除ボタンを押したとき、内容の削除
          $editid = $_POST["editid"];//編集番号
          $inputpass = $_POST["inputpass"];//入力されたパスワード
          $pass_auth = pass_auth($pdo, $table, $inputpass, $editid);//パスワード判定
          if($pass_auth["result"]){//結果で分岐
            $editid = $_POST["editid"];//編集番号
            $sql = $pdo->prepare("DELETE FROM {$table} where id=:editid;");
            $sql->bindParam(':editid', $editid, PDO::PARAM_INT);
            $sql->execute();
            echo "{$editid}番のコメントを削除しました。<br>";
          }else echo "不正な値が入力されました。(pass)<br>";

        }else echo "不正なアクセスが発生しました。(submit)<br>";//&submitの値が想定外の物であった場合
      }//データレコードの変更終了
    }//編集・削除の申請時、何もしない,$submitの分岐処理終了
  }//初回実行時等は何もしない
}


//表示用関数
function show($pdo, $table, $filename, $imagefile){//PDOインスタンス、テーブル名、実行しているファイル名、画像フォルダの指定
  /*リンククリックの値はGETのgetidに入れる
    idのみリンクで表示、名前とコメントはエスケープ処理を行う
    更にコメントは改行を入れて表示させる
    *変数以外でメイン側から行う処理なし
    返り値なし
    表示されない画像を削除するための関数remove_image()を呼び出す*/

  //値の取得
  $sql = $pdo->prepare("SELECT * FROM ".$table);
  $sql->bindParam(":table", $table, PDO::PARAM_STR);
  $sql->execute();
  $result = $sql->fetchall();
  $logdata = array_reverse($result);//配列を逆順にし、表示の際最新のコメントがフォームの直後に来るようにする
  $image_array = array();//表示した画像のパスの配列

  //値の表示
  echo "<hr><hr>";
  foreach($logdata as $log){//コメント番号をハイパーリンクで表示し、クリックでコメント番号を取得できるようにする?>
    <a href="<?=$filename?>?getid=<?=$log["id"]?>"><?=$log["id"]?></a>
    <?php
    echo ":".htmlspecialchars($log["name"])."<br>";//idと名前を一行で表示

    if(isset($log["editdat"])){//編集履歴があるときのみ編集時間を表示
        echo "Post:".$log["dat"]."=>Edit:".$log["editdat"]."<br>";
      }else{
        echo "Post:".$log["dat"]."<br>";//日付は一行で表示する
      }

    echo nl2br(htmlspecialchars($log["comment"]))."<br>";//コメントは改行を入れて表示

    if(file_exists($log["imagepath"])):
      if(exif_imagetype($log["imagepath"]))://画像ファイルかのチェック
        $image_array[] = $log["imagepath"];?>
        <img src="<?=$log["imagepath"]?>" height="400">
<?php endif;
    endif;

    echo "<hr>";//コメントごとにラインを挿入
  }

  remove_image($image_array, $imagefile);
}


//削除された画像ファイルの削除
/*投稿された画像ファイルのうち、表示されるもの以外を削除する。*/
function remove_image($image_array, $imagefile){
  $diff = array_diff(glob($imagefile."*"), $image_array);//画像フォルダのうち、表示されていないファイルを割り出す。

  foreach($diff as $di){
    unlink($di);//表示されていないものを削除する。
  }
}
?>
