document.querySelectorAll('.ai-search-form').forEach(form => {
    const output = form.parentElement.querySelector('.ai-search-results');
    const button = form.querySelector('button');
    let activeQuery = '';
    const search = async (page = 1) => {
        button.disabled = true;
        output.setAttribute('aria-busy', 'true');
        output.textContent = 'Hledám související články…';
        try {
            const data = new FormData(form);
            data.set('query', activeQuery);
            data.set('page', String(page));
            const response = await fetch(form.getAttribute('action'), {method: 'POST', body: data, credentials: 'same-origin'});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.data?.message || 'Hledání nyní není dostupné. Zkuste běžné hledání výše.');
            const records = payload.data.results;
            const pagination = payload.data.pagination;
            output.replaceChildren();
            const status = document.createElement('p');
            status.textContent = records.length ? 'Související články a diskuse:' : 'Nenašli jsme odpovídající výsledek. Zkuste kratší dotaz nebo běžné hledání výše.';
            if (pagination?.mode === 'author') status.textContent = `Články autora: celkem ${pagination.total}. Strana ${pagination.page} z ${pagination.pages}.`;
            if (pagination?.mode === 'discussion') status.textContent = pagination.total ? `Nalezené komentáře: ${pagination.total}. Strana ${pagination.page} z ${pagination.pages}.` : 'Odpovídající veřejný komentář jsme nenašli. Zkuste jiné téma nebo přezdívku.';
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
            if (pagination?.pages > 1) {
                const nav = document.createElement('nav');
                nav.setAttribute('aria-label', 'Stránkování nalezených článků');
                [[pagination.page - 1, '← Předchozí'], [pagination.page + 1, 'Další →']].forEach(([target, label]) => {
                    if (target < 1 || target > pagination.pages) return;
                    const next = document.createElement('button');
                    next.type = 'button';
                    next.textContent = label;
                    next.addEventListener('click', () => search(target));
                    nav.append(next);
                });
                output.append(nav);
            }
        } catch (error) {
            output.textContent = error instanceof SyntaxError ? 'Hledání nyní není dostupné. Zkuste běžné hledání výše.' : error.message;
        } finally {
            button.disabled = false;
            output.removeAttribute('aria-busy');
        }
    };
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (button.disabled) return;
        activeQuery = new FormData(form).get('query');
        search();
    });
});
