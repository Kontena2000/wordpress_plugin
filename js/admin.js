jQuery(document).ready(function($) {
    // Progress bar functionality
    var progressBar = $("<div>").addClass("export-progress-bar");
    var progressText = $("<div>").addClass("export-progress-text");
    
    $("#run-export").after(progressBar, progressText);
    
    function updateProgress() {
        $.ajax({
            url: wpContentToVector.ajax_url,
            type: "POST",
            data: {
                action: "get_export_progress",
                nonce: wpContentToVector.nonce
            },
            success: function(response) {
                if (response.status !== "idle") {
                    var percent = Math.round((response.processed / response.total) * 100) || 0;
                    progressBar.css("width", percent + "%");
                    progressText.text(
                        "Processing: " + response.processed + "/" + response.total + 
                        " (Batch " + response.current_batch + "/" + response.total_batches + ")"
                    );
                    
                    if (response.status !== "completed" && response.status !== "error") {
                        setTimeout(updateProgress, 1000);
                    }
                }
            }
        });
    }
    
    // Statistics page charts
    if ($("#dailyExportsChart").length) {
        new Chart($("#dailyExportsChart"), {
            type: 'line',
            data: dailyExportsData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    if ($("#contentTypeChart").length) {
        new Chart($("#contentTypeChart"), {
            type: 'pie',
            data: contentTypeData,
            options: {
                responsive: true
            }
        });
    }
    
    // Schedule settings
    $("#schedule-type").on("change", function() {
        if ($(this).val() === "custom") {
            $("#custom-schedule-time").show();
        } else {
            $("#custom-schedule-time").hide();
        }
    });
});
