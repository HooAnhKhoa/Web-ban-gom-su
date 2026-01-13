<?php 
if(!isset($_SESSION)) { session_start(); }

// 1. Kiểm tra đăng nhập và kết nối
require_once '../../../conn.php';
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';

// 2. Lấy thống kê từ database
// Tổng số đơn hàng
$sql_total_orders = "SELECT COUNT(*) as total FROM tbl_donhang";
$result_total_orders = $conn->query($sql_total_orders);
$total_orders = $result_total_orders->fetch_assoc()['total'];

// Tổng doanh thu
$sql_total_revenue = "SELECT SUM(tongtien) as total FROM tbl_donhang WHERE trangthai IN ('da_xac_nhan', 'dang_giao', 'da_giao')";
$result_total_revenue = $conn->query($sql_total_revenue);
$total_revenue = $result_total_revenue->fetch_assoc()['total'] ?? 0;

// Đơn hàng hôm nay (join với hóa đơn để lấy ngày)
$today = date('Y-m-d');
$sql_today_orders = "
    SELECT COUNT(*) as total 
    FROM tbl_donhang d
    JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
    WHERE DATE(h.ngaydat) = '$today'
";
$result_today_orders = $conn->query($sql_today_orders);
$today_orders = $result_today_orders->fetch_assoc()['total'];

// Doanh thu hôm nay
$sql_today_revenue = "
    SELECT SUM(d.tongtien) as total 
    FROM tbl_donhang d
    JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
    WHERE DATE(h.ngaydat) = '$today' 
    AND d.trangthai IN ('da_xac_nhan', 'dang_giao', 'da_giao')
";
$result_today_revenue = $conn->query($sql_today_revenue);
$today_revenue = $result_today_revenue->fetch_assoc()['total'] ?? 0;

// Đơn hàng theo trạng thái
$sql_status_stats = "SELECT trangthai, COUNT(*) as count FROM tbl_donhang GROUP BY trangthai";
$result_status_stats = $conn->query($sql_status_stats);
$status_stats = [];
while($row = $result_status_stats->fetch_assoc()) {
    $status_stats[$row['trangthai']] = $row['count'];
}

// Sản phẩm bán chạy (top 5)
$sql_top_products = "
    SELECT sp.tensanpham, SUM(cthd.soluong) as total_sold, SUM(cthd.thanhtien) as total_revenue
    FROM tbl_chitiethoadon cthd
    JOIN tbl_sanpham sp ON cthd.masanpham = sp.masanpham
    GROUP BY cthd.masanpham
    ORDER BY total_sold DESC
    LIMIT 5
";
$result_top_products = $conn->query($sql_top_products);
$top_products = [];
while($row = $result_top_products->fetch_assoc()) {
    $top_products[] = $row;
}

// Doanh thu 7 ngày gần nhất
$sql_week_revenue = "
    SELECT 
        DATE(h.ngaydat) as date,
        SUM(d.tongtien) as revenue,
        COUNT(*) as orders
    FROM tbl_donhang d
    JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
    WHERE h.ngaydat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND d.trangthai IN ('da_xac_nhan', 'dang_giao', 'da_giao')
    GROUP BY DATE(h.ngaydat)
    ORDER BY date
";
$result_week_revenue = $conn->query($sql_week_revenue);
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];
while($row = $result_week_revenue->fetch_assoc()) {
    $chart_labels[] = date('d/m', strtotime($row['date']));
    $chart_revenue[] = (float)$row['revenue'];
    $chart_orders[] = (int)$row['orders'];
}

