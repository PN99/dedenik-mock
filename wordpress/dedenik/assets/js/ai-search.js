document.querySelectorAll('.ai-search-form').forEach(form => {
    const output = form.parentElement.querySelector('.ai-search-results');
    const button = form.querySelector('button');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        button.disabled = true;
        output.setAttribute('aria-busy', 'true');
        output.textContent = 'Hledám související články…';
        try {
            const response = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), credentials: 'same-origin'});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.data?.message || 'Hledání nyní není dostupné. Zkuste běžné hledání výše.');
            const records = payload.data.results;
            output.replaceChildren();
            const status = document.createElement('p');
            status.textContent = records.length ? 'Související články z archivu:' : 'Nenašli jsme odpovídající článek. Zkuste kratší dotaz nebo běžné hledání výše.';
            output.append(status);
            const list = document.createElement('ul');
            records.forEach(record => {
                const url = new URL(record.url, location.origin);
                if (url.origin !== location.origin) return;
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = url.href;
                link.textContent = record.title;
                const excerpt = document.createElement('p');
                excerpt.textContent = record.date + ' · ' + record.excerpt.slice(0, 240) + (record.excerpt.length > 240 ? '…' : '');
                item.append(link, excerpt);
                list.append(item);
            });
            output.append(list);
        } catch (error) {
            output.textContent = error instanceof SyntaxError ? 'Hledání nyní není dostupné. Zkuste běžné hledání výše.' : error.message;
        } finally {
            button.disabled = false;
            output.removeAttribute('aria-busy');
        }
    });
});
