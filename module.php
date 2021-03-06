<?php
namespace Vytux\webtrees_vytux_gallery3;

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
use PDO;
use Fisharebest\Webtrees as webtrees;

class VytuxGallery3Module extends webtrees\Module\AbstractModule implements webtrees\Module\ModuleBlockInterface, webtrees\Module\ModuleConfigInterface, webtrees\Module\ModuleMenuInterface  {

    const CUSTOM_VERSION = '1.7.5';
    const CUSTOM_WEBSITE = 'https://vytux.com/main/projects/webtrees/vytux_gallery3/';

	public function __construct() {
		parent::__construct('vytux_gallery3');
	}
	
	// Extend class WT_Module
	public function getTitle() {
		return webtrees\I18N::translate('Vytux Gallery 3');
	}

	public function getMenuTitle() {
		return webtrees\I18N::translate('Gallery');
	}

	// Extend class WT_Module
	public function getDescription() {
		return webtrees\I18N::translate('Display image galleries.');
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 40;
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
        return webtrees\Auth::PRIV_NONE;
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin_config';
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
		global $controller, $WT_TREE;
		
		$args                = array();
		$args['block_order'] = 0;
		$args['module_name'] = $this->getName();
		
		$block_id = webtrees\Filter::get('block_id');
		$default_block = webtrees\Database::prepare(
			"SELECT block_id FROM `##block` WHERE block_order=:block_order AND module_name=:module_name"
		)->execute($args)->fetchOne();

		if (webtrees\Auth::isSearchEngine()) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/')) {
			echo '<link rel="stylesheet" href="' . WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="' . WT_MODULES_DIR . $this->getName() . '/themes/webtrees/style.css" type="text/css">';
		}

