<?php
// ==============================================================
// admin/movies.php - Movie Catalog Management: View, Add, Edit, Delete
// ==============================================================

$admin_page_title = "Manage Movies - CineBook Admin";
require_once __DIR__ . '/includes/header.php';

$message = "";
$error   = "";

// Correct upload directory path
$upload_dir     = __DIR__ . '/../uploads/posters/';
$upload_web_dir = '../uploads/posters/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. Handle Movie DELETION
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = mysqli_prepare($conn, "SELECT poster FROM movies WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($m = mysqli_fetch_assoc($res)) {
        if (!empty($m['poster']) && $m['poster'] !== 'default_poster.jpg' && file_exists($upload_dir . $m['poster'])) {
            unlink($upload_dir . $m['poster']);
        }
    }
    mysqli_stmt_close($stmt);
    $del_stmt = mysqli_prepare($conn, "DELETE FROM movies WHERE id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $delete_id);
    if (mysqli_stmt_execute($del_stmt)) { $message = "Movie deleted successfully."; }
    else { $error = "Failed to delete movie: " . mysqli_error($conn); }
    mysqli_stmt_close($del_stmt);
}

// 2. Handle ADD Movie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $title        = trim($_POST['title'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $language     = trim($_POST['language'] ?? '');
    $duration     = intval($_POST['duration'] ?? 0);
    $release_date = trim($_POST['release_date'] ?? '');
    $status       = trim($_POST['status'] ?? 'now_showing');
    $description  = trim($_POST['description'] ?? '');

    if (empty($title) || empty($genre) || empty($language) || $duration <= 0 || empty($release_date)) {
        $error = "Please fill in all required movie fields.";
    } else {
        $poster_filename = '';
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg','jpeg','png','webp'])) {
                $new_filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $upload_dir . $new_filename)) {
                    $poster_filename = $new_filename;
                } else { $error = "Failed to save uploaded file. Check folder permissions."; }
            } else { $error = "Invalid file type. Only JPG, PNG, and WebP allowed."; }
        }
        if (empty($error)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO movies (title, genre, language, duration, release_date, poster, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssissss", $title, $genre, $language, $duration, $release_date, $poster_filename, $description, $status);
            if (mysqli_stmt_execute($stmt)) { $message = "Movie added successfully!"; }
            else { $error = "Failed to add movie: " . mysqli_error($conn); }
            mysqli_stmt_close($stmt);
        }
    }
}

// 3. Handle EDIT Movie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_movie'])) {
    $edit_id      = intval($_POST['edit_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $language     = trim($_POST['language'] ?? '');
    $duration     = intval($_POST['duration'] ?? 0);
    $release_date = trim($_POST['release_date'] ?? '');
    $status       = trim($_POST['status'] ?? 'now_showing');
    $description  = trim($_POST['description'] ?? '');
    $old_poster   = trim($_POST['old_poster'] ?? '');

    if (empty($title) || empty($genre) || empty($language) || $duration <= 0 || empty($release_date) || $edit_id <= 0) {
        $error = "Please fill in all required fields for editing.";
    } else {
        $poster_filename = $old_poster;
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg','jpeg','png','webp'])) {
                $new_filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $upload_dir . $new_filename)) {
                    if (!empty($old_poster) && file_exists($upload_dir . $old_poster)) {
                        unlink($upload_dir . $old_poster);
                    }
                    $poster_filename = $new_filename;
                } else { $error = "Failed to save new poster. Check folder permissions."; }
            } else { $error = "Invalid file type. Only JPG, PNG, and WebP allowed."; }
        }
        if (empty($error)) {
            $stmt = mysqli_prepare($conn, "UPDATE movies SET title=?, genre=?, language=?, duration=?, release_date=?, poster=?, description=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssissssi", $title, $genre, $language, $duration, $release_date, $poster_filename, $description, $status, $edit_id);
            if (mysqli_stmt_execute($stmt)) { $message = "Movie updated successfully!"; }
            else { $error = "Failed to update movie: " . mysqli_error($conn); }
            mysqli_stmt_close($stmt);
        }
    }
}

