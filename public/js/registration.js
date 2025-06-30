document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    const messages = document.getElementById('form-messages');
    const button = document.getElementById('submitButton');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        messages.innerHTML = '';
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        button.disabled = true;
        button.textContent = 'Inscription en cours...';

        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, { method: 'POST', body: formData });
            const data = await res.json();

            if (res.ok && data.success) {
                messages.innerHTML = `<div class="alert alert-success">Inscription réussie ! Redirection...</div>`;
                form.reset();
                setTimeout(() => window.location.href = data.redirect || '/login', 1500);
            } else if (data.errors) {
                Object.entries(data.errors).forEach(([field, errors]) => {
                    const input = form.querySelector(`[name$="[${field}]"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const div = document.createElement('div');
                        div.className = 'invalid-feedback';
                        div.textContent = errors.join(' ');
                        input.parentNode.appendChild(div);
                    }
                });
                if (data.message) {
                    messages.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            } else {
                messages.innerHTML = `<div class="alert alert-warning">${data.message || "Erreur inattendue."}</div>`;
            }
        } catch {
            messages.innerHTML = `<div class="alert alert-danger">Erreur réseau. Veuillez réessayer.</div>`;
        } finally {
            button.disabled = false;
            button.textContent = "S'inscrire";
        }
    });
});
