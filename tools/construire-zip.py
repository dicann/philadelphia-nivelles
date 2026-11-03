#!/usr/bin/env python3
"""Crée les archives à téléverser dans cPanel (sans config.php, sans données, sans cache).

  philadelphia-nivelles-site.zip  : public_html/ + philadelphia-private/ (installation complète)
  1-site.zip                      : le CONTENU de public_html, à extraire directement dans le dossier du site
  2-philadelphia-private.zip      : le dossier philadelphia-private, à extraire dans le dossier principal du compte
"""
import os, zipfile

here = os.path.dirname(os.path.abspath(__file__))
base = os.path.normpath(os.path.join(here, '..'))
dist = os.path.join(base, 'dist')
skip_dirs = {os.path.normpath(os.path.join(dist, p)) for p in ('public_html/cache/thumbs', 'philadelphia-private/data')}


def files(sub):
    for root, _, names in os.walk(os.path.join(dist, sub)):
        for f in names:
            if f == 'config.php' and 'philadelphia-private' in root:
                continue
            if os.path.normpath(root) in skip_dirs and f not in ('.htaccess', '.gitkeep'):
                continue
            yield os.path.join(root, f)


def make(name, subs, strip):
    out = os.path.join(base, name)
    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        for sub in subs:
            for full in files(sub):
                z.write(full, os.path.relpath(full, os.path.join(dist, strip)))
    print('Créé :', out)


make('philadelphia-nivelles-site.zip', ['public_html', 'philadelphia-private'], '')
make('1-site.zip', ['public_html'], 'public_html')
make('2-philadelphia-private.zip', ['philadelphia-private'], '')
