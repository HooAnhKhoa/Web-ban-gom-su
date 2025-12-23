<?php
// Kiểm tra session và thông tin người dùng
if(!isset($_SESSION)) { session_start(); }
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
    <title>Admin Dashboard - Argon</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <link href="../assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
      .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
      .status-active { background: linear-gradient(195deg, #66BB6A, #43A047); color: white; }
      .status-inactive { background: linear-gradient(195deg, #EF5350, #E53935); color: white; }
      .btn-edit { background: linear-gradient(195deg, #49a3f1, #1A73E8); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: all 0.3s; }
      .btn-delete { background: linear-gradient(195deg, #EF5350, #E53935); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: all 0.3s; }
      .btn-toggle { background: linear-gradient(195deg, #FFA726, #FB8C00); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: all 0.3s; }
    </style>
</head>
<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>