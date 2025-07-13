// Dashboard Admin - JavaScript pour améliorer l'UX

document.addEventListener('DOMContentLoaded', function() {
    // Confirmation personnalisée pour la suppression
    const deleteButtons = document.querySelectorAll('form[action*="delete"] button[type="submit"]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const articleTitle = this.getAttribute('data-article-title');
            
            if (confirm(`Êtes-vous vraiment sûr de vouloir supprimer l'article "${articleTitle}" ?\n\nCette action est irréversible et supprimera également les métadonnées associées dans MongoDB.`)) {
                form.submit();
            }
        });
    });
    
    // Effet de hover sur les lignes du tableau
    const tableRows = document.querySelectorAll('.admin-dashboard tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Auto-dismiss des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
