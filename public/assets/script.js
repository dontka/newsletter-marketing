/* ============================================
   SCRIPT.JS - Interactions et Utilitaires
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    initializeMobileMenu();
    initializeSearch();
    initializeNewsletterBuilder();
});

/**
 * Initialisation des formulaires
 */
function initializeForm() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-small"></span> Traitement...';
                
                // Réactiver après 2 secondes si pas de redirection
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 2000);
            }
        });
    });
}

/**
 * Menu mobile
 */
function initializeMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (menuToggle && navbarMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navbarMenu.classList.toggle('active');
            this.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!navbarMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                navbarMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navbarMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
    }
}

/**
 * Éditeur de contenu marketing
 */
function initializeNewsletterBuilder() {
    const editor = document.getElementById('contentEditor');
    const hiddenContent = document.getElementById('content');
    const previewFrame = document.getElementById('previewFrame');
    const toolbarButtons = document.querySelectorAll('.editor-toolbar-actions .format-btn');

    if (!editor || !hiddenContent || !previewFrame) {
        return;
    }

    const defaultContent = `
        <div style="font-family: Arial, sans-serif; max-width: 640px; margin: 0 auto; padding: 24px; background: #f7faff; border-radius: 24px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px; border-radius: 20px;">
                <h1 style="margin: 0 0 12px; font-size: 28px;">Votre message principal</h1>
                <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6;">Présentez votre offre, votre nouveauté ou votre événement avec une promesse claire.</p>
                <a href="#" style="display: inline-block; background: #fff; color: #667eea; padding: 12px 18px; border-radius: 999px; font-weight: 700; text-decoration: none;">Découvrir l’offre</a>
            </div>
            <div style="padding: 24px 0;">
                <h2 style="font-size: 20px; margin: 0 0 10px; color: #2d3748;">Pourquoi c’est utile</h2>
                <p style="margin: 0; color: #4a5568; line-height: 1.7;">Ajoutez ici un bénéfice, une preuve sociale ou un témoignage pour renforcer votre message.</p>
            </div>
        </div>
    `;

    function buildPreviewMarkup(markup) {
        return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { margin: 0; padding: 20px; background: #f7faff; font-family: Arial, sans-serif; }
    a { color: #667eea; }
  </style>
</head>
<body>${markup}</body>
</html>`;
    }

    function syncEditorToHidden() {
        const value = editor.innerHTML.trim() || defaultContent;
        hiddenContent.value = value;
        previewFrame.srcdoc = buildPreviewMarkup(value);
    }

    function runEditorCommand(command, value = null) {
        editor.focus();
        if (command === 'createLink') {
            const url = prompt('Entrez l’URL du lien', 'https://');
            if (!url) {
                return;
            }
            document.execCommand(command, false, url);
        } else if (command === 'insertImage') {
            insertImage();
        } else {
            document.execCommand(command, false, value);
        }
        syncEditorToHidden();
    }

    function syncHiddenToEditor() {
        const value = hiddenContent.value.trim();
        editor.innerHTML = value || defaultContent;
        previewFrame.srcdoc = buildPreviewMarkup(editor.innerHTML);
    }

    function insertBlock(type) {
        let html = '';
        switch (type) {
            case 'hero':
                html = `<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 24px; border-radius: 20px; margin-bottom: 16px;"><h2 style="margin: 0 0 8px; font-size: 24px;">Lancement important</h2><p style="margin: 0 0 14px; line-height: 1.6;">Présentez une offre exclusive ou un événement à ne pas manquer.</p><a href="#" style="display:inline-block; background:#fff; color:#667eea; padding:10px 16px; border-radius:999px; font-weight:700; text-decoration:none;">Réserver</a></div>`;
                break;
            case 'features':
                html = `<div style="padding: 18px 0; margin-bottom: 16px;"><h3 style="margin: 0 0 8px; color: #2d3748;">Vos bénéfices principaux</h3><ul style="margin: 0; padding-left: 20px; color: #4a5568; line-height: 1.7;"><li>Gain de temps</li><li>Résultat rapide</li><li>Expérience simple</li></ul></div>`;
                break;
            case 'cta':
                html = `<div style="background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 16px;"><h3 style="margin:0 0 8px; color:#2d3748;">Prêt à passer à l’action ?</h3><p style="margin:0 0 12px; color:#4a5568;">Cliquez sur le bouton ci-dessous pour découvrir l’offre.</p><a href="#" style="display:inline-block; background:#48bb78; color:#fff; padding:10px 16px; border-radius:999px; font-weight:700; text-decoration:none;">Je veux essayer</a></div>`;
                break;
            case 'testimonial':
                html = `<div style="background: #fff; border-left: 4px solid #667eea; padding: 16px 18px; border-radius: 12px; margin-bottom: 16px;"><p style="margin: 0 0 8px; color: #4a5568; font-style: italic;">“Ce que nous avons livré a vraiment transformé notre communication.”</p><strong style="color:#2d3748;">— Client satisfait</strong></div>`;
                break;
            default:
                html = '';
        }

        if (html) {
            editor.insertAdjacentHTML('beforeend', html);
            syncEditorToHidden();
        }
    }

    function applyTemplate(type) {
        let markup = defaultContent;
        if (type === 'promo') {
            markup = `<div style="font-family: Arial, sans-serif; max-width: 640px; margin: 0 auto; padding: 24px; background: #fff9f2; border-radius: 24px;"><div style="background: #ed8936; color: #fff; padding: 24px; border-radius: 20px;"><h1 style="margin: 0 0 10px; font-size: 28px;">Offre limitée</h1><p style="margin: 0 0 14px; line-height: 1.6;">Profitez d’une remise exclusive jusqu’à la fin de la semaine.</p><a href="#" style="display:inline-block; background:#fff; color:#ed8936; padding:10px 16px; border-radius:999px; font-weight:700; text-decoration:none;">Profiter de l’offre</a></div><div style="padding: 20px 0;"><h2 style="font-size: 20px; margin: 0 0 10px; color:#2d3748;">Ce qui vous attend</h2><p style="margin: 0; color:#4a5568; line-height:1.7;">Accès immédiat, accompagnement et support prioritaire.</p></div></div>`;
        }

        editor.innerHTML = markup;
        syncEditorToHidden();
    }

    function insertImage() {
        const imageUrl = prompt('URL de l’image à insérer dans le contenu (https://...)');
        if (!imageUrl) {
            return;
        }

        const sanitizedUrl = imageUrl.trim();
        if (!/^https?:\/\//i.test(sanitizedUrl)) {
            alert('Veuillez saisir une URL d’image valide commençant par http:// ou https://');
            return;
        }

        insertImageTag(sanitizedUrl);
    }

    function uploadImage() {
        const imageFileInput = document.getElementById('imageFileInput');
        if (imageFileInput) {
            imageFileInput.click();
        }
    }

    function insertImageFromUrlInput() {
        const imageUrlInput = document.getElementById('imageSourceUrl');
        if (!imageUrlInput) {
            insertImage();
            return;
        }

        const imageUrl = imageUrlInput.value.trim();
        if (!imageUrl) {
            alert('Veuillez saisir une URL d’image valide avant d’insérer.');
            return;
        }

        if (!/^https?:\/\//i.test(imageUrl)) {
            alert('Veuillez saisir une URL d’image valide commençant par http:// ou https://');
            return;
        }

        insertImageTag(imageUrl);
        imageUrlInput.value = '';
    }

    function handleImageUpload(event) {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner un fichier image valide.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target?.result;
            if (typeof dataUrl === 'string') {
                insertImageTag(dataUrl);
            }
        };
        reader.readAsDataURL(file);
    }

    function insertImageTag(src) {
        const imageHtml = `<div style="text-align:center; margin: 16px 0;"><img src="${src}" alt="Image de la newsletter" style="max-width:100%; height:auto; border-radius:16px;"></div>`;
        editor.insertAdjacentHTML('beforeend', imageHtml);
        syncEditorToHidden();
    }

    function loadTemplateById(templateId) {
        if (!window.savedEmailTemplates || !window.savedEmailTemplates.length) {
            alert('Aucun modèle enregistré disponible.');
            return;
        }

        const template = window.savedEmailTemplates.find(t => String(t.id) === String(templateId));
        if (!template) {
            alert('Modèle introuvable.');
            return;
        }

        editor.innerHTML = template.content || defaultContent;
        const subjectInput = document.getElementById('subject');
        if (subjectInput && template.subject) {
            subjectInput.value = template.subject;
        }
        syncEditorToHidden();
    }

    toolbarButtons.forEach(button => {
        button.addEventListener('click', () => {
            const command = button.getAttribute('data-command');
            const value = button.getAttribute('data-value');
            runEditorCommand(command, value);
            button.classList.toggle('is-active', ['bold', 'italic', 'underline'].includes(command));
        });
    });

    document.querySelectorAll('[data-insert-block]').forEach(button => {
        button.addEventListener('click', () => insertBlock(button.getAttribute('data-insert-block')));
    });

    document.querySelectorAll('[data-template-id]').forEach(button => {
        button.addEventListener('click', () => loadTemplateById(button.getAttribute('data-template-id')));
    });

    const imageInsertButton = document.querySelector('[data-action="insert-image"]');
    if (imageInsertButton) {
        imageInsertButton.addEventListener('click', insertImage);
    }

    const imageUrlInsertButton = document.querySelector('[data-action="insert-image-url"]');
    if (imageUrlInsertButton) {
        imageUrlInsertButton.addEventListener('click', insertImageFromUrlInput);
    }

    const imageUploadButton = document.querySelector('[data-action="upload-image"]');
    if (imageUploadButton) {
        imageUploadButton.addEventListener('click', uploadImage);
    }

    const fileInput = document.getElementById('imageFileInput');
    if (fileInput) {
        fileInput.addEventListener('change', handleImageUpload);
    }

    document.querySelectorAll('[data-action="reset"]').forEach(button => {
        button.addEventListener('click', () => {
            editor.innerHTML = defaultContent;
            syncEditorToHidden();
        });
    });

    editor.addEventListener('input', syncEditorToHidden);
    editor.addEventListener('blur', syncEditorToHidden);

    const form = hiddenContent.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            hiddenContent.value = editor.innerHTML.trim() || defaultContent;
        });
    }

    syncHiddenToEditor();
}

