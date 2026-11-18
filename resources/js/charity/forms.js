const normalizeIban = value => value.replace(/\s/g, '').toUpperCase();

function attachBankCheck(region) {
    const input = region.querySelector('[data-iban-input]');
    const button = region.querySelector('[data-bank-check-button]');
    const status = region.querySelector('[data-bank-status]');
    let controller;
    let sequence = 0;

    const show = (state, message) => {
        status.dataset.state = state;
        status.textContent = message;
    };

    input.addEventListener('input', () => {
        sequence += 1;
        controller?.abort();
        button.disabled = false;
        region.setAttribute('aria-busy', 'false');
        show('idle', 'Gewijzigd; nog niet gecontroleerd.');
    });

    const check = async () => {
        const iban = normalizeIban(input.value);
        input.value = iban.replace(/(.{4})(?=.)/g, '$1 ');
        if (!iban) {
            show('error', 'Vul eerst een IBAN in.');
            input.focus();
            return;
        }

        controller?.abort();
        controller = new AbortController();
        const currentController = controller;
        const currentSequence = ++sequence;
        const timeout = setTimeout(() => currentController.abort(), 8000);
        button.disabled = true;
        region.setAttribute('aria-busy', 'true');
        show('checking', 'IBAN wordt gecontroleerd...');

        try {
            const response = await fetch(region.dataset.checkUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': region.closest('form').querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({ iban }),
                signal: currentController.signal,
            });
            if (currentSequence !== sequence) return;
            if (response.redirected || [401, 403, 419].includes(response.status)) {
                show('error', 'Je sessie is verlopen. Log opnieuw in bij Lief & Leed.');
                return;
            }
            if (response.status === 429) {
                show('error', 'Je hebt te vaak gecontroleerd. Probeer het over een minuut opnieuw.');
                return;
            }
            const payload = await response.json();
            if (!response.ok) {
                show('error', payload.errors?.iban?.[0] ?? 'De controle is niet beschikbaar. Probeer het opnieuw.');
                return;
            }
            show('valid', payload.message);
        } catch {
            if (currentSequence === sequence) {
                show('error', 'De controle is niet bereikbaar. Bij het indienen wordt je IBAN opnieuw gecontroleerd.');
            }
        } finally {
            clearTimeout(timeout);
            if (currentSequence === sequence) {
                button.disabled = false;
                region.setAttribute('aria-busy', 'false');
            }
        }
    };

    button.addEventListener('click', check);
    input.addEventListener('blur', () => { if (input.value.trim()) check(); });
}

function attachForm(form) {
    if (form.dataset.charityBound) return;
    form.dataset.charityBound = 'true';

    form.querySelectorAll('[data-bank-check]').forEach(attachBankCheck);
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', () => {
            input.setCustomValidity('');
            input.setAttribute('aria-invalid', 'false');
            const error = [...form.querySelectorAll('[data-field-error]')].find(field => field.dataset.fieldError === input.name);
            if (error) error.textContent = '';
        });
        input.addEventListener('invalid', () => {
            input.setCustomValidity(input.validity.valueMissing ? 'Vul dit verplichte veld in.' : 'Controleer de ingevulde waarde.');
        });
    });

    const search = form.querySelector('[data-member-search]');
    const select = form.querySelector('[data-member-select]');
    if (search && select) {
        const options = [...select.options].map(option => option.cloneNode(true));
        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase('nl');
            const selected = select.value;
            const matches = options.filter(option => !option.value || option.value === selected || option.textContent.toLocaleLowerCase('nl').includes(query));
            select.replaceChildren(...matches.map(option => option.cloneNode(true)));
            select.value = selected;
            const count = options.filter(option => option.value && option.textContent.toLocaleLowerCase('nl').includes(query)).length;
            form.querySelector('[data-member-search-feedback]').textContent = query ? `${count} collega${count === 1 ? '' : "'s"} gevonden.` : '';
        });
    }

    const event = form.querySelector('[data-event-select]');
    if (event) {
        const updateSummary = () => {
            const option = event.selectedOptions[0];
            form.querySelector('[data-event-title]').textContent = option?.dataset.title ?? 'Kies een gebeurtenis';
            form.querySelector('[data-event-amount]').textContent = option?.dataset.amount ? `\u20ac ${option.dataset.amount}` : '\u2014';
        };
        event.addEventListener('change', updateSummary);
        updateSummary();
    }

    form.addEventListener('submit', event => {
        if (form.dataset.confirmMessage && !window.confirm(form.dataset.confirmMessage)) {
            event.preventDefault();
            return;
        }
        if (form.dataset.submitting) {
            event.preventDefault();
            return;
        }
        if (!form.checkValidity()) return;
        form.dataset.submitting = 'true';
        const button = form.querySelector('[data-submit-button]');
        if (button) button.disabled = true;
        const label = form.querySelector('[data-submit-label]');
        if (label) label.textContent = 'Bezig met versturen...';
    });
}

function initialize() {
    document.querySelectorAll('[data-charity-form]').forEach(attachForm);
    document.querySelector('[data-error-summary]')?.focus();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
document.addEventListener('livewire:navigated', initialize);
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-charity-form]').forEach(form => {
        if (!form.dataset.submitting) return;
        delete form.dataset.submitting;
        const button = form.querySelector('[data-submit-button]');
        if (button) button.disabled = false;
        const label = form.querySelector('[data-submit-label]');
        if (label) label.textContent = form.dataset.submitLabel ?? 'Aanvraag versturen';
    });
});