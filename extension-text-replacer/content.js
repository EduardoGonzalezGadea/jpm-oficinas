let replacementRules = [];
let isEnabled = true;

// Marcador para que la aplicación detecte que la extensión está instalada
document.documentElement.setAttribute('data-text-replacer-installed', 'true');
window.dispatchEvent(new CustomEvent('text-replacer-detected'));

// Cargar reglas e interruptor inicialmente
chrome.storage.local.get({ replacements: [], isEnabled: true }, (data) => {
    replacementRules = data.replacements;
    isEnabled = data.isEnabled;
});

// Escuchar cambios en la configuración en tiempo real
chrome.storage.onChanged.addListener((changes, area) => {
    if (area === 'local') {
        if (changes.replacements) {
            replacementRules = changes.replacements.newValue;
        }
        if (changes.isEnabled !== undefined) {
            isEnabled = changes.isEnabled.newValue;
        }
    }
});

// Función para procesar el texto y reemplazar
function processReplacement(element) {
    // Solo actuamos si la extensión está habilitada y hay reglas
    if (!isEnabled || replacementRules.length === 0) return;

    let content = "";
    const isInput = (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA');

    if (isInput) {
        content = element.value;
    } else {
        content = element.innerText;
    }

    let modified = false;
    let newContent = content;

    // Buscamos cada disparador en el contenido
    replacementRules.forEach(rule => {
        if (content.includes(rule.trigger)) {
            // Reemplazo simple
            const regex = new RegExp(escapeRegExp(rule.trigger), 'g');
            newContent = newContent.replace(regex, rule.replacement);
            modified = true;
        }
    });

    if (modified) {
        if (isInput) {
            const start = element.selectionStart;
            element.value = newContent;
            element.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
            element.innerText = newContent;
            const range = document.createRange();
            const sel = window.getSelection();
            if (element.childNodes.length > 0) {
                range.selectNodeContents(element);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
            element.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Escuchamos el evento 'input' que se dispara al escribir
document.addEventListener('input', (event) => {
    const target = event.target;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
        processReplacement(target);
    }
}, true);
