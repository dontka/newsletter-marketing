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
    const editorElement = document.getElementById('contentEditor');
    const hiddenContent = document.getElementById('content');
    const previewFrame = document.getElementById('previewFrame');
    const toolbarElement = document.getElementById('quillToolbar');

    if (!editorElement || !hiddenContent || !previewFrame || !toolbarElement || !window.Quill) {
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

    const quill = new Quill(editorElement, {
        theme: 'snow',
        modules: {
            toolbar: toolbarElement
        }
    });

    const imageEditControls = document.getElementById('imageEditControls');
    const imageWidthRange = document.getElementById('imageWidthRange');
    const imageAlignButtons = document.querySelectorAll('[data-image-align]');
    let activeImageElement = null;
    let isApplyingEditorContent = false;

    function setEditorHtml(html) {
        const content = html || defaultContent;
        isApplyingEditorContent = true;

        try {
            quill.enable(false);
            quill.setText('');
            quill.clipboard.dangerouslyPasteHTML(0, content);
        } finally {
            window.setTimeout(() => {
                isApplyingEditorContent = false;
                quill.enable(true);
            }, 0);
        }

        previewFrame.srcdoc = buildPreviewMarkup(content);
    }

    function updateImageControls() {
        if (!imageEditControls || !imageWidthRange) {
            return;
        }

        if (!activeImageElement) {
            imageEditControls.hidden = true;
            return;
        }

        imageEditControls.hidden = false;

        const widthValue = parseInt(activeImageElement.style.width || '100', 10);
        const safeWidth = Number.isFinite(widthValue) ? Math.min(Math.max(widthValue, 20), 100) : 100;
        imageWidthRange.value = String(safeWidth);

        const currentAlign = activeImageElement.getAttribute('data-image-align') || 'center';
        imageAlignButtons.forEach(button => {
            button.classList.toggle('is-active', button.getAttribute('data-image-align') === currentAlign);
        });
    }

    function applyImageLayout(align, width) {
        if (!activeImageElement) {
            return;
        }

        const resolvedAlign = align || activeImageElement.getAttribute('data-image-align') || 'center';
        const resolvedWidth = width || parseInt(activeImageElement.style.width || '100', 10) || 100;

        activeImageElement.style.width = `${resolvedWidth}%`;
        activeImageElement.style.maxWidth = '100%';
        activeImageElement.style.height = 'auto';
        activeImageElement.style.display = 'block';
        activeImageElement.style.float = 'none';

        if (resolvedAlign === 'left') {
            activeImageElement.style.margin = '0';
            activeImageElement.style.float = 'left';
        } else if (resolvedAlign === 'right') {
            activeImageElement.style.margin = '0 0 0 auto';
            activeImageElement.style.float = 'right';
        } else {
            activeImageElement.style.margin = '0 auto';
        }

        activeImageElement.setAttribute('data-image-align', resolvedAlign);
        syncEditorToHidden();
    }

    if (imageEditControls && imageWidthRange) {
        quill.root.addEventListener('click', (event) => {
            const clickedImage = event.target.closest('img');
            if (clickedImage) {
                activeImageElement = clickedImage;
                updateImageControls();
                return;
            }

            if (!event.target.closest('.image-edit-controls')) {
                activeImageElement = null;
                imageEditControls.hidden = true;
            }
        });

        imageWidthRange.addEventListener('input', (event) => {
            applyImageLayout(null, Number(event.target.value));
        });

        imageAlignButtons.forEach(button => {
            button.addEventListener('click', () => {
                applyImageLayout(button.getAttribute('data-image-align'), null);
            });
        });
    }

    function syncEditorToHidden() {
        if (isApplyingEditorContent) {
            return;
        }

        const value = quill.root.innerHTML.trim() || defaultContent;
        hiddenContent.value = value;
        previewFrame.srcdoc = buildPreviewMarkup(value);
    }

    function syncHiddenToEditor() {
        const value = hiddenContent.value.trim();
        if (!value) {
            setEditorHtml(defaultContent);
            return;
        }

        setEditorHtml(value);
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
            const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
            quill.clipboard.dangerouslyPasteHTML(range.index, html);
            syncEditorToHidden();
        }
    }

    function insertImageFromUrlInput() {
        const imageUrlInput = document.getElementById('imageSourceUrl');
        if (!imageUrlInput) {
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

        const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
        quill.insertEmbed(range.index, 'image', imageUrl, 'user');
        quill.setSelection(range.index + 1, 0);
        imageUrlInput.value = '';
        syncEditorToHidden();
    }

    function insertImage() {
        const imageUrl = prompt('URL de l’image à insérer dans le contenu (https://...)');
        if (!imageUrl) {
            return;
        }

        if (!/^https?:\/\//i.test(imageUrl.trim())) {
            alert('Veuillez saisir une URL d’image valide commençant par http:// ou https://');
            return;
        }

        const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
        quill.insertEmbed(range.index, 'image', imageUrl.trim(), 'user');
        quill.setSelection(range.index + 1, 0);
        syncEditorToHidden();
    }

    function uploadImage() {
        const imageFileInput = document.getElementById('imageFileInput');
        if (imageFileInput) {
            imageFileInput.click();
        }
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
                const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
                quill.insertEmbed(range.index, 'image', dataUrl, 'user');
                quill.setSelection(range.index + 1, 0);
                syncEditorToHidden();
            }
        };
        reader.readAsDataURL(file);
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

        setEditorHtml(template.content || defaultContent);
        const subjectInput = document.getElementById('subject');
        if (subjectInput && template.subject) {
            subjectInput.value = template.subject;
        }
        syncEditorToHidden();
    }

    document.querySelectorAll('[data-insert-block]').forEach(button => {
        button.addEventListener('click', () => insertBlock(button.getAttribute('data-insert-block')));
    });

    const imageUrlInsertButton = document.querySelector('[data-action="insert-image-url"]');
    if (imageUrlInsertButton) {
        imageUrlInsertButton.addEventListener('click', insertImageFromUrlInput);
    }

    const imageInsertButton = document.querySelector('[data-action="insert-image"]');
    if (imageInsertButton) {
        imageInsertButton.addEventListener('click', insertImage);
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
            setEditorHtml(defaultContent);
            syncEditorToHidden();
        });
    });

    document.querySelectorAll('[data-template-id]').forEach(button => {
        button.addEventListener('click', () => loadTemplateById(button.getAttribute('data-template-id')));
    });

    quill.on('text-change', syncEditorToHidden);

    const form = hiddenContent.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            hiddenContent.value = quill.root.innerHTML.trim() || defaultContent;
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
