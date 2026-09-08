<?php
/**
 * Excel Export Functions với Chi phí & Lợi nhuận
 * Sử dụng HTML table export (tương thích Excel)
 */

// Hàm xuất báo cáo doanh thu & lợi nhuận ra Excel
function exportRevenueReport($from_date, $to_date, $conn) {
    // Lấy dữ liệu với chi phí và lợi nhuận
    $revenue_query = "SELECT 
        DATE(o.order_date) as date,
        COUNT(DISTINCT o.order_id) as orders_count,
        SUM(o.total_amount) as revenue,
        SUM(oi.quantity * p.material_cost) as material_cost,
        SUM(o.total_amount) - SUM(oi.quantity * p.material_cost) as profit,
        ((SUM(o.total_amount) - SUM(oi.quantity * p.material_cost)) / SUM(o.total_amount) * 100) as profit_margin
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.status = 'completed'
        AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
        GROUP BY DATE(o.order_date)
        ORDER BY date DESC";
    
    $result = mysqli_query($conn, $revenue_query);
    
    // Tạo header cho file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="BaoCaoDoanhThu_LoiNhuan_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Bắt đầu xuất HTML table
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>Báo cáo doanh thu</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:Print>';
    echo '<x:ValidPrinterInfo/>';
    echo '</x:Print>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '</head>';
    echo '<body>';
    
    // Tiêu đề báo cáo
    echo '<table border="1">';
    echo '<tr>';
    echo '<td colspan="6" style="font-size: 18px; font-weight: bold; text-align: center; background-color: #00704A; color: white;">BÁO CÁO DOANH THU & LỢI NHUẬN STARBUCKS</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center;">Từ ngày: ' . date('d/m/Y', strtotime($from_date)) . ' đến ngày: ' . date('d/m/Y', strtotime($to_date)) . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="6"></td></tr>'; // Dòng trống
    
    // Header bảng
    echo '<tr style="background-color: #d4af37; font-weight: bold;">';
    echo '<td style="width: 120px;">Ngày</td>';
    echo '<td style="width: 100px;">Số đơn hàng</td>';
    echo '<td style="width: 150px;">Doanh thu (VNĐ)</td>';
    echo '<td style="width: 150px;">Chi phí NL (VNĐ)</td>';
    echo '<td style="width: 150px;">Lợi nhuận (VNĐ)</td>';
    echo '<td style="width: 100px;">Biên LN (%)</td>';
    echo '</tr>';
    
    // Dữ liệu
    $total_orders = 0;
    $total_revenue = 0;
    $total_cost = 0;
    $total_profit = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . date('d/m/Y', strtotime($row['date'])) . '</td>';
        echo '<td style="text-align: center;">' . $row['orders_count'] . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['revenue'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right; color: #dc3545;">' . number_format($row['material_cost'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right; color: #28a745;">' . number_format($row['profit'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: center;">' . number_format($row['profit_margin'], 1, ',', '.') . '%</td>';
        echo '</tr>';
        
        $total_orders += $row['orders_count'];
        $total_revenue += $row['revenue'];
        $total_cost += $row['material_cost'];
        $total_profit += $row['profit'];
    }
    
    $total_margin = $total_revenue > 0 ? ($total_profit / $total_revenue * 100) : 0;
    
    // Tổng cộng
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<td>TỔNG CỘNG</td>';
    echo '<td style="text-align: center;">' . $total_orders . '</td>';
    echo '<td style="text-align: right;">' . number_format($total_revenue, 0, ',', '.') . '</td>';
    echo '<td style="text-align: right; color: #dc3545;">' . number_format($total_cost, 0, ',', '.') . '</td>';
    echo '<td style="text-align: right; color: #28a745;">' . number_format($total_profit, 0, ',', '.') . '</td>';
    echo '<td style="text-align: center;">' . number_format($total_margin, 1, ',', '.') . '%</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
    exit();
}

