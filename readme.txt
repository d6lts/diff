DESCRIPTION
----------------
This module adds a 'diff' tab for sufficiently permissioned users. The tab shows all revisions similar standard Drupal
but it also provides pretty viewing of all added/changed/deleted words between revisions.

INSTALL
----------------
Install as usual for Drupal modules

TECHNICAL
-------------------
- we are comparing the whole node body after it passes through the output filters. This is the analogous the chunk
of HTML which gets indexed by search.module.
- The PEAR Diff library comes with this module, and powers the comparing of revisions.

TODO
-----------------
consider using in core.
