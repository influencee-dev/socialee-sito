#!/usr/bin/env python3
"""Estrae da copia-sito/html (pagine di socialee.it fatte con Elementor) il materiale del sito senza Elementor.

Ogni pagina Elementor di socialee.it è fatta di tre widget «HTML»: intestazione (modello 3199), contenuto della
pagina, piè di pagina (modello 3267). Elementor fa solo da contenitore: qui prendiamo l'HTML scritto a mano.

Uscita in sorgente/:
  shell/header.html   menu e script dell'intestazione (senza <style>)
  shell/footer.html   piè di pagina e script (senza <style>)
  css/sito.css        tutto il CSS dei widget di intestazione e piè di pagina, nell'ordine originale
  pagine/<slug>.html  contenuto della pagina (con i suoi eventuali <style>/<script>)
  meta.json           per ogni percorso: title, description, robots, canonical, immagine og, JSON-LD di Rank Math,
                      classi del body, id della pagina WordPress

Uso: python3 -I scripts/estrai.py   (dalla cartella del repository)
"""
import collections
import glob
import hashlib
import html
import json
import os
import re
import sys

from bs4 import BeautifulSoup

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
SRC = os.path.join(ROOT, 'copia-sito', 'html')
OUT = os.path.join(ROOT, 'sorgente')


def path_of(fname):
    base = os.path.basename(fname)[:-5]
    return '/' if base == 'home' else '/' + base.replace('__', '/') + '/'


def inner(widget):
    box = widget.select_one(':scope > .elementor-widget-container') or widget
    return box


def meta_of(soup):
    def m(sel, attr='content'):
        t = soup.select_one(sel)
        return html.unescape(t.get(attr, '')) if t else ''
    lds = []
    for s in soup.select('script[type="application/ld+json"]'):
        try:
            lds.append(json.loads(s.get_text()))
        except ValueError:
            pass
    body = soup.body
    pid = ''
    for c in body.get('class', []):
        if c.startswith('page-id-') or c.startswith('postid-'):
            pid = c.split('-')[-1]
    return {
        'title': html.unescape(soup.title.get_text()) if soup.title else '',
        'description': m('meta[name="description"]'),
        'robots': m('meta[name="robots"]'),
        'canonical': m('link[rel="canonical"]', 'href'),
        'og_image': m('meta[property="og:image"]'),
        'og_type': m('meta[property="og:type"]'),
        'ld': lds,
        'body_class': [c for c in body.get('class', []) if 'elementor' not in c and not c.startswith('e--')],
        'wp_id': pid,
    }


def main():
    if os.path.exists(os.path.join(OUT, 'meta.json')) and '--forza' not in sys.argv:
        sys.exit('sorgente/ esiste già e contiene modifiche fatte dopo l\'estrazione (es. modulo contatti senza chiave '
                 'Brevo): rilanciare solo con --forza e poi riapplicare le modifiche.')
    files = sorted(glob.glob(os.path.join(SRC, '*.html')))
    if not files:
        sys.exit('copia-sito/html è vuota: prima la copia del sito')
    os.makedirs(os.path.join(OUT, 'shell'), exist_ok=True)
    os.makedirs(os.path.join(OUT, 'css'), exist_ok=True)
    os.makedirs(os.path.join(OUT, 'pagine'), exist_ok=True)

    headers, footers = collections.Counter(), collections.Counter()
    meta, saltate = {}, []
    for f in files:
        soup = BeautifulSoup(open(f, encoding='utf-8', errors='ignore').read(), 'lxml')
        path = path_of(f)
        ws = soup.select('[data-widget_type="html.default"]')
        hdr = [w for w in ws if w.find_parent(attrs={'data-elementor-type': 'header'})]
        ftr = [w for w in ws if w.find_parent(attrs={'data-elementor-type': 'footer'})]
        cnt = [w for w in ws if not w.find_parent(attrs={'data-elementor-type': ['header', 'footer']})]
        if hdr:
            headers[str(inner(hdr[0]))] += 1
        if ftr:
            footers[str(inner(ftr[0]))] += 1
        if len(cnt) != 1:
            saltate.append((path, f'{len(cnt)} blocchi di contenuto'))
            continue
        body = inner(cnt[0]).decode_contents().strip()
        slug = os.path.basename(f)[:-5]
        with open(os.path.join(OUT, 'pagine', slug + '.html'), 'w', encoding='utf-8') as fh:
            fh.write(body + '\n')
        meta[path] = dict(meta_of(soup), slug=slug, sha=hashlib.sha256(body.encode()).hexdigest()[:12])

    # intestazione e piè di pagina: la versione più diffusa
    def split_styles(markup):
        s = BeautifulSoup(markup, 'lxml')
        box = s.body.find(True)  # contenitore del widget
        styles = []
        for st in box.find_all('style'):
            styles.append((st.get('id') or '', st.get_text()))
            st.decompose()
        return box.decode_contents().strip(), styles

    css_parts = []
    for name, counter in (('header', headers), ('footer', footers)):
        markup, n = counter.most_common(1)[0]
        body, styles = split_styles(markup)
        with open(os.path.join(OUT, 'shell', name + '.html'), 'w', encoding='utf-8') as fh:
            fh.write(body + '\n')
        for sid, css in styles:
            css_parts.append(f'/* ── {name}{" #" + sid if sid else ""} ── */\n{css.strip()}\n')
        print(f'{name}: versione usata da {n} pagine su {sum(counter.values())}')
    with open(os.path.join(OUT, 'css', 'sito.css'), 'w', encoding='utf-8') as fh:
        fh.write('\n'.join(css_parts))
    with open(os.path.join(OUT, 'meta.json'), 'w', encoding='utf-8') as fh:
        json.dump(meta, fh, ensure_ascii=False, indent=1)
    print(f'pagine estratte: {len(meta)}')
    for p, why in saltate:
        print(f'  saltata {p}: {why}')


if __name__ == '__main__':
    main()