// 3. Nhúng Header và Sidebar
include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Trang chủ</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Dashboard</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Bảng điều khiển</h6>
            </nav>
            <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
                <div class="flex items-center md:ml-auto md:pr-4">
                    <div class="text-white mr-4">
                        <i class="fas fa-calendar-day mr-1"></i> <?php echo date('d/m/Y'); ?>
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
        <!-- Thống kê nhanh -->
        <div class="flex flex-wrap -mx-3">
            <!-- Doanh thu hôm nay -->
            <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 xl:w-1/4">
                <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl hover:shadow-lg transition-shadow">
                    <div class="flex-auto p-4">
                        <div class="flex flex-row -mx-3">
                            <div class="flex-none w-2/3 px-3">
                                <p class="mb-0 text-sm font-semibold leading-normal uppercase opacity-60">Doanh thu hôm nay</p>
                                <h5 class="mb-2 font-bold text-green-600"><?php echo number_format($today_revenue); ?>₫</h5>
                                <p class="text-xs text-gray-500">
                                    <?php echo $today_orders; ?> đơn hàng
                                </p>
                            </div>
                            <div class="px-3 text-right basis-1/3">
                                <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-green-500 to-emerald-500">
                                    <i class="fas fa-money-bill-wave text-lg relative top-3.5 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng đơn hàng -->
            <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 xl:w-1/4">
                <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl hover:shadow-lg transition-shadow">
                    <div class="flex-auto p-4">
                        <div class="flex flex-row -mx-3">
                            <div class="flex-none w-2/3 px-3">
                                <p class="mb-0 text-sm font-semibold leading-normal uppercase opacity-60">Tổng đơn hàng</p>
                                <h5 class="mb-2 font-bold text-blue-600"><?php echo number_format($total_orders); ?></h5>
                                <p class="text-xs text-gray-500">
                                    <?php echo ($status_stats['da_giao'] ?? 0); ?> đã giao
                                </p>
                            </div>
                            <div class="px-3 text-right basis-1/3">
                                <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                    <i class="fas fa-shopping-cart text-lg relative top-3.5 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng doanh thu -->
            <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 xl:w-1/4">
                <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl hover:shadow-lg transition-shadow">
                    <div class="flex-auto p-4">
                        <div class="flex flex-row -mx-3">
                            <div class="flex-none w-2/3 px-3">
                                <p class="mb-0 text-sm font-semibold leading-normal uppercase opacity-60">Tổng doanh thu</p>
                                <h5 class="mb-2 font-bold text-purple-600"><?php echo number_format($total_revenue); ?>₫</h5>
                                <p class="text-xs text-gray-500">
                                    Tất cả đơn thành công
                                </p>
                            </div>
                            <div class="px-3 text-right basis-1/3">
                                <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-purple-500 to-pink-500">
                                    <i class="fas fa-chart-line text-lg relative top-3.5 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Đơn hàng mới hôm nay -->
            <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 xl:w-1/4">
                <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl hover:shadow-lg transition-shadow">
                    <div class="flex-auto p-4">
                        <div class="flex flex-row -mx-3">
                            <div class="flex-none w-2/3 px-3">
                                <p class="mb-0 text-sm font-semibold leading-normal uppercase opacity-60">Đơn hàng mới</p>
                                <h5 class="mb-2 font-bold text-orange-600"><?php echo number_format($today_orders); ?></h5>
                                <p class="text-xs text-gray-500">
                                    <?php echo ($status_stats['cho_xac_nhan'] ?? 0); ?> chờ xác nhận
                                </p>
                            </div>
                            <div class="px-3 text-right basis-1/3">
                                <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-orange-500 to-yellow-500">
                                    <i class="fas fa-bell text-lg relative top-3.5 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap mt-6 -mx-3">
            <!-- Biểu đồ doanh thu -->
            <div class="w-full max-w-full px-3 mt-0 lg:w-7/12">
                <div class="relative z-20 flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <div class="flex justify-between items-center">
                            <h6 class="capitalize font-bold">Thống kê doanh thu 7 ngày qua</h6>
                            <span class="text-sm text-gray-500">
                                <?php 
                                if(count($chart_labels) > 0) {
                                    echo "Từ " . $chart_labels[0] . " đến " . date('d/m');
                                } else {
                                    echo "Không có dữ liệu";
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex-auto p-4">
                        <?php if(count($chart_labels) > 0): ?>
                            <canvas id="revenueChart" height="300"></canvas>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center h-64">
                                <i class="fas fa-chart-bar text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500">Chưa có dữ liệu doanh thu 7 ngày qua</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Thống kê trạng thái đơn hàng -->
            <div class="w-full max-w-full px-3 mt-0 lg:w-5/12">
                <div class="relative z-20 flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="capitalize font-bold">Trạng thái đơn hàng</h6>
                    </div>
                    <div class="flex-auto p-4">
                        <div class="space-y-4">
                            <?php 
                            $status_labels = [
                                'cho_xac_nhan' => ['label' => 'Chờ xác nhận', 'color' => 'bg-yellow-500'],
                                'da_xac_nhan' => ['label' => 'Đã xác nhận', 'color' => 'bg-blue-500'],
                                'dang_giao' => ['label' => 'Đang giao', 'color' => 'bg-orange-500'],
                                'da_giao' => ['label' => 'Đã giao', 'color' => 'bg-green-500'],
                                'da_huy' => ['label' => 'Đã hủy', 'color' => 'bg-red-500']
                            ];
                            
                            foreach($status_labels as $key => $status): 
                                $count = $status_stats[$key] ?? 0;
                                $percentage = $total_orders > 0 ? ($count / $total_orders * 100) : 0;
                            ?>
                            <div class="mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium"><?php echo $status['label']; ?></span>
                                    <span class="text-sm font-bold"><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="<?php echo $status['color']; ?> h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap mt-6 -mx-3">
            <!-- Sản phẩm bán chạy -->
            <div class="w-full max-w-full px-3 mt-0">
                <div class="relative z-20 flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="capitalize font-bold">Top 5 sản phẩm bán chạy</h6>
                    </div>
                    <div class="flex-auto p-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng đã bán</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if(count($top_products) > 0): ?>
                                        <?php foreach($top_products as $index => $product): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 font-bold">
                                                    <?php echo $index + 1; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($product['tensanpham']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                    <?php echo number_format($product['total_sold']); ?> sản phẩm
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                                <?php echo number_format($product['total_revenue']); ?>₫
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <i class="fas fa-box-open text-gray-300 text-4xl mb-4"></i>
                                                    <p class="text-gray-500">Chưa có dữ liệu sản phẩm bán chạy</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>
</main>

<script src="../assets/js/plugins/chartjs.min.js" async></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đợi Chart.js load
    setTimeout(function() {
        if(typeof Chart !== 'undefined') {
            // Lấy dữ liệu từ PHP
            const chartLabels = <?php echo json_encode($chart_labels); ?>;
            const chartRevenue = <?php echo json_encode($chart_revenue); ?>;
            const chartOrders = <?php echo json_encode($chart_orders); ?>;
            
            // Kiểm tra nếu có dữ liệu
            if(chartLabels.length > 0 && chartRevenue.length > 0) {
                // Tạo biểu đồ doanh thu
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Doanh thu (VNĐ)',
                            data: chartRevenue,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Số đơn hàng',
                            data: chartOrders,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Doanh thu (VNĐ)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('vi-VN').format(value) + '₫';
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Số đơn hàng'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label.includes('Doanh thu')) {
                                            return label + ': ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + '₫';
                                        } else {
                                            return label + ': ' + context.parsed.y + ' đơn';
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    }, 100);
});
</script>

<style>
/* Hiệu ứng hover cho các thẻ thống kê */
.hover\:shadow-lg {
    transition: box-shadow 0.3s ease;
}

/* Định dạng số */
.font-bold {
    font-weight: 700;
}

/* Định dạng biểu đồ */
canvas {
    max-height: 300px;
}

/* Chiều cao cho phần không có biểu đồ */
.h-64 {
    height: 16rem;
}
</style>