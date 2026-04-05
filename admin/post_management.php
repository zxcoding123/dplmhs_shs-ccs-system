<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['STATUS'] = "ADMIN_NOT_LOGGED_IN";
    header("Location: admin_login_page.php");
}

include('processes/server/conn.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AdNU - CCS | Post Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/app.js"></script>

    <script>
        $(document).ready(function() {
            $('#postsTable').DataTable({
                responsive: true,
                dom: '<"top"lf>rt<"bottom"ip>',
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ]
            });
        });
    </script>

    <style>
        table.dataTable {
            font-size: 12px;
        }

        td {
            text-align: center;
            vertical-align: middle;
        }

        .btn-csms {
            background-color: #10177a;
            color: white;
        }

        .btn-csms:hover {
            border: 1px solid #10177a;
            color: #10177a;
        }

        .post-content {
           
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tox-tinymce {
            border-radius: 0.375rem !important;
            border: 1px solid #dee2e6 !important;
        }
    </style>

    <style>
        .responsive-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1em 0;
        }

        .responsive-table th.text-center,
        .responsive-table td.text-center {
            text-align: center;
        }

        .responsive-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #ddd;
        }

        .responsive-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1em;
        }

        .responsive-table {
            min-width: 800px;
        }

        .responsive-table td .btn {
            margin: 2px;
        }

        @media screen and (max-width: 768px) {

            .responsive-table th,
            .responsive-table td {
                font-size: 14px;
            }

            .responsive-table td .btn {
                padding: 4px 8px;
                font-size: 12px;
            }

            @media screen and (max-width: 480px) {
                .responsive-table td .btn {
                    display: block;
                    width: 100%;
                    margin: 2px 0;
                }
            }
        }

        /* Optional scrolling for long content */
        .modal .post-content {
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Featured image styling */
        .modal .modal-body>img.img-fluid {
            max-height: 50vh;
            object-fit:contain;
            width: 100%;
        }
    </style>


</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php') ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="external/img/ADNU_Logo.png" class="logo-small">
                <span class="text-white"><b>AdNU</b> - Post Management System</span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h5 class="h5 mb-3"><a href="index.php" class="nav-ham-link">Home</a> /
                                            <span>Post Management</span>
                                        </h5>

                                        <div class="ms-auto" aria-hidden="true">
                                            <img src="external/svgs/undraw_favorite_gb6n.svg"
                                                class="small-picture img-fluid">
                                        </div>
                                    </div>

                                    <br>

                                    <h5 class="card-title mb-0">
                                        <div class="d-flex align-items-center">
                                            <h3>Post List</h3>
                                            <div class="ms-auto" aria-hidden="true">
                                                <button type="button" class="btn btn-csms" data-bs-toggle="modal"
                                                    data-bs-target="#addPostModal"><i
                                                        class="bi bi-plus-circle"></i>
                                                    Create New Post</button>
                                            </div>
                                        </div>
                                    </h5>
                                </div>
                                <div class="card-body">

                                    <?php
                                    // Query to select posts with author information
                                    $sql = "SELECT p.id, p.title, p.content, p.category, p.status, 
                                                   p.created_at, p.updated_at, 
                                                   CONCAT(a.first_name, ' ', a.last_name) AS author_name
                                            FROM posts p
                                            JOIN admin a ON p.author_id = a.id
                                            ORDER BY p.created_at DESC";
                                    $stmt = $pdo->query($sql);

                                    if ($stmt->rowCount() > 0) {
                                        echo '
                                        <div class="table-wrapper">
                        	<table id="postsTable" class="responsive" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                   <th>Title</th>
                                                   <th>Content</th>
                                                   <th class="text-center">Category</th>
                                                   <th class="text-center">Author</th>
                                                   <th class="text-center">Status</th>
                                                   <th class="text-center">Created At</th>
                                                   <th class="text-center">Updated At</th>
                                                   <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tfoot class="text-center">
                                                <tr>
                                                   <th>Title</th>
                                                   <th>Content</th>
                                                   <th>Category</th>
                                                   <th>Author</th>
                                                   <th>Status</th>
                                                   <th>Created At</th>
                                                   <th>Updated At</th>
                                                   <th>Actions</th>
                                                </tr>
                                            </tfoot>
                                            <tbody>';

                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // Set text and class for publish/unpublish button
                                            $statusText = $row['status'] == 'Published' ? 'Unpublish' : 'Publish';
                                            $statusBtnClass = $row['status'] == 'Published' ? 'btn-warning' : 'btn-success';

                                            // Format dates
                                            $createdAt = date('M d, Y h:i A', strtotime($row['created_at']));
                                            $updatedAt = $row['updated_at'] ? date('M d, Y h:i A', strtotime($row['updated_at'])) : 'Not updated';

                                            echo '<tr>
                                                <td>' . htmlspecialchars($row['title']) . '</td>
                                                <td class="post-content" title="' . htmlspecialchars(strip_tags($row['content'])) . '">' . htmlspecialchars(strip_tags($row['content'])) . '</td>
                                                <td>' . htmlspecialchars($row['category']) . '</td>
                                                <td>' . htmlspecialchars($row['author_name']) . '</td>
                                                <td><span class="badge ' . ($row['status'] == 'Published' ? 'bg-success' : 'bg-secondary') . '">' . htmlspecialchars($row['status']) . '</span></td>
                                                <td>' . $createdAt . '</td>
                                                <td>' . $updatedAt . '</td>
                                                <td>
                                                    <button type="button" data-bs-toggle="modal" data-bs-target="#viewModal' . $row['id'] . '" class="btn btn-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <button type="button" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id'] . '" class="btn btn-info">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <button type="button" onclick="deleteModal(' . $row['id'] . ')" class="btn btn-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                    <button type="button" onclick="changeStatus(' . $row['id'] . ', \'' . $statusText . '\')" class="btn ' . $statusBtnClass . '">
                                                        <i class="bi bi-megaphone"></i> ' . $statusText . '
                                                    </button>
                                                </td>
                                            </tr>';
                                        }

                                        echo '</tbody></table>';
                                    } else {
                                        echo '<h1 class="text-center">No posts created yet.</h1>';
                                    }
                                    echo "</div>";
                                    ?>

                                    <script>
                                        function changeStatus(postId, currentAction) {
                                            const newStatus = currentAction === 'Publish' ? 'Published' : 'Draft';

                                            console.log(`Changing status for post ${postId} to ${newStatus}`);

                                            var xhr = new XMLHttpRequest();
                                            xhr.open('POST', 'processes/admin/posts/change.php', true);
                                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                                            xhr.onload = function() {
                                                if (xhr.status === 200) {
                                                    console.log("Status update response:", xhr.responseText);
                                                    location.reload();
                                                } else {
                                                    console.error("Error updating status:", xhr.status, xhr.statusText);
                                                }
                                            };

                                            xhr.onerror = function() {
                                                console.error("Request failed");
                                            };

                                            xhr.send('post_id=' + postId + '&new_status=' + newStatus);
                                        }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/app.js"></script>

    <!-- Add Post Modal -->
    <div class="modal fade" id="addPostModal" tabindex="-1" aria-labelledby="addPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addPostModalLabel"><b>Create New Post</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="processes/admin/posts/add.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="postTitle" class="form-label bold">Title</label>
                            <input type="text" class="form-control" id="postTitle" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="postContent" class="form-label bold">Content</label>
                            <textarea name="content" id="editor" cols="30" rows="10"></textarea>
                        </div>

                        <script>
                            ClassicEditor
                                .create(document.querySelector('#editor'))
                                .catch(error => {
                                    console.error(error);
                                });
                        </script>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="postCategory" class="form-label bold">Category</label>
                                <select class="form-control" name="category" id="postCategory" required>
                                    <option value="">Select a category</option>
                                    <option value="Announcement">Announcement</option>
                                    <option value="News">News</option>
                                    <option value="Event">Event</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="postStatus" class="form-label bold">Status</label>
                                <select class="form-control" name="status" id="postStatus">
                                    <option value="Draft">Draft</option>
                                    <option value="Published">Publish Immediately</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="postImage" class="form-label bold">Featured Image (Optional)</label>
                            <input type="file" class="form-control" id="postImage" name="featured_image" accept="image/*">
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Edit and View Modals for each post
    $sql = "SELECT p.*, CONCAT(a.first_name, ' ', a.last_name) AS author_name 
            FROM posts p 
            JOIN admin a ON p.author_id = a.id";
    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $createdAt = date('M d, Y h:i A', strtotime($row['created_at']));
        $updatedAt = $row['updated_at'] ? date('M d, Y h:i A', strtotime($row['updated_at'])) : 'Not updated';

        // Edit Modal
        echo '
<div class="modal fade" id="editModal' . $row['id'] . '" tabindex="-1" aria-labelledby="editModalLabel' . $row['id'] . '" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editModalLabel' . $row['id'] . '"><b>Editing Post: ' . htmlspecialchars($row['title']) . '</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="processes/admin/posts/edit.php?id=' . $row['id'] . '" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="editTitle' . $row['id'] . '" class="form-label bold">Title</label>
                        <input type="text" class="form-control" id="editTitle' . $row['id'] . '" name="title" value="' . htmlspecialchars($row['title']) . '" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContent' . $row['id'] . '" class="form-label bold">Content</label>
                        <textarea class="form-control" id="editor-' . $row['id'] . '" name="content" rows="6" required>' . htmlspecialchars($row['content']) . '</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editCategory' . $row['id'] . '" class="form-label bold">Category</label>
                            <select class="form-control" name="category" id="editCategory' . $row['id'] . '" required>
                                <option value="Announcement"' . ($row['category'] == 'Announcement' ? ' selected' : '') . '>Announcement</option>
                                <option value="News"' . ($row['category'] == 'News' ? ' selected' : '') . '>News</option>
                                <option value="Event"' . ($row['category'] == 'Event' ? ' selected' : '') . '>Event</option>
                                <option value="Academic"' . ($row['category'] == 'Academic' ? ' selected' : '') . '>Academic</option>
                                <option value="Other"' . ($row['category'] == 'Other' ? ' selected' : '') . '>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="editStatus' . $row['id'] . '" class="form-label bold">Status</label>
                            <select class="form-control" name="status" id="editStatus' . $row['id'] . '">
                                <option value="Draft"' . ($row['status'] == 'Draft' ? ' selected' : '') . '>Draft</option>
                                <option value="Published"' . ($row['status'] == 'Published' ? ' selected' : '') . '>Published</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editImage' . $row['id'] . '" class="form-label bold">Featured Image</label>';

        if ($row['featured_image']) {
            echo '<div class="mb-2">
            <img src="uploads/posts/' . htmlspecialchars($row['featured_image']) . '" class="img-thumbnail" style="max-height: 150px;">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage' . $row['id'] . '">
                <label class="form-check-label" for="removeImage' . $row['id'] . '">
                    Remove current image
                </label>
            </div>
        </div>';
        }

        echo '<input type="file" class="form-control" id="editImage' . $row['id'] . '" name="featured_image" accept="image/*">
        <small class="text-muted">Leave blank to keep current image</small>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
</div>
</div>
</div>
</div>

<script>
// Initialize CKEditor for this modal
document.addEventListener("DOMContentLoaded", function() {
    ClassicEditor
        .create(document.querySelector("#editor-' . $row['id'] . '"), {
            toolbar: {
                items: [
                    "heading", "|",
                    "bold", "italic", "link", "|",
                    "bulletedList", "numberedList", "|",
                    "blockQuote", "insertTable", "|",
                    "undo", "redo"
                ]
            }
        })
        .catch(error => {
            console.error(error);
        });
});
</script>';

        // View Modal
        echo '
        <div class="modal fade" id="viewModal' . $row['id'] . '" tabindex="-1" aria-labelledby="viewModalLabel' . $row['id'] . '" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="viewModalLabel' . $row['id'] . '"><b>Viewing Post: ' . htmlspecialchars($row['title']) . '</b></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h3>' . htmlspecialchars($row['title']) . '</h3>
                            <div class="d-flex justify-content-between text-muted mb-3">
                                <span>By: ' . htmlspecialchars($row['author_name']) . '</span>
                                <span>Category: ' . htmlspecialchars($row['category']) . '</span>
                                <span>Status: <span class="badge ' . ($row['status'] == 'Published' ? 'bg-success' : 'bg-secondary') . '">' . htmlspecialchars($row['status']) . '</span></span>
                            </div>
                            <hr>';
        echo '<div class="post-content">' . $row['content'] . '</div>
                        </div>
                        
                        <div class="row text-muted mt-3">
                            <div class="col-md-6">
                                <small>Created: ' . $createdAt . '</small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small>Last Updated: ' . $updatedAt . '</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>';
    }
    ?>

