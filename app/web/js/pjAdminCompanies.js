var jQuery = jQuery || $.noConflict();
(function ($, undefined) {
	$(function () {
		var validate = ($.fn.validate !== undefined),
			datagrid = ($.fn.datagrid !== undefined),
			select2 = ($.fn.select2 !== undefined),
			$frmCompany = $("#frmCompany"),
			dataUrl = "index.php?controller=pjAdminCompanies&action=pjActionGetCompany",
			$grid = null;

		/* ---------- List ---------- */
		if ($("#grid").length > 0 && datagrid) {
			var buttonsOpts = [],
				actionsOpts = [];

			if (myLabel.has_update) {
				buttonsOpts.push({type: "edit", url: "index.php?controller=pjAdminCompanies&action=pjActionUpdate&id={:id}"});
			}
			if (myLabel.has_delete) {
				buttonsOpts.push({type: "delete", url: "index.php?controller=pjAdminCompanies&action=pjActionDeleteCompany&id={:id}"});
			}
			if (myLabel.has_delete_bulk) {
				actionsOpts.push({text: myLabel.delete_selected, url: "index.php?controller=pjAdminCompanies&action=pjActionDeleteCompanyBulk", render: true, confirmation: myLabel.delete_confirmation});
			}

			$grid = $("#grid").datagrid({
				buttons: buttonsOpts,
				columns: [
					{text: myLabel.name, type: "text", sortable: true, editable: myLabel.has_update},
					{text: myLabel.email, type: "text", sortable: true, editable: false},
					{text: myLabel.phone, type: "text", sortable: true, editable: false},
					{text: myLabel.city, type: "text", sortable: true, editable: false},
					{text: myLabel.user, type: "text", sortable: false, editable: false},
					{text: myLabel.status, type: "toggle", sortable: true, editable: myLabel.has_update, positiveLabel: myLabel.active, positiveValue: "T", negativeLabel: myLabel.inactive, negativeValue: "F"}
				],
				dataUrl: dataUrl + pjGrid.queryString,
				dataType: "json",
				fields: ['name', 'email', 'phone', 'city', 'user_name', 'status'],
				paginator: {
					actions: actionsOpts,
					gotoPage: true,
					paginate: true,
					total: true,
					rowCount: true
				},
				saveUrl: "index.php?controller=pjAdminCompanies&action=pjActionSaveCompany&id={:id}",
				select: {
					field: "id",
					name: "record[]",
					cellClass: 'cell-width-2'
				}
			});
		}

		function reloadGrid(obj) {
			var content = $grid.datagrid("option", "content"),
				cache = $grid.datagrid("option", "cache");
			$.extend(cache, obj);
			$grid.datagrid("option", "cache", cache);
			$grid.datagrid("load", dataUrl + pjGrid.queryString, content.column, content.direction, content.page, content.rowCount);
		}

		$(document).on("submit", ".frm-filter", function (e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			if ($grid) { reloadGrid({q: $(this).find("input[name='q']").val()}); }
			return false;
		}).on("click", ".btn-all", function (e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			$(this).addClass("active").siblings(".btn").removeClass("active btn-primary").addClass("btn-default");
			$(this).removeClass("btn-default").addClass("btn-primary");
			if ($grid) { reloadGrid({status: "", q: ""}); }
			return false;
		}).on("click", ".btn-filter", function (e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			var $this = $(this), obj = {status: ""};
			$this.removeClass("btn-default").addClass("active btn-primary").siblings(".btn").removeClass("active btn-primary").addClass("btn-default");
			obj[$this.data("column")] = $this.data("value");
			if ($grid) { reloadGrid(obj); }
			return false;
		});

		/* ---------- Form ---------- */
		if ($frmCompany.length > 0) {
			if (select2 && $frmCompany.find(".select-item").length) {
				$frmCompany.find(".select-item").select2({
					allowClear: true,
					width: "100%"
				});
			}
			if (validate) {
				$frmCompany.validate({
					onkeyup: false,
					ignore: ":hidden:not(.select-item)",
					rules: {
						commission: {number: true, min: 0, max: 100}
					},
					submitHandler: function (form) {
						var $btn = $(form).find(".ladda-button");
						if ($btn.length && window.Ladda) {
							Ladda.create($btn.get(0)).start();
						}
						form.submit();
					}
				});
			}
		}
	});
})(jQuery);
