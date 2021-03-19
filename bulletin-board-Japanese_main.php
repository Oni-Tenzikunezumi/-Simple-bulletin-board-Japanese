<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>mission_5-1</title>
</head>

<?php
include "bulletin-board-Japanese_function.php";
//ユーザー定義関数の呼び出し
$reload = reload($pdo, $D_Prevent);//リロードの検出
?>

<html>
<body>
<div style="text-align:center">

<form action="" method="post">
  <input type="hidden" name="reload" value="<?=$reload["rand"]?>"><!--リロード判定用パラメータ-->

  <?php if($submit_value=="投稿")://投稿を行う場合 ?>
    <?php if($editid=="")://idの選択がない場合?>
            <!--投稿用フォームの表示-->
            mission5-1 掲示板<br>
            <input type="text" name="name" placeholder="名前">
            <input type="password" name="pass" placeholder="password" required>
            <br>
            <textarea name="comment" rows="4" cols="50" placeholder="コメントを入力…"></textarea>
            <br>
            <input type="hidden" name="config" value="投稿"><!--表示設定-->
            <input type="submit" name="submit" value="投稿"><!--投稿ボタン-->

            <input type="reset" name="reset"><!--リセットボタン-->
            <br>編集、削除はコメント番号を選択してください。<br>

    <?php else://編集、削除の申請を行う場合?>
            <!--申請用フォームの表示-->
            パスワードを入力してください。<br>
            <input type="password" name="inputpass" placeholder="password">
            <br>
            <input type="hidden" name="submit" value="申請"><!--次の編集は行わない-->
            <input type="number" name="editid" value="<?php echo $editid?>" style="width:40px" placeholder="コメント番号" min=0>
            <input type="submit" name="config" value="編集"><!--編集ボタン-->

            <input type="submit" name="config" value="削除"><!--削除ボタン-->

            <input type="submit" name="config" value="キャンセル"><!--キャンセルボタン-->
    <?php endif; ?>

  <?php elseif($submit_value=="編集")://編集を行う場合?>
          <!--編集用フォームの表示-->
          編集中。<br>
          <input type="text" name="name" placeholder="名前" value="<?=$display["name"]?>" >
          <input type="password" name="pass" placeholder="password" value="<?=$display["pass"]?>" required>
          <br>
          <textarea name="comment" rows="4" cols="50" placeholder="コメントを入力…" ><?=$display["comment"]?></textarea>
          <br>
          <input type="number" name="editid" value="<?=$display["id"]?>" style="width:50px" readonly><!--コメント番号-->
          <input type="hidden" name="inputpass" value="<?=$inputpass?>">
          <input type="hidden" name="config" value="投稿"><!--configを再設定-->
          <input type="submit" name="submit" value="編集"><!--編集ボタン-->

          <input type="submit" name="config" value="キャンセル"><!--キャンセルボタン-->

          <input type="reset" name="reset"><!--リセットボタン-->

  <?php elseif($submit_value=="削除")://削除を行う場合?>
          <!--削除用フォームの表示-->
          このコメントを削除しますか？<br>
          <input type="text" name="name" value="<?=$display["name"]?>" readonly>
          <input type="password" name="pass" value="<?=$display["pass"]?>" readonly>
          <br>
          <textarea name="comment" rows="4" cols="50" readonly><?=$display["comment"]?></textarea>
          <br>
          <input type="number" name="editid" value="<?=$display["id"]?>" style="width:50px" readonly><!--コメント番号-->
          <input type="hidden" name="inputpass" value="<?=$inputpass?>">
          <input type="hidden" name="config" value="投稿"><!--configを再設定-->
          <input type="submit" name="submit" value="削除"><!--削除ボタン-->

          <input type="submit" name="config" value="キャンセル"><!--キャンセルボタン-->
  <?php endif;?>
</form>

</div>
<hr>
  <?php
  //メッセージの表示
  if(isset($message)){
    foreach($message as $message) echo $message;
  }
  //ユーザー定義関数の呼び出し
  //リロードの判定結果で分岐
  if(!$reload["reload"]) editdata($pdo, $LogTable);//リロードされていない場合、データレコードの変更
  show($pdo, $LogTable, basename(__FILE__));//表示,実行しているファイル名の取得
  ?>
</body>
</html>
