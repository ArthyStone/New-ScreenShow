<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? "ScreenShow" ?></title>
    <link rel="stylesheet" type="text/css" href="/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/css/<?= lcfirst($title) ?>.css">
    <link rel="icon" href="/resources/icon.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- HEADER -->
    <header>
        <span>
            <button class="toggle-sidebar" id="toggle-sidebar">
                <i class="fa-regular fa-compass"></i>
            </button>
            <h1><?= $title ?? "ScreenShow" ?></h1>
        </span>
        <div class="user-info">
            <div class="user-details">
                <span class="username"><?= $user_name ?? 'Utilisateur' ?></span>
                <span class="tickets"><?= number_format($user_tickets ?? 0, 0, ',', ' ') ?><i class="fa-solid fa-ticket"></i></span>
            </div>
            <img class="avatar" src="<?= $user_pfp ?? 'https://i.pinimg.com/170x/1d/ec/e2/1dece2c8357bdd7cee3b15036344faf5.jpg' ?>" alt="Avatar">
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="disabled">
        <div class="nav-links">
            <a href="/infos">
                <i class="fa-solid fa-circle-info<?= $title === "Infos" ? " fa-fade" : ""?>"></i>
                <span class="label">Informations</span>
            </a>
            <a href="/images">
                <i class="fa-solid fa-image<?= $title === "Images" ? " fa-fade" : ""?>"></i>
                <span class="label">Images</span>
            </a>
            <a href="/display">
                <i class="icon fa-solid fa-tower-broadcast"></i> <!-- Pas besoin de fade car c'est une page qui ne sera pas affichée via ce layout -->
                <span class="label">Direct</span>
            </a>
            <a href="/liste">
                <i class="icon fa-solid fa-list-check<?= $title === "Liste" ? " fa-fade" : ""?>"></i>
                <span class="label">Liste</span>
            </a>
            <a href="/ajouter">
                <i class="fa-solid fa-upload<?= $title === "Ajouter" ? " fa-fade" : ""?>"></i>
                <span class="label">Ajouter</span>
            </a>
            <a href="/redeem">
                <i class="icon fa-solid fa-key<?= $title === "Redeem" ? " fa-fade" : ""?>"></i>
                <span class="label">Coupons</span>
            </a>
            <?php if (isset($permissions) && (in_array("manageUploads", $permissions, true) || in_array("manageUsers", $permissions, true) || in_array("admin", $permissions, true))): ?>
            <a href="/dashboard">
                <i class="fa-solid fa-warehouse<?= $title === "Dashboard" ? " fa-fade" : ""?>"></i>
                <span class="label">Dashboard</span>
            </a>
            <?php endif; ?>
        </div>
        <div class="buttons">
            <button id="theme-toggle">
                <i class="fa-solid fa-moon"></i>
                <span class="label">Dark Mode</span>
            </button>
            <?php if (isset($user_name)): ?>
                <a href="/logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="label">Se déconnecter</span>
                </a>
            <?php else: ?>
                <a href="/login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span class="label">Se connecter</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <main>
        <?= $content ?>
    </main>
    <script src="scripts/<?= lcfirst($title) ?>.js"></script>
    <script>
        const toggleBtn = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('disabled');
        });
        const darkmodeToggleBtn = document.getElementById('theme-toggle');

        darkmodeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            if(document.body.classList.contains('dark')) {
                darkmodeToggleBtn.querySelector('i').classList.replace('fa-moon', 'fa-sun');
                darkmodeToggleBtn.querySelector('.label').textContent = 'Light Mode';
            } else {
                darkmodeToggleBtn.querySelector('i').classList.replace('fa-sun', 'fa-moon');
                darkmodeToggleBtn.querySelector('.label').textContent = 'Dark Mode';
            }

            // sauvegarde dans le navigateur
            localStorage.setItem(
                'theme',
                document.body.classList.contains('dark') ? 'dark' : 'light'
            );
        });

        // Au chargement de la page
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            darkmodeToggleBtn.querySelector('i').classList.replace('fa-moon', 'fa-sun');
            darkmodeToggleBtn.querySelector('.label').textContent = 'Light Mode';
        }
    </script>
</body>
</html>