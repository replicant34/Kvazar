<?php
session_start();
require_once '../config/form_access.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - KVAZAR LOGISTICS</title>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/analytics_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.0/index.min.js"></script>
</head>
<body>
    <?php include '../elements/admin_sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../elements/admin_navbar.php'; ?>
        
        <div class="dashboard-wrapper">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-chart-bar"></i> Analytics Dashboard</h1>
                    <p>Comprehensive business analytics and performance metrics</p>
                </div>
                
                <div class="header-controls">
                    <div class="date-range-selector">
                        <select id="dateRangeSelect">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                            <option value="custom">Custom range</option>
                        </select>
                        
                        <div id="customDateRange" style="display: none;">
                            <input type="date" id="startDate" />
                            <input type="date" id="endDate" />
                            <button id="applyDateRange" class="btn-primary">Apply</button>
                        </div>
                    </div>
                    
                    <div class="export-controls">
                        <div class="dropdown">
                            <button class="btn-secondary dropdown-toggle">
                                <i class="fas fa-download"></i> Export Reports
                            </button>
                            <div class="dropdown-menu">
                                <button onclick="exportReport('overview')">Overview Report</button>
                                <button onclick="exportReport('clients')">Clients Report</button>
                                <button onclick="exportReport('couriers')">Couriers Report</button>
                                <button onclick="exportReport('orders')">Orders Report</button>
                            </div>
                        </div>
                    </div>
                    
                    <button id="refreshDashboard" class="btn-primary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="dashboardLoading" class="loading-overlay">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading dashboard data...</p>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-section">
                <div class="kpi-card">
                    <div class="kpi-icon total-orders">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="totalOrders">0</div>
                        <div class="kpi-label">Total Orders</div>
                        <div class="kpi-change" id="totalOrdersChange">+0%</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="totalRevenue">$0</div>
                        <div class="kpi-label">Total Revenue</div>
                        <div class="kpi-change" id="revenueChange">+0%</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="completedOrders">0</div>
                        <div class="kpi-label">Completed Orders</div>
                        <div class="kpi-change" id="completedChange">+0%</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon avg-value">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="avgOrderValue">$0</div>
                        <div class="kpi-label">Avg Order Value</div>
                        <div class="kpi-change" id="avgValueChange">+0%</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-row">
                    <!-- Daily Trends Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Daily Order Trends</h3>
                            <div class="chart-controls">
                                <button class="chart-toggle active" data-chart="orders">Orders</button>
                                <button class="chart-toggle" data-chart="revenue">Revenue</button>
                            </div>
                        </div>
                        <div class="chart-content">
                            <canvas id="dailyTrendsChart"></canvas>
                        </div>
                    </div>

                    <!-- Status Distribution Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Order Status Distribution</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-row">
                    <!-- Top Clients Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Top Clients by Orders</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="topClientsChart"></canvas>
                        </div>
                    </div>

                    <!-- Courier Performance Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Courier Performance</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="courierPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="tables-section">
                <div class="table-row">
                    <!-- Recent Activity -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        </div>
                        <div class="table-content">
                            <div id="recentActivityTable" class="activity-list">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Top Performers -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-trophy"></i> Top Performers</h3>
                            <div class="table-tabs">
                                <button class="tab-btn active" data-tab="clients">Clients</button>
                                <button class="tab-btn" data-tab="couriers">Couriers</button>
                            </div>
                        </div>
                        <div class="table-content">
                            <div id="topPerformersTable">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Analysis Section -->
            <div class="revenue-section">
                <div class="section-header">
                    <h2><i class="fas fa-money-bill-wave"></i> Revenue Analysis</h2>
                </div>
                
                <div class="revenue-cards">
                    <div class="revenue-card">
                        <div class="revenue-header">
                            <h4>Monthly Comparison</h4>
                        </div>
                        <div class="revenue-content">
                            <div class="month-comparison">
                                <div class="current-month">
                                    <span class="month-label">This Month</span>
                                    <span class="month-value" id="currentMonthRevenue">$0</span>
                                </div>
                                <div class="growth-indicator" id="monthlyGrowth">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>0%</span>
                                </div>
                                <div class="previous-month">
                                    <span class="month-label">Last Month</span>
                                    <span class="month-value" id="previousMonthRevenue">$0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="revenue-card">
                        <div class="revenue-header">
                            <h4>Revenue by Shipping Type</h4>
                        </div>
                        <div class="revenue-content">
                            <canvas id="shippingRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/analytics_dashboard.js"></script>
</body>
</html> 