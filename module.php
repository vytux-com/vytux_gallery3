<?php
// webtrees - vytux_gallery3 module based on vytux_gallery3
//
// Copyright (C) 2013 Vytautas Krivickas and vytux.com. All rights reserved.
//
// Copyright (C) 2012 Nigel Osborne and kiwtrees.net. All rights reserved.
//
// webtrees: Web based Family History software
// Copyright (C) 2012 webtrees development team.
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
use WT\Auth;
use WT\Theme;

class vytux_gallery3_WT_Module extends WT_Module implements WT_Module_Menu, WT_Module_Block, WT_Module_Config {

	public function __construct() {
		parent::__construct();
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR.$this->getName().'/language')) {
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('csv', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv', WT_LOCALE)
				);
			}
		}
	}

	// Extend class WT_Module
	public function getTitle() {
		return WT_I18N::translate('Vytux Gallery 3');
	}

	public function getMenuTitle() {
		return WT_I18N::translate('Gallery');
	}

	// Extend class WT_Module
	public function getDescription() {
		return WT_I18N::translate('Display image galleries.');
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 40;
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return WT_PRIV_NONE;
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod='.$this->getName().'&amp;mod_action=admin_config';
	}

	// Implement class WT_Module_Block
	public function getBlock($block_id, $template=true, $cfg=null) {
	}

	// Implement class WT_Module_Block
	public function loadAjax() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isUserBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isGedcomBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function configureBlock($block_id) {
	}

	// Implement WT_Module_Menu
	public function getMenu() {
		global $controller, $SEARCH_SPIDER;
		
		$block_id=WT_Filter::get('block_id');
		$default_block=WT_DB::prepare(
			"SELECT block_id FROM `##block` WHERE block_order=? AND module_name=?"
		)->execute(array(0, $this->getName()))->fetchOne();

		if ($SEARCH_SPIDER) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/')) {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/webtrees/style.css" type="text/css">';
		}

		//-- main GALLERIES menu item
		$menu = new WT_Menu($this->getMenuTitle(), 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;album_id='.$default_block, 'menu-my_gallery', 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($this->getMenuAlbumList() as $item) {
			$languages=get_block_setting($item->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item->album_access>=WT_USER_ACCESS_LEVEL) {
				$path = 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;album_id='.$item->block_id;
				$submenu = new WT_Menu(WT_I18N::translate($item->album_title), $path, 'menu-my_gallery-'.$item->block_id);
				$menu->addSubmenu($submenu);
			}
		}
		if (Auth::isAdmin()) {
			$submenu = new WT_Menu(WT_I18N::translate('Edit albums'), $this->getConfigLink(), 'menu-my_gallery-edit');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'show':
			$this->show();
			break;
		case 'admin_config':
			$this->config();
			break;
		case 'admin_delete':
			$this->delete();
			$this->config();
			break;
		case 'admin_edit':
			$this->edit();
			break;
		case 'admin_movedown':
			$this->moveDown();
			$this->config();
			break;
		case 'admin_moveup':
			$this->moveUp();
			$this->config();
			break;
		default:
			http_response_code(404);
		}
	}

	// Action from the configuration page
	private function edit() {
		global $MEDIA_DIRECTORY;
		require_once WT_ROOT.'includes/functions/functions_edit.php';
		
		if (WT_Filter::postBool('save') && WT_Filter::checkCsrf()) {
			$block_id=WT_Filter::post('block_id');
			if ($block_id) {
				WT_DB::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					(int)WT_Filter::post('block_order'),
					$block_id
				));
			} else {
				WT_DB::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					$this->getName(),
					(int)WT_Filter::post('block_order')
				));
				$block_id=WT_DB::getInstance()->lastInsertId();
			}
			set_block_setting($block_id, 'album_title',		  WT_Filter::post('album_title')); // allow html
			set_block_setting($block_id, 'album_description', WT_Filter::post('album_description')); // allow html
			set_block_setting($block_id, 'album_folder_w',	  WT_Filter::post('album_folder_w'));
			set_block_setting($block_id, 'album_folder_f',	  WT_Filter::post('album_folder_f'));
			set_block_setting($block_id, 'album_folder_p',	  WT_Filter::post('album_folder_p'));
			set_block_setting($block_id, 'album_access',	  WT_Filter::post('album_access'));
			set_block_setting($block_id, 'plugin',			  WT_Filter::post('plugin'));
			$languages=array();
			foreach (WT_I18N::installed_languages() as $code=>$name) {
				if (WT_Filter::postBool('lang_'.$code)) {
					$languages[]=$code;
				}
			}
			set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id=WT_Filter::get('block_id');
			$controller=new WT_Controller_Page();
			if ($block_id) {
				$controller->setPageTitle(WT_I18N::translate('Edit album'));
				$item_title=get_block_setting($block_id, 'album_title');
				$item_description=get_block_setting($block_id, 'album_description');
				$item_folder_w=get_block_setting($block_id, 'album_folder_w');
				$item_folder_f=get_block_setting($block_id, 'album_folder_f');
				$item_folder_p=get_block_setting($block_id, 'album_folder_p');
				$item_access=get_block_setting($block_id, 'album_access');
				$plugin=get_block_setting($block_id, 'plugin');
				$block_order=WT_DB::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id=WT_DB::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(WT_I18N::translate('Add album to gallery'));
				$item_title='';
				$item_description='';
				$item_folder_w=$MEDIA_DIRECTORY;
				$item_folder_f='';
				$item_folder_p='';
				$item_access=1;
				$plugin='webtrees';
				$block_order=WT_DB::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
				$gedcom_id=WT_GED_ID;
			}
			$controller
				->pageHeader()
				->addInlineJavaScript('
					function hide_fields(){ 
						if (jQuery("#webtrees-radio").is(":checked")){ 
							jQuery("#fs_album_folder_w").prop("disabled", false);
							jQuery("#fs_album_folder_f").prop("disabled", true);
							jQuery("#fs_album_folder_p").prop("disabled", true);
						}
						else if (jQuery("#flickr-radio").is(":checked")){ 
							jQuery("#fs_album_folder_w").prop("disabled", true);
							jQuery("#fs_album_folder_f").prop("disabled", false);
							jQuery("#fs_album_folder_p").prop("disabled", true);
						}
						else if (jQuery("#picasa-radio").is(":checked")){ 
							jQuery("#fs_album_folder_w").prop("disabled", true);
							jQuery("#fs_album_folder_f").prop("disabled", true);
							jQuery("#fs_album_folder_p").prop("disabled", false);
						}
					};
					jQuery("#galleryForm").on("submit", function() {
						jQuery("#fs_album_folder_w").prop("disabled", false);
						jQuery("#fs_album_folder_f").prop("disabled", false);
						jQuery("#fs_album_folder_p").prop("disabled", false);
					});
				');
			
			if (array_key_exists('ckeditor', WT_Module::getActiveModules())) {
				ckeditor_WT_Module::enableEditor($controller);
			}
			?>
			
			<ol class="breadcrumb small">
				<li><a href="admin.php"><?php echo WT_I18N::translate('Control panel'); ?></a></li>
				<li><a href="admin_modules.php"><?php echo WT_I18N::translate('Module administration'); ?></a></li>
				<li><a href="module.php?mod=<?php echo $this->getName(); ?>&mod_action=admin_config"><?php echo WT_I18N::translate($this->getTitle()); ?></a></li>
				<li class="active"><?php echo $controller->getPageTitle(); ?></li>
			</ol>

			<form class="form-horizontal" method="POST" action="#" name="gallery" id="galleryForm">
				<?php echo WT_Filter::getCsrf(); ?>
				<input type="hidden" name="save" value="1">
				<input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
				<h3><?php echo WT_I18N::translate('General'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="title">
						<?php echo WT_I18N::translate('Title'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="title"
							size="90"
							name="album_title"
							required
							type="text"
							value="<?php echo WT_Filter::escapeHtml($item_title); ?>"
							>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="description">
						<?php echo WT_I18N::translate('Description'); ?>
					</label>
					<div class="col-sm-9">
						<textarea
							class="form-control html-edit"
							id="description"
							rows="10"
							cols="90"
							name="album_description"
							required
							type="text">
								<?php echo WT_Filter::escapeHtml($item_description); ?>
						</textarea>
					</div>
				</div>
				
				<h3><?php echo WT_I18N::translate('Source'); ?></h3>
				<span class="help-block small text-muted">
					<?php echo WT_I18N::translate('Here you can either select the webtrees media folder to display in this album page, or you can set a link to a Flickr or Picasa location for your group of images.<br><em>[Such external sources must be public or they will not be viewable in webtrees.]</em><br><br>The module will add these references to the correct URLs to link to your Flickr or Picasa sites.'); ?>
				</span>
				<div class="form-group">
					<label class="control-label col-sm-3" for="plugin">
						<?php echo WT_I18N::translate('Gallery Source'); ?>
					</label>
					<div class="row col-sm-9">
						<div class="col-sm-4">
							<label class="radio-inline">
								<input id="webtrees-radio" type="radio" name="plugin" value="webtrees" <?php if ($plugin=='webtrees') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo WT_I18N::translate('webtrees'); ?>
							</label>
						</div>
						<div class="col-sm-4">
							<label class="radio-inline ">
								<input id="flickr-radio" type="radio" name="plugin" value="flickr" <?php if ($plugin=='flickr') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo WT_I18N::translate('Flickr'); ?>
							</label>
						</div>
						<div class="col-sm-4">
							<label class="radio-inline ">
								<input id="picasa-radio" type="radio" name="plugin" value="picasa" <?php if ($plugin=='picasa') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo WT_I18N::translate('Picasa'); ?>
							</label>
						</div>
					</div>
				</div>
				<fieldset id="fs_album_folder_w" <?php if ($plugin!=='webtrees') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_w">
							<?php echo WT_I18N::translate('Folder name on server'); ?>
						</label>
						<div class="col-sm-9" >
							<div class="input-group">
								<span class="input-group-addon">
									<?php echo WT_DATA_DIR, $MEDIA_DIRECTORY; ?>
								</span>
								<?php echo select_edit_control('album_folder_w', WT_Query_Media::folderList(), null, htmlspecialchars($item_folder_w), 'class="form-control"'); ?>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset id="fs_album_folder_f" <?php if ($plugin!=='flickr') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_f">
							<?php echo WT_I18N::translate('Flickr set number'); ?>
						</label>
						<div class="col-sm-9">
							<input
							class="form-control"
							id="album_folder_f"
							size="90"
							name="album_folder_f"
							required
							type="number"
							value="<?php echo WT_Filter::escapeHtml($item_folder_f); ?>"
							>
						</div>
						<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
							<?php echo WT_I18N::translate('For Flickr (www.flickr.com), enter the <strong>Set</strong> number of your images, usually a long number like <strong>72157633272831222</strong>. Nothing else is required in this field.'); ?>
						</span>
					</div>
				</fieldset>
				<fieldset id="fs_album_folder_p" <?php if ($plugin!=='picasa') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_p">
							<?php echo WT_I18N::translate('Picasa user/album'); ?>
						</label>
						<div class="col-sm-9">
							<input
							class="form-control"
							id="album_folder_p"
							size="90"
							name="album_folder_p"
							required
							type="text"
							value="<?php echo WT_Filter::escapeHtml($item_folder_p); ?>"
							>
						</div>
						<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
							<?php echo WT_I18N::translate('For Picassa (picasaweb.google.com) enter your user name and user album, in the format <strong>username/album</strong> like <strong>kiwi3685/NZImages</strong>'); ?>
						</span>
					</div>
				</fieldset>
				
				<h3><?php echo WT_I18N::translate('Languages'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="lang_*">
						<?php echo WT_I18N::translate('Show this album for which languages?'); ?>
					</label>
					<div class="row col-sm-9">
						<?php 
							$accepted_languages=explode(',', get_block_setting($block_id, 'languages'));
							foreach (WT_I18N::installed_languages() as $locale => $language) {
								$checked = in_array($locale, $accepted_languages) ? 'checked' : ''; 
						?>
								<div class="col-sm-3">
									<label class="checkbox-inline "><input type="checkbox" name="lang_<?php echo $locale; ?>" <?php echo $checked; ?> ><?php echo $language; ?></label>
								</div>
						<?php 
							}
						?>
					</div>
				</div>
				
				<h3><?php echo WT_I18N::translate('Visibility and Access'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo WT_I18N::translate('Album position'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="position"
							name="block_order"
							size="3"
							required
							type="number"
							value="<?php echo WT_Filter::escapeHtml($block_order); ?>"
						>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo WT_I18N::translate('This field controls the order in which the gallery albums are displayed.'),
							'<br><br>',
							WT_I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other albums later. For example, if you use the numbers 1, 6, 11, 16, you can later insert albums with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert albums in front of the first one.'),
							'<br><br>',
							WT_I18N::translate('When more than one gallery album has the same position number, only one of these albums will be visible.'); 
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo WT_I18N::translate('Album visibility'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo select_edit_control('gedcom_id', WT_Tree::getIdList(), WT_I18N::translate('All'), $gedcom_id, 'class="form-control"'); ?>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo WT_I18N::translate('You can determine whether this album will be visible regardless of family tree, or whether it will be visible only to the selected family tree.'); 
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="album_access">
						<?php echo WT_I18N::translate('Access level'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo edit_field_access_level('album_access', $item_access, 'class="form-control"'); ?>
					</div>
				</div>
				
				<div class="row col-sm-9 col-sm-offset-3">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-check"></i>
						<?php echo WT_I18N::translate('save'); ?>
					</button>
					<button class="btn" type="button" onclick="window.location='<?php echo $this->getConfigLink(); ?>';">
						<i class="fa fa-close"></i>
						<?php echo WT_I18N::translate('cancel'); ?>
					</button>
				</div>
			</form>
<?php
		}
	}

	private function delete() {
		$block_id=WT_Filter::get('block_id');

		WT_DB::prepare(
			"DELETE FROM `##block_setting` WHERE block_id=?"
		)->execute(array($block_id));

		WT_DB::prepare(
			"DELETE FROM `##block` WHERE block_id=?"
		)->execute(array($block_id));
	}

	private function moveUp() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function moveDown() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function config() {
		require_once 'includes/functions/functions_edit.php';

		$controller=new WT_Controller_Page();
		$controller
			->setPageTitle($this->getTitle())
			->pageHeader();

		$albums=WT_DB::prepare(
			"SELECT block_id, block_order, gedcom_id, bs1.setting_value AS album_title, bs2.setting_value AS album_description".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='album_title'".
			" AND bs2.setting_name='album_description'".
			" AND IFNULL(gedcom_id, ?)=?".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID, WT_GED_ID))->fetchAll();

		$min_block_order=WT_DB::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		$max_block_order=WT_DB::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();
		?>
		
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo WT_I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo WT_I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		
		<div class="row">
			<div class="col-sm-4">
				<form class="form form-inline">
					<label for="ged" class="sr-only">
						<?php echo WT_I18N::translate('Family tree'); ?>
					</label>
					<input type="hidden" name="mod" value="<?php echo  $this->getName(); ?>">
					<input type="hidden" name="mod_action" value="admin_config">
					<?php echo select_edit_control('ged', WT_Tree::getNameList(), null, WT_GEDCOM, 'class="form-control"'); ?>
					<input type="submit" class="btn btn-primary" value="<?php echo WT_I18N::translate('show'); ?>">
				</form>
			</div>
			<div class="col-sm-4 text-center">
				<p>
					<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit" class="btn btn-primary">
						<i class="fa fa-plus"></i>
						<?php echo WT_I18N::translate('Add album to gallery'); ?>
					</a>
				</p>
			</div>
			<div class="col-sm-4 text-right">		
				<?php // TODO: Move to internal item/page
				if (file_exists(WT_MODULES_DIR.$this->getName().'/readme.html')) { ?>
					<a href="<?php echo WT_MODULES_DIR.$this->getName(); ?>/readme.html" class="btn btn-info">
						<i class="fa fa-newspaper-o"></i>
						<?php echo WT_I18N::translate('ReadMe'); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		
		<table class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo WT_I18N::translate('Position'); ?></th>
					<th class="col-sm-3"><?php echo WT_I18N::translate('Album title'); ?></th>
					<th class="col-sm-1" colspan=4><?php echo WT_I18N::translate('Controls'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($albums as $album): ?>
				<tr>
					<td>
						<?php echo $album->block_order, ', ';
						if ($album->gedcom_id==null) {
							echo WT_I18N::translate('All');
						} else {
							echo WT_Tree::get($album->gedcom_id)->titleHtml();
						} ?>
					</td>
					<td>
						<?php echo WT_Filter::escapeHtml(WT_I18N::translate($album->album_title)); ?>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit&amp;block_id=<?php echo $album->block_id; ?>">
							<div class="icon-edit">&nbsp;</div>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_moveup&amp;block_id=<?php echo $album->block_id; ?>">
							<?php
								if ($album->block_order==$min_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-uarrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_movedown&amp;block_id=<?php echo $album->block_id; ?>">
							<?php
								if ($album->block_order==$max_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-darrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_delete&amp;block_id=<?php echo $album->block_id; ?>"
							onclick="return confirm('<?php echo WT_I18N::translate('Are you sure you want to delete this album?'); ?>');">
							<div class="icon-delete">&nbsp;</div>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
<?php
	}

	private function getJavaScript($item_id) {
		$theme = "classic";// alternatives: "azur"
		$plugin=get_block_setting($item_id, 'plugin');
		$js='Galleria.loadTheme("'.WT_STATIC_URL.WT_MODULES_DIR.$this->getName().'/galleria/themes/'.$theme.'/galleria.'.$theme.'.js");';
			switch ($plugin) {
			case 'flickr':
			$flickr_set = get_block_setting($item_id, 'album_folder_f');
			$js.='
				Galleria.run("#galleria", {
					flickr: "set:'.$flickr_set.'",
					flickrOptions: {
						sort: "date-posted-asc",
						description: true,
						imageSize: "big"
					},
					_showCaption: false,
					imageCrop: true,
					decription: true
				});
			';
			break;
			case 'picasa':
			$picasa_set = get_block_setting($item_id, 'album_folder_p');
			$js.='
				Galleria.run("#galleria", {
					picasa: "useralbum:'.$picasa_set.'",
					picasaOptions: {
						sort: "date-posted-asc"
					},
					_showCaption: false,
					imageCrop: true
				});
			';
			break;
			default:		
			$js.='
				Galleria.ready(function(options) {
					this.bind("image", function(e) {
						data = e.galleriaData;
						$("#links_bar").html(data.layer);
					});
				});
				Galleria.run("#galleria", {
					_showCaption: false,
					_locale: {
						show_captions:		"'.WT_I18N::translate('Show descriptions').'",
						hide_captions:		"'.WT_I18N::translate('Hide descriptions').'",
						play:				"'.WT_I18N::translate('Play slideshow').'",
						pause:				"'.WT_I18N::translate('Pause slideshow').'",
						enter_fullscreen:	"'.WT_I18N::translate('Enter fullscreen').'",
						exit_fullscreen:	"'.WT_I18N::translate('Exit fullscreen').'",
						next:				"'.WT_I18N::translate('Next image').'",
						prev:				"'.WT_I18N::translate('Previous image').'",
						showing_image:		"" // counter not compatible with I18N of webtrees
					}
				});
			';			
			break;
		}
		return $js;
	}

	// Return the list of albums
	private function getAlbumList() {
		return WT_DB::prepare(
			"SELECT block_id, 
				bs1.setting_value AS album_title, 
				bs2.setting_value AS album_access, 
				bs3.setting_value AS album_description, 
				bs4.setting_value AS album_folder_w, 
				bs5.setting_value AS album_folder_f, 
				bs6.setting_value AS album_folder_p".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" JOIN `##block_setting` bs3 USING (block_id)".
			" JOIN `##block_setting` bs4 USING (block_id)".
			" JOIN `##block_setting` bs5 USING (block_id)".
			" JOIN `##block_setting` bs6 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='album_title'".
			" AND bs2.setting_name='album_access'".
			" AND bs3.setting_name='album_description'".
			" AND bs4.setting_name='album_folder_w'".
			" AND bs5.setting_name='album_folder_f'".
			" AND bs6.setting_name='album_folder_p'".
			" AND (gedcom_id IS NULL OR gedcom_id=?)".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID))->fetchAll();
	}
	
	// Return the list of albums for menu
	private function getMenuAlbumList() {
		return WT_DB::prepare(
			"SELECT block_id, bs1.setting_value AS album_title, bs2.setting_value AS album_access".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='album_title'".
			" AND bs2.setting_name='album_access'".
			" AND (gedcom_id IS NULL OR gedcom_id=?)".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID))->fetchAll();
	}
	
	// Print the Notes for each media item
	static function FormatGalleryNotes($haystack) {
		$needle   = '1 NOTE';
		$before   = substr($haystack, 0, strpos($haystack, $needle));
		$after    = substr(strstr($haystack, $needle), strlen($needle));
		$final    = $before.$needle.$after;
		$notes    = print_fact_notes($final, 1, true, true);
		if ($notes !='' && $notes != '<br>') {
			$html = htmlspecialchars($notes);
			return $html;
		}
		return false;
	}

	// Start to show the gallery display with the parts common to all galleries
	private function show() {
		global $MEDIA_DIRECTORY, $controller;
		$gallery_header_description = '';
		$item_id=WT_Filter::get('album_id');
		$controller=new WT_Controller_Page();
		$controller
			->setPageTitle(WT_I18N::translate('Picture galleries'))
			->pageHeader()
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.$this->getName().'/galleria/galleria-1.4.2.min.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.$this->getName().'/galleria/plugins/flickr/galleria.flickr.min.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.$this->getName().'/galleria/plugins/picasa/galleria.picasa.min.js')
			->addInlineJavaScript($this->getJavaScript($item_id));
		?>
		<div id="gallery-page">
			<div id="gallery-container">
				<h2><?php echo $controller->getPageTitle(); ?></h2>
				<?php echo $gallery_header_description; ?>
				<div style="clear:both;"></div>
				<div id="gallery_tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
					<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
					<?php 
						$item_list=$this->getAlbumList();
						foreach ($item_list as $item) {
							$languages=get_block_setting($item->block_id, 'languages');
							if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item->album_access>=WT_USER_ACCESS_LEVEL) { ?>
								<li class="ui-state-default ui-corner-top <?php echo ($item_id==$item->block_id ? ' ui-tabs-selected ui-state-active' : ''); ?>">
									<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=show&amp;album_id=<?php echo $item->block_id; ?>">
										<span title="<?php echo WT_I18N::translate($item->album_title); ?>"><?php echo WT_I18N::translate($item->album_title); ?></span>
									</a>
								</li>
							<?php 
							}
						} 
					?>
					</ul>
					<div id="outer_gallery_container" style="padding: 1em;">
					<?php 
						foreach ($item_list as $item) {
							$languages=get_block_setting($item->block_id, 'languages');
							if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item_id==$item->block_id && $item->album_access>=WT_USER_ACCESS_LEVEL) {
								$item_gallery='<h4>'.WT_I18N::translate($item->album_description).'</h4>'.$this->mediaDisplay($item->album_folder_w, $item_id);
							}
						}
						if (!isset($item_gallery)) {
							echo '<h4>'.WT_I18N::translate('Image collections related to our family').'</h4>'.$this->mediaDisplay('//', $item_id);
						} else {
							echo $item_gallery;
						}
					?>
					</div>
				</div>
			</div>
			<?php
	}

	// Print the gallery display
	private function mediaDisplay($sub_folder, $item_id) {
		global $MEDIA_DIRECTORY;
		$plugin=get_block_setting($item_id, 'plugin');
		$images=''; 
		// Get the related media items
		$sub_folder=str_replace($MEDIA_DIRECTORY, "",$sub_folder);
		$sql = "SELECT * FROM ##media WHERE m_filename LIKE '%" . $sub_folder . "%' ORDER BY m_filename";
		$rows=WT_DB::prepare($sql)->execute()->fetchAll(PDO::FETCH_ASSOC);
		if ($plugin=='webtrees') {
			foreach ($rows as $rowm) {
				// Get info on how to handle this media file
				$media=WT_Media::getInstance($rowm['m_id']);
				if ($media->canShow()) {
					$links = array_merge(
						$media->linkedIndividuals('OBJE'),
						$media->linkedFamilies('OBJE'),
						$media->linkedSources('OBJE')
					);
					$rawTitle = $rowm['m_titl'];
					if (empty($rawTitle)) $rawTitle = get_gedcom_value('TITL', 2, $rowm['m_gedcom']);
					if (empty($rawTitle)) $rawTitle = basename($rowm['m_filename']);
					$mediaTitle = htmlspecialchars(strip_tags($rawTitle));
					$rawUrl = $media->getHtmlUrlDirect();
					$thumbUrl = $media->getHtmlUrlDirect('thumb');
					$media_notes = $this->FormatGalleryNotes($rowm['m_gedcom']);
					$mime_type = $media->mimeType();
					$gallery_links='';
					if (WT_USER_CAN_EDIT) {
						$gallery_links.='<div class="edit_links">';
							$gallery_links.='<div class="image_option"><a href="'. $media->getHtmlUrl(). '"><img src="'.WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/edit.png" title="'.WT_I18N::translate('Edit').'"></a></div>';
							if (WT_USER_GEDCOM_ADMIN) {
								if (array_key_exists('GEDFact_assistant', WT_Module::getActiveModules())) {
									$gallery_links.='<div class="image_option"><a onclick="return ilinkitem(\''.$rowm['m_id'].'\', \'manage\')" href="#"><img src="'.WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/link.png" title="'.WT_I18N::translate('Manage links').'"></a></div>';
								}
							}
						$gallery_links.='</div><hr>';// close .edit_links
					}						
					if ($links) {
						$gallery_links .='<h4>'.WT_I18N::translate('Linked to:').'</h4>';
						$gallery_links .='<div id="image_links">';
							foreach ($links as $record) {
									$gallery_links .= '<a href="' . $record->getHtmlUrl() . '">' . $record->getFullname().'</a><br>';
							}
						$gallery_links.='</div>';
					}
					$media_links = htmlspecialchars($gallery_links);
					if ($mime_type == 'application/pdf'){ 
						$images.='<a href="'.$rawUrl.'"><img class="iframe" src="'.$thumbUrl.'" data-title="'.$mediaTitle.'" data-layer="'.$media_links.'" data-description="'.$media_notes.'"></a>';
					} else {
						$images.='<a href="'.$rawUrl.'"><img src="'.$thumbUrl.'" data-title="'.$mediaTitle.'" data-layer="'.$media_links.'" data-description="'.$media_notes.'"></a>';
					}
				}
			}
			if (WT_USER_CAN_ACCESS || (isset($media_links) && $media_links != '')) {
				$html=
					'<div id="links_bar"></div>'.
					'<div id="galleria" style="width:80%;">';
			} else {
				$html=
					'<div id="galleria" style="width:100%;">';
			}
		} else {
			$html = '<div id="galleria" style="width:100%;">';
			$images.='&nbsp;';
		}
		if ($images) {
			$html.=$images.
				'</div>'.// close #galleria
				'<a id="copy" href="http://galleria.io/">Display by Galleria</a>'.// gallery.io attribution
				'</div>'.// close #page
				'<div style="clear: both;"></div>';
		} else {
			$html.=WT_I18N::translate('Album is empty. Please choose other album.').
				'</div>'.// close #galleria
				'</div>'.// close #page
				'<div style="clear: both;"></div>';
		}
		return $html;
	}
}
