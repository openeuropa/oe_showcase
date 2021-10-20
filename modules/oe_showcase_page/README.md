# OpenEuropa Showcase Page

The OpenEuropa Showcase Page is part of the Showcase profile implementation, it aims to demo several features of the [OpenEuropa Showcase](https://github.com/openeuropa/oe_showcase) module and [OpenEuropa Whitelabel](https://github.com/openeuropa/oe_whitelabel) by providing a content type to create pages with multiple content configuration using several paragraph components that can be placed in any order.

The available paragraph types are:
- Accordion
- Accordion item
- Links block
- Listing item
- Listing Item Block
- Quote
- Rich text
- Text with Featured media
- Document

## Dependencies

- [Paragraphs](https://www.drupal.org/project/paragraphs)
- [OpenEuropa Paragraphs](https://github.com/openeuropa/oe_paragraphs)
- [OpenEuropa Media](https://github.com/openeuropa/oe_media)

## Related modules

The related modules below provide demo content and configuration for the oe_showcase_page module:

- [OpenEuropa Showcase Navigation](https://github.com/openeuropa/oe_showcase): Together with OpenEuropa Showcase Default Content provides default configuration of menu links to each showcase page.
- [OpenEuropa Showcase Default Content](https://github.com/openeuropa/oe_showcase): Provides default demo content with several pages demonstrating the use of paragraphs. This module is based on the [Default Content](https://www.drupal.org/project/default_content) module, for further instructions on how to export/import content please refer to its page for updated information.

## Installation

Enable this module using Drush or normally via the Drupal Module management list.

In the root of the project, run

```
$ ./vendor/bin/drush en oe_showcase_page
```
## Usage
### Adding paragraphs to a page:
To add paragraphs to a page, create a new page or edit an existing one, then on the body field click on the dropdown arrow to reveal the enabled paragraphs types.

Select the desired paragraph, e.g. Accordion (which contains several accordion items), fill out the required fields (Title and Body) To add a new Accordion item, click on [Add Accordion Item]. To add other paragraphs types click on the dropdown and select the desired component.
The order of each paragraph can also be rearranged  by dragging and dropping them using the left move cursor icon.

### Configuration
To add new paragraphs types to the body field (other then the ones enabled by default): Edit the body field by going to Administration > Structure > Content Types > OE Showcase Page > Manage Fields > Edit Body
`/admin/structure/types/manage/oe_showcase_page/fields/node.oe_showcase_page.field_body` on the Reference Type section enable the desired paragraph.

To export configuration using drush type:
```
$ ./vendor/bin/drush cde oe_showcase_page
```

To import configuration using drush type:
```
$ ./vendor/bin/drush cdi oe_showcase_page
```