// 4. Fetch all movies
$movies_result = mysqli_query($conn, "SELECT * FROM movies ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="font-weight-bold text-light mb-1"><i class="fas fa-film text-warning mr-2"></i>Movies Catalog</h3>
        <p class="text-muted small mb-0">Add, edit, or remove movies from the listings</p>
    </div>
    <button type="button" class="btn btn-warning font-weight-bold" data-toggle="modal" data-target="#addMovieModal">
        <i class="fas fa-plus mr-1"></i> Add New Movie
    </button>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
        <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px;">POSTER</th>
                    <th>TITLE &amp; GENRE</th>
                    <th>LANG &amp; DURATION</th>
                    <th>RELEASE DATE</th>
                    <th>STATUS</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($movies_result) > 0): ?>
                    <?php while ($m = mysqli_fetch_assoc($movies_result)):
                        $poster_src = (!empty($m['poster']) && file_exists($upload_dir . $m['poster']))
                            ? $upload_web_dir . $m['poster']
                            : 'https://placehold.co/100x140/1e293b/f8fafc?text=No+Poster';
                        $poster_url_js = (!empty($m['poster']) && file_exists($upload_dir . $m['poster']))
                            ? addslashes($upload_web_dir . $m['poster']) : '';
                    ?>
                    <tr>
                        <td class="align-middle">
                            <img src="<?php echo htmlspecialchars($poster_src); ?>" alt="Poster"
                                 style="width:50px;height:70px;object-fit:cover;border-radius:4px;border:1px solid #444;">
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-light"><?php echo htmlspecialchars($m['title']); ?></div>
                            <span class="badge badge-info small"><?php echo htmlspecialchars($m['genre']); ?></span>
                        </td>
                        <td class="align-middle small text-muted">
                            <div><i class="fas fa-globe mr-1"></i><?php echo htmlspecialchars($m['language']); ?></div>
                            <div><i class="far fa-clock mr-1"></i><?php echo (int)$m['duration']; ?> mins</div>
                        </td>
                        <td class="align-middle small"><?php echo date('d M Y', strtotime($m['release_date'])); ?></td>
                        <td class="align-middle">
                            <?php if ($m['status'] === 'now_showing'): ?>
                                <span class="badge badge-success px-2 py-1">Now Showing</span>
                            <?php elseif ($m['status'] === 'coming_soon'): ?>
                                <span class="badge badge-warning text-dark px-2 py-1">Coming Soon</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Ended</span>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle text-right">
                            <button type="button" class="btn btn-outline-warning btn-sm mr-1"
                                onclick="openEditModal(<?php echo $m['id']; ?>,'<?php echo addslashes($m['title']); ?>','<?php echo addslashes($m['genre']); ?>','<?php echo addslashes($m['language']); ?>',<?php echo (int)$m['duration']; ?>,'<?php echo $m['release_date']; ?>','<?php echo $m['status']; ?>','<?php echo addslashes(htmlspecialchars_decode($m['description'])); ?>','<?php echo addslashes($m['poster']); ?>','<?php echo $poster_url_js; ?>')">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <a href="movies.php?delete_id=<?php echo $m['id']; ?>"
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Delete \'<?php echo addslashes($m['title']); ?>\'?');">
                                <i class="fas fa-trash-alt mr-1"></i>Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-film fa-2x mb-2 d-block"></i>No movies added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD Movie Modal -->
<div class="modal fade" id="addMovieModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content bg-dark text-light border border-secondary">
        <div class="modal-header border-secondary">
            <h5 class="modal-title font-weight-bold text-warning"><i class="fas fa-plus-circle mr-2"></i>Add New Movie</h5>
            <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
        </div>
        <form action="movies.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_movie" value="1">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label class="small text-muted font-weight-bold">MOVIE TITLE *</label>
                        <input type="text" name="title" class="form-control bg-secondary border-0 text-light" placeholder="e.g. Oppenheimer" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">GENRE *</label>
                        <input type="text" name="genre" class="form-control bg-secondary border-0 text-light" placeholder="e.g. Action" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">LANGUAGE *</label>
                        <input type="text" name="language" class="form-control bg-secondary border-0 text-light" placeholder="e.g. English" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">DURATION (MINS) *</label>
                        <input type="number" name="duration" class="form-control bg-secondary border-0 text-light" placeholder="e.g. 148" min="1" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">RELEASE DATE *</label>
                        <input type="date" name="release_date" class="form-control bg-secondary border-0 text-light" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="small text-muted font-weight-bold">STATUS</label>
                        <select name="status" class="custom-select bg-secondary border-0 text-light">
                            <option value="now_showing" selected>Now Showing</option>
                            <option value="coming_soon">Coming Soon</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="small text-muted font-weight-bold">POSTER IMAGE (JPG/PNG/WEBP)</label>
                        <div class="d-flex align-items-center">
                            <div id="add-poster-preview" class="mr-3" style="display:none;">
                                <img id="add-poster-img" src="" alt="Preview" style="width:60px;height:84px;object-fit:cover;border-radius:4px;border:2px solid #f59e0b;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="poster" id="add_poster_input" class="form-control-file text-muted" accept=".jpg,.jpeg,.png,.webp" onchange="previewPoster(this,'add-poster-preview','add-poster-img')">
                                <small class="text-muted">Leave empty to use a placeholder.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="small text-muted font-weight-bold">SYNOPSIS / DESCRIPTION</label>
                    <textarea name="description" class="form-control bg-secondary border-0 text-light" rows="3" placeholder="Brief movie plot summary..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i>Save Movie</button>
            </div>
        </form>
    </div></div>
