// 后台二级菜单折叠按钮
// 依赖：二级菜单拆分功能生成的 .js-SubMenu 容器（未启用该项时折叠无效果）

$(function() {
  // 折叠菜单按钮
  $("#topmenu").append("<li id=\"mz-sm-burger\" class=\"mz-sm-burger\"><a href=\"javascript:;\" title=\"折叠菜单\"><i class=\"icon-menu-button-wide-fill\"></i><span>隐藏/显示</span></a></li>");

  let bolHideSubMenu = localStorage.bolHideSubMenu ? localStorage.bolHideSubMenu : 0;
  bolHideSubMenu = parseInt(bolHideSubMenu);
  if (bolHideSubMenu) { $(".js-SubMenu").addClass("hidden"); }

  $("#mz-sm-burger").click(function() {
    $(".js-SubMenu").toggleClass("hidden");
    if ($(".js-SubMenu").hasClass("hidden")) {
      localStorage.bolHideSubMenu = 1;
    } else {
      localStorage.bolHideSubMenu = 0;
    }
  });
});
