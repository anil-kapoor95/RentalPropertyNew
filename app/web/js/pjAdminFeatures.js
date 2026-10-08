var jQuery = jQuery || $.noConflict();
(function ($, undefined) {
	$(function () {
		var multilang = ($.fn.multilang !== undefined),
			validate = ($.fn.validate !== undefined),
			datagrid = ($.fn.datagrid !== undefined);

		var $grid = null;

		if ($("#grid").length > 0 && datagrid) {
			var buttonsOpts = [];
			var actionsOpts = [];

			if (myLabel.has_update)
			{
				buttonsOpts.push({type: "edit", url: "index.php?controller=pjAdminFeatures&action=pjActionUpdate&id={:id}"});
			}
			if (myLabel.has_delete)
			{
				buttonsOpts.push({type: "delete", url: "index.php?controller=pjAdminFeatures&action=pjActionDeleteFeature&id={:id}"});
			}
			if (myLabel.has_delete_bulk)
			{
				actionsOpts.push({text: myLabel.delete_selected, url: "index.php?controller=pjAdminFeatures&action=pjActionDeleteFeatureBulk", render: true, confirmation: myLabel.delete_confirmation});
			}

			$grid = $("#grid").datagrid({
				buttons: buttonsOpts,
				columns: [
				          {text: myLabel.name, type: "text", sortable: true, editable: myLabel.has_update},
				          {text: myLabel.category, type: "text", sortable: false, editable: false},
				          {text: myLabel.status, type: "toggle", sortable: true, editable: myLabel.has_update, positiveLabel: myLabel.active, positiveValue: "T", negativeLabel: myLabel.inactive, negativeValue: "F"}
				          ],
				dataUrl: "index.php?controller=pjAdminFeatures&action=pjActionGetFeature" + pjGrid.queryString,
				dataType: "json",
				fields: ['name', 'category', 'status'],
				paginator: {
					actions: actionsOpts,
					gotoPage: true,
					paginate: true,
					total: true,
					rowCount: true
				},
				saveUrl: "index.php?controller=pjAdminFeatures&action=pjActionSaveFeature&id={:id}",
				select: {
					field: "id",
					name: "record[]",
					cellClass: 'cell-width-2'
				}
			});
		}

		$(document).on("submit", ".frm-filter", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			var $this = $(this),
				content = $grid.datagrid("option", "content"),
				cache = $grid.datagrid("option", "cache");
			$.extend(cache, {
				q: $this.find("input[name='q']").val()
			});
			$grid.datagrid("option", "cache", cache);
			$grid.datagrid("load", "index.php?controller=pjAdminFeatures&action=pjActionGetFeature" + pjGrid.queryString, content.column, content.direction, content.page, content.rowCount);
			return false;
		}).on("click", ".btn-all", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			$(this).addClass("active").siblings(".btn").removeClass("active");
			var content = $grid.datagrid("option", "content"),
				cache = $grid.datagrid("option", "cache");
			$.extend(cache, {
				status: "",
				q: ""
			});
			$grid.datagrid("option", "cache", cache);
			$grid.datagrid("load", "index.php?controller=pjAdminFeatures&action=pjActionGetFeature" + pjGrid.queryString, content.column, content.direction, content.page, content.rowCount);
			return false;
		}).on("click", ".btn-filter", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			var $this = $(this),
				content = $grid.datagrid("option", "content"),
				cache = $grid.datagrid("option", "cache"),
				obj = {};
			$this.removeClass("btn-default").addClass("active").addClass("btn-primary").siblings(".btn").removeClass("active").removeClass("btn-primary").addClass("btn-default");
			obj.status = "";
			obj[$this.data("column")] = $this.data("value");
			$.extend(cache, obj);
			$grid.datagrid("option", "cache", cache);
			$grid.datagrid("load", "index.php?controller=pjAdminFeatures&action=pjActionGetFeature" + pjGrid.queryString, content.column, content.direction, content.page, content.rowCount);
			return false;
		}).on("click", ".btnAddFeature, .pj-table-icon-edit", function (e) {
			var $url = $(this).attr("href");
			$("#myModalFeature").data('href', $url).modal('show');
			return false;
		});

		$("#myModalFeature").on("show.bs.modal", function(e) {
		    var $href = $("#myModalFeature").data('href');
		    $(this).find(".modal-content").load($href, function() {
		    	if (multilang && myLabel.isFlagReady == 1) {
					$(".multilang").multilang({
						langs: pjLocale.langs,
						flagPath: pjLocale.flagPath,
						tooltip: "",
						select: function (event, ui) {
							$("input[name='locale_id']").val(ui.index);					
						}
					});

					var $multilangItem = $('#myModalFeature').find('.pj-form-langbar-item');
					$multilangItem.each(function(e){
						var locale_id = $(this).attr('data-index');
						if(locale_id == myLabel.localeId)
						{
							$(this).trigger('click');
						}
					});
				}
			    $('.tooltip-demo').tooltip({
			        selector: "[data-toggle=tooltip]"
			    });
		    });
		}).on("click", ".btnSaveFeature", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			var $form = $(this).closest('form');
			if ($form.valid()) {
				var l = Ladda.create($(this).get(0));
				l.start();
				$.post($form.attr('action'), $form.serialize()).done(function (data) {
					var content = $grid.datagrid("option", "content");
					$grid.datagrid("load", "index.php?controller=pjAdminFeatures&action=pjActionGetFeature" + pjGrid.queryString, content.column, content.direction, content.page, content.rowCount);
					l.stop();
					$("#myModalFeature").modal('hide');
				}).always(function () {
					l.stop();
				});
			}
			return false;
		});
	});
})(jQuery);
