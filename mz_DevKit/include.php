<?php
require __DIR__ . '/function.php';
// 注册插件
RegisterPlugin("mz_DevKit", "ActivePlugin_mz_DevKit");

function ActivePlugin_mz_DevKit()
{
    // 启用插件或主题时自动启用其依赖
    Add_Filter_Plugin('Filter_Plugin_Cmd_Begin', 'mz_DevKit_PluginEnb');
    // // 后台二级菜单拆分、拖拽排序
    // Add_Filter_Plugin('Filter_Plugin_Admin_Footer', 'mz_DevKit_SubMenu');
    // // 后台二级菜单折叠按钮
    // Add_Filter_Plugin('Filter_Plugin_Admin_Footer', 'mz_DevKit_SubMenuBurger');
    // 插件管理页「最近使用」
    Add_Filter_Plugin('Filter_Plugin_Admin_Footer', 'mz_DevKit_PlugHistory');
}

/**
 * 路径辅助
 */
function mz_DevKit_Path($file, $t = 'path')
{
    global $zbp;
    $result = $zbp->$t . 'zb_users/plugin/mz_DevKit/';
    switch ($file) {
        case 'u-plugin-group':
            return $result . 'usr/plugin-group.json';
            break;
        case 'usr':
            return $result . 'usr/';
            break;
        case 'var':
            return $result . 'var/';
            break;
        case 'js-submenu':
            return $result . 'var/submenu.js';
            break;
        case 'js-burger':
            return $result . 'var/burger.js';
            break;
        case 'js-history':
            return $result . 'var/history.js';
            break;
        case 'style':
            return $result . 'var/style.css';
            break;
        case 'config':
            return $result . 'config/cfg.json';
            break;
        case 'save':
            return $result . 'main.php?act=save';
            break;
        case 'main':
            return $result . 'main.php';
            break;
        default:
            return $result . $file;
    }
}

/**
 * 页脚注入，同一个标识只输出一次
 * @param string $key 标识
 * @param string $html 要输出的标签
 * @return bool 是否为首次输出
 */
function mz_DevKit_EchoOnce($key, $html)
{
    static $done = array();
    if (isset($done[$key])) {
        return false;
    }
    $done[$key] = true;
    echo $html . "\n";
    return true;
}

/**
 * 共用样式表，各功能按需调用，重复调用只输出一次
 */
function mz_DevKit_EchoStyle()
{
    return mz_DevKit_EchoOnce('style', '<link rel="stylesheet" href="' . mz_DevKit_Path("style", "host") . '" />');
}

/**
 * 二级菜单拆分、拖拽排序
 */
function mz_DevKit_SubMenu()
{
    mz_DevKit_EchoStyle();

    $file = mz_DevKit_Path("config");
    if (!is_file($file)) {
        return;
    }

    $arrSubMenu = file_get_contents($file);
    $html = '<script>'
        . 'const urlSubMenu = "' . mz_DevKit_Path("save", "host") . '";'
        . 'const arrSubMenu = ' . $arrSubMenu . ';'
        . '</script>'
        . '<script src="' . mz_DevKit_Path("js-submenu", "host") . '"></script>';

    mz_DevKit_EchoOnce('submenu', $html);
}

/**
 * 二级菜单折叠按钮
 * 依赖上面的二级菜单拆分功能生成的 .js-SubMenu 容器，该项未输出时只显示按钮，折叠无效果
 */
function mz_DevKit_SubMenuBurger()
{
    mz_DevKit_EchoStyle();

    mz_DevKit_EchoOnce('burger', '<script src="' . mz_DevKit_Path("js-burger", "host") . '"></script>');
}

/**
 * 插件管理页「最近使用」
 * 仅在插件管理页注入
 */
function mz_DevKit_PlugHistory()
{
    if (GetVars('act', 'GET') !== 'PluginMng') {
        return;
    }
    mz_DevKit_EchoStyle();

    mz_DevKit_EchoOnce('plugin-history', '<script src="' . mz_DevKit_Path("js-history", "host") . '"></script>');
}

/**
 * 启用插件或主题时触发
 */
function mz_DevKit_PluginEnb()
{
    $act = GetVars("act", "GET");
    if ($act !== "PluginEnb" && $act !== "ThemeSet") {
        return false;
    }
    if ($act == "PluginEnb") {
        $appInfo = array("plugin", GetVars('name', 'GET'));
    } else {
        $appInfo = array("theme", GetVars('theme', 'POST'));
    }
    $app = new App();
    if ($app->LoadInfoByXml($appInfo[0], $appInfo[1]) == false) {
        return;
    }

    // 遍历依赖插件并启用，仍然会报错，但是刷新下就好
    $ad = explode('|', $app->advanced_dependency);
    foreach ($ad as $d) {
        if (!$d) {
            continue;
        }
        EnablePlugin($d);
    }
}

/**
 * 确保二级菜单配置文件存在
 */
function mz_DevKit_InitCfg()
{
    $file = mz_DevKit_Path("config");
    if (!is_file($file)) {
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, "[]");
    }
}

function InstallPlugin_mz_DevKit()
{
    global $zbp;
    mz_DevKit_InitCfg();
    // 本地环境安装即开启开发模式
    if (mz_DevKit_SetDevMode()) {
        $zbp->SetHint("good", "已经启用开发模式");
    } else {
        $zbp->SetHint("tips", "当前站点非本地地址，未自动开启开发模式");
    }
}

function UninstallPlugin_mz_DevKit() {}
