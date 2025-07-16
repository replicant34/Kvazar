// Analytics Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let dashboardData = null;
    let currentDateRange = '30';
    let customDateRange = null;
    let charts = {};
    
    // Initialize dashboard
    initializeDashboard();
    
    function initializeDashboard() {
        setupEventListeners();
        loadDashboardData();
    }
    
    function setupEventListeners() {
        // Date range selector
        const dateRangeSelect = document.getElementById('dateRangeSelect');
        const customDateRangeDiv = document.getElementById('customDateRange');
        const applyDateRangeBtn = document.getElementById('applyDateRange');
        const refreshBtn = document.getElementById('refreshDashboard');
        
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRangeDiv.style.display = 'flex';
            } else {
                customDateRangeDiv.style.display = 'none';
                currentDateRange = this.value;
                customDateRange = null;
                loadDashboardData();
            }
        });
        
        applyDateRangeBtn.addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                customDateRange = { start: startDate, end: endDate };
                loadDashboardData();
            } else {
                alert('Please select both start and end dates');
            }
        });
        
        refreshBtn.addEventListener('click', function() {
            loadDashboardData();
        });
        
        // Chart toggle buttons
        const chartToggles = document.querySelectorAll('.chart-toggle');
        chartToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const chartType = this.dataset.chart;
                const container = this.closest('.chart-container');
                const toggles = container.querySelectorAll('.chart-toggle');
                
                toggles.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                updateDailyTrendsChart(chartType);
            });
        });
        
        // Tab buttons
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.dataset.tab;
                const container = this.closest('.table-container');
                const tabBtns = container.querySelectorAll('.tab-btn');
                
                tabBtns.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                updateTopPerformersTable(tab);
            });
        });
    }
    
    function showLoading() {
        document.getElementById('dashboardLoading').style.display = 'flex';
    }
    
    function hideLoading() {
        document.getElementById('dashboardLoading').style.display = 'none';
    }
    
    function loadDashboardData() {
        showLoading();
        
        let url = '../assets/get_dashboard_statistics.php';
        let params = new URLSearchParams();
        
        if (customDateRange) {
            params.append('start_date', customDateRange.start);
            params.append('end_date', customDateRange.end);
        } else {
            params.append('date_range', currentDateRange);
        }
        
        fetch(`${url}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dashboardData = data.statistics;
                    updateDashboard();
                } else {
                    console.error('Error loading dashboard data:', data.error);
                    alert('Error loading dashboard data: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
                alert('Error loading dashboard data');
            })
            .finally(() => {
                hideLoading();
            });
    }
    
    function updateDashboard() {
        updateKPICards();
        updateCharts();
        updateTables();
        updateRevenueAnalysis();
    }
    
    function updateKPICards() {
        const overall = dashboardData.overall;
        const monthlyComparison = dashboardData.monthly_comparison;
        
        // Total Orders
        document.getElementById('totalOrders').textContent = formatNumber(overall.total_orders);
        updateKPIChange('totalOrdersChange', monthlyComparison.order_growth);
        
        // Total Revenue
        document.getElementById('totalRevenue').textContent = formatCurrency(overall.completed_revenue);
        updateKPIChange('revenueChange', monthlyComparison.revenue_growth);
        
        // Completed Orders
        document.getElementById('completedOrders').textContent = formatNumber(overall.completed_orders);
        
        // Average Order Value
        document.getElementById('avgOrderValue').textContent = formatCurrency(overall.avg_order_value);
    }
    
    function updateKPIChange(elementId, change) {
        const element = document.getElementById(elementId);
        const isPositive = change >= 0;
        
        element.textContent = (isPositive ? '+' : '') + change.toFixed(1) + '%';
        element.className = 'kpi-change ' + (isPositive ? 'positive' : 'negative');
    }
    
    function updateCharts() {
        createDailyTrendsChart();
        createStatusDistributionChart();
        createTopClientsChart();
        createCourierPerformanceChart();
        createShippingRevenueChart();
    }
    
    function createDailyTrendsChart() {
        const ctx = document.getElementById('dailyTrendsChart').getContext('2d');
        const trends = dashboardData.daily_trends;
        
        if (charts.dailyTrends) {
            charts.dailyTrends.destroy();
        }
        
        const labels = trends.map(trend => formatDate(trend.order_date));
        const ordersData = trends.map(trend => parseInt(trend.total_orders));
        const revenueData = trends.map(trend => parseFloat(trend.daily_revenue));
        
        charts.dailyTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Orders',
                    data: ordersData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Revenue',
                    data: revenueData,
                    borderColor: '#f093fb',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4,
                    fill: true,
                    hidden: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function updateDailyTrendsChart(type) {
        if (!charts.dailyTrends) return;
        
        const datasets = charts.dailyTrends.data.datasets;
        if (type === 'orders') {
            datasets[0].hidden = false;
            datasets[1].hidden = true;
        } else {
            datasets[0].hidden = true;
            datasets[1].hidden = false;
        }
        
        charts.dailyTrends.update();
    }
    
    function createStatusDistributionChart() {
        const ctx = document.getElementById('statusDistributionChart').getContext('2d');
        const statusData = dashboardData.status_distribution;
        
        if (charts.statusDistribution) {
            charts.statusDistribution.destroy();
        }
        
        const labels = statusData.map(status => status.Status_name);
        const data = statusData.map(status => parseInt(status.count));
        const colors = statusData.map(status => status.Status_color || '#667eea');
        
        charts.statusDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                }
            }
        });
    }
    
    function createTopClientsChart() {
        const ctx = document.getElementById('topClientsChart').getContext('2d');
        const clientsData = dashboardData.top_clients.slice(0, 5); // Top 5
        
        if (charts.topClients) {
            charts.topClients.destroy();
        }
        
        const labels = clientsData.map(client => 
            client.client_name.length > 20 ? 
            client.client_name.substring(0, 20) + '...' : 
            client.client_name
        );
        const data = clientsData.map(client => parseInt(client.order_count));
        
        charts.topClients = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Orders',
                    data: data,
                    backgroundColor: '#667eea',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    }
                }
            }
        });
    }
    
    function createCourierPerformanceChart() {
        const ctx = document.getElementById('courierPerformanceChart').getContext('2d');
        const courierData = dashboardData.courier_performance.slice(0, 5); // Top 5
        
        if (charts.courierPerformance) {
            charts.courierPerformance.destroy();
        }
        
        const labels = courierData.map(courier => 
            courier.courier_name.length > 20 ? 
            courier.courier_name.substring(0, 20) + '...' : 
            courier.courier_name
        );
        const data = courierData.map(courier => parseFloat(courier.completion_rate));
        
        charts.courierPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Completion Rate %',
                    data: data,
                    backgroundColor: '#4facfe',
                    borderColor: '#4facfe',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function createShippingRevenueChart() {
        const ctx = document.getElementById('shippingRevenueChart').getContext('2d');
        const shippingData = dashboardData.shipping_revenue;
        
        if (charts.shippingRevenue) {
            charts.shippingRevenue.destroy();
        }
        
        const labels = shippingData.map(shipping => shipping.Shipping_type_name);
        const data = shippingData.map(shipping => parseFloat(shipping.total_revenue));
        
        const colors = [
            '#667eea', '#f093fb', '#4facfe', '#43e97b', 
            '#f5576c', '#38f9d7', '#ffd93d', '#ff6b6b'
        ];
        
        charts.shippingRevenue = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 10
                        }
                    }
                }
            }
        });
    }
    
    function updateTables() {
        updateRecentActivityTable();
        updateTopPerformersTable('clients');
    }
    
    function updateRecentActivityTable() {
        const container = document.getElementById('recentActivityTable');
        const activities = dashboardData.recent_activity;
        
        if (activities.length === 0) {
            container.innerHTML = '<div class="no-activity">No recent activity</div>';
            return;
        }
        
        let html = '';
        activities.forEach(activity => {
            html += `
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: ${activity.new_status_color || '#667eea'}">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            Order ${activity.display_order_number} status changed
                        </div>
                        <div class="activity-details">
                            ${activity.client_name} • ${activity.previous_status || 'New'} → ${activity.new_status}
                            ${activity.changed_by ? ' by ' + activity.changed_by : ''}
                        </div>
                    </div>
                    <div class="activity-time">${activity.Change_date}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    function updateTopPerformersTable(type) {
        const container = document.getElementById('topPerformersTable');
        const data = type === 'clients' ? dashboardData.top_clients : dashboardData.courier_performance;
        
        if (data.length === 0) {
            container.innerHTML = '<div class="no-data">No data available</div>';
            return;
        }
        
        let html = '<div class="performers-list">';
        
        data.slice(0, 8).forEach((item, index) => {
            const name = type === 'clients' ? item.client_name : item.courier_name;
            const metric = type === 'clients' ? 
                `${item.order_count} orders • ${formatCurrency(item.total_revenue)}` :
                `${item.completion_rate}% completion • ${item.assigned_orders} orders`;
            
            html += `
                <div class="performer-item">
                    <div class="performer-rank">${index + 1}</div>
                    <div class="performer-info">
                        <div class="performer-name" title="${name}">${name}</div>
                        <div class="performer-stats">${metric}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    function updateRevenueAnalysis() {
        const monthlyComparison = dashboardData.monthly_comparison;
        
        // Monthly comparison
        document.getElementById('currentMonthRevenue').textContent = 
            formatCurrency(monthlyComparison.current_month.revenue);
        document.getElementById('previousMonthRevenue').textContent = 
            formatCurrency(monthlyComparison.previous_month.revenue);
        
        const growthElement = document.getElementById('monthlyGrowth');
        const growthValue = monthlyComparison.revenue_growth;
        const isPositive = growthValue >= 0;
        
        growthElement.className = 'growth-indicator ' + (isPositive ? 'positive' : 'negative');
        growthElement.querySelector('span').textContent = Math.abs(growthValue).toFixed(1) + '%';
    }
    
    // Export functions
    window.exportReport = function(reportType) {
        let url = '../assets/export_dashboard_report.php';
        let params = new URLSearchParams();
        
        params.append('type', 'csv');
        params.append('report', reportType);
        
        if (customDateRange) {
            params.append('start_date', customDateRange.start);
            params.append('end_date', customDateRange.end);
        } else {
            params.append('date_range', currentDateRange);
        }
        
        window.open(`${url}?${params}`, '_blank');
    };
    
    // Utility functions
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(num);
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    // Auto-refresh every 5 minutes
    setInterval(() => {
        loadDashboardData();
    }, 5 * 60 * 1000);
});

// Additional CSS for performers table
const additionalCSS = `
.performers-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.performer-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.performer-item:hover {
    background: #e9ecef;
}

.performer-rank {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    margin-right: 12px;
}

.performer-info {
    flex: 1;
    min-width: 0;
}

.performer-name {
    font-weight: 600;
    color: #333;
    font-size: 14px;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.performer-stats {
    font-size: 12px;
    color: #666;
}

.no-activity, .no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px;
}
`;

// Inject additional CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style); 