		//-- main GALLERIES menu item
		$menu = new webtrees\Menu($this->getMenuTitle(), 'module.php?mod=' . $this->getName() . '&amp;mod_action=show&amp;album_id=' . $default_block, $this->getName());
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($this->getMenuAlbumList() as $item) {
			$languages = $this->getBlockSetting($item->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item->album_access >= webtrees\Auth::accessLevel($WT_TREE)) {
				$path = 'module.php?mod=' . $this->getName() . '&amp;mod_action=show&amp;album_id=' . $item->block_id;
				$submenu = new webtrees\Menu(webtrees\I18N::translate($item->album_title), $path, $this->getName() . '-' . $item->block_id);
				$menu->addSubmenu($submenu);
			}
		}
		if (webtrees\Auth::isAdmin()) {
			$submenu = new webtrees\Menu(webtrees\I18N::translate('Edit albums'), $this->getConfigLink(), $this->getName() . '-edit');
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
		global $MEDIA_DIRECTORY, $WT_TREE;
		$args = array();
		
		if (webtrees\Filter::postBool('save') && webtrees\Filter::checkCsrf()) {
			$block_id = webtrees\Filter::post('block_id');
			
			if ($block_id) {
				$args['tree_id']     = webtrees\Filter::post('gedcom_id');
				$args['block_order'] = (int)webtrees\Filter::post('block_order');
				$args['block_id']    = $block_id;
				webtrees\Database::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(:tree_id, ''), block_order=:block_order WHERE block_id=:block_id"
				)->execute($args);
			} else {
				$args['tree_id']     = webtrees\Filter::post('gedcom_id');
				$args['module_name'] = $this->getName();
				$args['block_order'] = (int)webtrees\Filter::post('block_order');
				webtrees\Database::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(:tree_id, ''), :module_name, :block_order)"
				)->execute($args);
				$block_id = webtrees\Database::getInstance()->lastInsertId();
			}
			$this->setBlockSetting($block_id, 'album_title',       webtrees\Filter::post('album_title')); // allow html
			$this->setBlockSetting($block_id, 'album_description', webtrees\Filter::post('album_description')); // allow html
			$this->setBlockSetting($block_id, 'album_folder_w',	   webtrees\Filter::post('album_folder_w'));
			$this->setBlockSetting($block_id, 'album_folder_f',	   webtrees\Filter::post('album_folder_f'));
			$this->setBlockSetting($block_id, 'album_folder_p',	   webtrees\Filter::post('album_folder_p'));
			$this->setBlockSetting($block_id, 'album_access',	   webtrees\Filter::post('album_access'));
			$this->setBlockSetting($block_id, 'plugin',			   webtrees\Filter::post('plugin'));
			$languages = array();
			foreach (webtrees\I18N::activeLocales() as $locale) {
				$code = $locale->languageTag();
				$name = $locale->endonym();
				if (webtrees\Filter::postBool('lang_'.$code)) {
					$languages[] = $code;
				}
			}
			$this->setBlockSetting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id = webtrees\Filter::get('block_id');
			$controller = new webtrees\Controller\PageController();
            $controller->restrictAccess(webtrees\Auth::isEditor($WT_TREE));
			if ($block_id) {
				$controller->setPageTitle(webtrees\I18N::translate('Edit album'));
				$item_title       = $this->getBlockSetting($block_id, 'album_title');
				$item_description = $this->getBlockSetting($block_id, 'album_description');
				$item_folder_w    = $this->getBlockSetting($block_id, 'album_folder_w');
				$item_folder_f    = $this->getBlockSetting($block_id, 'album_folder_f');
				$item_folder_p    = $this->getBlockSetting($block_id, 'album_folder_p');
				$item_access      = $this->getBlockSetting($block_id, 'album_access');
				$plugin           = $this->getBlockSetting($block_id, 'plugin');
				$args['block_id'] = $block_id;
				$block_order      = webtrees\Database::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=:block_id"
				)->execute($args)->fetchOne();
				$gedcom_id        = webtrees\Database::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=:block_id"
				)->execute($args)->fetchOne();
			} else {
				$controller->setPageTitle(webtrees\I18N::translate('Add album to gallery'));
				$item_title          = '';
				$item_description    = '';
				$item_folder_w       = $MEDIA_DIRECTORY;
				$item_folder_f       = '';
				$item_folder_p       = '';
				$item_access         = 1;
				$plugin              = 'webtrees';
				$args['module_name'] = $this->getName();
				$block_order         = webtrees\Database::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=:module_name"
				)->execute($args)->fetchOne();
                $gedcom_id           = $WT_TREE->getTreeId();
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
			
			if (webtrees\Module::getModuleByName('ckeditor')) {
				webtrees\Module\CkeditorModule::enableEditor($controller);
			}
			?>
			
			<ol class="breadcrumb small">
				<li><a href="admin.php"><?php echo webtrees\I18N::translate('Control panel'); ?></a></li>
				<li><a href="admin_modules.php"><?php echo webtrees\I18N::translate('Module administration'); ?></a></li>
				<li><a href="module.php?mod=<?php echo $this->getName(); ?>&mod_action=admin_config"><?php echo webtrees\I18N::translate($this->getTitle()); ?></a></li>
				<li class="active"><?php echo $controller->getPageTitle(); ?></li>
			</ol>

			<form class="form-horizontal" method="POST" action="#" name="gallery" id="galleryForm">
				<?php echo webtrees\Filter::getCsrf(); ?>
				<input type="hidden" name="save" value="1">
				<input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
				<h3><?php echo webtrees\I18N::translate('General'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="title">
						<?php echo webtrees\I18N::translate('Title'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="title"
							size="90"
							name="album_title"
							required
							type="text"
							value="<?php echo webtrees\Filter::escapeHtml($item_title); ?>"
							>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="description">
						<?php echo webtrees\I18N::translate('Description'); ?>
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
								<?php echo webtrees\Filter::escapeHtml($item_description); ?>
						</textarea>
					</div>
				</div>
				
				<h3><?php echo webtrees\I18N::translate('Source'); ?></h3>
				<span class="help-block small text-muted">
					<?php echo webtrees\I18N::translate('Here you can either select the webtrees media folder to display in this album page, or you can set a link to a Flickr or Picasa location for your group of images.<br><em>[Such external sources must be public or they will not be viewable in webtrees.]</em><br><br>The module will add these references to the correct URLs to link to your Flickr or Picasa sites.'); ?>
				</span>
				<div class="form-group">
					<label class="control-label col-sm-3" for="plugin">
						<?php echo webtrees\I18N::translate('Gallery Source'); ?>
					</label>
					<div class="row col-sm-9">
						<div class="col-sm-4">
							<label class="radio-inline">
								<input id="webtrees-radio" type="radio" name="plugin" value="webtrees" <?php if ($plugin=='webtrees') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo webtrees\I18N::translate('webtrees'); ?>
							</label>
						</div>
						<div class="col-sm-4">
							<label class="radio-inline ">
								<input id="flickr-radio" type="radio" name="plugin" value="flickr" <?php if ($plugin=='flickr') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo webtrees\I18N::translate('Flickr'); ?>
							</label>
						</div>
						<div class="col-sm-4">
							<label class="radio-inline ">
								<input id="picasa-radio" type="radio" name="plugin" value="picasa" <?php if ($plugin=='picasa') {echo 'checked'; } ?> onclick="hide_fields();"><?php echo webtrees\I18N::translate('Picasa'); ?>
							</label>
						</div>
					</div>
				</div>
				<fieldset id="fs_album_folder_w" <?php if ($plugin!=='webtrees') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_w">
							<?php echo webtrees\I18N::translate('Folder name on server'); ?>
						</label>
						<div class="col-sm-9" >
							<div class="input-group">
								<span class="input-group-addon">
									<?php echo WT_DATA_DIR, $MEDIA_DIRECTORY; ?>
								</span>
								<?php echo webtrees\Functions\FunctionsEdit::selectEditControl('album_folder_w', webtrees\Query\QueryMedia::folderList(), null, htmlspecialchars($item_folder_w), 'class="form-control"'); ?>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset id="fs_album_folder_f" <?php if ($plugin!=='flickr') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_f">
							<?php echo webtrees\I18N::translate('Flickr set number'); ?>
						</label>
						<div class="col-sm-9">
							<input
							class="form-control"
							id="album_folder_f"
							size="90"
							name="album_folder_f"
							required
							type="number"
							value="<?php echo webtrees\Filter::escapeHtml($item_folder_f); ?>"
							>
						</div>
						<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
							<?php echo webtrees\I18N::translate('For Flickr (www.flickr.com), enter the <strong>Set</strong> number of your images, usually a long number like <strong>72157633272831222</strong>. Nothing else is required in this field.'); ?>
						</span>
					</div>
				</fieldset>
				<fieldset id="fs_album_folder_p" <?php if ($plugin!=='picasa') { echo 'disabled="disabled"'; } ?>>
					<div class="form-group">
						<label class="control-label col-sm-3" for="album_folder_p">
							<?php echo webtrees\I18N::translate('Picasa user/album'); ?>
						</label>
						<div class="col-sm-9">
							<input
							class="form-control"
							id="album_folder_p"
							size="90"
							name="album_folder_p"
							required
							type="text"
							value="<?php echo webtrees\Filter::escapeHtml($item_folder_p); ?>"
							>
						</div>
						<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
							<?php echo webtrees\I18N::translate('For Picassa (picasaweb.google.com) enter your user name and user album, in the format <strong>username/album</strong> like <strong>kiwi3685/NZImages</strong>'); ?>
						</span>
					</div>
				</fieldset>
				
				<h3><?php echo webtrees\I18N::translate('Languages'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="lang_*">
						<?php echo webtrees\I18N::translate('Show this album for which languages?'); ?>
					</label>
					<div class="col-sm-9">
						<?php 
							$accepted_languages=explode(',', $this->getBlockSetting($block_id, 'languages'));
							foreach (webtrees\I18N::activeLocales() as $locale) {
						?>
								<div class="checkbox">
									<label title="<?php echo $locale->languageTag(); ?>">
										<input type="checkbox" name="lang_<?php echo $locale->languageTag(); ?>" <?php echo in_array($locale->languageTag(), $accepted_languages) ? 'checked' : ''; ?> ><?php echo $locale->endonym(); ?>
									</label>
								</div>
						<?php 
							}
						?>
					</div>
				</div>
				
				<h3><?php echo webtrees\I18N::translate('Visibility and Access'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo webtrees\I18N::translate('Album position'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="position"
							name="block_order"
							size="3"
							required
							type="number"
							value="<?php echo webtrees\Filter::escapeHtml($block_order); ?>"
						>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo webtrees\I18N::translate('This field controls the order in which the gallery albums are displayed.'),
							'<br><br>',
							webtrees\I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other albums later. For example, if you use the numbers 1, 6, 11, 16, you can later insert albums with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert albums in front of the first one.'),
							'<br><br>',
							webtrees\I18N::translate('When more than one gallery album has the same position number, only one of these albums will be visible.'); 
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo webtrees\I18N::translate('Album visibility'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo webtrees\Functions\FunctionsEdit::selectEditControl('gedcom_id', webtrees\Tree::getIdList(), webtrees\I18N::translate('All'), $gedcom_id, 'class="form-control"'); ?>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo webtrees\I18N::translate('You can determine whether this album will be visible regardless of family tree, or whether it will be visible only to the selected family tree.'); 
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="album_access">
						<?php echo webtrees\I18N::translate('Access level'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo webtrees\Functions\FunctionsEdit::editFieldAccessLevel('album_access', $item_access, 'class="form-control"'); ?>
					</div>
				</div>
				
				<div class="row col-sm-9 col-sm-offset-3">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-check"></i>
						<?php echo webtrees\I18N::translate('save'); ?>
					</button>
					<button class="btn" type="button" onclick="window.location='<?php echo $this->getConfigLink(); ?>';">
						<i class="fa fa-close"></i>
						<?php echo webtrees\I18N::translate('cancel'); ?>
					</button>
				</div>
			</form>
<?php
		}
	}

	private function delete() {
        global $WT_TREE;
        
        if (webtrees\Auth::isManager($WT_TREE)) {
			$args             = array();
			$args['block_id'] = webtrees\Filter::get('block_id');

			webtrees\Database::prepare(
				"DELETE FROM `##block_setting` WHERE block_id=:block_id"
			)->execute($args);

			webtrees\Database::prepare(
				"DELETE FROM `##block` WHERE block_id=:block_id"
			)->execute($args);
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function moveUp() {
        global $WT_TREE;
        
        if (webtrees\Auth::isManager($WT_TREE)) {
			$block_id         = webtrees\Filter::get('block_id');
			$args             = array();
			$args['block_id'] = $block_id;

			$block_order = webtrees\Database::prepare(
				"SELECT block_order FROM `##block` WHERE block_id=:block_id"
			)->execute($args)->fetchOne();

			$args                = array();
			$args['module_name'] = $this->getName();
			$args['block_order'] = $block_order;
			
			$swap_block = webtrees\Database::prepare(
				"SELECT block_order, block_id".
				" FROM `##block`".
				" WHERE block_order = (".
				"  SELECT MAX(block_order) FROM `##block` WHERE block_order < :block_order AND module_name = :module_name".
				" ) AND module_name = :module_name".
				" LIMIT 1"
			)->execute($args)->fetchOneRow();
			if ($swap_block) {
				$args                = array();
				$args['block_id']    = $block_id;
				$args['block_order'] = $swap_block->block_order;
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order = :block_order WHERE block_id = :block_id"
				)->execute($args);
				
				$args                = array();
				$args['block_order'] = $block_order;
				$args['block_id']    = $swap_block->block_id;
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order = :block_order WHERE block_id = :block_id"
				)->execute($args);
			}
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function moveDown() {
        global $WT_TREE;
        
        if (webtrees\Auth::isManager($WT_TREE)) {
			$block_id         = webtrees\Filter::get('block_id');
			$args             = array();
			$args['block_id'] = $block_id;

			$block_order = webtrees\Database::prepare(
				"SELECT block_order FROM `##block` WHERE block_id=:block_id"
			)->execute($args)->fetchOne();

			$args                = array();
			$args['module_name'] = $this->getName();
			$args['block_order'] = $block_order;
			
			$swap_block = webtrees\Database::prepare(
				"SELECT block_order, block_id".
				" FROM `##block`".
				" WHERE block_order = (".
				"  SELECT MIN(block_order) FROM `##block` WHERE block_order > :block_order AND module_name = :module_name".
				" ) AND module_name = :module_name".
				" LIMIT 1"
			)->execute($args)->fetchOneRow();
			if ($swap_block) {
				$args                = array();
				$args['block_id']    = $block_id;
				$args['block_order'] = $swap_block->block_order;
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order = :block_order WHERE block_id = :block_id"
				)->execute($args);
				
				$args                = array();
				$args['block_order'] = $block_order;
				$args['block_id']    = $swap_block->block_id;
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order = :block_order WHERE block_id = :block_id"
				)->execute($args);
			}
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function config() {
		global $WT_TREE;
		
		$controller = new webtrees\Controller\PageController();
		$controller
			->restrictAccess(webtrees\Auth::isManager($WT_TREE))
			->setPageTitle($this->getTitle())
			->pageHeader();

		$args                = array();
		$args['module_name'] = $this->getName();
        $args['tree_id']     = $WT_TREE->getTreeId();
		$albums              = webtrees\Database::prepare(
			"SELECT block_id, block_order, gedcom_id, bs1.setting_value AS album_title, bs2.setting_value AS album_description" .
			" FROM `##block` b" .
			" JOIN `##block_setting` bs1 USING (block_id)" .
			" JOIN `##block_setting` bs2 USING (block_id)" .
			" WHERE module_name = :module_name" .
			" AND bs1.setting_name = 'album_title'" .
			" AND bs2.setting_name = 'album_description'" .
			" AND IFNULL(gedcom_id, :tree_id) = :tree_id" .
			" ORDER BY block_order"
		)->execute($args)->fetchAll();

		unset($args['tree_id']);
		$min_block_order = webtrees\Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name = :module_name"
		)->execute($args)->fetchOne();

		$max_block_order = webtrees\Database::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name = :module_name"
		)->execute($args)->fetchOne();
		?>
		<style>
			.text-left-not-xs, .text-left-not-sm, .text-left-not-md, .text-left-not-lg {
				text-align: left;
			}
			.text-center-not-xs, .text-center-not-sm, .text-center-not-md, .text-center-not-lg {
				text-align: center;
			}
			.text-right-not-xs, .text-right-not-sm, .text-right-not-md, .text-right-not-lg {
				text-align: right;
			}
			.text-justify-not-xs, .text-justify-not-sm, .text-justify-not-md, .text-justify-not-lg {
				text-align: justify;
			}

			@media (max-width: 767px) {
				.text-left-not-xs, .text-center-not-xs, .text-right-not-xs, .text-justify-not-xs {
					text-align: inherit;
				}
				.text-left-xs {
					text-align: left;
				}
				.text-center-xs {
					text-align: center;
				}
				.text-right-xs {
					text-align: right;
				}
				.text-justify-xs {
					text-align: justify;
				}
			}
			@media (min-width: 768px) and (max-width: 991px) {
				.text-left-not-sm, .text-center-not-sm, .text-right-not-sm, .text-justify-not-sm {
					text-align: inherit;
				}
				.text-left-sm {
					text-align: left;
				}
				.text-center-sm {
					text-align: center;
				}
				.text-right-sm {
					text-align: right;
				}
				.text-justify-sm {
					text-align: justify;
				}
			}
			@media (min-width: 992px) and (max-width: 1199px) {
				.text-left-not-md, .text-center-not-md, .text-right-not-md, .text-justify-not-md {
					text-align: inherit;
				}
				.text-left-md {
					text-align: left;
				}
				.text-center-md {
					text-align: center;
				}
				.text-right-md {
					text-align: right;
				}
				.text-justify-md {
					text-align: justify;
				}
			}
			@media (min-width: 1200px) {
				.text-left-not-lg, .text-center-not-lg, .text-right-not-lg, .text-justify-not-lg {
					text-align: inherit;
				}
				.text-left-lg {
					text-align: left;
				}
				.text-center-lg {
					text-align: center;
				}
				.text-right-lg {
					text-align: right;
				}
				.text-justify-lg {
					text-align: justify;
				}
			}
		</style>
		
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo webtrees\I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo webtrees\I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		
		<div class="row">
			<div class="col-sm-4 col-xs-12">
				<form class="form">
					<label for="ged" class="sr-only">
						<?php echo webtrees\I18N::translate('Family tree'); ?>
					</label>
					<input type="hidden" name="mod" value="<?php echo  $this->getName(); ?>">
					<input type="hidden" name="mod_action" value="admin_config">
					<div class="col-sm-9 col-xs-9" style="padding:0;">
						<?php echo webtrees\Functions\FunctionsEdit::selectEditControl('ged', webtrees\Tree::getNameList(), null, $WT_TREE->getName(), 'class="form-control"'); ?>
					</div>
					<div class="col-sm-3" style="padding:0;">
						<input type="submit" class="btn btn-primary" value="<?php echo webtrees\I18N::translate('show'); ?>">
					</div>
				</form>
			</div>
			<span class="visible-xs hidden-sm hidden-md hidden-lg" style="display:block;"></br></br></span>
			<div class="col-sm-4 text-center text-left-xs col-xs-12">
				<p>
					<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit" class="btn btn-primary">
						<i class="fa fa-plus"></i>
						<?php echo webtrees\I18N::translate('Add Gallery'); ?>
					</a>
				</p>
			</div>
			<div class="col-sm-4 text-right text-left-xs col-xs-12">		
				<?php // TODO: Move to internal item/page
				if (file_exists(WT_MODULES_DIR . $this->getName() . '/readme.html')) { ?>
					<a href="<?php echo WT_MODULES_DIR . $this->getName(); ?>/readme.html" class="btn btn-info">
						<i class="fa fa-newspaper-o"></i>
						<?php echo webtrees\I18N::translate('ReadMe'); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		
		<table class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo webtrees\I18N::translate('Position'); ?></th>
					<th class="col-sm-3"><?php echo webtrees\I18N::translate('Album title'); ?></th>
					<th class="col-sm-1" colspan=4><?php echo webtrees\I18N::translate('Controls'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($albums as $album): ?>
				<tr>
					<td>
						<?php echo $album->block_order, ', ';
						if ($album->gedcom_id == null) {
							echo webtrees\I18N::translate('All');
						} else {
							echo webtrees\Tree::findById($album->gedcom_id)->getTitleHtml();
						} ?>
					</td>
					<td>
						<?php echo webtrees\Filter::escapeHtml(webtrees\I18N::translate($album->album_title)); ?>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit&amp;block_id=<?php echo $album->block_id; ?>">
							<div class="icon-edit">&nbsp;</div>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_moveup&amp;block_id=<?php echo $album->block_id; ?>">
							<?php
								if ($album->block_order == $min_block_order) {
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
								if ($album->block_order == $max_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-darrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_delete&amp;block_id=<?php echo $album->block_id; ?>"
							onclick="return confirm('<?php echo webtrees\I18N::translate('Are you sure you want to delete this album?'); ?>');">
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
		$plugin = $this->getBlockSetting($item_id, 'plugin');
		$js = 'Galleria.loadTheme("' . WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/galleria/themes/' . $theme . '/galleria.' . $theme.'.js");';
			switch ($plugin) {
			case 'flickr':
			$flickr_set = $this->getBlockSetting($item_id, 'album_folder_f');
			$js .= '
				Galleria.run("#galleria", {
					flickr: "set:' . $flickr_set . '",
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
			$picasa_set = $this->getBlockSetting($item_id, 'album_folder_p');
			$js .= '
				Galleria.run("#galleria", {
					picasa: "useralbum:' . $picasa_set . '",
					picasaOptions: {
						sort: "date-posted-asc"
					},
					_showCaption: false,
					imageCrop: true
				});
			';
			break;
			default:		
			$js .= '
				Galleria.ready(function(options) {
					this.bind("image", function(e) {
						data = e.galleriaData;
						$("#links_bar").html(data.layer);
					});
				});
				Galleria.run("#galleria", {
					_showCaption: false,
					_locale: {
						show_captions:		"' . webtrees\I18N::translate('Show descriptions') . '",
						hide_captions:		"' . webtrees\I18N::translate('Hide descriptions') . '",
						play:				"' . webtrees\I18N::translate('Play slideshow') . '",
						pause:				"' . webtrees\I18N::translate('Pause slideshow') . '",
						enter_fullscreen:	"' . webtrees\I18N::translate('Enter fullscreen') . '",
						exit_fullscreen:	"' . webtrees\I18N::translate('Exit fullscreen') . '",
						next:				"' . webtrees\I18N::translate('Next image') . '",
						prev:				"' . webtrees\I18N::translate('Previous image') . '",
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
		global $WT_TREE;
		
		$args                = array();
		$args['module_name'] = $this->getName();
        $args['tree_id']     = $WT_TREE->getTreeId();
		return webtrees\Database::prepare(
			"SELECT block_id, 
				bs1.setting_value AS album_title, 
				bs2.setting_value AS album_access, 
				bs3.setting_value AS album_description, 
				bs4.setting_value AS album_folder_w, 
				bs5.setting_value AS album_folder_f, 
				bs6.setting_value AS album_folder_p" . 
			" FROM `##block` b" . 
			" JOIN `##block_setting` bs1 USING (block_id)" .
			" JOIN `##block_setting` bs2 USING (block_id)" .
			" JOIN `##block_setting` bs3 USING (block_id)" .
			" JOIN `##block_setting` bs4 USING (block_id)" .
			" JOIN `##block_setting` bs5 USING (block_id)" .
			" JOIN `##block_setting` bs6 USING (block_id)" .
			" WHERE module_name = :module_name" .
			" AND bs1.setting_name = 'album_title'" .
			" AND bs2.setting_name = 'album_access'" .
			" AND bs3.setting_name = 'album_description'" .
			" AND bs4.setting_name = 'album_folder_w'" .
			" AND bs5.setting_name = 'album_folder_f'" .
			" AND bs6.setting_name = 'album_folder_p'" .
			" AND (gedcom_id IS NULL OR gedcom_id = :tree_id)" .
			" ORDER BY block_order"
		)->execute($args)->fetchAll();
	}
	
	// Return the list of albums for menu
	private function getMenuAlbumList() {
		global $WT_TREE;
		
		$args                = array();
		$args['module_name'] = $this->getName();
        $args['tree_id']     = $WT_TREE->getTreeId();
		return webtrees\Database::prepare(
			"SELECT block_id, bs1.setting_value AS album_title, bs2.setting_value AS album_access".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name = :module_name".
			" AND bs1.setting_name = 'album_title'".
			" AND bs2.setting_name = 'album_access'".
			" AND (gedcom_id IS NULL OR gedcom_id = :tree_id)".
			" ORDER BY block_order"
		)->execute($args)->fetchAll();
	}
	
	// Print the Notes for each media item
	static function FormatGalleryNotes($haystack) {
		$needle   = '1 NOTE';
		$before   = substr($haystack, 0, strpos($haystack, $needle));
		$after    = substr(strstr($haystack, $needle), strlen($needle));
		$final    = $before.$needle.$after;
		$notes    = webtrees\Functions\FunctionsPrint::printFactNotes($final, 1, true, true);
		if ($notes != '' && $notes != '<br>') {
			$html = htmlspecialchars($notes);
			return $html;
		}
		return false;
	}

	// Start to show the gallery display with the parts common to all galleries
	private function show() {
		global $MEDIA_DIRECTORY, $controller, $WT_TREE;
		$gallery_header_description = '';
		$item_id = webtrees\Filter::get('album_id');
		$controller = new webtrees\Controller\PageController();
		$controller
			->setPageTitle(webtrees\I18N::translate('Picture galleries'))
			->pageHeader()
			->addExternalJavaScript(WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/galleria/galleria-1.4.2.min.js')
			->addExternalJavaScript(WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/galleria/plugins/flickr/galleria.flickr.min.js')
			->addExternalJavaScript(WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/galleria/plugins/picasa/galleria.picasa.min.js')
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
						$item_list = $this->getAlbumList();
						foreach ($item_list as $item) {
							$languages = $this->getBlockSetting($item->block_id, 'languages');
							if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item->album_access >= webtrees\Auth::accessLevel($WT_TREE)) { ?>
								<li class="ui-state-default ui-corner-top <?php echo ($item_id == $item->block_id ? ' ui-tabs-selected ui-state-active' : ''); ?>">
									<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=show&amp;album_id=<?php echo $item->block_id; ?>">
										<span title="<?php echo webtrees\I18N::translate($item->album_title); ?>"><?php echo webtrees\I18N::translate($item->album_title); ?></span>
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
							$languages = $this->getBlockSetting($item->block_id, 'languages');
							if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $item_id == $item->block_id && $item->album_access >= webtrees\Auth::accessLevel($WT_TREE)) {
								$item_gallery='<h4>' . webtrees\I18N::translate($item->album_description) . '</h4>' . $this->mediaDisplay($item->album_folder_w, $item_id);
							}
						}
						if (!isset($item_gallery)) {
							echo '<h4>' . webtrees\I18N::translate('Image collections related to our family') . '</h4>' . $this->mediaDisplay('//', $item_id);
						} else {
							echo $item_gallery;
						}
					?>
					</div>
				</div>
			</div>
			<?php
	}

	/**
	 * get gedcom tag value
	 *
	 * @param string  $tag    The tag to find, use : to delineate subtags
	 * @param int $level  The gedcom line level of the first tag to find, setting level to 0 will cause it to use 1+ the level of the incoming record
	 * @param string  $gedrec The gedcom record to get the value from
	 *
	 * @return string the value of a gedcom tag from the given gedcom record
	 */
	private function getGedcomValue($tag, $level, $gedrec) {
		global $WT_TREE;
		if (empty($gedrec)) {
			return '';
		}
		$tags      = explode(':', $tag);
		$origlevel = $level;
		if ($level == 0) {
			$level = $gedrec{0} + 1;
		}
		$subrec = $gedrec;
		foreach ($tags as $t) {
			$lastsubrec = $subrec;
			$subrec     = webtrees\Functions\Functions::getSubRecord($level, "$level $t", $subrec);
			if (empty($subrec) && $origlevel == 0) {
				$level--;
				$subrec = webtrees\Functions\Functions::getSubRecord($level, "$level $t", $lastsubrec);
			}
			if (empty($subrec)) {
				if ($t == "TITL") {
					$subrec = webtrees\Functions\Functions::getSubRecord($level, "$level ABBR", $lastsubrec);
					if (!empty($subrec)) {
						$t = "ABBR";
					}
				}
				if (empty($subrec)) {
					if ($level > 0) {
						$level--;
					}
					$subrec = webtrees\Functions\Functions::getSubRecord($level, "@ $t", $gedrec);
					if (empty($subrec)) {
						return '';
					}
				}
			}
			$level++;
		}
		$level--;
		$ct = preg_match("/$level $t(.*)/", $subrec, $match);
		if ($ct == 0) {
			$ct = preg_match("/$level @.+@ (.+)/", $subrec, $match);
		}
		if ($ct == 0) {
			$ct = preg_match("/@ $t (.+)/", $subrec, $match);
		}
		if ($ct > 0) {
			$value = trim($match[1]);
			if ($t == 'NOTE' && preg_match('/^@(.+)@$/', $value, $match)) {
				$note = webtrees\Note::getInstance($match[1], $WT_TREE);
				if ($note) {
					$value = $note->getNote();
				} else {
					//-- set the value to the id without the @
					$value = $match[1];
				}
			}
			if ($level != 0 || $t != "NOTE") {
				$value .= webtrees\Functions\Functions::getCont($level + 1, $subrec);
			}
			return $value;
		}
		return "";
	}

	// Print the gallery display
	private function mediaDisplay($sub_folder, $item_id) {
		global $MEDIA_DIRECTORY, $WT_TREE;
		$plugin = $this->getBlockSetting($item_id, 'plugin');
		$images = ''; 
		// Get the related media items
		$sub_folder = str_replace($MEDIA_DIRECTORY, "", $sub_folder);
		$sql = "SELECT * FROM ##media WHERE m_filename LIKE '%" . $sub_folder . "%' ORDER BY m_filename";
		$rows = webtrees\Database::prepare($sql)->execute()->fetchAll(PDO::FETCH_ASSOC);
		if ($plugin == 'webtrees') {
			foreach ($rows as $rowm) {
				// Get info on how to handle this media file
				$media = webtrees\Media::getInstance($rowm['m_id'], $WT_TREE);
				if ($media->canShow()) {
					$links = array_merge(
						$media->linkedIndividuals('OBJE'),
						$media->linkedFamilies('OBJE'),
						$media->linkedSources('OBJE')
					);
					$rawTitle = $rowm['m_titl'];
					if (empty($rawTitle)) $rawTitle = $this->getGedcomValue('TITL', 2, $rowm['m_gedcom']);
					if (empty($rawTitle)) $rawTitle = basename($rowm['m_filename']);
					$mediaTitle = htmlspecialchars(strip_tags($rawTitle));
					$rawUrl = $media->getHtmlUrlDirect();
					$thumbUrl = $media->getHtmlUrlDirect('thumb');
					$media_notes = $this->FormatGalleryNotes($rowm['m_gedcom']);
					$mime_type = $media->mimeType();
					$gallery_links = '';
					if (webtrees\Auth::isEditor($WT_TREE)) {
						$gallery_links .= '<div class="edit_links">';
							$gallery_links .= '<div class="image_option"><a href="' . $media->getHtmlUrl() . '"><img src="' . WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/edit.png" title="'.webtrees\I18N::translate('Edit').'"></a></div>';
							if (webtrees\Auth::isManager($WT_TREE)) {
								if (webtrees\Module::getModuleByName('GEDFact_assistant')) {
									$gallery_links .= '<div class="image_option"><a onclick="return ilinkitem(\'' . $rowm['m_id'] . '\', \'manage\')" href="#"><img src="' . WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/link.png" title="' . webtrees\I18N::translate('Manage links') . '"></a></div>';
								}
							}
						$gallery_links .= '</div><hr>';// close .edit_links
					}						
					if ($links) {
						$gallery_links .= '<h4>'.webtrees\I18N::translate('Linked to:') . '</h4>';
						$gallery_links .= '<div id="image_links">';
							foreach ($links as $record) {
									$gallery_links .= '<a href="' . $record->getHtmlUrl() . '">' . $record->getFullname() . '</a><br>';
							}
						$gallery_links .= '</div>';
					}
					$media_links = htmlspecialchars($gallery_links);
					if ($mime_type == 'application/pdf'){ 
						$images .= '<a href="' . $rawUrl . '"><img class="iframe" src="' . $thumbUrl . '" data-title="' . $mediaTitle . '" data-layer="' . $media_links . '" data-description="' . $media_notes . '"></a>';
					} else {
						$images .= '<a href="' . $rawUrl . '"><img src="' . $thumbUrl . '" data-title="' . $mediaTitle . '" data-layer="' . $media_links . '" data-description="' . $media_notes . '"></a>';
					}
				}
			}
			if (webtrees\Auth::isMember($WT_TREE) || (isset($media_links) && $media_links != '')) {
				$html =
					'<div id="links_bar"></div>'.
					'<div id="galleria" style="width:80%;">';
			} else {
				$html =
					'<div id="galleria" style="width:100%;">';
			}
		} else {
			$html = '<div id="galleria" style="width:100%;">';
			$images .= '&nbsp;';
		}
		if ($images) {
			$html .= $images.
				'</div>' . // close #galleria
				'<a id="copy" href="http://galleria.io/">Display by Galleria</a>' . // gallery.io attribution
				'</div>' . // close #page
				'<div style="clear: both;"></div>';
		} else {
			$html .= webtrees\I18N::translate('Album is empty. Please choose other album.') .
				'</div>' . // close #galleria
				'</div>' . // close #page
				'<div style="clear: both;"></div>';
		}
		return $html;
	}
}
return new VytuxGallery3Module;