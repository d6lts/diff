DESCRIPTION
----------------
This module adds 'diff' functionality to the 'Revisions' tab, allowing
users to nicely view all the changes between any two revisions of a node.

INSTALL
----------------
Install as usual for Drupal modules

TECHNICAL
-------------------
- This version compares the raw data, not the filtered output, making
it easier to see changes to HTML entities, etc.
- The diff engine itself is a GPL'ed php diff engine from phpwiki.

TODO
-----------------
consider using in core.
