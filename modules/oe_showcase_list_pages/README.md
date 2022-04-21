# OpenEuropa Showcase List Pages

The scope of this module is to demo the OpenEuropa Library List pages.
This module uses [Open Europa Listing Page](https://github.com/openeuropa/oe_list_pages) and enables [Open Europa Whitelabel List Pages](https://github.com/openeuropa/oe_whitelabel). This module contains some facets exported and a dedicated index
and server for the search.

## Create a list page and setting up bundles and filters
By default enables oe_list_pages, the news bundle as well as the title and publication date filters are available.

### Create list pages
* Go to add/oe_list_page.
* Enter a title to the list page.
* Expand the "List Page" section in the right panel.
* Select the bundle on "Source bundle".
* Check "Override default exposed filters" and select the desired exposed filters you may as well set default values for these filters.

### To set up new bundles

* Go to `config/search/search_api`.
* Add new index.
* Check Content on "Datasources".
* Select the desired bundle(s).
* Click on Save.
* Click on the tab fields to add the fields do be indexed.
* Click on save.

### Set up new filters
Filters are facets on search API module. To create new facets you should:
* Go to "admin/config/search/facets".
* Click on "+Add facet".
* Select the source and the fields added previously.
* Click on save.
