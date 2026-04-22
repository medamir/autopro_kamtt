$(document).ready(function () {
	function updateRate(brandId) {
		$.ajax({
			url: "ajax/get_rate.php",
			type: "POST",
			data: {
				brand_id: brandId,
				token: $('input[name="token"]').val(),
			},
			dataType: "json",
			success: function (response) {
				if (response.fee !== undefined) {
					$('input[name="fee"]').val(response.fee);
				}
			},
		});
	}

	// on change
	$("#brand_id").on("change", function () {
		const brandId = $(this).val();
		if (brandId) {
			updateRate(brandId);
		}
	});
});
