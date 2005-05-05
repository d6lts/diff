DESCRIPTION
----------------
This module adds a tab for sufficiently permissioned users. The tab shows all revisions like standard Drupal
but it also allows pretty viewing of all added/changed/deleted words between revisions.

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
gather feedback and then consider using in core.
