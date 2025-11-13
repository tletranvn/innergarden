document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('commentForm');
    const commentsList = document.getElementById('comments-list');
    const commentFormMessages = document.getElementById('comment-form-messages');
    const submitCommentButton = document.getElementById('submitCommentButton');

    // Fonction pour afficher un message (succès ou erreur)
    function displayMessage(container, message, type) {
        container.innerHTML = `<div class="alert alert-${type}" role="alert">${message}</div>`;
        // Supprime le message après 5 secondes
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    // Fonction pour vider les messages précédents et les erreurs de champs
    function clearFormErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
    }

    // Fonction pour échapper les caractères HTML dangereux (Protection XSS)
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Fonction pour créer l'élément HTML d'un nouveau commentaire
    function createCommentElement(commentData) {
        const commentDiv = document.createElement('div');
        commentDiv.classList.add('card', 'mb-3', 'comment-item');
        commentDiv.id = `comment-${commentData.id}`;

        // SÉCURITÉ : Échapper toutes les données utilisateur avant l'insertion
        commentDiv.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">
                    ${escapeHtml(commentData.authorPseudo)}
                    <small class="text-muted">- ${escapeHtml(commentData.createdAt)}</small>
                </h5>
                <p class="card-text">${escapeHtml(commentData.content)}</p>
            </div>
        `;
        return commentDiv;
    }

    if (commentForm) {
        commentForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Empêche la soumission normale du formulaire
            clearFormErrors(commentForm); // Efface les erreurs précédentes
            commentFormMessages.innerHTML = ''; // Efface les messages globaux

            submitCommentButton.disabled = true; // Désactive le bouton
            submitCommentButton.textContent = 'Envoi en cours...'; // Texte d'attente

            const formData = new FormData(commentForm); // ← Récupère toutes les données du formulaire

            try {
                const response = await fetch(commentForm.action, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json(); // Attend et parse la réponse JSON

                if (response.ok) { // Si la réponse HTTP est un succès (code 2xx)
                    if (data.success) {
                        displayMessage(commentFormMessages, data.message, 'success');
                        commentForm.reset(); // Vide le formulaire après succès

                        // Crée et ajoute le nouveau commentaire à la liste
                        const newCommentElement = createCommentElement(data.comment);
                        commentsList.prepend(newCommentElement); // Ajoute au début de la liste
                        
                        // Si le message "Soyez le premier à commenter !" est présent, le supprimer
                        const noCommentsMessage = commentsList.querySelector('.text-muted');
                        if (noCommentsMessage && noCommentsMessage.textContent.includes('Soyez le premier à commenter')) {
                            noCommentsMessage.remove();
                        }

                    } else if (data.message) {
                        displayMessage(commentFormMessages, data.message, 'warning');
                    }
                } else { // Si la réponse HTTP indique une erreur (4xx, 5xx)
                    if (data.errors) {
                        // Afficher les erreurs de validation spécifiques aux champs
                        for (const fieldName in data.errors) {
                            const fieldErrors = data.errors[fieldName];
                            const inputElement = commentForm.querySelector(`[name$="[${fieldName}]"]`);
                            if (inputElement) {
                                inputElement.classList.add('is-invalid');
                                const errorDiv = document.createElement('div');
                                errorDiv.classList.add('invalid-feedback');
                                errorDiv.textContent = fieldErrors.join(' '); // Affiche toutes les erreurs pour ce champ
                                inputElement.parentNode.appendChild(errorDiv);
                            } else {
                                // Erreur non liée à un champ spécifique, l'afficher en haut
                                displayMessage(commentFormMessages, `${fieldName}: ${fieldErrors.join(' ')}`, 'danger');
                            }
                        }
                    } else if (data.message) {
                        // Afficher un message d'erreur général du serveur
                        displayMessage(commentFormMessages, data.message, 'danger');
                    } else {
                        displayMessage(commentFormMessages, 'Une erreur inattendue est survenue lors de l\'envoi du commentaire.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la soumission du commentaire :', error);
                displayMessage(commentFormMessages, 'Une erreur de communication est survenue. Veuillez réessayer.', 'danger');
            } finally {
                submitCommentButton.disabled = false; // Réactive le bouton
                submitCommentButton.textContent = 'Envoyer le commentaire'; // Restaure le texte du bouton
            }
        });
    }
}); 