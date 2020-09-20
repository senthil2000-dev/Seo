<?php
session_start();
if(!isset($_SESSION["success"])) {
    header('Location: generate.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/styles2.css">
    <link rel="stylesheet" type="text/css" href="assets/css/styles4.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <title>SEARCH</title>
</head>
<body>

<div class="container">
        <ul>
        <li><a class='menu' href="index.php">HOME</a></li>
        <li><a class='menu' href="images.php">IMAGES</a></li>
        <li><a class='menu active' href="news.php">NEWS</a></li>
        <li><a class='menu' href="addDomain.php">ENTER DOMAIN</a></li>
        <li><a class='menu' href="ranking.php">RANK</a></li>
        </ul>
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Surf the world wide web" id="searchQuery" autocomplete="off">
            <div class="input-group-btn">
                <button onclick="getRes(1)" class="btn btn-default">
                    <i class="glyphicon glyphicon-search"></i>
                </button>
            </div>
        </div>
</div>
<div class='allSols'>
    
</div>
<div class='pagination'></div>
<div class="selectStat">
    <label>SORT BY: </label>
    <select name='sort' class='sortBy' onchange="changeSort(this)">
        <option value="0">publishedAt</option>
        <option value="0">relevancy</option>
        <option value="0">popularity</option>
        <option value="0">publishedAt</option>
    </select>
</div>
</body>
<script>
const inp=document.getElementById("searchQuery");
const contain = document.querySelector('.allSols');
const pagin = document.querySelector('.pagination');
let pageNo;
let numResults=0;
let sortBy="publishedAt";
function getRes(page) {
    pageNo=page;
    const secret="39734d50e992419c98618168b4e9a70b";    
    let topic=inp.value;
    console.log(topic);
    contain.innerHTML="";
    let link = `https://newsapi.org/v2/everything?q=${topic}&apiKey=${secret}&page=${pageNo}&pageSize=10&sortBy=${sortBy}`;
    fetchData(link);
}

async function fetchData(link) {
    const data=await fetch(link);
    const result=await data.json();
    numResults = Math.min(100, result.totalResults);
    let resultsArray = result.articles;
    resultsArray.forEach(item => {
    contain.innerHTML+="<div class='searchSol'><img src='"+item.urlToImage+"'><div class='side'><a class='direct' href='"+item.url+"'><span>"+ item.publishedAt+"</span><h4>"+item.title+"</h4></a><div class='desp'>"+item.description+"</div></div>";
    })
    console.log(numResults);
    apply();
}

let numToDisplay=10;
let pageSize=10;
function apply() {
    console.log(numResults);
    pagin.innerHTML="";
    present=pageNo-Math.floor(numToDisplay/2);
    numberOfPages=Math.ceil(numResults/pageSize);
    pagesRemaining=Math.min(numToDisplay, numberOfPages);  
    if(present<1) {
        present=1;
    }
    if(present+pagesRemaining>numberOfPages+1) {
        present=numberOfPages+1-pagesRemaining;
    }
    while(pagesRemaining!=0) {
        if(present==pageNo) {
            pagin.innerHTML+= "<div><span class='number'>"+present+"</span></div>";
        }
        else {
            pagin.innerHTML+=  "<div><a onclick='getRes("+present+")'><span class='number'>"+present+"</span></a></div>";
        }
                            
        present++;
        pagesRemaining--;
    } 
}

function changeSort(selected) {
    sortBy=selected.options[selected.selectedIndex].text;
    getRes(pageNo);
}
</script>
</html>
