<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

if (!$USER->IsAdmin())
	$APPLICATION->AuthForm();

IncludeModuleLangFile(__FILE__);
$MODULE_ID = 'xtt.mpbuilder';
$xttMPBRootModuleDir = COption::GetOptionString($MODULE_ID, 'root_folder');

$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_EDITOR"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
	["DIV" => "tab1", "TAB" => GetMessage("BITRIX_MPBUILDER_STEP"), "ICON" => "main_user_edit", "TITLE" => GetMessage("BITRIX_MPBUILDER_EDITOR")],
];
$editTab = new CAdminTabControl("editTab", $aTabs, true, true);

echo BeginNote() .
	GetMessage("BITRIX_MPBUILDER_V_KACESTVE_RAZDELITE") . ' "_".' .
	EndNote();

list($rootDir, $_REQUEST['module_id'])  = explode(';', str_replace(['..', '/', '\\'], '', $_REQUEST['module_id']));
if ($_REQUEST['module_id'] && is_dir($_SERVER['DOCUMENT_ROOT'] . '/' . $rootDir . '/modules/' . $_REQUEST['module_id'])) {
	$module_id = $_SESSION['mpbuilder']['module_id'] = $_REQUEST['module_id'];
	$_SESSION['mpbuilder']['rootDir'] = $rootDir;
} else {
	$module_id = $_SESSION['mpbuilder']['module_id'];
	$rootDir = $_SESSION['mpbuilder']['rootDir'];
}
$m_dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $rootDir . '/modules/' . $module_id;

$file = str_replace('\\', '/', $_REQUEST['file']);
if (!preg_match('#\.php$#', $file) || strpos($file, '../') !== false)
	$file = '';

if (check_bitrix_sessid()) {
	$lang_file = str_replace('\\', '/', $_REQUEST['lang_file']);

	if ($_REQUEST['next']) {
		LocalRedirect('/bitrix/admin/' . $MODULE_ID . '_step4.php?module_id=' . $module_id . '&lang=' . LANGUAGE_ID);
	}
	if ($_REQUEST['save']) {
		if (($str0 = file_get_contents($m_dir . $file)) && is_array($arMess = GetMess($lang_file))) {
			$arNewMess = array();
			foreach ($arMess as $key => $val) {
				$new_key = str_replace(' ', '_', $_REQUEST['prefix'] . $_REQUEST['arMess'][$key]);
				if ($key != $new_key) {
					$i = 0;
					while (array_key_exists($new_key, $arNewMess))
						$new_key = str_replace(' ', '_', $_REQUEST['prefix'] . $_REQUEST['arMess'][$key]) . (++$i);
					$arNewMess[$new_key] = $val;
					$str0 = str_replace('GetMessage("' . $key . '")',   'GetMessage("' . $new_key . '")', $str0);
					$str0 = str_replace('GetMessage(\'' . $key . '\')', 'GetMessage("' . $new_key . '")', $str0);
					$str0 = str_replace('GetMessageJS("' . $key . '")',   'GetMessageJS("' . $new_key . '")', $str0);
					$str0 = str_replace('GetMessageJS(\'' . $key . '\')', 'GetMessageJS("' . $new_key . '")', $str0);
				} else
					$arNewMess[$key] = $val;
			}

			$str = "<" . "?\n";
			foreach ($arNewMess as $key => $val)
				$str .= '$MESS["' . $key . '"] = "' . str_replace('"', '\\"', str_replace('\\', '\\\\', $val)) . '";' . "\n";
			$str .= "?" . ">";
			if (file_put_contents($lang_file, $str) && file_put_contents($m_dir . $file, $str0))
				CAdminMessage::ShowMessage(array(
					"MESSAGE" => GetMessage("BITRIX_MPBUILDER_SAVED"),
					"DETAILS" => GetMessage("BITRIX_MPBUILDER_LANG_FILE") . $lang_file,
					"TYPE" => "OK",
					"HTML" => true
				));
			else
				CAdminMessage::ShowMessage(array(
					"MESSAGE" => GetMessage("BITRIX_MPBUILDER_ERROR"),
					"DETAILS" => GetMessage("BITRIX_MPBUILDER_ERR_SAVE") . $lang_file,
					"TYPE" => "ERROR",
					"HTML" => true
				));
		} else
			CAdminMessage::ShowMessage(array(
				"MESSAGE" => GetMessage("BITRIX_MPBUILDER_ERROR"),
				"DETAILS" => GetMessage("BITRIX_MPBUILDER_FILE_NOT_FOUND") . $lang_file,
				"TYPE" => "ERROR",
				"HTML" => true
			));
	}
}

