#!/usr/bin/env python3
"""Crée philadelphia-nivelles-site.zip, prêt à téléverser dans cPanel (sans config.php ni données)."""
import os, zipfile
here = os.path.dirname(os.path.abspath(__file__))
dist = os.path.join(here, '..', 'dist')
out = os.path.join(here, '..', 'philadelphia-nivelles-site.zip')
skip_dirs = {os.path.normpath(os.path.join(dist, 'public_html/cache/thumbs')), os.path.normpath(os.path.join(dist, 'philadelphia-private/data'))}
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(dist):
        rel_root = os.path.relpath(root, dist)
        for f in files:
            full = os.path.join(root, f)
            if f == 'config.php' and 'philadelphia-private' in root:
                continue
            if os.path.normpath(root) in skip_dirs and f not in ('.htaccess', '.gitkeep'):
                continue
            z.write(full, os.path.join(rel_root, f))
print('Créé :', os.path.normpath(out))
