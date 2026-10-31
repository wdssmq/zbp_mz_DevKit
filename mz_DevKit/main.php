<?php
require '../../../zb_system/function/c_system_base.php';

require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
    $zbp->ShowError(6);

    exit();
}
if (!$zbp->CheckPlugin('mz_DevKit')) {
    $zbp->ShowError(48);

    exit();
}

$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');

// 确保二级菜单配置文件存在
mz_DevKit_InitCfg();

if ('save' == $act) {
    // 二级菜单排序结果的异步保存
    $data = GetVars('data', 'POST');
    if (null !== $data) {
        file_put_contents(mz_DevKit_Path('config'), $data);
        echo 'ok';

        exit();
    }

    // 表单提交
    CheckIsRefererValid();
    // 修改编辑器
    $edt = GetVars('read_edt', 'POST');
    mz_DevKit_SetCurEdt($edt);
    // 切换插件启用
    $switch = GetVars('read_switch', 'POST');
    $switchTo = GetVars('read_switchTo', 'POST');
    if (1 == $switch) {
        mz_DevKit_PlugGroupSwitch($switchTo);
    }
    // 保存配置项
    foreach ($_POST as $key => $val) {
        if ('read_' == substr($key, 0, 5)) {
            continue;
        }
        $zbp->Config('mz_DevKit')->{$key} = trim($val);
    }
    $zbp->SaveConfig('mz_DevKit');
    $zbp->BuildTemplate();
    $zbp->SetHint('good');
    Redirect('./main.php' . (null == $suc ? '' : "?act={$suc}"));
}

$cfgPlugGroup = mz_DevKit_GetPlugGroupCFG();

$blogtitle = '开发工具箱';

require $blogpath . 'zb_system/admin/admin_header.php';

require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
    <div class="divHeader">
        <?php echo $blogtitle; ?>
    </div>
    <div class="SubMenu">
        <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    </div>
    <div id="divMain2">
        <form action="<?php echo BuildSafeURL('main.php?act=save'); ?>" method="post">
            <table width="100%" class="tableBorder">
                <tr>
                    <th width="10%">项目</th>
                    <th>内容</th>
                    <th width="45%">说明</th>
                </tr>
                <tr>
                    <td>编辑器切换</td>
                    <td><?php zbpform::radio('read_edt', $edtList, mz_DevKit_GetCurEdt()); ?></td>
                    <td>停用当前编辑器并启用所选编辑器</td>
                </tr>
                <tr>
                    <td>切换插件启用</td>
                    <td>
                        <?php if ($cfgPlugGroup->hasGroup) {
    echo mz_DevKit_BuildPlugGroupForm($cfgPlugGroup, 0);
    echo '<label for="read_switch">执行切换：</label>';
    echo zbpform::zbradio('read_switch', 0);
} else {
    echo '无分组配置，请复制一份 /usr_cfg/plugin-group.json 到 /usr/plugin-group.json';
} ?>
                    </td>
                    <td>
                        启用「指定分组 + 固定分组」中的插件，停用其他插件
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2"><input type="submit" value="提交" /><small><a title="刷新" href="javascript:;" onclick="location.reload();" style="font-size: 16px;display: inline-block;margin-left: 5px;">刷新</a></small></td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
