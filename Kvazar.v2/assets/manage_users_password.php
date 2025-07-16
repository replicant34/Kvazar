<?php
// Password Verification Modal Component
?>
<div id="passwordModal" class="mc-modal">
    <div class="mc-modal-content">
        <span class="mc-close" id="passwordModalClose">&times;</span>
        <h2>Введите пароль для подтверждения действия</h2>
        <form id="passwordForm">
            <input type="hidden" id="actionType" name="action_type">
            <input type="password" id="actionPassword" name="action_password" placeholder="Пароль" required>
            <button type="submit" class="mc-btn-submit">Подтвердить</button>
        </form>
        <div id="passwordError" style="color: red; display: none; margin-top: 10px;"></div>
    </div>
</div> 