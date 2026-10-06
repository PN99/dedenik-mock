/* Ukázkové záznamy převzaté z klientského návrhu. CMS adaptér nahradí tento objekt. */
window.DEDENIK_CONTENT = {
  articles: [
    {id:'pratelstvi',title:'Přátelství nemá hranice',category:'Zvířata',rubric:'Zvířata',author:'Matylda',date:'2024-10-01',comments:45,image:'hero',excerpt:'Příběhy o zvířatech nás učí víc, než si myslíme. O důvěře, trpělivosti a radosti z každého dne.',tags:['psi','kočky','přátelství']},
    {id:'doma',title:'Jakpak je dnes u nás doma',category:'Zvířata',rubric:'Matyldoviny',author:'Matylda',date:'2024-10-01',comments:45,image:'dog',excerpt:'Matylda má svoje dny. Někdy je neunavná, jindy filozofická…',tags:['psi','veselé','domov']},
    {id:'zahada',title:'Zatím záhada…',category:'Literatura',rubric:'Literární léto',author:'EvaB',date:'2024-10-01',comments:32,image:'reading',excerpt:'Podzim je čas otázek, které nemají jednoduché odpovědi.',tags:['povídky','podzim','knihy']},
    {id:'armenie',title:'Arménie – země klášterů a hor',category:'Foto',rubric:'Fotografové a fotografie',author:'MartinR',date:'2024-09-30',comments:49,image:'armenia',excerpt:'Cesta, která voněla historií, kamenem a sluncem.',tags:['svět','fotografie','cestování']},
    {id:'houby',title:'Houby, které potěší',category:'Příroda',rubric:'Příroda',author:'Tora',date:'2024-09-29',comments:27,image:'forest',excerpt:'Letos je v lese krásně. Přináším pár fotografií a tipů na místa.',tags:['les','podzim','fotografie']},
    {id:'snih',title:'První sníh s Matyldou',category:'Zvířata',rubric:'Matyldoviny',author:'Matylda',date:'2014-10-01',comments:0,image:'archive',excerpt:'Vzpomínka na den, kdy jsme poprvé společně vyrazili do zasněženého lesa…',tags:['psi','vzpomínky','archiv']}
  ],
  topics:['Zvířata','Literatura','Fotografové a fotografie','Příroda','Lidé','Svět','Jídlo','Kultura','BTW','Chatník','Povídky','Zahrada','Historično'],
  authors:['Dede','Matylda','EvaB','Tora','MartinR','Aida','Raven','Alex','Anemone','Netopýrka','Sarkany'],
  discussions:[['Co vás dnes potěšilo?',20],['Podzimní fotografie',14],['Naše zvířecí radosti',32],['Knižní tipy',18],['Zahrádka v říjnu',11]],
  world:[
    {kind:'Podcast',title:'Právě posloucháme',description:'O zvířatech, lidech a životě.',image:'podcast',action:'Poslechnout',className:'green',badge:'pod',note:'Podklad neobsahuje zvuk epizody ani odkaz na přehrávač. Při napojení na CMS se zde zobrazí aktuální epizoda a skutečný přehrávač.'},
    {kind:'Dedivadlo',title:'V Dedivadle',description:'Kočky, příběhy a jiné životy.',image:'theatre',action:'Do Dedivadla',className:'gold',badge:'theatre',note:'Podklad neobsahuje termín, místo ani odkaz na představení. Tyto údaje se převezmou z redakčního záznamu aktuální inscenace.'},
    {kind:'Knihy',title:'Z knih Dede',description:'Alžběta — příběh o síle, přátelství a nových začátcích.',image:'book',action:'Objevit knihy',className:'book-button',badge:'books',note:'Alžběta je titul uvedený v návrhu. Pro ostrý web doplnit schválený popis, obálku a odkaz vydavatele.'}
  ]
};
