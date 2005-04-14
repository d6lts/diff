DESCRIPTION
----------------

This module adds a tab for sufficiently permissioned users. The tab shows all revisions like standard Drupal but it also allows pretty viewing of all added/changed/deleted words between revisions.

TECHNICAL
-------------------

The PEAR Diff library comes with this module, and powers the comparing of revisions.

TODO
-----------------
Fix an 'off by one' bug when viewing differences
Handle custom node types better. currently only looks for changes in $node->body