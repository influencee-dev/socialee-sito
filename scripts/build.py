#!/usr/bin/env python3
"""Costruisce i plugin di socialee.it e la cartella che il sito legge per aggiornarsi da solo.

  sorgente/ (pagine, intestazione, piè di pagina, CSS estratti da Elementor, poi modificati qui)
    → wp-plugin/socialee-pagine/{pages,shell,assets}   (copie generate, non modificarle a mano)
    → dist/<plugin>.zip
    → consegne/plugin-wordpress/<plugin>.zip + versioni.json (letto ogni ora da Socialee Aggiornamenti)

Uso: python3 -I scripts/build.py
"""
import datetime
import hashlib
import json
import os
import re
import shutil
import zipfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
SRC = os.path.join(ROOT, 'sorgente')
WP = os.path.join(ROOT, 'wp-plugin')
DIST = os.path.join(ROOT, 'dist')
CONS = os.path.join(ROOT, 'consegne', 'plugin-wordpress')
PLUGINS = ['socialee-core', 'socialee-pagine', 'socialee-aggiornamenti']


def minify_css(css):
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    css = re.sub(r'\s+', ' ', css)
    css = re.sub(r'\s*([{};,>])\s*', r'\1', css)
    return css.replace(';}', '}').strip() + '\n'


def version_of(slug):
    head = open(os.path.join(WP, slug, slug + '.php'), encoding='utf-8').read(2000)
    return re.search(r'Version:\s*([0-9.]+)', head).group(1)


def pagine():
    dst = os.path.join(WP, 'socialee-pagine')
    for d in ('pages', 'shell', 'assets'):
        shutil.rmtree(os.path.join(dst, d), ignore_errors=True)
        os.makedirs(os.path.join(dst, d))
    meta = json.load(open(os.path.join(SRC, 'meta.json'), encoding='utf-8'))
    out = {}
    for path, m in meta.items():
        shutil.copy(os.path.join(SRC, 'pagine', m['slug'] + '.html'), os.path.join(dst, 'pages', m['slug'] + '.html'))
        out[path] = {'slug': m['slug'], 'title': m['title']}
    json.dump(out, open(os.path.join(dst, 'pages', 'meta.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    for n in ('header', 'footer'):
        shutil.copy(os.path.join(SRC, 'shell', n + '.html'), os.path.join(dst, 'shell', n + '.html'))
    css = minify_css(''.join(open(os.path.join(SRC, 'css', f), encoding='utf-8').read() + '\n' for f in ('kit.css', 'sito.css')))
    open(os.path.join(dst, 'assets', 'sito.css'), 'w', encoding='utf-8').write(css)
    return len(out), len(css)


def zip_plugin(slug):
    os.makedirs(DIST, exist_ok=True)
    z = os.path.join(DIST, slug + '.zip')
    with zipfile.ZipFile(z, 'w', zipfile.ZIP_DEFLATED) as zf:
        base = os.path.join(WP, slug)
        for dp, dn, fn in os.walk(base):
            dn.sort()
            for f in sorted(fn):
                full = os.path.join(dp, f)
                info = zipfile.ZipInfo(os.path.join(slug, os.path.relpath(full, base)), (2026, 1, 1, 0, 0, 0))
                info.compress_type = zipfile.ZIP_DEFLATED
                info.external_attr = 0o644 << 16
                zf.writestr(info, open(full, 'rb').read())  # date fisse: stesso contenuto = stesso sha256
    return z


def main():
    n, kb = pagine()
    print(f'socialee-pagine: {n} pagine, CSS {kb // 1024} KB')
    auto = json.load(open(os.path.join(WP, 'aggiornamenti.json'), encoding='utf-8'))
    os.makedirs(CONS, exist_ok=True)
    man = {'generato': datetime.datetime.now(datetime.timezone.utc).isoformat(timespec='seconds'), 'plugin': {}}
    for slug in PLUGINS:
        z = zip_plugin(slug)
        shutil.copy(z, os.path.join(CONS, slug + '.zip'))
        a = auto.get(slug)
        e = {'versione': version_of(slug), 'file': slug + '.zip', 'sha256': hashlib.sha256(open(z, 'rb').read()).hexdigest(), 'auto': a is True or a == 'installa'}
        if a == 'installa':
            e['installa'] = True
        man['plugin'][slug] = e
        print(f'  {slug:24} {e["versione"]:8} {os.path.getsize(z) // 1024:5} KB  auto={e["auto"]}{" installa" if a == "installa" else ""}')
    json.dump(man, open(os.path.join(CONS, 'versioni.json'), 'w', encoding='utf-8'), indent=1)


if __name__ == '__main__':
    main()
