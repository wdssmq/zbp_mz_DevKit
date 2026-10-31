/* global arrSubMenu urlSubMenu */
// 后台二级菜单拆分、拖拽排序
// 依赖：外部需先定义 arrSubMenu（拆分项）与 urlSubMenu（保存地址）

$(function() {
  if (typeof arrSubMenu === "undefined" || typeof urlSubMenu === "undefined") {
    return;
  }

  // 追加菜单列表
  $(".menu").after("<ul class=\"js-SubMenu SubMenu\"></ul>");

  // arrSubMenu 数组内匹配的项移动副菜单
  for (var x = 0; x < arrSubMenu.length; x++) {
    $("#topmenu li").each(function() {
      if ($(this).text() == arrSubMenu[x]) {
        const $a = $(this).find("a");
        $a.html(`<span>${$a.text()}</span>`);
        $(this).appendTo($(".js-SubMenu"));
      }
    });
  }

  // 排序事件
  $(".js-SubMenu").sortable({
    items: "li",
    start: function(event, ui) {
      var c = ui.item.find("a").text();
      if (ui.item.parent().find("a:contains(" + c + ")").length > 1) {
        ui.item.remove();
      }
    },
    stop: function(event, ui) {
      ui.item.find("i").remove();
      window.setTimeout(fnSaveSub, 800);
      fnDrag();
    },
  });
  // 排序事件
  $("#topmenu").sortable({
    items: "li",
    start: function(event, ui) {
      var c = ui.item.find("a").text();
      if (ui.item.parent().find("a:contains(" + c + ")").length > 1) {
        ui.item.remove();
      }
    },
    stop: function(event, ui) {
      window.setTimeout(fnSaveSub, 800);
      fnDrag();
    },
  });

  // 监听拖拽事件
  function fnDrag() {
    $("#topmenu li").draggable({
      connectToSortable: ".js-SubMenu",
      revert: "invalid",
      containment: "document",
      helper: "clone",
      cursor: "move",
      start: function(event, ui) {
        if (ui.helper.hasClass("mz-sm-burger")) {
          return false;
        }
        $(".js-SubMenu").removeClass("hidden");
        $(".js-SubMenu").append("<li class=\"placeholder\"><a href=\"#\"><span>add</span></a></li>");
      },
      stop: function(event, ui) {
        if ($(".js-SubMenu li").length > 1) {
          $(".placeholder").remove();
        }
        if ($(".js-SubMenu").find("a:contains(" + $(this).text() + ")").length > 0) {
          $(this).remove();
        }
      },
    }).disableSelection();

    $(".js-SubMenu li").draggable({
      connectToSortable: "#topmenu",
      revert: "invalid",
      containment: "document",
      helper: "clone",
      cursor: "move",
      start: function(event, ui) {
        $("#topmenu").prepend("<li class=\"placeholder\"><a href=\"#\"><span>add</span></a></li>");
        $("#topmenu").css({
          "padding": "0 0 0 100px",
        });
      },
      stop: function(event, ui) {
        if ($("#topmenu li").length > 1) {
          $(".placeholder").remove();
        }
        if ($("#topmenu").find("a:contains(" + $(this).text() + ")").length > 0) {
          $(this).remove();
        }
      },
    }).disableSelection();
  }
  fnDrag();

  function fnSaveSub() {
    var arr = [];
    $(".js-SubMenu a").each(function() {
      if ($(this).text() != "add")
        arr.push($(this).text());
    });
    $.post(urlSubMenu, {
      "data": JSON.stringify(arr),
    },
    function(data) {
      // alert("Data Loaded: " + data);
    });
  }
});
