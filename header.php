<?php
session_start();
if(!isset($_SESSION["success"])) {
    header('Location: generate.php');
}
$toBeSearch='';
$maxRes=15;
$present=1;
if(isset($_GET['searchQuery'])) {
    $toBeSearch=$_GET['searchQuery'];
}
if(isset($_GET['numRes'])) {
    $maxRes=$_GET['numRes'];
}
if(isset($_GET['paginNo'])) {
    $present=$_GET['paginNo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="assets/css/styles4.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <title>SEARCH</title>
</head>
<body>
<div class="container">
<ul>
  <li><a class='menu active' href="index.php">HOME</a></li>
  <li><a class='menu' href="images.php">IMAGES</a></li>
  <li><a class='menu' href="news.php">NEWS</a></li>
  <li><a class='menu' href="addDomain.php">ENTER DOMAIN</a></li>
  <li><a class='menu' href="ranking.php">RANK</a></li>
  <input class='entry' type="number" name='paginNo' placeholder='Enter page number' value='<?php echo $present; ?>'>
  <input class='entry' type="number" name='numRes' placeholder='No of results in a page' value='<?php echo $maxRes; ?>'>
</ul>
    <form action="" method="GET">
        <div class="input-group">
            <input type="text" value='<?php echo $toBeSearch; ?>' class="form-control" id='reqVal' placeholder="Surf the world wide web" name="searchQuery" autocomplete="off">
            <div class="input-group-btn">
                <button type="submit" class="btn btn-default">
                    <i class="glyphicon glyphicon-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>
<script>
    let thisPage = location.pathname.substring(location.pathname.lastIndexOf('/')+1);
    var listItems = document.querySelectorAll('.menu');
    for (let m = 0;m<listItems.length; m++) {
        if (listItems[m].getAttribute("href").indexOf(thisPage)!=-1) {
            document.querySelector(".active").classList.remove('active');
            listItems[m].classList.add("active");
        }
    }
    const entries=document.querySelectorAll(".entry");
    const searchValue=document.querySelector('#reqVal');
    Array.from(entries).forEach(el => {
        el.addEventListener('change', function () {
            window.location=thisPage+'?searchQuery='+searchValue.value+'&paginNo='+entries[0].value+'&numRes='+entries[1].value;
        });
    });
</script>
</body>
</html>