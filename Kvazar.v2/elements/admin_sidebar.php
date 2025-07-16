<nav id="sidebar" class="collapsed">
    <div class="sidebar-header">
        <div class="user-info">
            <img src="../images/logo.png" alt="User Avatar" class="avatar">
            <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
            <span>Администратор</span>
        </div>
    </div>

    <div class="sidebar-content">
        <ul class="list-unstyled components">
            <li>
                <a href="/Kvazar.v2/pages/admin_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Панель управления</span>
                </a>
            </li>
            
            <li>
                <a href="/Kvazar.v2/pages/analytics_dashboard.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Аналитика</span>
                </a>
            </li>
            
            <li>
                <a href="#ordersSubmenu" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-box"></i>
                    <span>Заказы</span>
                </a>
                <ul class="collapse list-unstyled" id="ordersSubmenu">
                    <li>
                        <a href="/Kvazar.v2/pages/add_order.php">
                            <i class="fas fa-plus"></i>
                            <span>Новый заказ</span>
                        </a>
                    </li>
                    <li>
                        <a href="/Kvazar.v2/pages/manage_orders.php">
                            <i class="fas fa-tasks"></i>
                            <span>Управление заказами</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="#usersSubmenu" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-users"></i>
                    <span>Пользователи</span>
                </a>
                <ul class="collapse list-unstyled" id="usersSubmenu">
                    <li>
                        <a href="/Kvazar.v2/pages/add_user.php"><i class="fas fa-user-plus"></i> Добавить пользователя</a>
                    </li>
                    <li>
                        <a href="/Kvazar.v2/pages/manage_users.php"><i class="fas fa-user-cog"></i> Управление пользователями</a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="#clientsSubmenu" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-users"></i>
                    <span>Клиенты</span>
                </a>
                <ul class="collapse list-unstyled" id="clientsSubmenu">
                    <li>
                        <a href="/Kvazar.v2/pages/add_partner.php"><i class="fas fa-plus-circle"></i> Новый клиент</a>
                    </li>
                    <li>
                        <a href="/Kvazar.v2/pages/manage_partners.php"><i class="fas fa-tasks"></i> Управление партнерами</a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="#contractsSubmenu" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-file-contract"></i>
                    <span>Договоры</span>
                </a>
                <ul class="collapse list-unstyled" id="contractsSubmenu">
                    <li>
                        <a href="/Kvazar.v2/pages/add_contract.php">
                            <i class="fas fa-plus"></i>
                            <span>Новый договор</span>
                        </a>
                    </li>
                    <li>
                        <a href="/Kvazar.v2/pages/manage_contracts.php">
                            <i class="fas fa-tasks"></i>
                            <span>Управление договорами</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <a href="/Kvazar.v2/elements/logout.php" class="btn btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Выйти</span>
        </a>
    </div>
</nav>
