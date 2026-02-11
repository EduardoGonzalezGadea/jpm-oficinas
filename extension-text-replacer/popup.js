document.addEventListener('DOMContentLoaded', () => {
    const triggerInput = document.getElementById('trigger');
    const replacementInput = document.getElementById('replacement');
    const addBtn = document.getElementById('addBtn');
    const replacementsList = document.getElementById('replacementsList');
    const rulesCount = document.getElementById('rulesCount');
    const searchInput = document.getElementById('searchInput');
    const masterToggle = document.getElementById('masterToggle');

    const exportBtn = document.getElementById('exportBtn');
    const importBtn = document.getElementById('importBtn');
    const importFile = document.getElementById('importFile');

    let allRules = [];

    // Cargar configuración inicial
    chrome.storage.local.get({ replacements: [], isEnabled: true }, (data) => {
        allRules = data.replacements;
        masterToggle.checked = data.isEnabled;
        renderList(allRules);
    });

    // --- Master Toggle ---
    masterToggle.addEventListener('change', () => {
        chrome.storage.local.set({ isEnabled: masterToggle.checked });
    });

    // --- Búsqueda ---
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        const filtered = allRules.filter(r =>
            r.trigger.toLowerCase().includes(query) ||
            r.replacement.toLowerCase().includes(query)
        );
        renderList(filtered, true);
    });

    // --- Añadir Regla ---
    addBtn.addEventListener('click', () => {
        const trigger = triggerInput.value.trim();
        const replacement = replacementInput.value.trim();

        if (trigger && replacement) {
            saveReplacement(trigger, replacement);
            triggerInput.value = '';
            replacementInput.value = '';
            searchInput.value = ''; // Limpiar búsqueda al añadir
        } else {
            showToast('Por favor, completa ambos campos.', 'error');
        }
    });

    function saveReplacement(trigger, replacement) {
        chrome.storage.local.get({ replacements: [] }, (data) => {
            const replacements = data.replacements;
            const existingIndex = replacements.findIndex(r => r.trigger === trigger);

            if (existingIndex !== -1) {
                replacements[existingIndex].replacement = replacement;
            } else {
                replacements.push({ trigger, replacement });
            }

            chrome.storage.local.set({ replacements }, () => {
                allRules = replacements;
                renderList(allRules);
            });
        });
    }

    function renderList(rules, isFiltering = false) {
        replacementsList.innerHTML = '';
        rulesCount.textContent = `${rules.length} regla${rules.length !== 1 ? 's' : ''}`;

        if (rules.length === 0) {
            replacementsList.innerHTML = `
                <div class="empty-state">
                    <i>empty</i>
                    <p>${isFiltering ? 'No se encontraron reglas.' : 'No hay reglas configuradas.'}</p>
                </div>
            `;
            return;
        }

        rules.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'rule-card';
            card.innerHTML = `
                <div class="rule-content">
                    <span class="rule-trigger">${escapeHtml(item.trigger)}</span>
                    <span class="rule-replacement">${escapeHtml(item.replacement)}</span>
                </div>
                <button class="btn-icon btn-delete" data-trigger="${escapeHtml(item.trigger)}" title="Eliminar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
            `;
            replacementsList.appendChild(card);
        });

        // Eventos de eliminar
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const triggerToRemove = e.currentTarget.getAttribute('data-trigger');
                deleteReplacement(triggerToRemove);
            });
        });
    }

    function deleteReplacement(trigger) {
        chrome.storage.local.get({ replacements: [] }, (data) => {
            const replacements = data.replacements.filter(r => r.trigger !== trigger);
            chrome.storage.local.set({ replacements }, () => {
                allRules = replacements;
                renderList(allRules);
            });
        });
    }

    // --- Exportar Reglas ---
    exportBtn.addEventListener('click', () => {
        chrome.storage.local.get({ replacements: [] }, (data) => {
            if (data.replacements.length === 0) {
                alert('No hay reglas para exportar.');
                return;
            }
            const blob = new Blob([JSON.stringify(data.replacements, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'text-replacer-pro-backup.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    });

    // --- Importar Reglas ---
    importBtn.addEventListener('click', () => {
        importFile.click();
    });

    importFile.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const importedRules = JSON.parse(event.target.result);
                if (Array.isArray(importedRules)) {
                    const validRules = importedRules.filter(r => r.trigger && r.replacement);
                    if (confirm(`Se encontraron ${validRules.length} reglas. ¿Deseas reemplazar las actuales?`)) {
                        chrome.storage.local.set({ replacements: validRules }, () => {
                            allRules = validRules;
                            renderList(allRules);
                            alert('Importación exitosa.');
                        });
                    }
                } else {
                    alert('Formato de archivo inválido.');
                }
            } catch (error) {
                alert('Error al procesar el archivo.');
            }
        };
        reader.readAsText(file);
        importFile.value = '';
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(msg, type) {
        // Implementación básica para no romper flujo, el usuario pidió impacto visual
        alert(msg);
    }
});
