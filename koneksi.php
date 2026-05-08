<?php

$host="localhost";
$user="root";
$pass="";
$db="mypadel";

$koneksi = mysqli_connect($host,$user,$pass,$db);

if(!$koneksi){
    die("Koneksi Gagal". mysqli_error());
}

?>