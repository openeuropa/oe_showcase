# OpenEuropa Showcase List Pages

The scope of this module is to demo the OpenEuropa Library List pages.
This module uses [Open Europa Listing Page](https://github.com/openeuropa/oe_list_pages) and enables [Open Europa Whitelabel List Pages](https://github.com/openeuropa/oe_whitelabel). This module contains some facets exported and a dedicated index
and server for the search.

## Setting up bundles and filters

By default when enabling oe_list_pages the news bundle is enabled by default, title and publication date are enabled filters.

To add new bundles you should:
* Go to configuration -> search API.
* Add new index.
* Select the desired bundle.
* Click on save and add fields.
* Add the desired fields.
* Click on save.

To set up new filters:
Filters are facets on search API module, to create new facets do the following:
* Go to config facets.
* Click on add facets.
* Select the source and the fields added previously.
* Click on save.

Then you will find your bundles and facets when you go to add/oe_list_page.
