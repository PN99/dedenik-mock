(() => {
  'use strict';
  const data = window.DEDENIK_CONTENT;
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => [...r.querySelectorAll(s)];
  const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const date = value => new Intl.DateTimeFormat('cs-CZ',{day:'numeric',month:'long',year:'numeric'}).format(new Date(value+'T12:00:00'));
  let favorites = [];
  try { const saved=JSON.parse(localStorage.getItem('dedenik-favorites')||'[]'); if(Array.isArray(saved)) favorites=saved.filter(id=>data.articles.some(a=>a.id===id)); } catch {}
  let active='Vše', expanded=false;
  const picture = (name, alt, cls='photo') => `<img class="${cls}" src="assets/${esc(name)}.webp" srcset="assets/${esc(name)}-small.webp 240w, assets/${esc(name)}.webp 480w" sizes="(max-width:620px) 90vw, 280px" loading="lazy" alt="${esc(alt)}">`;
  const card = a => `<article class="card">${picture(a.image,a.title)}<div class="body"><div class="kicker">${esc(a.rubric)}</div><h3><a href="#clanek/${a.id}" data-article="${a.id}">${esc(a.title)}</a></h3><p>${esc(a.excerpt)}</p><div class="foot"><span>${esc(a.author)}<br>${date(a.date)}</span><button data-article="${a.id}" aria-label="${a.comments} komentářů k článku ${esc(a.title)}">◯ ${a.comments}</button><button class="favorite" data-favorite="${a.id}" aria-label="Uložit článek ${esc(a.title)}" aria-pressed="${favorites.includes(a.id)}">${favorites.includes(a.id)?'♥':'♡'}</button></div></div></article>`;
  function renderArticles(){
    const records = data.articles.filter(a=>!['pratelstvi','snih'].includes(a.id)||expanded).filter(a=>active==='Vše'||a.category===active||a.tags.includes(active.toLowerCase()));
    $('.cards').innerHTML=records.length?records.map(card).join(''):'<p class="empty" role="status">V ukázkových podkladech zatím není článek z této rubriky. Zkuste jinou rubriku.</p>';
    $$('.tab').forEach(b=>{b.classList.toggle('active',b.textContent===active);b.setAttribute('aria-pressed',String(b.textContent===active));});
  }
  const dialog=$('#detail');
  let previousFocus;
  function show(html){previousFocus=document.activeElement;$('#dialog-content').innerHTML=html;if(!dialog.open)dialog.showModal();$('.close',dialog).focus();}
  $('.close').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('close',()=>previousFocus?.focus());
  function article(id){const a=data.articles.find(x=>x.id===id);if(!a)return;
    show(`<div class="kicker">${esc(a.rubric)}</div><h2>${esc(a.title)}</h2><p class="dialog-meta">${esc(a.author)} · ${date(a.date)}</p>${picture(a.image,a.title,'dialog-photo')}<p class="reader-copy">${esc(a.excerpt)}</p><p class="comments-note">Ukázka detailu článku. E-mail obsahuje titulek a perex, plný text a komentáře nejsou součástí podkladů.</p><h3>Diskuse · ${a.comments} komentářů v návrhu</h3><p>Po napojení na současný redakční systém se zde zobrazí původní diskuse.</p><button class="btn green" data-save="${a.id}">${favorites.includes(a.id)?'Odebrat z oblíbených':'Uložit do oblíbených'}</button>`);
  }
  function save(id){favorites=favorites.includes(id)?favorites.filter(x=>x!==id):[...favorites,id];try{localStorage.setItem('dedenik-favorites',JSON.stringify(favorites));}catch{}renderArticles();}
  function collection(title,records,intro=''){show(`<h2>${esc(title)}</h2>${intro?`<p>${esc(intro)}</p>`:''}<div class="cards">${records.length?records.map(card).join(''):'<p class="empty">Pro tuto část zatím nebyl dodán ukázkový článek.</p>'}</div>`);}
  function search(query){const normalized=s=>s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');const terms=normalized(query).trim().split(/\s+/).filter(Boolean);
    const records=data.articles.map(a=>({a,score:terms.reduce((n,t)=>n+Number(normalized([a.title,a.rubric,a.category,a.author,a.excerpt,...a.tags].join(' ')).includes(t)),0)})).filter(x=>!terms.length||x.score>0).sort((a,b)=>b.score-a.score).map(x=>x.a);
    collection(query?`Výsledky pro „${query}“`:'Všechny ukázkové články',records,'Vyhledávání v ukázkovém obsahu podle slov, témat a autorů.');
  }
  $$('.tab').forEach(span=>{const button=document.createElement('button');button.className=span.className;button.textContent=span.textContent;button.type='button';span.replaceWith(button);button.addEventListener('click',()=>{active=button.textContent;renderArticles();});});
  $$('.search,.aiinput').forEach(form=>form.addEventListener('submit',e=>{e.preventDefault();search(new FormData(form).get('q')||'');}));
  $$('.chip').forEach(span=>{const b=document.createElement('button');b.className='chip';b.textContent=span.textContent;span.replaceWith(b);b.addEventListener('click',()=>search(b.textContent.includes('Arménie')?'Arménie':b.textContent.includes('Matyldovin')?'Matyldoviny':b.textContent.includes('Netopýrky')?'Netopýrka':'psi kočky'));});
  $('.topics').innerHTML=data.topics.map(t=>`<button class="topic" data-topic="${esc(t)}">${esc(t)}</button>`).join('');
  $('.authors').innerHTML=data.authors.map(a=>`<button class="author" data-author="${esc(a)}"><span class="face" aria-hidden="true">${esc(a[0])}</span>${esc(a)}</button>`).join('');
  $('.worldgrid').innerHTML=data.world.map((w,i)=>`<article class="worldcard">${picture(w.image,w.kind,'worldpic')}<div><span class="badge ${w.badge}">${esc(w.kind)}</span><h3>${esc(w.title)}</h3><p>${esc(w.description)}</p><button class="btn ${w.className}" data-world="${i}">${esc(w.action)} →</button></div></article>`).join('');
  const chat=$('.chat');chat.innerHTML='<h2>Chatník</h2><p>Právě se diskutuje…</p>'+data.discussions.map(([name,count],i)=>`<button class="chatrow" data-discussion="${i}">${esc(name)}<span>◯ ${count}</span></button>`).join('')+'<button class="btn" data-chat> Přejít do Chatníku →</button>';
  $$('.tool').forEach(div=>{const button=document.createElement('button');button.className='tool';button.textContent=div.textContent;div.replaceWith(button);button.addEventListener('click',()=>{
    if(button.textContent.includes('Náhodný'))article(data.articles[Math.floor(Math.random()*data.articles.length)].id);
    else if(button.textContent.includes('Oblíbené'))collection('Oblíbené články',data.articles.filter(a=>favorites.includes(a.id)),'Uložené v tomto prohlížeči.');
    else if(button.textContent.includes('archivu'))collection('Z archivu',data.articles.filter(a=>a.date<'2020-01-01'));
    else if(button.textContent.includes('Vyhledávání')){$('.aiinput input').focus();$('.ai').scrollIntoView();}
    else collection('Co číst dnes?',data.articles.slice(0,3));
  });});
  $('.menu-toggle').addEventListener('click',e=>{const open=$('.nav').classList.toggle('open');e.currentTarget.setAttribute('aria-expanded',String(open));});
  $$('.nav a').forEach(a=>a.addEventListener('click',()=>{$('.nav').classList.remove('open');$('.menu-toggle').setAttribute('aria-expanded','false');}));
  $('.login').addEventListener('click',()=>show('<h2>Přihlášení do Dedeníku</h2><p>V tomto návrhu se přihlášení ještě nenapojuje na čtenářské účty. Ostrá verze použije současné účty Dedeníku.</p>'));
  $('.hero .white').href='#clanek/pratelstvi';$('.hero .white').dataset.article='pratelstvi';
  $('.archive a').href='#clanek/snih';$('.archive a').dataset.article='snih';
  $('.archive h2').textContent='Z archivu – vzpomínka na říjen';
  $('#latest .headrow>a').href='#latest';$('#latest .headrow>a').addEventListener('click',e=>{e.preventDefault();expanded=!expanded;e.currentTarget.textContent=expanded?'Zobrazit nejnovější →':'Další články →';renderArticles();});
  $('#temata .headrow>a').href='#temata';$('#temata .headrow>a').addEventListener('click',e=>{e.preventDefault();show('<h2>Témata a rubriky</h2><div class="topics">'+data.topics.map(t=>`<button class="topic" data-topic="${esc(t)}">${esc(t)}</button>`).join('')+'</div>');});
  $('#autori .headrow>a').href='#autori';$('#autori .headrow>a').addEventListener('click',e=>{e.preventDefault();show('<h2>Autoři Dedeníku</h2><div class="authors">'+data.authors.map(a=>`<button class="author" data-author="${esc(a)}"><span class="face">${esc(a[0])}</span>${esc(a)}</button>`).join('')+'</div>');});
  $('.world .headrow>a').href='#svet-dede';$('.world').id='svet-dede';$('.world .headrow>a').addEventListener('click',e=>{e.preventDefault();show('<h2>Svět Dede</h2><p>Dedeník, Podcast, Dedivadlo a knihy. Příběhy a setkávání, která přirozeně navazují na život Dedeníku.</p>');});
  $$('.links a').forEach(a=>{const map={'Články':'latest','Témata':'temata','Autoři':'autori','Archiv':'archiv','Chatník':'chatnik','Podcast':'svet-dede','Dedivadlo':'svet-dede','Knihy':'svet-dede','Další projekty':'svet-dede'};a.href='#'+(map[a.textContent]||'o');if(!map[a.textContent])a.addEventListener('click',e=>{e.preventDefault();show(`<h2>${esc(a.textContent)}</h2><p>Tato stránka bude při nasazení propojena s existujícím obsahem Dedeníku. Text nebyl součástí dodaných podkladů.</p>`);});});
  document.addEventListener('click',e=>{
    const a=e.target.closest('[data-article]');if(a){e.preventDefault();article(a.dataset.article);return;}
    const f=e.target.closest('[data-favorite],[data-save]');if(f){save(f.dataset.favorite||f.dataset.save);if(f.dataset.save)article(f.dataset.save);return;}
    const author=e.target.closest('[data-author]');if(author){collection(author.dataset.author,data.articles.filter(a=>a.author===author.dataset.author),'Ukázka autorského profilu a jeho článků.');return;}
    const topic=e.target.closest('[data-topic]');if(topic){const t=topic.dataset.topic;if(t==='Chatník'){show('<h2>Chatník</h2>'+data.discussions.map(([name,count],i)=>`<button class="chatrow" data-discussion="${i}">${esc(name)}<span>${count}</span></button>`).join(''));return;}const records=data.articles.filter(a=>a.rubric===t||a.category===t||a.tags.includes(t.toLowerCase()));collection(t,records,t==='Fotografové a fotografie'?'Samostatná rubrika pro fotočlánky, galerie a fotografy.':'');if(t==='Fotografové a fotografie')$('#dialog-content').insertAdjacentHTML('beforeend','<h3>Ukázková galerie</h3><div class="gallery"><figure>'+picture('armenia','Klášter v Arménii')+'<figcaption>Arménie · MartinR · ilustrace z návrhu</figcaption></figure><figure>'+picture('forest','Houby v lese')+'<figcaption>Příroda · Tora · ilustrace z návrhu</figcaption></figure></div><h3>Fotografové</h3><button class="btn green" data-author="MartinR">MartinR</button>');return;}
    const w=e.target.closest('[data-world]');if(w){const x=data.world[Number(w.dataset.world)];show(`<div class="kicker">${esc(x.kind)}</div><h2>${esc(x.title)}</h2>${picture(x.image,x.kind,'dialog-photo')}<p>${esc(x.description)}</p><p class="comments-note">${esc(x.note)}</p>`);return;}
    const d=e.target.closest('[data-discussion]');if(d){const [name,count]=data.discussions[Number(d.dataset.discussion)];show(`<h2>${esc(name)}</h2><p>${count} komentářů v dodaném návrhu.</p><p class="comments-note">Obsah diskuse zatím nebyl dodán. Ostrá verze zobrazí původní komentáře a umožní přihlášeným čtenářům přispívat.</p>`);return;}
    if(e.target.closest('[data-chat]'))show('<h2>Chatník</h2>'+data.discussions.map(([name,count],i)=>`<button class="chatrow" data-discussion="${i}">${esc(name)}<span>${count}</span></button>`).join(''));
  });
  renderArticles();
})();
