<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

if (!$USER->IsAdmin())
    $APPLICATION->AuthForm();

IncludeModuleLangFile(__FILE__);
$MODULE_ID = 'xtt.mpbuilder';
$xttMPBRootModuleDir = COption::GetOptionString($MODULE_ID, 'root_folder');

$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_SAG_TRETIY_SOZDANIE"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
    ["DIV" => "tab1", "TAB" => GetMessage("BITRIX_MPBUILDER_SAG"), "ICON" => "main_user_edit", "TITLE" => GetMessage("BITRIX_MPBUILDER_SOZDANIE_ARHIVA")],
];
$editTab = new CAdminTabControl("editTab", $aTabs, true, true);

echo BeginNote() .
    GetMessage("BITRIX_MPBUILDER_VSE_SKRIPTY_MODULA_B") . ' cp1251, ' . GetMessage("BITRIX_MPBUILDER_ZATEM_BUDET_SOZDAN_A") . ' moduleId(version).tar.gz, ' .
    GetMessage("BITRIX_MPBUILDER_KOTORYY_NADO_OTPRAVI") . ' <a href="https://partners.1c-bitrix.ru/personal/modules/edit_module.php?ID=' . $module_id . '" target="_blank">marketplace</a>.' .
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

$arModuleVersion = [];

if ($module_id) {
    include($m_dir . '/install/version.php');
}

if ($_REQUEST['action'] == 'delete' && $module_id && check_bitrix_sessid()) {
    BuilderRmDir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp/' . $module_id);
} elseif ($_REQUEST['next']) {
    LocalRedirect('/bitrix/admin/' . $MODULE_ID . '_step5.php?module_id=' . $module_id . '&lang=' . LANGUAGE_ID);
} elseif ($_POST['save'] && $module_id && check_bitrix_sessid()) {
    $strError = '';

    if ($v = trim($_REQUEST['version'])) {
        $f = $m_dir . '/install/version.php';
        if (!file_put_contents(
            $f,
            '<' . '?' . "\n" .
                '$arModuleVersion = array(' . "\n" .
                '	"VERSION" => "' . EscapePHPString($v) . '",' . "\n" .
                '	"VERSION_DATE" => "' . date('Y-m-d H:i:s') . '"' . "\n" .
                ');' . "\n" .
                '?' . '>'
        )) {
            $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ") . $f . '<br>';
        }
        include($m_dir . '/install/version.php');
    } else {
        $v = $arModuleVersion['VERSION'];
    }

    if (is_dir($tmp = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/tmp/' . $module_id)) {
        BuilderRmDir($tmp);
    }

    $tarFile = $module_id . '(' . $v . ').tar.gz';
    mkdir($tmp . '/.last_version', BX_DIR_PERMISSIONS, true);

    if (function_exists('mb_internal_encoding'))
        mb_internal_encoding('ISO-8859-1');

    $tar = new CTarBuilder;
    $tar->path = $tmp;
    if (!$tar->openWrite($f = $tmp . '/' . $tarFile)) {
        $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_OTKRYTQ_F") . $f . '<br>';
    } else {
        $ar = BuilderGetFiles($m_dir, ['.svn', '.hg', '.git'], true);
        foreach ($ar as $file) {
            $from = $m_dir . $file;
            $to = $tmp . '/.last_version' . $file;

            if (false === $str = file_get_contents($from)) {
                $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_PROCITATQ") . $from . '<br>';
            } else {
                if (substr($file, -4) == '.php' && GetStringCharset($str) == 'utf8') {
                    $str = $APPLICATION->ConvertCharset($str, 'utf8', 'cp1251');
                }

                if (!file_exists($dir = dirname($to))) {
                    mkdir($dir, BX_DIR_PERMISSIONS, true);
                }

                if (false === file_put_contents($to, $str)) {
                    $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $to . '<br>';
                } else {
                    $tar->addFile($to);
                }
            }
        }
        $tar->close();
    }

    if (!$strError) {
        $link = '/bitrix/tmp/' . $module_id . '/' . $tarFile;
        $href = "/bitrix/admin/fileman_file_download.php?path=" . UrlEncode($link);
        CAdminMessage::ShowMessage([
            "MESSAGE" => GetMessage("BITRIX_MPBUILDER_ARHIV_SOZDAN_USPESNO"),
            "DETAILS" => GetMessage("BITRIX_MPBUILDER_GOTOVYY_VARIANT_MOJN") .
                ': <a href="' . $href . '">' . $link . '</a>' .
                '<br><input type=button value="' . GetMessage("BITRIX_MPBUILDER_UDALITQ_VREMENNYE_FA") . '" onclick="if(confirm(\'' . GetMessage("BITRIX_MPBUILDER_UDALITQ_PAPKU") . ' &quot;/bitrix/tmp/' . $module_id . '&quot; ' . GetMessage("BITRIX_MPBUILDER_I_EE_SODERJIMOE") . '?\'))document.location=\'?action=delete&' . bitrix_sessid_get() . '\'">',
            "TYPE" => "OK",
            "HTML" => true
        ]);
    } else {
        CAdminMessage::ShowMessage([
            "MESSAGE" => GetMessage("BITRIX_MPBUILDER_OSIBKA_OBRABOTKI_FAY"),
            "DETAILS" => $strError,
            "TYPE" => "ERROR",
            "HTML" => true
        ]);
    }
}

?>
<form action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" method="POST" enctype="multipart/form-data">
    <?= bitrix_sessid_post() ?>
    <?
    $editTab->Begin();
    $editTab->BeginNextTab();
    ?>
    <tr class="heading">
        <td colspan="2"><?= GetMessage("BITRIX_MPBUILDER_VYBOR_MODULA") ?></td>
    </tr>
    <tr>
        <td><?= GetMessage("BITRIX_MPBUILDER_TEKUSIY_MODULQ") ?></td>
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
    <?
    if ($module_id) {
    ?>
        <tr>
            <td><?= GetMessage("BITRIX_MPBUILDER_VERSIA_MODULA") ?></td>
            <td>
                <input name="version" value="<?= htmlspecialcharsbx(VersionUp($arModuleVersion['VERSION'])) ?>" id='version_field' disabled>
                <label>
                    <input type="checkbox" onchange="document.getElementById('version_field').disabled=!this.checked"> <?= GetMessage("BITRIX_MPBUILDER_OBNOVITQ_VERSIU") ?>
                </label>
            </td>
        </tr>
    <?
    }

    $editTab->Buttons();
    ?>
    <input type="submit" name="save" value="<?= GetMessage("BITRIX_MPBUILDER_BTN_CREATE") ?>">
    <input type="submit" name="next" value="<?= GetMessage("BITRIX_MPBUILDER_BTN_NEXT") ?>">
</form>
<? $editTab->End(); ?>

<? require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php"); ?>