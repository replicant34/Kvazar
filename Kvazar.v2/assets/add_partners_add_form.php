<div class="form-row">
    <!-- Partner Type Selector -->
    <div class="form-group partner-type-selector">
        <label for="partner_type">Тип партнера</label>
        <select id="partner_type" name="partner_type" required>
            <option value="" disabled selected>Выберите тип партнера</option>
            <option value="client">Клиент</option>
            <option value="courier">Перевозчик</option>
            <option value="agent">Агент</option>
        </select>
    </div>
</div>

<div class="form-row">
    <!-- Company Information Column -->
    <div class="form-column">
        <h2 class="column-header">Информация о компании</h2>
        
        <div class="form-group">
            <label for="company_type">Тип компании</label>
            <select id="company_type" name="company_type" required>
                <option value="ООО">ООО</option>
                <option value="ИП">ИП</option>
                <option value="Самозанятый">Самозанятый</option>
            </select>
        </div>

        <div class="form-group">
            <label for="full_company_name">Полное название компании</label>
            <input type="text" id="full_company_name" name="full_company_name" required placeholder="Введите полное название компании">
        </div>

        <div class="form-group">
            <label for="short_company_name">Краткое название компании</label>
            <input type="text" id="short_company_name" name="short_company_name" placeholder="Введите краткое название компании">
        </div>

        <div class="form-group">
            <label for="inn">ИНН</label>
            <input type="text" id="inn" name="inn" placeholder="Введите ИНН">
        </div>

        <div class="form-group">
            <label for="kpp">КПП</label>
            <input type="text" id="kpp" name="kpp" placeholder="Введите КПП">
        </div>

        <div class="form-group">
            <label for="ogrn">ОГРН</label>
            <input type="text" id="ogrn" name="ogrn" placeholder="Введите ОГРН">
        </div>

        <div class="form-group">
            <label for="physical_address">Физический адрес</label>
            <input type="text" id="physical_address" name="physical_address" placeholder="Введите физический адрес">
        </div>

        <div class="form-group">
            <label for="legal_address">Юридический адрес</label>
            <input type="text" id="legal_address" name="legal_address" placeholder="Введите юридический адрес">
        </div>
    </div>

    <!-- Contact and Bank Information Column -->
    <div class="form-column">
        <h2 class="column-header">Контактная информация и банковские реквизиты</h2>

        <div class="form-group">
            <label for="bank_name">Название банка</label>
            <input type="text" id="bank_name" name="bank_name" class="autocomplete" placeholder="Введите название банка">
        </div>

        <div class="form-group">
            <label for="bik">БИК</label>
            <input type="text" id="bik" name="bik" class="autocomplete" placeholder="Введите БИК">
        </div>

        <div class="form-group">
            <label for="settlement_account">Расчетный счет</label>
            <input type="text" id="settlement_account" name="settlement_account" placeholder="Введите расчетный счет">
        </div>

        <div class="form-group">
            <label for="correspondent_account">Корреспондентский счет</label>
            <input type="text" id="correspondent_account" name="correspondent_account" placeholder="Введите корреспондентский счет">
        </div>

        <div class="form-group">
            <label for="contact_person">Контактное лицо</label>
            <input type="text" id="contact_person" name="contact_person" placeholder="Введите контактное лицо">
        </div>

        <div class="form-group">
            <label for="contact_person_position">Должность контактного лица</label>
            <input type="text" id="contact_person_position" name="contact_person_position" class="autocomplete" placeholder="Введите должность контактного лица">
        </div>

        <div class="form-group">
            <label for="contact_person_phone">Телефон контактного лица</label>
            <input type="text" id="contact_person_phone" name="contact_person_phone" placeholder="Введите телефон контактного лица">
        </div>

        <div class="form-group">
            <label for="contact_person_email">Email контактного лица</label>
            <input type="email" id="contact_person_email" name="contact_person_email" placeholder="Введите email контактного лица">
        </div>

        <div class="form-group">
            <label for="head_position">Должность руководителя</label>
            <input type="text" id="head_position" name="head_position" class="autocomplete" placeholder="Введите должность руководителя">
        </div>

        <div class="form-group">
            <label for="head_name">ФИО руководителя</label>
            <input type="text" id="head_name" name="head_name" placeholder="Введите ФИО руководителя">
        </div>
    </div>
</div> 