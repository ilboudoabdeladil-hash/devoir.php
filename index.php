<?php
session_start();

// Connexion à la base de données
try {
    $dsn = "mysql:dbname=new_db;host=127.0.0.1;port=3307;charset=utf8";
    $login = "root";
    $pass = "Adil2005#";

    $pdo = new PDO($dsn, $login, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

} catch (Exception $e){
    die("Erreur BD : " . $e->getMessage());
}

// FONCTIONS 
if(!isset($_SESSION["t"])) $_SESSION["t"] = bin2hex(random_bytes(20));
function e($s){ return htmlspecialchars($s,ENT_QUOTES); }
function csrf_ok($t){ return hash_equals($_SESSION["t"], $t); }
function imgok($n){ return in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), ["jpg","jpeg","png","gif","webp"]); }

// CLASSE PUBLICATION
class Publication {
    private $id, $title, $picture, $description, $datetime, $is_published;

    public function getId(){ return $this->id; }
    public function setId($v){ $this->id=$v; }

    public function getTitle(){ return $this->title; }
    public function setTitle($v){ $this->title=$v; }

    public function getPicture(){ return $this->picture; }
    public function setPicture($v){ $this->picture=$v; }

    public function getDescription(){ return $this->description; }
    public function setDescription($v){ $this->description=$v; }

    public function getDatetime(){ return $this->datetime; }
    public function setDatetime($v){ $this->datetime=$v; }

    public function isPublished(){ return $this->is_published; }
    public function setPublished($v){ $this->is_published=$v; }
}

$a = $_POST["a"] ?? $_GET["a"] ?? "list";

// Protection CSRF
if($_SERVER["REQUEST_METHOD"]==="POST" && !csrf_ok($_POST["t"] ?? "")) die("CSRF");

if($a==="create" && $_POST){
    $p = new Publication();
    $p->setTitle($_POST["title"]);
    $p->setDescription($_POST["desc"]);
    $p->setPublished(isset($_POST["pub"])?1:0);
    $p->setDatetime(date("Y-m-d H:i"));

    // image
    $img = null;
    if(!empty($_FILES["pic"]["name"]) && imgok($_FILES["pic"]["name"])){
        $img = uniqid().".".pathinfo($_FILES["pic"]["name"], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES["pic"]["tmp_name"], "uploads/$img");
    }
    $p->setPicture($img);

    $pdo->prepare("INSERT INTO publication(title,picture,description,datetime,is_published)
    VALUES(?,?,?,?,?)")->execute([
        $p->getTitle(),
        $p->getPicture(),
        $p->getDescription(),
        $p->getDatetime(),
        $p->isPublished()
    ]);

    header("Location:index.php"); exit;
}

// Delete
if($a==="delete" && $_POST){
    $pdo->prepare("DELETE FROM publication WHERE id=?")->execute([$_POST["id"]]);
    header("Location:index.php"); exit;
}

// TOGGLE PUBLISH
if($a==="toggle" && $_POST){
    $pdo->prepare("UPDATE publication SET is_published=1-is_published WHERE id=?")->execute([$_POST["id"]]);
    header("Location:index.php"); exit;
}

// UPDATE
if($a==="update" && $_POST){
    $p = new Publication();
    $p->setId($_POST["id"]);
    $p->setTitle($_POST["title"]);
    $p->setDescription($_POST["desc"]);
    $p->setPublished(isset($_POST["pub"])?1:0);

    $query = "UPDATE publication SET title=?, description=?, is_published=?";
    $params = [ $p->getTitle(), $p->getDescription(), $p->isPublished() ];

    if(!empty($_FILES["pic"]["name"]) && imgok($_FILES["pic"]["name"])){
        $img = uniqid().".".pathinfo($_FILES["pic"]["name"], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES["pic"]["tmp_name"], "uploads/$img");
        $query .= ", picture=?";
        $params[] = $img;
    }
    $query .= " WHERE id=?";
    $params[] = $p->getId();
    $pdo->prepare($query)->execute($params);
    header("Location:index.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php if($a==="list"):
$rows=$pdo->query("SELECT * FROM publication ORDER BY datetime DESC")->fetchAll(); ?>
<a href="?a=new">Nouvelle</a><br><br>
<table border=1>
<tr><th>ID</th><th>Titre</th><th>Img</th><th>Pub</th><th>Actions</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?=$r["id"]?></td>
<td><?=e($r["title"])?></td>
<td><?php if($r["picture"]): ?><img src="uploads/<?=$r["picture"]?>" width=80><?php endif;?></td>
<td><?=$r["is_published"]?"Oui":"Non"?></td>
<td>
<a href="?a=edit&id=<?=$r["id"]?>">Éditer</a>
<form style="display:inline" method="post">
<input type="hidden" name="t" value="<?=$_SESSION["t"]?>">
<input type="hidden" name="a" value="delete">
<input type="hidden" name="id" value="<?=$r["id"]?>">
<button>Suppr</button></form>
 
<form style="display:inline" method="post">
<input type="hidden" name="t" value="<?=$_SESSION["t"]?>">
<input type="hidden" name="a" value="toggle">
<input type="hidden" name="id" value="<?=$r["id"]?>">
<button><?=$r["is_published"]?"Dépub":"Publier"?></button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
 
<?php if($a==="new"): ?>
<h3>Créer</h3>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="t" value="<?=$_SESSION["t"]?>">
<input type="hidden" name="a" value="create">
Titre : <input name="title"><br>
Image : <input type="file" name="pic"><br>
Desc : <textarea name="desc"></textarea><br>
Publiée ? <input type="checkbox" name="pub"><br>
<button>OK</button>
</form>
<?php endif; ?>
</body>
</html>
 