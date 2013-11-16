<?php
// Module help text.
//
// This file is included from the application help_text.php script.
// It simply needs to set $title and $text for the help topic $help_topic
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id: help_text.php 13034 2011-12-12 04:03:13Z lukasz $

if (!defined('WT_WEBTREES') || !defined('WT_SCRIPT_NAME') || WT_SCRIPT_NAME!='help_text.php') {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

switch ($help) {
case 'add_album':
	$title=WT_I18N::translate('Add album to gallery');
	$text=WT_I18N::translate('The gallery should contains albums. Using this option you can add album details.');
	break;

case 'album_position':
	$title=WT_I18N::translate('Album position');
	$text=WT_I18N::translate('This field controls the order in which the gallery albums are displayed.').'<br><br>'.WT_I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other albums later. For example, if you use the numbers 1, 6, 11, 16, you can later insert albums with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert albums in front of the first one.').'<br><br>'.WT_I18N::translate('When more than one gallery album has the same position number, only one of these albums will be visible.');
	break;

case 'album_visibility':
	$title=WT_I18N::translate('Album visibility');
	$text=WT_I18N::translate('You can determine whether this album will be visible regardless of family tree, or whether it will be visible only to the current family tree.').
	'<br><ul><li><b>'.WT_I18N::translate('All').'</b>&nbsp;&nbsp;&nbsp;'.WT_I18N::translate('The album will appear in all galleries, regardless of family tree.').'</li><li><b>'.get_gedcom_setting(WT_GED_ID, 'title').'</b>&nbsp;&nbsp;&nbsp;'.WT_I18N::translate('The album will appear only in the currently active family trees\'s gallery.').'</li></ul>';
	break;

case 'delete_album':
	$title=WT_I18N::translate('Delete album');
	$text=WT_I18N::translate('This option will let you delete an album from the gallery.');
	break;

case 'edit_album':
	$title=WT_I18N::translate('Edit album');
	$text=WT_I18N::translate('This option will let you edit an album on the gallery.');
	break;

case 'movedown_album':
	$title=WT_I18N::translate('Move album down');
	$text=WT_I18N::translate('This option will let you move an album downwards on the gallery page.').'<br><br>'.WT_I18N::translate('Each time you use this option, the position number of this album is increased by one. You can achieve the same effect by editing the album and changing the album position field.').'<br><br>'.WT_I18N::translate('When more than one gallery album has the same position number, only one of these albums will be visible.');
	break;

case 'moveup_album':
	$title=WT_I18N::translate('Move album up');
	$text=WT_I18N::translate('This option will let you move an album upwards on the gallery page.').'<br><br>'.WT_I18N::translate('Each time you use this option, the position number of this album is reduced by one. You can achieve the same effect by editing the album and changing the album position field.').'<br><br>'.WT_I18N::translate('When more than one gallery album has the same position number, only one of these albums will be visible.');
	break;

case 'album_source':
	$title=WT_I18N::translate('Album source');
	$text=WT_I18N::translate('Here you can either select the webtrees media folder to display in this album page, or you can set a link to a Flickr or Picasa location for your group of images.'.
	'<br>'.
	'<em>[Such external sources must be public or they will not be viewable in webtrees.]</em>'.
	'<br><br>'.
	'For Flickr (www.flickr.com), enter the <strong>Set</strong> number of your images, usually a long number like <strong>72157633272831222</strong>. Nothing else is required in this field.'.
	'<br><br>'.
	'For Picassa (picasaweb.google.com) enter your user name and user album, in the format <strong>username/album</strong> like <strong>kiwi3685/NZImages</strong>'.
	'<br><br>'.
	'The module will add these references to the correct URLs to link to your Flickr or Picasa sites.');
	break;
}
