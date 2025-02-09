<?php
echo '<title>Strona logowania</title>';
?>
<div class="login-page-wrapper">
    <div class="container">
        <div class="inner">
            <img class="hero-location" src="/media/2024/08/DALL·E-2024-08-31-12.08.30-A-dark-and-mysterious-Slavic-village-scene-set-in-a-dense-forest-at-twilight-or-night.-The-village-consists-of-traditional-wooden-huts-with-thatched-.webp" alt="">
            <div class="content">
                <!-- <a class="btn-slice" href="#">
                <div class="top"><span>Sliced Button</span></div>
                <div class="bottom"><span>Sliced Button</span></div>
            </a> -->
                <h1>
                    Sebastianowo – Miasto Bez Praw, Ale Z Zasadami
                </h1>
                <p class="description">
                    Tu nie ma miejsca dla mięczaków. W Sebastianowie rządzą osiedlowe ekipy, a jeśli chcesz przetrwać, musisz się dostosować. Dołącz do ekipy albo staniesz się łatwym celem. Na blokach każdy zna swoje miejsce – pytanie, czy znajdziesz swoje, czy skończysz jako nikt.
                </p>
                <p class="description">
                    <strong>Zarejestruj swoje imię. Wybierz swoją ścieżkę. Stań się legendą.</strong>
                </p>
                <ul class="tabs">
                    <li class="tab-link btn active" data-tab="login-tab">Logowanie</li>
                    <li class="tab-link btn" data-tab="register-tab">Rejestracja</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="login-page">

    <div id="login-tab" class="tab-content">
        <div class="container">
            <h2>Zaloguj się do gry</h2>
            <?php wp_login_form(); ?>
        </div>
    </div>
    <div id="register-tab" class="tab-content">
        <div class="container">
            <h2>Zarejestruj sie</h2>
            <div class="close-popup-button">
                <?php et_svg('media/2024/08/close-ellipse-svgrepo-com.svg'); ?>
            </div>
            <form method="post" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>">
                <p>
                    <label for="user_login">Nazwa użytkownika</label>
                    <input type="text" name="user_login" id="user_login" class="input" value="" size="20" required>
                </p>
                <p>
                    <label for="user_email">Adres e-mail</label>
                    <input type="email" name="user_email" id="user_email" class="input" value="" size="25" required>
                </p>
                <p>
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Zarejestruj się">
                </p>
            </form>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tabLinks = document.querySelectorAll('.tab-link');
        var tabContents = document.querySelectorAll('.tab-content');

        tabContents.forEach(function(content) {
            var closeButton = document.createElement('div');
            closeButton.classList.add('close');
            closeButton.textContent = '×';
            closeButton.addEventListener('click', function() {
                content.classList.remove('active');
                tabLinks.forEach(function(link) {
                    if (link.getAttribute('data-tab') === content.id) {
                        link.classList.remove('active');
                    }
                });
            });
            content.appendChild(closeButton);
        });

        tabLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                tabLinks.forEach(function(link) {
                    link.classList.remove('active');
                });
                link.classList.add('active');

                tabContents.forEach(function(content) {
                    content.classList.remove('active');
                });

                var targetContent = document.getElementById(link.getAttribute('data-tab'));
                targetContent.classList.add('active');
            });
        });
    });
</script>