# OpenEuropa Showcase List Pages

The scope of this module is to demo the OpenEuropa Library List pages.
This module uses [Open Europa Listing Page](https://github.com/openeuropa/oe_list_pages) and enables [Open Europa Whitelabel List Pages](https://github.com/openeuropa/oe_whitelabel). This module contains some facets exported and a dedicated index
and server for the search.

## Create a list page and setting up bundles and filters
By default this module enables oe_list_pages module, the "news" bundle as well as the fields "title" and "publication date" filters.

### Create list pages
* Go to `node/add/oe_list_page`.
* Enter a title to the list page.
* Expand the "List Page" section in the right panel.
* Select the bundle on "Source bundle".
* Check "Override default exposed filters" and select the desired exposed filters you may as well set default values for these filters.

### To set up new bundles

* Go to `config/search/search-api`.
* Add new index.
* Check Content on "Datasources".
* Select the desired bundle(s).
* Click on Save.
* Click on the tab fields to add the fields do be indexed.
* Click on save.

### Set up new filters
Filters are facets on search API module. To create new facets you should:
* Go to `admin/config/search/facets`.
* Click on "+Add facet".
* Select the source and the fields added previously.
* Click on save.

### Note

To overcome an [issue](https://www.drupal.org/project/facets/issues/3262863) with facets 2 we used below patch:

```json
"drupal/facets": {
    "https://www.drupal.org/project/facets/issues/3262863": "https://git.drupalcode.org/project/facets/-/commit/88fd8a88a7206ea2e3828718fc404665ee20b064.diff"
}
```

To use labels instead of country codes in address facets we added a patch:
```json
"openeuropa/oe_list_pages": {
    "Support Address field in country code processor": "https://patch-diff.githubusercontent.com/raw/openeuropa/oe_list_pages/pull/139.diff"
}
```
