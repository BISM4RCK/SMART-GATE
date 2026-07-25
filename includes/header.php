<?php

require_once __DIR__.'/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title><?=e($pageTitle)?> | <?=APP_NAME?></title>

<link rel="stylesheet"
href="<?=url('assets/css/style.css')?>">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>

<body>

<?php include __DIR__.'/navbar.php'; ?>

<div class="app">

<?php

if($user){

include __DIR__.'/sidebar.php';

}

?>

<div class="main">