</body>



<script>
    function getTime() {
        const now = new Date();
        const newTime = now.toLocaleString();

        document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
    }
    setInterval(getTime, 100);

    function deleteModal(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "processes/admin/posts/delete.php?id=" + id;
            }
        });
    }
</script>

<?php
if (isset($_SESSION['STATUS'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {";

    switch ($_SESSION['STATUS']) {
        case 'POST_ADDITION_SUCCESS':
            echo "Swal.fire('Success!', 'Post has been added successfully.', 'success');";
            break;

        case 'POST_DELETION_SUCCESS':
            echo "Swal.fire('Deleted!', 'Post has been deleted successfully.', 'success');";
            break;

        case 'POST_DELETION_ERROR':
            echo "Swal.fire('Error!', 'Failed to delete the post. Please try again.', 'error');";
            break;

        case 'POST_UPDATED_SUCCESS':
            echo "Swal.fire('Updated!', 'Post has been updated successfully.', 'success');";
            break;

        case 'POST_UPDATED_ERROR':
            echo "Swal.fire('Error!', 'Failed to update the post. Please try again.', 'error');";
            break;

        case 'POST_SWITCH_STATUS_SUCCESS':
            echo "Swal.fire('Success!', 'The status of the post has been succesfully updated!', 'success');";
            break;

        case 'POST_SWITCH_STATUS_ERROR':
            echo "Swal.fire('Error!', 'Failed to switch the status of the post. Please try again.', 'error');";
            break;
    }

    echo "});
    </script>";
    unset($_SESSION['STATUS']);
}
?>

</html>
<?php include('processes/server/alerts.php') ?>