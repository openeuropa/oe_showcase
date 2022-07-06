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

### Composer dependencies

Packages:

- `openeuropa/oe_list_pages`
  - `drupal/facets` and `drupal/search_api` will be installed as dependencies.
- `drupal/extra_field`
- `openeuropa/oe_whitelabel` - if you want OpenEuropa Whitelabel as a theme.

Patches:

- `drupal/facets` needs a patch to fix [#3262863: setHierarchy method causing the fatal error on facets settings form submission](https://www.drupal.org/project/facets/issues/3262863).

Look at the `composer.json` in `oe_showcase` for up-to-date version numbers and patch urls.

### Drupal modules

The following modules should be installed, and/or added as dependencies:

- `oe_list_pages` for the basic list pages functionality.
- `oe_list_page_content_type` for a pre-defined content type that acts as a list page.
- `extra_field` to have list and filters available as extra fields in the view mode.
- `oe_whitelabel_list_pages` for theming.

You may look at the dependencies sections in `oe_showcase_list_pages.info.yml` and `oe_whitelabel_list_pages.info.yml`.

### Search index

Create a search index to make specific content types available for use in list pages:

* Visit `config/search/search-api`.
* Create a search server, unless you want to reuse one that already exists.
* Click "Add index".
* Enter an index name, e.g. "List pages index". Review or adjust the machine name, e.g. `myproject_list_pages_index` or just `list_pages_index`.
  * _Note: We recommend to have one shared search index for all list pages, but to have distinct search indices for other purposes like site search.
* In "Datasources", choose "Content".
* In "Bundles", choose the content types that should be indexed.
* Click "Save and add fields".
* In the "Fields" tab, add the fields:
  * Add the "Content type" field, if the index covers more than one content type.
  * Add additional fields as needed to create facets later.
* Click "Save".
* Clear the cache, e.g. with `drush cr`.
  * This is needed for newly added fields and content types to become available when creating facets.
* Run the indexing process for the newly created or updated search index.

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
  * In "Widget", choose one of the widgets provided by `oe_list_pages`, such as "List pages date", "List pages fulltext" or "List pages multiselect".
    * To be more precise, only widget are supported that implement `ListPagesWidgetInterface`. Other widgets won't show up as filters on the list page.
* On the facets overview page, the new facets should appear in a facet source like `'list_facet_source:node:oe_sc_news'`.

### Permissions

All you need is a role (e.g. "editor") with permission to create list page content.

### Conclusion

Now users with the respective permission will be able to create list pages, as described above.

You may export the configuration and deploy on production.
