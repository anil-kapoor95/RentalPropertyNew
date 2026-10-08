var jQuery_1_8_2 = jQuery_1_8_2 || jQuery.noConflict();
(function ($, undefined) {
	$(function () {
		var $modalRazorpayInfo = $("#modalRazorpayInfo"),
			datagrid = ($.fn.datagrid !== undefined),
			$grid;

		function formatBtn(str, obj) {
		    return ['<a href="#" data-id="', obj.id, '"', ' data-remote="false" data-toggle="modal" data-target="#modalRazorpayInfo" class="btn btn-primary btn-outline"><i class="fa fa-eye"></i> ', myLabel.btn_view, '</a>'].join("");
		}

		if ($("#grid").length > 0 && datagrid) {
			$grid = $("#grid").datagrid({
				buttons: [],
				columns: [
				    {text: myLabel.razorpay_id, type: "text", sortable: true, editable: false},
				    {text: myLabel.order_id, type: "text", sortable: true, editable: false},
				    {text: myLabel.amount, type: "text", sortable: true, editable: false},
				    {text: myLabel.created, type: "text", sortable: true, editable: false},
				    {text: "", type: "text", sortable: false, editable: false, renderer: formatBtn}
				],
				dataUrl: "index.php?controller=pjRazorpay&action=pjActionGetRazorpay",
				dataType: "json",
				fields: ['razorpay_id', 'order_id', 'amount', 'created', 'id'],
				paginator: {
					actions: [],
					gotoPage: true,
					paginate: true,
					total: true,
					rowCount: true
				},
				select: false
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
			$grid.datagrid("load", "index.php?controller=pjRazorpay&action=pjActionGetRazorpay", content.column, content.direction,  content.page, content.rowCount);
			return false;
		});

		if ($modalRazorpayInfo.length > 0) {
            $modalRazorpayInfo.on("show.bs.modal", function(e) {
                var link = $(e.relatedTarget);
                $(this).find(".modal-body").load('index.php?controller=pjRazorpay&action=pjActionGetDetails&id=' + link.attr("data-id"), function (e) {

                });
            });
		}
	});
})(jQuery_1_8_2);