// Hàm xuất báo cáo sản phẩm bán chạy với lợi nhuận ra Excel
function exportBestSellingProducts($from_date, $to_date, $conn) {
    $query = "SELECT 
        p.product_name,
        c.category_name,
        p.price,
        p.material_cost,
        (p.price - p.material_cost) as profit_per_unit,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.price * oi.quantity) as total_revenue,
        SUM(oi.quantity * p.material_cost) as total_material_cost,
        SUM(oi.price * oi.quantity) - SUM(oi.quantity * p.material_cost) as total_profit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.status = 'completed'
        AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
        GROUP BY oi.product_id
        ORDER BY total_profit DESC";
    
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="SanPhamLoiNhuanCao_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<td colspan="8" style="font-size: 18px; font-weight: bold; text-align: center; background-color: #00704A; color: white;">BÁO CÁO SẢN PHẨM LỢI NHUẬN CAO NHẤT</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="8" style="text-align: center;">Từ ngày: ' . date('d/m/Y', strtotime($from_date)) . ' đến ngày: ' . date('d/m/Y', strtotime($to_date)) . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="8"></td></tr>';
    
    echo '<tr style="background-color: #d4af37; font-weight: bold;">';
    echo '<td style="width: 50px;">Hạng</td>';
    echo '<td style="width: 200px;">Tên sản phẩm</td>';
    echo '<td style="width: 120px;">Danh mục</td>';
    echo '<td style="width: 100px;">SL bán</td>';
    echo '<td style="width: 130px;">Doanh thu (VNĐ)</td>';
    echo '<td style="width: 130px;">Chi phí NL (VNĐ)</td>';
    echo '<td style="width: 130px;">Lợi nhuận (VNĐ)</td>';
    echo '<td style="width: 120px;">LN/Sản phẩm (VNĐ)</td>';
    echo '</tr>';
    
    $rank = 1;
    $total_qty = 0;
    $total_rev = 0;
    $total_cost = 0;
    $total_profit = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td style="text-align: center;">' . $rank . '</td>';
        echo '<td>' . $row['product_name'] . '</td>';
        echo '<td>' . $row['category_name'] . '</td>';
        echo '<td style="text-align: center;">' . $row['total_quantity'] . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['total_revenue'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right; color: #dc3545;">' . number_format($row['total_material_cost'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right; color: #28a745;">' . number_format($row['total_profit'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['profit_per_unit'], 0, ',', '.') . '</td>';
        echo '</tr>';
        
        $rank++;
        $total_qty += $row['total_quantity'];
        $total_rev += $row['total_revenue'];
        $total_cost += $row['total_material_cost'];
        $total_profit += $row['total_profit'];
    }
    
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<td colspan="3">TỔNG CỘNG</td>';
    echo '<td style="text-align: center;">' . $total_qty . '</td>';
    echo '<td style="text-align: right;">' . number_format($total_rev, 0, ',', '.') . '</td>';
    echo '<td style="text-align: right; color: #dc3545;">' . number_format($total_cost, 0, ',', '.') . '</td>';
    echo '<td style="text-align: right; color: #28a745;">' . number_format($total_profit, 0, ',', '.') . '</td>';
    echo '<td></td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
    exit();
}

// Hàm xuất danh sách khách hàng VIP ra Excel
function exportTopCustomers($from_date, $to_date, $conn) {
    $query = "SELECT 
        u.full_name,
        u.email,
        u.phone,
        COUNT(o.order_id) as total_orders,
        SUM(o.total_amount) as total_spent,
        AVG(o.total_amount) as avg_order_value
        FROM users u
        JOIN orders o ON u.user_id = o.user_id
        WHERE o.status = 'completed'
        AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
        GROUP BY u.user_id
        ORDER BY total_spent DESC";
    
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="KhachHangVIP_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<td colspan="6" style="font-size: 18px; font-weight: bold; text-align: center; background-color: #00704A; color: white;">DANH SÁCH KHÁCH HÀNG VIP</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center;">Từ ngày: ' . date('d/m/Y', strtotime($from_date)) . ' đến ngày: ' . date('d/m/Y', strtotime($to_date)) . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="6"></td></tr>';
    
    echo '<tr style="background-color: #d4af37; font-weight: bold;">';
    echo '<td style="width: 50px;">Hạng</td>';
    echo '<td style="width: 200px;">Họ tên</td>';
    echo '<td style="width: 200px;">Email</td>';
    echo '<td style="width: 120px;">Số điện thoại</td>';
    echo '<td style="width: 100px;">Số đơn</td>';
    echo '<td style="width: 150px;">Tổng chi tiêu (VNĐ)</td>';
    echo '</tr>';
    
    $rank = 1;
    $total_orders = 0;
    $total_spent = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td style="text-align: center;">';
        if ($rank <= 3) {
            $medals = ['🥇', '🥈', '🥉'];
            echo $medals[$rank - 1];
        } else {
            echo $rank;
        }
        echo '</td>';
        echo '<td>' . $row['full_name'] . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>' . ($row['phone'] ? $row['phone'] : 'N/A') . '</td>';
        echo '<td style="text-align: center;">' . $row['total_orders'] . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['total_spent'], 0, ',', '.') . '</td>';
        echo '</tr>';
        
        $rank++;
        $total_orders += $row['total_orders'];
        $total_spent += $row['total_spent'];
    }
    
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<td colspan="4">TỔNG CỘNG</td>';
    echo '<td style="text-align: center;">' . $total_orders . '</td>';
    echo '<td style="text-align: right;">' . number_format($total_spent, 0, ',', '.') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
    exit();
}
?>
