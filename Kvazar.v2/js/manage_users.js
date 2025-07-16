document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('mc-editModal');
    const deleteModal = document.getElementById('mc-deleteModal');
    const editForm = document.getElementById('mc-editForm');
    const closeButtons = document.querySelectorAll('.mc-close');
    const downloadCsvButton = document.getElementById('mc-download-csv');

    // Password modal elements
    const passwordModal = document.getElementById('passwordModal');
    const passwordModalClose = document.getElementById('passwordModalClose');
    const passwordForm = document.getElementById('passwordForm');
    const actionTypeInput = document.getElementById('actionType');
    const actionPasswordInput = document.getElementById('actionPassword');
    const passwordError = document.getElementById('passwordError');

    // Fetch user data function
    function fetchUserData(userId) {
        fetch(`../assets/manage_users_get_users.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Ошибка: ' + data.error);
                    return;
                }
                // Populate form fields
                document.getElementById('editUserId').value = data.User_id;
                document.getElementById('editFullName').value = data.Full_name;
                document.getElementById('editClientId').value = data.Client_id;
                document.getElementById('editPosition').value = data.Position;
                document.getElementById('editPhone').value = data.Phone;
                document.getElementById('editEmail').value = data.Email;
                document.getElementById('editLogin').value = data.Login;
                document.getElementById('editRole').value = data.Role;
            })
            .catch(error => console.error('Ошибка:', error));
    }

    // Handle dropdown menu
    document.querySelectorAll('.dropbtn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
            this.nextElementSibling.classList.toggle('show');
        });
    });

    // Helper: Show/hide password modal
    function showPasswordModal(actionType, onSuccess) {
        actionTypeInput.value = actionType;
        actionPasswordInput.value = '';
        passwordError.style.display = 'none';
        passwordModal.style.display = 'block';
        passwordForm.onsubmit = function(e) {
            e.preventDefault();
            // Verify password via AJAX
            fetch('../assets/manage_users_verify_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action_type: actionTypeInput.value,
                    action_password: actionPasswordInput.value
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    passwordModal.style.display = 'none';
                    onSuccess();
                } else {
                    passwordError.textContent = data.error || 'Неверный пароль';
                    passwordError.style.display = 'block';
                }
            });
        };
    }
    passwordModalClose.onclick = () => { passwordModal.style.display = 'none'; };
    window.addEventListener('click', function(e) {
        if (e.target === passwordModal) passwordModal.style.display = 'none';
    });

    // Helper: Check if password is required for action
    function checkPasswordRequired(actionType, callback) {
        fetch('../assets/manage_users_check_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action_type: actionType })
        })
        .then(res => res.json())
        .then(data => {
            callback(data.request_password === 1);
        });
    }

    // Handle edit action
    document.querySelectorAll('.edit').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const userId = this.getAttribute('data-id');
            checkPasswordRequired('edit', function(required) {
                if (required) {
                    showPasswordModal('edit', function() {
                        fetchUserData(userId);
                        editModal.style.display = 'block';
                    });
                } else {
                    fetchUserData(userId);
                    editModal.style.display = 'block';
                }
            });
        });
    });

    // Handle delete action
    document.querySelectorAll('.delete').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const userId = this.getAttribute('data-id');
            checkPasswordRequired('delete', function(required) {
                if (required) {
                    showPasswordModal('delete', function() {
                        deleteModal.style.display = 'block';
                        document.getElementById('mc-confirmDelete').setAttribute('data-id', userId);
                    });
                } else {
                    deleteModal.style.display = 'block';
                    document.getElementById('mc-confirmDelete').setAttribute('data-id', userId);
                }
            });
        });
    });

    // Handle edit form submission
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../assets/manage_users_edit_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }
            alert('Успешно: ' + (data.success || 'Данные сохранены'));
            editModal.style.display = 'none';
            location.reload();
        })
        .catch(error => console.error('Ошибка:', error));
    });

    // Handle delete confirmation
    document.getElementById('mc-confirmDelete').addEventListener('click', function() {
        const userId = this.getAttribute('data-id');
        fetch('../assets/manage_users_delete_form.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ user_id: userId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }
            alert('Пользователь успешно удален');
            deleteModal.style.display = 'none';
            location.reload();
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при удалении пользователя');
        });
    });

    // Close modals with X button
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            editModal.style.display = 'none';
            deleteModal.style.display = 'none';
        });
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === editModal || e.target === deleteModal) {
            editModal.style.display = 'none';
            deleteModal.style.display = 'none';
        }
    });

    // Table scrolling controls
    document.getElementById('mc-scroll-left').addEventListener('click', function() {
        document.querySelector('.mc-table-container').scrollBy({
            left: -200,
            behavior: 'smooth'
        });
    });

    document.getElementById('mc-scroll-right').addEventListener('click', function() {
        document.querySelector('.mc-table-container').scrollBy({
            left: 200,
            behavior: 'smooth'
        });
    });

    // CSV download
    downloadCsvButton.addEventListener('click', function(event) {
        event.preventDefault();
        checkPasswordRequired('download_csv', function(required) {
            if (required) {
                showPasswordModal('download_csv', function() {
                    window.location.href = '../assets/manage_users_download_csv.php';
                });
            } else {
                window.location.href = '../assets/manage_users_download_csv.php';
            }
        });
    });

    // --- Filtering ---
    const userTableFilter = document.getElementById('userTableFilter');
    if (userTableFilter) {
        userTableFilter.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('.mc-clients-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // --- Sorting ---
    document.querySelectorAll('.mc-clients-table th.sortable').forEach((header, colIndex) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = header.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const asc = !header.classList.contains('asc');
            rows.sort((a, b) => {
                const cellA = a.children[colIndex].textContent.trim().toLowerCase();
                const cellB = b.children[colIndex].textContent.trim().toLowerCase();
                if (!isNaN(cellA) && !isNaN(cellB)) {
                    // Numeric sort
                    return asc ? (Number(cellA) - Number(cellB)) : (Number(cellB) - Number(cellA));
                }
                return asc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });
            rows.forEach(row => tbody.appendChild(row));
            table.querySelectorAll('th').forEach(th => th.classList.remove('asc', 'desc'));
            header.classList.add(asc ? 'asc' : 'desc');
        });
    });

    // Initialize partner ID links
    if (typeof refreshPartnerIdLinks === 'function') {
        refreshPartnerIdLinks();
    }
});