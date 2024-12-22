<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Society Management</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.20.0/standard/ckeditor.js"></script>
    <style>
        .column-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .column-row input,
        .column-row select {
            margin-right: 10px;
            flex: 1;
        }
        .column-row input[type="number"] {
            width: 80px;
        }
    </style>
</head>
<body>
    <h1>Society Management</h1>

    <!-- Create Table Form -->
    <h2>Create Table</h2>
    <form id="createTableForm">
        <label>Society Name:</label>
        <input type="text" name="societyName" id="societyname1" required>
        <label>Number of Columns:</label>
        <input type="number" id="columnCount" min="1" placeholder="Enter number of columns" required>
        <button type="button" id="generateColumns">Generate Columns</button>
        <div id="columns"></div>
        <button type="submit">Create Table</button>
    </form>
    <div id="createTableResponse"></div>

    <!-- Send Email Form -->
    <h2>Send Email</h2>
    <form id="sendMailForm">
    <input type="text" name="societyName1" id="societyName" placeholder="Enter society name" style="display:none;" required>
        <label>Subject:</label>
        <input type="text" name="subject" required>
        <label>Body:</label>
        <textarea name="body" id="emailBody" required></textarea>
        <button type="submit">Send Email</button>
    </form>
    <div id="sendMailResponse"></div>

    <!-- Upload CSV Form -->
    <h2>Upload CSV</h2>
    <form id="uploadCSVForm" enctype="multipart/form-data">
    <label>Table name:</label>
    <input type="text" name="societyName1" id="societyName" placeholder="Enter society name" style="display:none;" required>
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload</button>
    </form>
    <div id="uploadCSVResponse"></div>

    <script>
        // Initialize Text Editor for Email Body
        CKEDITOR.replace('emailBody');

        // Generate Columns Based on Number Input
        $('#generateColumns').on('click', function () {
            const columnCount = parseInt($('#columnCount').val(), 10);
            const $columns = $('#columns');

            if (isNaN(columnCount) || columnCount < 1) {
                alert('Please enter a valid number of columns.');
                return;
            }

            $columns.empty();
            for (let i = 0; i < columnCount; i++) {
                $columns.append(`
                    <div class="column-row">
                        <input type="text" name="columns[${i}][name]" placeholder="Column Name" required>
                        <select name="columns[${i}][type]" required>
                            <option value="INT">INT</option>
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="TEXT">TEXT</option>
                            <option value="DATE">DATE</option>
                            <option value="FLOAT">FLOAT</option>
                            <option value="DOUBLE">DOUBLE</option>
                            <option value="BOOLEAN">BOOLEAN</option>
                        </select>
                        <input type="number" name="columns[${i}][length]" placeholder="Length" min="1">
                    </div>
                `);
            }
        });

        // AJAX Form Handlers
        function handleFormSubmission(formSelector, endpoint, responseSelector) {
            $(formSelector).on('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(this);

                // Handle CKEditor content
                if (formSelector === '#sendMailForm') {
                    formData.set('body', CKEDITOR.instances['emailBody'].getData());
                }

                $.ajax({
                    url: endpoint,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $(responseSelector).html(
                            `<p style="color: ${response.success ? 'green' : 'red'};">${response.message}</p>`
                        );
                    },
                    error: function () {
                        $(responseSelector).html('<p style="color: red;">An error occurred. Please try again.</p>');
                    },
                });
            });
        }

        // Attach Handlers
        handleFormSubmission('#createTableForm', 'create_table.php', '#createTableResponse');
        handleFormSubmission('#sendMailForm', 'send_mail.php', '#sendMailResponse');
        handleFormSubmission('#uploadCSVForm', 'upload_csv.php', '#uploadCSVResponse');
    </script>
    <script>
    function syncSocietyNameFields() {
        const field1 = document.getElementById('societyname1');
        const field2 = document.getElementById('societyName');

        // Synchronize field2 when field1 changes
        field1.addEventListener('input', function () {
            field2.value = this.value;
        });

        // Synchronize field1 when field2 changes
        field2.addEventListener('input', function () {
            field1.value = this.value;
        });
    }

    // Initialize synchronization when the page loads
    document.addEventListener('DOMContentLoaded', syncSocietyNameFields);
</script>

</body>
</html>
