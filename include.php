<?
IncludeModuleLangFile(__FILE__);

class CXttMpBuilder
{
	function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
	{
		if ($GLOBALS['APPLICATION']->GetGroupRight("main") < "R")
			return;

		$MODULE_ID = basename(dirname(__FILE__));
		$aMenu = array(
			"parent_menu" => "global_menu_settings", // global_menu_services
			"section" => $MODULE_ID,
			"sort" => 50,
			"text" => GetMessage("BITRIX_MPBUILDER_MENU"),
			"title" => GetMessage("BITRIX_MPBUILDER_TITLE"),
			// "url" => "partner_modules.php?module=".$MODULE_ID,
			"icon" => "iblock_menu_icon_settings",
			"page_icon" => "",
			"items_id" => $MODULE_ID . "_items",
			"more_url" => array(),
			"items" => array()
		);
		$xttMPBRootModuleDir = COption::GetOptionString($MODULE_ID, 'root_folder');

		if (file_exists($path = dirname(__FILE__) . '/admin')) {
			if ($dir = opendir($path)) {
				$arFiles = array();

				while (false !== $item = readdir($dir)) {
					if (in_array($item, array('.', '..', 'menu.php')))
						continue;

					if (!file_exists($file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $MODULE_ID . '_' . $item))
						file_put_contents($file, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/' . $xttMPBRootModuleDir . '/modules/' . $MODULE_ID . '/admin/' . $item . '");?' . '>');

					$arFiles[] = $item;
				}

				sort($arFiles);
				$arTitles = array(
					'step1.php' => GetMessage("BITRIX_MPBUILDER_STRUKTURA_MODULA"),
					'step2.php' => GetMessage("BITRIX_MPBUILDER_VYDELENIE_FRAZ"),
					'step3.php' => GetMessage("BITRIX_MPBUILDER_REDAKTOR_KLUCEY"),
					'step4.php' => GetMessage("BITRIX_MPBUILDER_SOZDANIE_ARHIVA"),
					'step5.php' => GetMessage("BITRIX_MPBUILDER_SBORKA_OBNOVLENIY")
				);

				foreach ($arFiles as $item)
					$aMenu['items'][] = array(
						'text' => $arTitles[$item],
						'url' => $MODULE_ID . '_' . $item,
						'module_id' => $MODULE_ID,
						"title" => "",
					);
			}
		}
		$aModuleMenu[] = $aMenu;
	}
}

if (!class_exists('CBuilderLang')) {
	require_once 'tools/classes.php';
}
