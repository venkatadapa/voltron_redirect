This module will redirect the old platform urls (where page id matters) to new 
drupal page after migration. 

ex: <old-platform>/someurl-1234.htm will redirect to respective page in drupal
system.

This old page id (1234 here as an example) needs to be stored in a field called 
field_old_page_id in page content type during content migration.
