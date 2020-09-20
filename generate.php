
 <!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>NOT A BOT?</title>
     <link rel="stylesheet" href="assets/css/styles3.css">
 </head>
 <body>
<?php
session_start();
if(isset($_POST["input"])) {
    if($_COOKIE['answer']==$_POST["input"]) {
        unset($_COOKIE['answer']);
        $_SESSION["success"]=1;
        header("Location: index.php");
    }
    else {
        unset($_COOKIE['answer']);
        echo "<div class='alert alert-danger'>Wrong!! Try again!!</div>";
    }
    
}
?>
       <div class='centre'>
            <h3>CONFIRM U ARE A HUMAN</h3>
            <div class="marg">
                <img src="pic.php">
            </div>
            <form class="marg" action="" method="POST">
                <button class='btn' type="submit" name="refresh">GET NEW CAPTCHA</button>
            </form>
            <form class="marg" action="" method="POST">
                <input type="text" name="input" autocomplete="off"/>
                <button class='btn' type="submit" name="submit">CHECK</button>
            </form>
            
        </div>      
</body>
 </html>