$prefix = '';

?>
<form action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" method="POST" enctype="multipart/form-data">
	<?= bitrix_sessid_post() ?>
	<?
	$editTab->Begin();
	$editTab->BeginNextTab();
	?>
	<tr>
		<td width="40%"><?= GetMessage("BITRIX_MPBUILDER_CUR_MODULE") ?></td>
		<td>
			<select name="module_id" onchange="document.location='?module_id='+this.value">
				<option disabled>Select module</option>
				<?
				$arModules = [];
				function loadModules($rootDir, $type)
				{
					global $module_id, $arModules;
					if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $rootDir)) return false;
					$dir = opendir($path = $_SERVER['DOCUMENT_ROOT'] . $rootDir . '/modules');
					while (false !== $item = readdir($dir)) {
						if ($item == '.' || $item == '..' || !is_dir($path . '/' . $item) || !strpos($item, '.'))
							continue;
						$arModules[$item] = '<option value="' . $type . ';' . htmlspecialcharsbx($item) . '" ' . ($module_id == $item ? 'selected' : '') . '>' . $item . ' (' . $type . ')</option>';
					}
					closedir($dir);
				}

				loadModules(BX_ROOT, 'bitrix');
				loadModules('/local', 'local');

				asort($arModules);
				echo implode("\n", $arModules);
				?>
			</select>
		</td>
	</tr>
	<? if ($module_id) { ?>
		<tr>
			<td><?= GetMessage("BITRIX_MPBUILDER_PATH_TO_FILE") ?></td>
			<td><select name="file" onchange="document.location='?<?= bitrix_sessid_get() ?>&file='+this.value">
					<option disabled>Select file path</option>
					<?
					$ar = BuilderGetFiles($m_dir);
					sort($ar);
					foreach ($ar as $f)
						if (!preg_match('#^/lang/#', $f))
							echo '<option value="' . htmlspecialcharsbx($f) . '" ' . ($f == $file ? 'selected' : '') . '>' . $f . '</option>';
					?>
				</select></td>
		</tr>
		<?
		if ($file) {
			$lang_file = $m_dir . GetLangPath($file, $m_dir);

			if ($arMess = GetMess($lang_file))
				echo '<input type="hidden" name="lang_file" value="' . htmlspecialcharsbx($lang_file) . '">';
		?>
			<tr class="heading">
				<td colspan="2"><?= GetMessage("BITRIX_MPBUILDER_KEY_LIST") ?></td>
			</tr>
	<?
			if ($arMess) {
				if ($_REQUEST['disable_prefix']) {
					$prefix = '';
					$l = 0;
				} else {
					$prefix = strtoupper(str_replace('.', '_', $module_id)) . '_';
					$l = strlen($prefix);

					foreach ($arMess as $key => $val) {
						if (strpos($key, $prefix) !== 0) {
							$prefix = '';
							$l = 0;
							break;
						}
					}
				}

				if ($prefix) {
					echo '<tr>
						<td colspan="2" align="center">' . GetMessage("BITRIX_MPBUILDER_VSE_KLUCI_IMEUT_PREF") . htmlspecialcharsbx($prefix) . '</b>. <a href="javascript:if(confirm(\'' . GetMessage("BITRIX_MPBUILDER_PEREGRUZITQ_STRANICU") . '?\'))document.location=\'?file=' . urlencode($file) . '&disable_prefix=1\'">' . GetMessage("BITRIX_MPBUILDER_REDAKTIROVATQ_KLUCI") . '?</a></td>
					</tr>';
				}

				foreach ($arMess as $key => $val) {
					echo '<tr>
						<td>' . htmlspecialcharsbx(substr($val, 0, 100)) . (strlen($val) > 100 ? '...' : '') . '</td>
						<td style="color:#666">' . $prefix . '<input size=40 name="arMess[' . htmlspecialcharsbx($key) . ']" value="' . htmlspecialcharsbx(substr($key, $l)) . '" onchange="this.value=this.value.replace(/ /,\'_\')"></td>
					</tr>';
				}
			}
		}
	}
	$editTab->Buttons();
	?>
	<input type="hidden" name="prefix" value="<?= $prefix ?>">
    <input type="submit" name="save" value="<?= GetMessage("BITRIX_MPBUILDER_BTN_CREATE") ?>">
    <input type="submit" name="next" value="<?= GetMessage("BITRIX_MPBUILDER_BTN_NEXT") ?>">
	<? $editTab->End(); ?>
</form>

<? require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php"); ?>