<?php 
if(!isset($_SESSION)) { session_start(); }

// 1. Kiểm tra đăng nhập và kết nối
require_once '../../../conn.php';
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';

// 2. Nhúng Header và Sidebar
include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Dashboard</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Dashboard</h6>
            </nav>
            <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
                <div class="flex items-center md:ml-auto md:pr-4">
                    <div class="relative flex flex-wrap items-stretch w-full transition-all rounded-lg ease">
                        <span class="absolute z-50 flex items-center h-full px-2.5 text-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </span>
                        <input type="text" class="pl-9 text-sm focus:shadow-primary-outline ease w-1/100 leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 bg-white py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" placeholder="Tìm kiếm...">
                    </div>
                </div>
                <ul class="flex flex-row justify-end pl-0 mb-0 list-none md-max:w-full">
                    <li class="flex items-center px-4 text-white">
                        <i class="fa fa-user mr-1"></i> <?php echo htmlspecialchars($userName); ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 xl:w-1/4">
                <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="flex-auto p-4">
                        <div class="flex flex-row -mx-3">
                            <div class="flex-none w-2/3 px-3">
                                <p class="mb-0 text-sm font-semibold leading-normal uppercase opacity-60">Doanh thu hôm nay</p>
                                <h5 class="mb-2 font-bold">$53,000</h5>
                            </div>
                            <div class="px-3 text-right basis-1/3">
                                <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                    <i class="ni ni-money-coins text-lg relative top-3.5 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

        <div class="flex flex-wrap mt-6 -mx-3">
            <div class="w-full max-w-full px-3 mt-0 lg:w-7/12">
                <div class="relative z-20 flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="capitalize">Tổng quan doanh số</h6>
                    </div>
                    <div class="flex-auto p-4">
                        <canvas id="chart-line" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>
</main>

<script src="../assets/js/plugins/chartjs.min.js" async></script>