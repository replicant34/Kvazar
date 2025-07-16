<?php
// Edit User Modal Component
?>
<div id="mc-editModal" class="mc-modal">
    <div class="mc-modal-content">
        <span class="mc-close">&times;</span>
        <h2>Изменить пользователя</h2>
        <form id="mc-editForm">
            <input type="hidden" id="editUserId" name="user_id">
            <div class="mc-form-group">
                <label for="editFullName">Имя и фамилия</label>
                <input type="text" id="editFullName" name="full_name">
            </div>
            <div class="mc-form-group">
                <label for="editClientId">ID партнера</label>
                <input type="text" id="editClientId" name="client_id">
            </div>
            <div class="mc-form-group">
                <label for="editPosition">Должность</label>
                <input type="text" id="editPosition" name="position">
            </div>
            <div class="mc-form-group">
                <label for="editPhone">Телефон</label>
                <input type="text" id="editPhone" name="phone">
            </div>
            <div class="mc-form-group">
                <label for="editEmail">Почта</label>
                <input type="text" id="editEmail" name="email">
            </div>
            <div class="mc-form-group">
                <label for="editLogin">Логин</label>
                <input type="text" id="editLogin" name="login">
            </div>
            <div class="mc-form-group">
                <label for="editRole">Роль</label>
                <input type="text" id="editRole" name="role">
            </div>
            <button type="submit" class="mc-btn-submit">Сохранить</button>
        </form>
    </div>
</div> 