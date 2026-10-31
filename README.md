# 「水水」开发工具箱（mz_DevKit）

面向本地开发的 Z-BlogPHP 后台增强工具集，把开发过程中反复要做的几件事收进一个插件：依赖自动启用、开发模式、编辑器 / 插件分组切换、插件管理页最近使用。

> 本插件的部分能力（开发模式、按 `ZC_USING_PLUGIN_LIST` 整组改写启用列表）会直接写到系统配置，建议只在开发环境启用。

## 插件仓库

**插件仓库如下，购买视为捐助：**

wdssmq/zbp\_mz\_DevKit: 依赖自动启用、开发模式自动开启、编辑器与插件分组切换、最近管理插件历史 - Z-BlogPHP 插件：

[https://github.com/wdssmq/zbp_mz_DevKit](https://github.com/wdssmq/zbp_mz_DevKit "wdssmq/zbp\_mz\_DevKit: 依赖自动启用、开发模式自动开启、编辑器与插件分组切换、最近管理插件历史 - Z-BlogPHP 插件")

## 功能说明

整合了下边两个插件的部分功能：

[「水水」开发者基础工具 - Z-Blog 应用中心](https://app.zblogcn.com/?id=1254 "「水水」开发者基础工具 - Z-Blog 应用中心")

[后台二级菜单 - Z-Blog 应用中心](https://app.zblogcn.com/?id=1212 "后台二级菜单 - Z-Blog 应用中心")

### 依赖自动启用

启用某个插件或主题时，自动启用其 `advanced_dependency` 里声明的依赖插件。

- 挂载在 `Filter_Plugin_Cmd_Begin`，只在 `act=PluginEnb` / `act=ThemeSet` 时处理。
- 依赖是按顺序逐个启用的，首次访问可能出现报错提示，刷新一次即可。

### 开发模式自动开启

自动开启「应用中心开发者模式」和系统调试模式：

- 应用中心配置：`AppCentre` → `enabledevelop`
- 系统配置：`ZC_DEBUG_MODE`

**只在本地环境生效**。站点地址（`zbp->host`）满足以下任一条才算本地：

### 编辑器快速切换

后台表单里以单选切换主编调器，可选：`Neditor`、`Editor.md`、`iceEditor`、`UEditor`。提交后停用当前编辑器、启用所选编辑器。

### 插件按分组切换启用

把插件写进分组，一次切换整套启用组合，方便在不同开发场景间来回切。

分组文件：`zb_users/plugin/mz_DevKit/usr/plugin-group.json`

```json
[
  {
    "name": "固定分组",
    "fixed": true,
    "desc": "这里设置需要一直启用的插件",
    "list": ["AppCentre", "mz_DevKit"]
  },
  {
    "name": "精简分组",
    "desc": "请根据实际情况设置",
    "list": ["mz_BrowserSync", "mzGrunt"]
  }
]
```

- `fixed: true` 的分组会被视为「固定分组」，其插件在切换时始终启用。
- 实际启用结果 = 目标分组插件 + 固定分组插件，直接写入 `ZC_USING_PLUGIN_LIST`。
- 模板文件在 `usr_cfg/plugin-group.json`，复制一份到 `usr/plugin-group.json` 再按自己的情况改；没有该文件时后台会提示，而不是报错。

### 插件管理页「最近使用」

在插件列表上方追加一条「最近使用」的快速链接区，点击即滚动定位并高亮对应插件行。记录存放在 localStorage：

以下情况会把插件记入并置顶到「最近使用」最前：

- 新安装的插件
- 启用 / 停用状态发生变动
- 版本号或发布日期变动（更新后）
- 手动点了插件行的「管理」按钮

记录里已经停用或不存在的插件会被跳过；停用状态的链接不可点（`javascript:;`），但仍会滚动定位到对应行。

