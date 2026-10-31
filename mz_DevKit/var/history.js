// 插件管理页「最近使用」
$(document).ready(function() {
  if (location.href.indexOf("PluginMng") == -1) {
    return;
  }

  const curTimeInCustom = parseInt(new Date().getTime() / 1000 / 300);
  let urlPlugBase = bloghost + "zb_users/plugin/";
  const curHost = location.host;
  if (urlPlugBase.indexOf(curHost) == -1) {
    urlPlugBase = `//${curHost}/zb_users/plugin/`;
  }

  // localStorage 封装
  const lsObj = {
    setItem: function(key, value) {
      localStorage.setItem(key, JSON.stringify(value));
    },
    getItem: function(key, def = "") {
      const item = localStorage.getItem(key);
      if (item) {
        return JSON.parse(item);
      }
      return def;
    },
  };

  const gob = {
    curPlugList: null,
    lstPlugList: null,
    hisPlugList: null,
    lsKey: {
      lst: "lstPlugList",
      his: "hisPlugList",
    },
    load: function(lstDef, hisDef) {
      this.lstPlugList = lsObj.getItem(this.lsKey.lst, lstDef);
      this.hisPlugList = lsObj.getItem(this.lsKey.his, hisDef);
    },
    save: function() {
      // 仅保存最新的 11 条记录
      while (gob.hisPlugList.length > 11) {
        gob.hisPlugList.shift();
      }
      lsObj.setItem(this.lsKey.lst, this.lstPlugList);
      lsObj.setItem(this.lsKey.his, this.hisPlugList);
    },
  };

  // 取插件的发布日期
  function fnGetPlugDate($plugTr) {
    let plugDate = "";
    $plugTr.find("td").each(function() {
      const txt = $(this).text().trim();
      if (/^\d{4}-\d{2}-\d{2}$/.test(txt)) {
        plugDate = txt;
        return false;
      }
    });
    return plugDate;
  }

  // 读取插件列表
  function fnGetPlugList() {
    const plugList = [];
    $(".plugin-list tr").each(function() {
      const plugID = $(this).find(".plugin").data("pluginid");
      if (!plugID) {
        return;
      }
      $(this).addClass(`plugin-id-${plugID}`);
      const plugOn = $(this).find("a>.icon-power.off").length == 0 ? 1 : 0;
      const $plugMain = $(this).find("a>.icon-tools").parent();

      let plugMain = $plugMain.attr("href");
      if (plugMain) {
        plugMain = plugMain.replace(urlPlugBase, "");
      } else {
        plugMain = "";
      }
      // .plugin-note 形如「应用中心客户端 2.85」，末位是版本号
      const plugNote = $(this).find(".plugin-note").text();
      const verMatch = plugNote.match(/\s([\d.]+)$/);
      const plugInfo = {
        id: plugID,
        name: plugNote.replace(/\s[\d\.]+$/, ""),
        ver: verMatch ? verMatch[1] : "",
        date: fnGetPlugDate($(this)),
        on: plugOn,
        time: curTimeInCustom,
        main: plugMain,
      };
      plugList.push(plugInfo);
      $plugMain.click(function() {
        fnUpdateHistoryList(plugInfo);
      });
    });
    return plugList;
  }

  // 判断数组中是否存在同一状态的项
  // justID 为 true 时只比对 id；否则还需启用状态一致，版本号与日期在旧项有值时才参与比较
  // （旧缓存里没有版本号、日期，缺失时不比，避免升级后一次性把所有插件都判定为变动）
  function fnCheckItemInArray(arr, item, justID = false) {
    for (let i = 0; i < arr.length; i++) {
      if (arr[i].id != item.id) {
        continue;
      }
      if (justID) {
        return true;
      }
      if (arr[i].on != item.on) {
        return false;
      }
      if (arr[i].ver && arr[i].ver != item.ver) {
        return false;
      }
      if (arr[i].date && arr[i].date != item.date) {
        return false;
      }
      return true;
    }
    return false;
  }

  // 对比数组差异
  function fnDiffPlugList(arr1, arr2) {
    const diffList = [];
    for (let i = 0; i < arr1.length; i++) {
      if (!fnCheckItemInArray(arr2, arr1[i])) {
        diffList.push(arr1[i]);
      }
    }
    return diffList;
  }

  // 更新历史记录：提到最前
  function fnUpdateHistoryList(plugInfo) {
    gob.hisPlugList = fnTopPlugList(gob.hisPlugList, [plugInfo]);
    gob.save();
  }

  // 把发生变动的项提到「最近使用」最前
  // 渲染时数组末尾的项排在最前，所以先移除旧记录再追加到末尾
  function fnTopPlugList(hisList, diffList) {
    for (let i = 0; i < diffList.length; i++) {
      const index = hisList.findIndex(item => item.id == diffList[i].id);
      if (index > -1) {
        hisList.splice(index, 1);
      }
      hisList.push(diffList[i]);
    }
    return hisList;
  }

  // 滚动到插件位置并高亮
  function fnFocusToPlug(plugInfo, isMiddleClick) {
    const { plugID, isOn, mainUrl } = plugInfo;
    const $plug = $(`.plugin-id-${plugID}`);
    $("html, body").animate({ scrollTop: $plug.offset().top - 13 }, 500);
    // 移除其他插件的高亮
    $(".plugin-list tr").removeClass("mz-focus");
    // 添加类名用于高亮
    $plug.addClass("mz-focus");
    if (isOn) {
      // 移除其他插件的 mainUrl
      $(".mz-main-url").remove();
      // .plugin-note 后追加 mainUrl
      $plug.find(".plugin-note").after(`<a class="mz-main-url" href="${mainUrl}" title="管理">管理</a>`);
    }
    $(".mz-main-url").css({ float: "right" });
    return false;
  }

  // 向而面添加链接
  function fnPutLink(arr) {
    $(".plugin-list").before(`<div class="mz-his">
    <span>最近使用：</span>
    </div>`);
    const $base = $(".mz-his span");
    for (let i = 0; i < arr.length; i++) {
      // 跳过不存在的
      if (!fnCheckItemInArray(gob.curPlugList, arr[i], true)) {
        continue;
      }
      let curMainUrl = "";
      const { id: plugID, main: mainUrl, name: plugName, on: isOn } = arr[i];
      if (!isOn) {
        curMainUrl = "javascript:;";
      } else {
        curMainUrl = urlPlugBase + mainUrl;
      }
      $base.after(`
        <a href="${curMainUrl}" title="${plugName}" class="mz-his-link plug-id-${plugID} is-on-${isOn}">${plugName}</a>
      `);
      // 添加点击事件，包括中键点击
      $(`.plug-id-${plugID}`).on("mousedown", function(event) {
        // 判断是否为中键
        const isMiddleClick = event.button == 1;
        event.preventDefault();
        return fnFocusToPlug({ plugID, isOn, mainUrl: curMainUrl }, isMiddleClick);
      });
      // 匹配对应插件，并提前
      $(`.plugin-id-${plugID}`).insertAfter("table.plugin-list tbody tr:first-child");
    }
  }

  // 当前插件列表
  gob.curPlugList = fnGetPlugList();

  gob.load(gob.curPlugList, []);

  // 新安装、以及启用状态 / 版本号 / 日期有变动的插件
  const diffPlugList = fnDiffPlugList(gob.curPlugList, gob.lstPlugList);

  if (diffPlugList.length > 0) {
    // 变动项统一提到「最近使用」最前
    gob.hisPlugList = fnTopPlugList(gob.hisPlugList, diffPlugList);
    gob.lstPlugList = gob.curPlugList;
    gob.save();
  }

  fnPutLink(gob.hisPlugList);
});