/**
 * Recherche avec délai
 */
function initializeSearch() {
    const searchInput = document.querySelector('input[type="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Optionnel: soumettre automatiquement après 500ms d'inactivité
            clearTimeout(searchInput.searchTimeout);
            searchInput.searchTimeout = setTimeout(() => {
                // Auto-submit could be added here if needed
            }, 500);
        });
    }
}

/**
 * Copier du texte dans le presse-papiers
 */
function copyToClipboard(text, feedback = '.copy-feedback') {
    navigator.clipboard.writeText(text).then(() => {
        const feedback_el = document.querySelector(feedback);
        if (feedback_el) {
            feedback_el.textContent = 'Copié !';
            setTimeout(() => {
                feedback_el.textContent = '';
            }, 2000);
        }
    }).catch(err => {
        console.error('Erreur lors de la copie:', err);
    });
}

/**
 * Afficher/masquer les mots de passe
 */
function togglePasswordVisibility(inputSelector) {
    const input = document.querySelector(inputSelector);
    if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
    }
}

/**
 * Valider un email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Afficher les notifications toast
 */
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * Confirmation avant suppression
 */
function confirmDelete(message = 'Êtes-vous sûr ?') {
    return confirm(message);
}

/**
 * Formatage de dates
 */
function formatDate(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(date).toLocaleDateString('fr-FR', options);
}

/**
 * Export des données en CSV
 */
function exportTableToCSV(filename) {
    const csv = [];
    const rows = document.querySelectorAll('table tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename || 'export.csv');
}

/**
 * Télécharger un fichier CSV
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Animation de nombres progressifs
 */
function animateNumber(element, target, duration = 1000) {
    const start = parseInt(element.textContent) || 0;
    const increment = (target - start) / (duration / 16);
    let current = start;
    
    const interval = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
            element.textContent = target;
            clearInterval(interval);
        } else {
            element.textContent = Math.ceil(current);
        }
    }, 16);
}

/**
 * Ajouter une classe avec animation
 */
function animateAdd(element, className, duration = 300) {
    element.classList.add(className);
    setTimeout(() => {
        element.classList.remove(className);
    }, duration);
}
