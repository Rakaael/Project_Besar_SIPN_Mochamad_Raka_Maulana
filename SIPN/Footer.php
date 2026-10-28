<!-- Footer.php hanya berisi script Bootstrap yang dibutuhkan agar komponen UI seperti navbar bisa berfungsi. --><?php /* Fungsi JavaScript di bawah memperbesar foto profil dan menangani penutupannya. */ ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const root = document.documentElement;

    function updateThemeToggle() {
        const isDark = root.getAttribute('data-theme') === 'dark';
        if (!themeToggle) {
            return;
        }
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeToggle.setAttribute('aria-label', isDark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap');
        themeToggle.querySelector('.sipn-theme-icon').textContent = isDark ? '☀' : '☾';
        themeToggle.querySelector('.sipn-theme-label').textContent = isDark ? 'Light' : 'Dark';
    }

    if (themeToggle && themeToggle.dataset.themeBound !== 'true') {
        themeToggle.dataset.themeBound = 'true';
        updateThemeToggle();
        themeToggle.addEventListener('click', function () {
            const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', nextTheme);
            try {
                localStorage.setItem('sipn-theme', nextTheme);
            } catch (error) {
                // Tema tetap aktif selama halaman ini terbuka.
            }
            updateThemeToggle();
        });
    }

    const profileImages = document.querySelectorAll('.profile-photo[src]');
    if (!profileImages.length) {
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'profile-photo-zoom-modal hidden';
    modal.setAttribute('aria-hidden', 'true');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'profile-photo-zoom-close';
    closeBtn.setAttribute('aria-label', 'Tutup foto profil');
    closeBtn.innerHTML = '&times;';

    const modalImage = document.createElement('img');
    modalImage.alt = 'Foto profil diperbesar';

    modal.appendChild(closeBtn);
    modal.appendChild(modalImage);
    document.body.appendChild(modal);

    function openModal(image) {
        modalImage.src = image.src;
        modalImage.alt = image.alt || 'Foto profil diperbesar';
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    profileImages.forEach(function (image) {
        image.classList.add('profile-photo-clickable');
        image.setAttribute('role', 'button');
        image.setAttribute('tabindex', '0');

        image.addEventListener('click', function () {
            openModal(image);
        });

        image.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(image);
            }
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>
</body>
</html>
