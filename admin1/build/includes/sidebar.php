<?php
// Lấy tên file hiện tại (ví dụ: tables.php)
$current_page = basename($_SERVER['PHP_SELF']);

// Xác định nhóm trang sản phẩm
$product_pages = array('products.php', 'add_product.php', 'edit_product.php');
$is_product_page = in_array($current_page, $product_pages);

// Xác định nhóm trang loại sản phẩm
$category_pages = array('categories.php', 'add_category.php', 'edit_category.php');
$is_category_page = in_array($current_page, $category_pages);

// Xác định nhóm trang đơn hàng
$order_pages = array('orders.php', 'order_detail.php');
$is_order_page = in_array($current_page, $order_pages);

// Xác định nhóm trang nhập hàng
$import_pages = array('imports.php', 'add_import.php');
$is_import_page = in_array($current_page, $import_pages);

// Xác định nhóm trang báo cáo
$report_pages = array('reports.php', 'sales_report.php', 'inventory_report.php');
$is_report_page = in_array($current_page, $report_pages);
?>

<aside class="fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 xl:ml-6 max-w-64 ease-nav-brand z-990 rounded-2xl xl:left-0 xl:translate-x-0" aria-expanded="false">
  <div class="h-19">
    <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden" sidenav-close></i>
    <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap dark:text-white text-slate-700" href="javascript:;">
      <img src="../assets/img/logo-ct-dark.png" class="inline h-full max-w-full transition-all duration-200 dark:hidden ease-nav-brand max-h-8" alt="main_logo" />
      <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">Admin Dashboard</span>
    </a>
  </div>

  <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

  <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
    <ul class="flex flex-col pl-0 mb-0">
      
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo ($current_page == 'dashboard.php' || $current_page == 'dashboard.html') ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="dashboard.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-blue-500 ni ni-tv-2"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Dashboard</span>
        </a>
      </li>
      
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo ($current_page == 'tables.php') ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="tables.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-02"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý người dùng</span>
        </a>
      </li>
      
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo $is_product_page ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="products.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-orange-500 ni ni-calendar-grid-58"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý sản phẩm</span>
        </a>
      </li>

      <!-- Thêm menu Quản lý loại sản phẩm -->
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo $is_category_page ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="categories.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-emerald-500 ni ni-bullet-list-67"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý loại sản phẩm</span>
        </a>
      </li>

      <!-- Thêm menu Quản lý đơn hàng -->
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo $is_order_page ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="orders.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-cart"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý đơn hàng</span>
        </a>
      </li>

      <!-- Thêm menu Quản lý nhập hàng (nếu cần)
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80 <?php echo $is_import_page ? 'bg-blue-500/13 rounded-lg font-semibold text-slate-700' : ''; ?>" href="imports.php">
          <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
            <i class="relative top-0 text-sm leading-normal text-red-500 ni ni-delivery-fast"></i>
          </div>
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý nhập hàng</span>
        </a>
      </li> -->

      <!-- Thêm menu Báo cáo thống kê (nếu cần) -->
      <li class="mt-0.5 w-full">
        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80" href="../../../index.php">
          <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Đến website </span>
        </a>
      </li>

    </ul>
  </div>
</aside>