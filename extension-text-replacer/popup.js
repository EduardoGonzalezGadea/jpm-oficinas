document.addEventListener('DOMContentLoaded', () => {
    const triggerInput = document.getElementById('trigger');
    const replacementInput = document.getElementById('replacement');
    const addBtn = document.getElementById('addBtn');
    const replacementsList = document.getElementById('replacementsList');
    const rulesCount = document.getElementById('rulesCount');
    const searchInput = document.getElementById('searchInput');
    const masterToggle = document.getElementById('masterToggle');
    const emptyState = document.getElementById('emptyState');
    const emptyStateText = document.getElementById('emptyStateText');

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
        replacementsList.textContent = '';
        rulesCount.textContent = `${rules.length} regla${rules.length !== 1 ? 's' : ''}`;

        if (rules.length === 0) {
            emptyStateText.textContent = isFiltering ? 'No se encontraron reglas.' : 'No hay reglas configuradas.';
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';

        rules.forEach((item) => {
            const card = document.createElement('div');
            card.className = 'rule-card';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'rule-content';

            const triggerSpan = document.createElement('span');
            triggerSpan.className = 'rule-trigger';
            triggerSpan.textContent = item.trigger;

            const replacementSpan = document.createElement('span');
            replacementSpan.className = 'rule-replacement';
            replacementSpan.textContent = item.replacement;

            contentDiv.appendChild(triggerSpan);
            contentDiv.appendChild(replacementSpan);

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-icon btn-delete';
            deleteBtn.setAttribute('data-trigger', item.trigger);
            deleteBtn.setAttribute('title', 'Eliminar');

            const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("width", "16");
            svg.setAttribute("height", "16");
            svg.setAttribute("viewBox", "0 0 24 24");
            svg.setAttribute("fill", "none");
            svg.setAttribute("stroke", "currentColor");
            svg.setAttribute("stroke-width", "2");
            svg.setAttribute("stroke-linecap", "round");
            svg.setAttribute("stroke-linejoin", "round");

            const polyline = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            polyline.setAttribute("points", "3 6 5 6 21 6");
            svg.appendChild(polyline);

            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", "M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2");
            svg.appendChild(path);

            const line1 = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line1.setAttribute("x1", "10");
            line1.setAttribute("y1", "11");
            line1.setAttribute("x2", "10");
            line1.setAttribute("y2", "17");
            svg.appendChild(line1);

            const line2 = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line2.setAttribute("x1", "14");
            line2.setAttribute("y1", "11");
            line2.setAttribute("x2", "14");
            line2.setAttribute("y2", "17");
            svg.appendChild(line2);

            deleteBtn.appendChild(svg);
            
            card.appendChild(contentDiv);
            card.appendChild(deleteBtn);
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

    function showToast(msg, type) {
        // Implementación básica para no romper flujo, el usuario pidió impacto visual
        alert(msg);
    }
});
