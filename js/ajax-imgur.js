jQuery(document).ready(function ($) {
	$("#imgur-upload-button").on("click", function (e) {
		e.preventDefault();
		var file = $("#file-input").get(0).files[0]; // Assuming you have an <input type="file" id="file-input">

		var formData = new FormData();
		formData.append("image", file);

		$.ajax({
			url: ajaxurl,
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				// Handle success, display Imgur URL or insert into post content
			},
			error: function (response) {
				// Handle error
			},
		});
	});
});
