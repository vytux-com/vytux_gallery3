# vytux_gallery3

Display image galleries (albums) on your [webtrees](https://www.webtrees.net) site, using the jQuery-based [Galleria](https://galleria.io/) viewer.

Ported from the `simpl_gallery3` module by Nigel Osborne (kiwtrees.net), after its original author chose not to support webtrees 1.5. Ported to webtrees by Vytautas Krivickas ([vytux.com](https://vytux.com)).

> **Compatibility:** this module is for **webtrees 1.x** (1.6.0 or later). It does not work with webtrees 2.x.

Released October 2014, updated November 2014

Galleria 1.4.2 is included. No additional downloads are required, though extra Galleria themes are available if you want them.

## Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Upgrading](#upgrading)
- [Support](#support)

## Description

Turns each media sub-folder into a gallery page (an "album"), shown as a menu item and tab. Albums can be limited by access level, language and family tree.

## Requirements

- webtrees 1.6.0 or later

## Installation

1. Unzip the files and upload them to the webtrees `modules_vX/` folder on your web server (`X` is `3` at the time of writing, but may change in future releases).
2. Sign in to webtrees as an administrator, go to **Administration → Modules** and find **Vytux_Gallery3**. Make sure it is enabled.
3. Go to the **Menus** tab. Set the access level and position of the menu item. It will probably default to *Show only to managers*.
4. Click **Save**.

Refresh the Administration page and a new **vytux_gallery3** item appears near the bottom of the left column. You use it during setup.

## Setup

Setup has three parts: uploading images, creating a display page for each set of images, and adjusting the menu. Upload at least some images, and create their media folder, **before** creating the album page.

### 1. Upload images

1. Use **Administration → Upload media files**. In *Folder name on server*, enter a media sub-folder for the images. The module displays all images from one media folder on a single page.
2. During upload, or later by editing the image, you can add a **title** (shown to the right of the full-size image), a **note** (shown below the title), and links to one or more **individuals** (listed to the right, above the note).
3. If you are not linking an image to any individual, fact or event in your tree, open and save it once after uploading (even without changes) so that it is registered in the database.

### 2. Create one album per media sub-folder

Go to **Administration → vytux_gallery3** and click **Add album to gallery**.

1. Give the album a **title**. It is used as both the menu item and the tab label, so keep it short.
2. Add an optional **description**. Long text pushes the images down the page, so three or four lines at most is best.
3. Select the **media folder** containing the images.
4. Set the **access level**. This is in addition to any privacy controls on individual images linked to people in your tree.
5. Choose the **languages** to show the album in. Select none to show it in all languages. Select single languages only if you also plan to offer translated versions.
6. Set the **album position**. This decides where the album appears in the menu and tabs, and can be changed later by drag and drop on the vytux_gallery3 administration screen. The first album is the default when the main *Gallery* menu item is clicked.
7. Use **album visibility** to show the album in all family trees, or only the current one.
8. Click **Save**.

### Optional changes

- **Gallery page title:** defaults to "Picture galleries". Change it at row 630 of `vytux_gallery3/module.php`.
- **Menu item title:** taken from the module title, "Gallery" by default. Change it at row 39 of `vytux_gallery3/module.php`.
- **Menu icons:** each theme has its own icons in the module's `themes/<theme>/` folder (`webtrees`, `clouds`, `colors`, `xenea`, ...). Replace an icon with your own of a suitable size, keeping the same file name.
- **Fonts and colours:** edit `vytux_gallery3/themes/<theme>/style.css`.
- **Two kinds of theme:** the `themes/<theme>/` folders style the gallery container and menu icons, and switch automatically with the webtrees theme. Separately, the **Galleria theme** styles the image viewer itself (the black area in screenshots). Two are included, *Azur* and *Classic*. To switch, change line 504 of `vytux_gallery3/module.php`. To add others, download them from the [Galleria themes page](https://galleria.io/themes/) (some are paid), place them in `vytux_gallery3/galleria/themes/`, and enable them with the same edit.

## Upgrading

**From earlier vytux_gallery3 versions:** replace all the module files with the current ones.

**From `simpl_gallery3`:** albums cannot be transferred automatically. Delete the old module, then either re-create the albums or, **after** enabling the new module, run this SQL on your database:

```sql
UPDATE `wt_block` SET `module_name`='vytux_gallery3' WHERE `module_name`='simpl_gallery3';
```

(Adjust the `wt_` table prefix if you use a different one.)

## Support

- **Email:** [webtrees@vytux.com](mailto:webtrees@vytux.com) for vytux add-on support.
- **Forum:** general webtrees support is available on the [webtrees forum](https://www.webtrees.net/index.php/en/forum/index-en).

## Copyright

© 2013-2014 Vytautas Krivickas and vytux.com
