<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

$this->includeJsFile('mediatype.list.js.php');
$this->addJsFile('multilineinput.js');

$html_page = (new CHtmlPage())
	->setTitle(_('Media types'))
	->setDocUrl(CDocHelper::getUrl(CDocHelper::ALERTS_MEDIATYPE_LIST))
	->setControls((new CTag('nav', true,
		(new CList())
			->addItem((new CSimpleButton(_('Create media type')))->setId('js-create'))
			->addItem(
				(new CButton('', _('Import')))
					->onClick(
						'return PopUp("popup.import", {
							rules_preset: "mediatype", '.
							CCsrfTokenHelper::CSRF_TOKEN_NAME.': "'. CCsrfTokenHelper::get('import').
						'"},{
							dialogueid: "popup_import",
							dialogue_class: "modal-popup-generic"
						});'
					)
					->removeId()
			)
		))
			->setAttribute('aria-label', _('Content controls'))
	)
	->addItem((new CFilter())
		->setResetUrl((new CUrl('zabbix.php'))->setArgument('action', 'mediatype.list'))
		->setProfile($data['profileIdx'])
		->setActiveTab($data['active_tab'])
		->addFilterTab(_('Filter'), [
			(new CFormGrid())
				->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
				->addItem([
					new CLabel(_('Name'), 'filter_name'),
					new CFormField(
						(new CTextBox('filter_name', $data['filter']['name']))
							->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)
							->setAttribute('autofocus', 'autofocus')
					)
				]),
			(new CFormGrid())
				->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
				->addItem([
					new CLabel(_('Status')),
					new CFormField(
						(new CRadioButtonList('filter_status', (int) $data['filter']['status']))
							->addValue(_('Any'), -1)
							->addValue(_('Enabled'), MEDIA_TYPE_STATUS_ACTIVE)
							->addValue(_('Disabled'), MEDIA_TYPE_STATUS_DISABLED)
							->setModern()
					)
				])
		])
		->addVar('action', 'mediatype.list')
	);

// create form
$mediaTypeForm = (new CForm())->setName('mediaTypesForm');

// create table
$url = (new CUrl('zabbix.php'))
	->setArgument('action', 'mediatype.list')
	->getUrl();

$mediaTypeTable = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all_media_types'))
				->onClick("checkAll('".$mediaTypeForm->getName()."', 'all_media_types', 'mediatypeids');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		make_sorting_header(_('Name'), 'name', $data['sort'], $data['sortorder'], $url),
		make_sorting_header(_('Type'), 'type', $data['sort'], $data['sortorder'], $url),
		_('Status'),
		_('Used in actions'),
		_('Details'),
		_('Action')
	]);

$csrf_token = CCsrfTokenHelper::get('mediatype');

foreach ($data['mediatypes'] as $mediaType) {
	switch ($mediaType['typeid']) {
		case MEDIA_TYPE_EMAIL:
			if ($mediaType['provider'] == CMediatypeHelper::EMAIL_PROVIDER_SMTP) {
				$details =
					_('SMTP server').NAME_DELIMITER.'"'.$mediaType['smtp_server'].'", '.
					_('SMTP helo').NAME_DELIMITER.'"'.$mediaType['smtp_helo'].'", '.
					_('email').NAME_DELIMITER.'"'.$mediaType['smtp_email'].'"';
			}
			else {
				$details =
					_('SMTP server').NAME_DELIMITER.'"'.$mediaType['smtp_server'].'", '.
					_('email').NAME_DELIMITER.'"'.$mediaType['smtp_email'] . '"';
			}
			break;

		case MEDIA_TYPE_EXEC:
			$details = _('Script name').NAME_DELIMITER.'"'.$mediaType['exec_path'].'"';
			break;

		case MEDIA_TYPE_SMS:
			$details = _('GSM modem').NAME_DELIMITER.'"'.$mediaType['gsm_modem'].'"';
			break;

		default:
			$details = '';
			break;
	}

	// action list
	$actionLinks = [];
	if (!empty($mediaType['list_of_actions'])) {
		foreach ($mediaType['list_of_actions'] as $action) {
			$actionLinks[] = (new CLink($action['name']))
				->addClass('js-action-edit')
				->setAttribute('data-actionid', $action['actionid'])
				->setAttribute('data-eventsource', $action['eventsource']);

			$actionLinks[] = ', ';
		}
		array_pop($actionLinks);
	}
	else {
		$actionLinks = '';
	}
	$actionColumn = new CCol($actionLinks);
	$actionColumn->setAttribute('style', 'white-space: normal;');

	$statusLink = 'zabbix.php'.
		'?action='.($mediaType['status'] == MEDIA_TYPE_STATUS_DISABLED
			? 'mediatype.enable'
			: 'mediatype.disable'
		).
		'&mediatypeids[]='.$mediaType['mediatypeid'];

	$status = (MEDIA_TYPE_STATUS_ACTIVE == $mediaType['status'])
		? (new CLink(_('Enabled')))
			->addClass(ZBX_STYLE_GREEN)
			->addClass('js-disable')
			->setAttribute('data-mediatypeid', (int) $mediaType['mediatypeid'])
		: (new CLink(_('Disabled')))
			->addClass(ZBX_STYLE_RED)
			->addClass('js-enable')
			->setAttribute('data-mediatypeid', (int) $mediaType['mediatypeid']);

	$test_link = (new CButton('mediatypetest_edit', _('Test')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->removeId()
		->setEnabled(MEDIA_TYPE_STATUS_ACTIVE == $mediaType['status'])
		->setAttribute('data-mediatypeid', $mediaType['mediatypeid'])
		->addClass('js-test-edit');

	$name = (new CLink($mediaType['name']))
		->addClass('js-edit')
		->setAttribute('data-mediatypeid', $mediaType['mediatypeid']);

	// append row
	$mediaTypeTable->addRow([
		new CCheckBox('mediatypeids['.$mediaType['mediatypeid'].']', $mediaType['mediatypeid']),
		(new CCol($name))->addClass(ZBX_STYLE_NOWRAP),
		CMediatypeHelper::getMediaTypes($mediaType['typeid']),
		$status,
		$actionColumn,
		$details,
		$test_link
	]);
}

// append table to form
$mediaTypeForm->addItem([
	$mediaTypeTable,
	$data['paging'],
	new CActionButtonList('action', 'mediatypeids', [
		'mediatype.enable' => [

			'content' => (new CSimpleButton(_('Enable')))
				->addClass(ZBX_STYLE_BTN_ALT)
				->setId('js-massenable')
				->addClass('no-chkbxrange')
		],
		'mediatype.disable' => [
			'content' => (new CSimpleButton(_('Disable')))
				->addClass(ZBX_STYLE_BTN_ALT)
				->setId('js-massdisable')
				->addClass('no-chkbxrange')
		],
		'mediatype.export' => [
			'content' => new CButtonExport('export.mediatypes',
				(new CUrl('zabbix.php'))
					->setArgument('action', 'mediatype.list')
					->setArgument('page', ($data['page'] == 1) ? null : $data['page'])
					->getUrl()
			)
		],
		'mediatype.delete' => [
			'content' => (new CSimpleButton(_('Delete')))
				->addClass(ZBX_STYLE_BTN_ALT)
				->setId('js-massdelete')
				->addClass('no-chkbxrange')
		]
	], 'mediatype')
]);

// append form to widget
$html_page
	->addItem($mediaTypeForm)
	->show();

(new CScriptTag('view.init();'))
	->setOnDocumentReady()
	->show();
