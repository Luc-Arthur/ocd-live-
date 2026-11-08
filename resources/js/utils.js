export const fcfa = (n) => new Intl.NumberFormat('fr-FR').format(n).replace(/ | /g, ' ') + ' FCFA';
export const hh = (t) => t.replace(':', 'h');
export const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
export const DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
export const parseDate = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
export const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
export const frDate = (s) => { const d = parseDate(s); return `${DAYS[d.getDay()]} ${d.getDate() === 1 ? '1er' : d.getDate()} ${MONTHS[d.getMonth()]}`; };
export const ucfirst = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);