</div>

<!-- EDIT Movie Modal -->
<div class="modal fade" id="editMovieModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content bg-dark text-light border border-warning">
        <div class="modal-header border-secondary">
            <h5 class="modal-title font-weight-bold text-warning"><i class="fas fa-edit mr-2"></i>Edit Movie</h5>
            <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
        </div>
        <form action="movies.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_movie" value="1">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="old_poster" id="edit_old_poster">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label class="small text-muted font-weight-bold">MOVIE TITLE *</label>
                        <input type="text" name="title" id="edit_title" class="form-control bg-secondary border-0 text-light" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">GENRE *</label>
                        <input type="text" name="genre" id="edit_genre" class="form-control bg-secondary border-0 text-light" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">LANGUAGE *</label>
                        <input type="text" name="language" id="edit_language" class="form-control bg-secondary border-0 text-light" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">DURATION (MINS) *</label>
                        <input type="number" name="duration" id="edit_duration" class="form-control bg-secondary border-0 text-light" min="1" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small text-muted font-weight-bold">RELEASE DATE *</label>
                        <input type="date" name="release_date" id="edit_release_date" class="form-control bg-secondary border-0 text-light" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="small text-muted font-weight-bold">STATUS</label>
                        <select name="status" id="edit_status" class="custom-select bg-secondary border-0 text-light">
                            <option value="now_showing">Now Showing</option>
                            <option value="coming_soon">Coming Soon</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="small text-muted font-weight-bold">CHANGE POSTER IMAGE</label>
                        <div class="d-flex align-items-center">
                            <div id="edit-poster-preview" class="mr-3">
                                <img id="edit-poster-img" src="" alt="Current Poster" style="width:60px;height:84px;object-fit:cover;border-radius:4px;border:2px solid #6c757d;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="poster" id="edit_poster_input" class="form-control-file text-muted" accept=".jpg,.jpeg,.png,.webp" onchange="previewPoster(this,'edit-poster-preview','edit-poster-img')">
                                <small class="text-muted">Leave empty to keep current poster.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="small text-muted font-weight-bold">SYNOPSIS / DESCRIPTION</label>
                    <textarea name="description" id="edit_description" class="form-control bg-secondary border-0 text-light" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i>Update Movie</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function openEditModal(id,title,genre,language,duration,release_date,status,description,poster,posterUrl) {
    document.getElementById('edit_id').value           = id;
    document.getElementById('edit_title').value        = title;
    document.getElementById('edit_genre').value        = genre;
    document.getElementById('edit_language').value     = language;
    document.getElementById('edit_duration').value     = duration;
    document.getElementById('edit_release_date').value = release_date;
    document.getElementById('edit_description').value  = description;
    document.getElementById('edit_old_poster').value   = poster;
    var sel = document.getElementById('edit_status');
    for (var i=0;i<sel.options.length;i++) { sel.options[i].selected = (sel.options[i].value === status); }
    var img = document.getElementById('edit-poster-img');
    img.src = posterUrl ? posterUrl : 'https://placehold.co/60x84/1e293b/f8fafc?text=No+Poster';
    img.style.border = posterUrl ? '2px solid #f59e0b' : '2px solid #6c757d';
    document.getElementById('edit_poster_input').value = '';
    $('#editMovieModal').modal('show');
}
function previewPoster(input,previewDivId,imgId) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById(imgId);
        img.src = e.target.result;
        img.style.border = '2px solid #f59e0b';
        document.getElementById(previewDivId).style.display = 'block';
    };
    reader.readAsDataURL(file);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
