function showSection(sectionId) {
    
    document.querySelectorAll('.main-section').forEach(section => {
        section.classList.remove('active');
    });

    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    } else {
        console.error(`Section ${sectionId} not found`);
    }

    const url = new URL(window.location);
    if (sectionId === 'communityContent') {
        url.searchParams.set('section', 'community');
        url.searchParams.delete('search'); 
    } else if (sectionId === 'searchContent') {
        if (!url.searchParams.has('search')) {
            url.searchParams.set('search', ''); 
        }
        url.searchParams.delete('section');
        url.searchParams.delete('categoryId');
    } else if (sectionId === 'documentsContent') {
        url.searchParams.delete('section');
        url.searchParams.delete('search');
    } else {
        url.searchParams.delete('section');
        url.searchParams.delete('search');
        url.searchParams.delete('categoryId');
    }
    window.history.replaceState({}, '', url);
}

function showReplyForm(messageId, userId) {
    document.getElementById('reply-form-' + messageId).style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('addBtn');
    const modal = document.getElementById('uploadModal');
    const close = document.getElementsByClassName('close')[0];
    btn.addEventListener('click', () => modal.style.display = 'block');
    close.addEventListener('click', () => modal.style.display = 'none');
});

// Gestion du chargement initial
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    const search = urlParams.get('search');

    if (search !== null && search.trim() !== '') {
        showSection('searchContent');
    } else if (section === 'community') {
        showSection('communityContent');
    } else if (urlParams.has('categoryId')) {
        showSection('documentsContent');
    } else {
        showSection('homeContent');
    }

    // Prévisualisation des pièces jointes
    const attachInputs = document.querySelectorAll('input[type="file"][name="attachment"]');
    attachInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const previewContainer = document.createElement('div');
                previewContainer.className = 'attachment-preview';
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.className = 'attachment-image';
                    previewContainer.appendChild(img);
                } else if (file.type === 'application/pdf') {
                    const span = document.createElement('span');
                    span.textContent = `PDF: ${file.name}`;
                    previewContainer.appendChild(span);
                }
                input.parentElement.insertBefore(previewContainer, input.nextSibling);
            }
        });
    });
});

// Gestion du modal d'upload
const modal = document.getElementById("uploadModal");
const btn = document.getElementById("addBtn");
const span = document.getElementsByClassName("close")[0];

btn.onclick = function() {
    modal.style.display = "block";
}

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Gestion du toggle des sections (courses)
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
}