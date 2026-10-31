<?php

$edtList = array(
    "Neditor" => "Neditor",
    "Editormd" => "Editor.md",
    "iceEditor" => "iceEditor",
    "UEditor" => "UEditor",
);

/**
 * 判断 IPv4 是否落在指定网段内
 * @param string $ip 形如 192.168.1.10
 * @param string $range 形如 192.168.0.0/16
 * @return bool
 */
function mz_DevKit_IpInRange($ip, $range)
{
    static $cache = array();
    $key = $ip . '|' . $range;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (strpos($range, '/') === false) {
        $cache[$key] = ($ip === $range);
        return $cache[$key];
    }

    list($subnet, $bits) = explode('/', $range, 2);
    $ipLong = ip2long($ip);
    $subLong = ip2long($subnet);
    if ($ipLong === false || $subLong === false) {
        $cache[$key] = false;
        return $cache[$key];
    }

    $bits = (int) $bits;
    $mask = -1 << (32 - $bits);
    $cache[$key] = (($ipLong & $mask) === ($subLong & $mask));
    return $cache[$key];
}

/**
 * 判断是否为本地 / 内网地址
 * @param string $url 形如 http://localhost:8080/zblog/ ，缺省取当前站点地址
 * @param bool $allowLan 是否把私有网段（192.168.x 等）也算作本地
 * @return bool
 */
function mz_DevKit_IsLocalUrl($url = '', $allowLan = true)
{
    global $zbp;

    if ($url === '') {
        $url = $zbp->host;
    }
    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null || $host === false || $host === '') {
        // 无协议头的写法，如 localhost:8080
        $host = preg_replace('#^[a-z]+://#i', '', $url);
        $host = strtolower(preg_replace('#[:/?\#].*$#', '', $host));
    }
    // IPv6 带方括号
    $host = strtolower(trim((string) $host, '[]'));
    if ($host === '') {
        return false;
    }

    // 本机常见写法
    if (in_array($host, array('localhost', '127.0.0.1', '::1', '0.0.0.0'), true)) {
        return true;
    }

    // IPv6 本地：链路本地 fe80::/10、唯一本地 fc00::/7
    if (strpos($host, ':') !== false) {
        if (
            strpos($host, 'fe8') === 0 || strpos($host, 'fe9') === 0
            || strpos($host, 'fea') === 0 || strpos($host, 'feb') === 0
            || strpos($host, 'fc') === 0 || strpos($host, 'fd') === 0
        ) {
            return true;
        }
        return false;
    }

    // 本地开发常用域名后缀
    $localSuffix = array('.local', '.localhost', '.test', '.dev', '.lan', '.localdomain', '.home', '.internal', '.docker');
    foreach ($localSuffix as $suffix) {
        if (substr($host, -strlen($suffix)) === $suffix) {
            return true;
        }
    }

    // IPv4 私有网段
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (mz_DevKit_IpInRange($host, '127.0.0.0/8')) {
            return true;
        }
        if ($allowLan) {
            $lanRanges = array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16');
            foreach ($lanRanges as $range) {
                if (mz_DevKit_IpInRange($host, $range)) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * 开启开发模式（应用中心 + 系统）
 * 非本地站点不生效
 * @return bool 是否执行了开启
 */
function mz_DevKit_SetDevMode()
{
    global $zbp;
    // 非本地环境不自动开开发模式
    if (!mz_DevKit_IsLocalUrl()) {
        return false;
    }
    // 应用中心开发模式开启
    $zbp->Config('AppCentre')->enabledevelop = true;
    $zbp->SaveConfig('AppCentre');
    // zblog 开发模式
    $zbp->option['ZC_DEBUG_MODE'] = true;
    $zbp->SaveOption();
    return true;
}

/**
 * ↓ 编辑器快速切换
 */

function mz_DevKit_GetCurEdt()
{
    global $zbp, $edtList;
    $curEdt = "";
    foreach ($edtList as $key => $value) {
        if ($zbp->CheckPlugin("{$key}")) {
            $curEdt = $key;
            break;
        }
    }
    return $curEdt;
}

function mz_DevKit_SetCurEdt($edt)
{
    global $zbp;
    if (empty($edt)) {
        return;
    }
    // 获取当前编辑器
    $oldEdt = mz_DevKit_GetCurEdt();
    if ($oldEdt == $edt) {
        return;
    }
    // 停用当前编辑器
    if (!empty($oldEdt)) {
        DisablePlugin($oldEdt);
    }
    // $zbp->activedapps 内移除当前编辑器
    $zbp->activedapps = array_diff($zbp->activedapps, array($oldEdt));
    // 启用新编辑器
    EnablePlugin($edt);
}

/**
 * ↓ 插件按分组切换启用
 */

/**
 * 获取插件分组配置
 * @return object
 * @property bool $hasGroup 是否有分组配置
 * @property array $group 分组配置
 */
function mz_DevKit_GetPlugGroupCFG()
{
    $uFile = mz_DevKit_Path("u-plugin-group");
    $cfgData = (object) array("hasGroup" => false, "group" => array());
    if (file_exists($uFile)) {
        $cfgData->hasGroup = true;
        $cfgData->group = json_decode(file_get_contents($uFile));
    }
    return $cfgData;
}

// 切换分组配置构建表单
function mz_DevKit_BuildPlugGroupForm($cfgPlugGroup, $cur = 0)
{
    if ($cfgPlugGroup->hasGroup) {
        // 从分组中去掉「固定分组」返回新数组
        $group = array_filter($cfgPlugGroup->group, function ($value) {
            return !isset($value->fixed) || !$value->fixed;
        });
        $selectArr = array();
        foreach ($group as $key => $value) {
            $selectArr[$key] = $value->name;
        }
        ZbpForm::$setreturn = true;
        return zbpform::select("read_switchTo", $selectArr, $cur, "$('#read_switch+.imgcheck').click();");
    }
}

// 执行插件切换
function mz_DevKit_PlugGroupSwitch($to = 1)
{
    global $zbp;
    $pickPlugList = array();
    $cfgPlugGroup = mz_DevKit_GetPlugGroupCFG();
    foreach ($cfgPlugGroup->group as $key => $value) {
        // 合并「固定分组」内的插件
        if (isset($value->fixed) && $value->fixed) {
            $pickPlugList = array_merge($pickPlugList, $value->list);
        }
        // 如果是目标分组，合并插件
        if ($key == $to) {
            $pickPlugList = array_merge($pickPlugList, $value->list);
        }
    }
    // 启用插件
    // 拼接后赋值给 $zbp->option['ZC_USING_PLUGIN_LIST']
    $zbp->option['ZC_USING_PLUGIN_LIST'] = implode("|", $pickPlugList);
    $zbp->SaveOption();
    // 设置提示
    $zbp->SetHint('good', '插件分组切换成功');
}
