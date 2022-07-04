# OpenEuropa Showcase List Pages

This module contains example configuration for [Open Europa List Pages](https://github.com/openeuropa/oe_list_pages).

## What are list pages?

[OpenEuropa List Pages](https://github.com/openeuropa/oe_list_pages) allows content editors to create content listings directly on a production website, without the need for admin-level permissions or new code to be deployed each time.

A site builder will create and deploy a search index and search facets, and then content editors can create list pages for any content type that is indexed in the search index.

List pages are regular nodes, just with some additional settings and functionality.

## Preparation

If you are using OE Showcase, with this module already enabled, users with the "editor" role will be able to create list pages for some of the content types present in OE Showcase.

Otherwise, follow the steps in "Instructions for site builders" further below.

## Instructions for content editors

As a content editor (OE Showcase), or any user with permission to create 'List page' content:

* Visit `node/add/oe_list_page`.
* Enter a node title.
* Expand the "List Page" section in the right panel.
* In "Source entity type", keep "Content" to show a list of nodes.
* In "Source bundle", choose the content type, e.g. "News".
* Check "Override default exposed filters" to reveal an exposed filters subform.
* Select the desired exposed filters.
* (optional) Choose default filter values.
* Save.

The new page should show a list of content of the chosen type, and filters in the sidebar. (You may need to create some example content to make it work.)

## Instructions for site builders

This section describes how to set up list pages outside of OE Showcase.

### Required modules

Look at the dependencies sections in `oe_showcase_list_pages.info.yml` and `oe_whitelabel_list_pages.info.yml`.

Composer packages:

- `openeuropa/oe_list_pages`
  - `drupal/facets` and `drupal/search_api` will be installed as dependencies
  - Both `drupal/facets` and `openeuropa/oe_list_pages` may need a patch, see "Notes" below.
- `drupal/extra_field`
- `openeuropa/oe_whitelabel` - if you want OpenEuropa Whitelabel as a theme.

Modules:

- `oe_list_pages` for the basic list pages functionality.
- `oe_list_page_content_type` for a pre-defined content type that acts as a list page.
- `extra_field` to have list and filters available as extra fields in the view mode.
- `oe_whitelabel_list_pages` for theming.

### Search index

Create a search index to make specific content types available for use in list pages:

* Visit `config/search/search-api`.
* Create a search server, unless you want to reuse one that already exists.
* Click "Add index".
* Enter an index name, e.g. "List pages index", and review or adjust the machine name.
  * The index will be reused across content types, so a machine name like `myproject_list_pages_index` is fine.
  * It is probably a good idea to have dedicated search indices for list pages and for regular site search.
* In "Datasources", choose "Content".
* In "Bundles", choose the content types that should be indexed.
* Click "Save and add fields".
* In the "Fields" tab, add the fields you need:
  * The "Content type" field is required if the index covers multiple content types.
  * Add more fields to prepare for search facets.
* Click "Save".

### Facets

Create facets that can be added as filters in a list page:

* Visit `admin/config/search/facets`.
* Click "Add facet" to add facets:
  * In "Facet source", choose e.g. "List node:oe_sc_news", if the content type machine name is `oe_sc_news`.
  * In "Field", choose one of the fields created earlier.
  * Enter a name/label, and review the machine name:
    * Facets exist in a global namespace, so their machine names should be properly prefixed to make them distinguishable and prevent conflicts.
    * Facets and facet sources for list pages are _not_ reused across content types, so you may need to create multiple facets for the same field. This means the machine name prefix should include the content type.
  * Click "Save". This should open the "Edit" tab for the new facet.
  * In "Widget", it is recommended to choose one of the widgets provided by `oe_list_pages`, such as "List pages date", "List pages fulltext" or "List pages multiselect".
* On the facets overview page, the new facets should appear in a facet source like `'list_facet_source:node:oe_sc_news'`.

### Permissions

All you need is a role (e.g. "editor") with permission to create list page content.

### Conclusion

Now users with the respective permission will be able to create list pages, as described above.

You may export the configuration and deploy on production.

## Notes

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
