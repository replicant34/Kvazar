<?php
// Table controls
?>
<div class="mc-table-controls">
    <div class="mc-table-controls-left">
        <input type="text" id="userTableFilter" placeholder="Фильтр по имени, почте, роли..." style="margin-bottom: 0; width: 300px; padding: 6px;">
    </div>
    <div class="mc-table-controls-right">
        <button id="mc-download-csv" class="mc-btn-download">Скачать CSV</button>
    </div>
</div>

<div class="mc-table-container">
    <table class="mc-clients-table">
        <thead>
            <tr>
                <th class="sortable">ID</th>
                <th class="sortable">Имя</th>
                <th class="sortable">Партнер</th>
                <th class="sortable">Должность</th>
                <th class="sortable">Телефон</th>
                <th class="sortable">Почта</th>
                <th class="sortable">Логин</th>
                <th class="sortable">Роль</th>
                <th class="sortable">Создан</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['User_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['Full_name']); ?></td>
                    <td data-partner-id="<?php echo htmlspecialchars($user['Client_id']); ?>" data-partner-type="client">
                        <?php if ($user['Client_id']): ?>
                            <a href="#" class="partner-id-link" data-partner-id="<?php echo htmlspecialchars($user['Client_id']); ?>" data-partner-type="client">
                                <?php echo htmlspecialchars($user['Client_id']); ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['Position']); ?></td>
                    <td><?php echo htmlspecialchars($user['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                    <td><?php echo htmlspecialchars($user['Login']); ?></td>
                    <td><?php echo htmlspecialchars($user['Role']); ?></td>
                    <td><?php echo date('d.m.Y', strtotime($user['Formatted_date'])); ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="dropbtn"><i class="fas fa-ellipsis-v"></i></button>
                            <div class="dropdown-content">
                                <a href="#" class="edit" data-id="<?php echo $user['User_id']; ?>">Изменить</a>
                                <a href="#" class="delete" data-id="<?php echo $user['User_id']; ?>">Удалить</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mc-pagination">
    <button id="mc-scroll-left" class="mc-slider-btn"><i class="fas fa-chevron-left"></i></button>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <button id="mc-scroll-right" class="mc-slider-btn"><i class="fas fa-chevron-right"></i></button>
</div> 