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
					{text: myLabel.status, type: "toggle", sortable: true, editable: myLabel.has_update, positiveLabel: myLabel.active, positiveValue: "T", negativeLabel: myLabel.inactive, negativeValue: "F"}
				],
				dataUrl: dataUrl + pjGrid.queryString,
				dataType: "json",
				fields: ['name', 'email', 'phone', 'city', 'status'],
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
		// Eye icon: show / hide the password
		$(document).on("click", ".pj-toggle-password", function (e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			var $input = $($(this).data("target")),
				show = $input.attr("type") === "password";
			$input.attr("type", show ? "text" : "password");
			$(this).find("i").toggleClass("fa-eye", !show).toggleClass("fa-eye-slash", show);
			return false;
		});

		// Trash icon on the logo: delete it at once (no Save needed)
		$(document).on("click", ".pj-company-logo-delete", function (e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			var $btn = $(this);
			if ($btn.hasClass("disabled")) { return false; }
			$btn.addClass("disabled");
			$.post("index.php?controller=pjAdminCompanies&action=pjActionDeleteLogo", {id: $btn.data("id")}).done(function (data) {
				if (data && data.status === "OK") {
					$btn.closest(".pj-company-logo-box").remove();
				} else {
					$btn.removeClass("disabled");
					if (data && data.text) { window.alert(data.text); }
				}
			}).fail(function () {
				$btn.removeClass("disabled");
			});
			return false;
		});

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
						login_password_confirm: {equalTo: "#login_password"},
						login_password_current: {required: function () { return $("#login_password").val() !== "" || $("#login_password_confirm").val() !== ""; }}
					},
					errorPlacement: function (error, element) {
						// keep the eye icon next to the field
						if (element.parent(".input-group").length) {
							error.insertAfter(element.parent());
						} else {
							error.insertAfter(element);
						}
					},
					submitHandler: function (form) {
						var $btn = $(form).find(".ladda-button"),
							ladda = $btn.length && window.Ladda ? Ladda.create($btn.get(0)) : null,
							$pw = $(form).find("#login_password"),
							validator = this;
						if (ladda) {
							ladda.start();
						}
						if (!$pw.length || $pw.val() === "") {
							form.submit();
							return;
						}
						// new password: check the password rules first and show the exact rule that is not met
						$.post("index.php?controller=pjAdminCompanies&action=pjActionCheckPassword", {
							password: $pw.val(),
							csrf_token: $(form).find("input[name='csrf_token']").val()
						}).done(function (data) {
							if (data && data.status === "OK") {
								form.submit();
								return;
							}
							if (ladda) {
								ladda.stop();
							}
							validator.showErrors({login_password: data && data.text ? data.text : ""});
							$pw.trigger("focus");
						}).fail(function () {
							form.submit();
						});
					}
				});
			}
		}
	});
})(jQuery);
