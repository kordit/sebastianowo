<div class="village-layout">
    <h2>Witaj w swojej wiosce!</h2>
    <p>Jesteś mieszkańcem tej wioski.</p>
    <?php include('resident/main.php'); ?>
    <button
        type="button"
        id="leave-village-button"
        data-user-id="<?php echo esc_attr($current_user_id); ?>"
        data-post-id="<?php echo esc_attr($post_id); ?>"
        class="btn btn btn-red">
        Opuść wioskę
    </button>
    <?php if ($is_leader): ?>
        <div id="applicants-section" data-post-id="<?php echo esc_attr($post_id); ?>">
            <h2>Aplikacje do wioski</h2>
            <table id="applicants-table">
                <thead>
                    <tr>
                        <th>Imię i nazwisko</th>
                        <th>Email</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dane aplikantów będą ładowane dynamicznie -->
                </tbody>
            </table>
        </div>
        <div id="villagers-section" data-post-id="<?php echo esc_attr($post_id); ?>">
            <h2>Mieszkańcy wioski</h2>
            <table id="villagers-table">
                <thead>
                    <tr>
                        <th>Imię i nazwisko</th>
                        <th>Email</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dane mieszkańców będą ładowane dynamicznie -->
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>