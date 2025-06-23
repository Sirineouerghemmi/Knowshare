<?php 
include("../config/database.php");
include("../controller/tr.php");

session_start();
$currentSection = $_GET['section'] ?? $_POST['section'] ?? 'dashboard';
if (isset($_POST['add_category'])) {
  $categoryName = trim($_POST['category_name']);
  $section = $_POST['section'] ?? 'documents'; 
  if (!empty($categoryName)) {
      $stmt = $cnx->prepare("INSERT INTO category (nom) VALUES (:nom)");
      if ($stmt->execute(['nom' => $categoryName])) {
          $notification = ['message' => 'Category added successfully.', 'type' => 'success', 'section' => 'documents'];
      } else {
          $notification = ['message' => 'Error adding category.', 'type' => 'error', 'section' => 'documents'];
          header("Location: dashboard.php?section=documents&showCategoryModal=true#documents");
          exit;
      }
  } else {
      $notification = ['message' => 'Category name cannot be empty.', 'type' => 'error', 'section' => 'documents'];
      header("Location: dashboard.php?section=documents&showCategoryModal=true#documents");
      exit;
  }
  header("Location: dashboard.php?section=documents#documents");
  exit;
}

// Handle Update Document Popularity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["doc_id"])) {
    $doc_id = $_POST["doc_id"];
    $is_popular = isset($_POST["is_popular"]) ? 1 : 0;
    $section = $_POST['section'] ?? 'documents';

    try {
        $stmt = $cnx->prepare("UPDATE document SET is_popular = ? WHERE id = ?");
        if ($stmt->execute([$is_popular, $doc_id])) {
            $notification = ['message' => 'Document updated successfully.', 'type' => 'success', 'section' => 'documents'];
        } else {
            $notification = ['message' => 'Error updating document.', 'type' => 'error', 'section' => 'documents'];
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $notification = ['message' => 'Database error.', 'type' => 'error', 'section' => 'documents'];
    }
    header(header: "location:dashboard.php#documents");

}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = (int) $_POST['user_id'];
    $section = $_POST['section'] ?? 'users';

    if (deleteUser($user_id, $cnx)) {
        $notification = ['message' => 'User deleted successfully.', 'type' => 'success', 'section' => 'users'];
    } else {
        $notification = ['message' => 'Error deleting user.', 'type' => 'error', 'section' => 'users'];
    }
    header(header: "location:dashboard.php#users");

}

// Handle Delete Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_document') {
    $document_id = (int) $_POST['document_id'];
    $section = $_POST['section'] ?? 'documents';

    if (deleteDocument($document_id, $cnx)) {
        $notification = ['message' => 'Document deleted successfully.', 'type' => 'success', 'section' => 'documents'];
    } else {
        $notification = ['message' => 'Error deleting document.', 'type' => 'error', 'section' => 'documents'];
    }
    header(header: "location:dashboard.php#documents");

}

// Handle Delete Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    $commentId = (int) $_POST['comment_id'];
    $section = $_POST['section'] ?? 'comments';

    if (deleteComment($cnx, $commentId)) {
        $notification = ['message' => 'Comment deleted successfully.', 'type' => 'success', 'section' => 'comments'];
    } else {
        $notification = ['message' => 'Error deleting comment.', 'type' => 'error', 'section' => 'comments'];
    }
    header(header: "location:dashboard.php#comments");

}

// Handle Delete Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_report') {
    $idReport = (int) $_POST['idreport'];
    $section = $_POST['section'] ?? 'reports';

    if (deleteReport($cnx, $idReport)) {
        $notification = ['message' => 'Report deleted successfully.', 'type' => 'success', 'section' => 'reports'];
    } else {
        $notification = ['message' => 'Error deleting report.', 'type' => 'error', 'section' => 'reports'];
    }
    header(header: "location:dashboard.php#reports");

}

// Handle Update Comment Featured Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'])) {
  $comment_id = (int) $_POST['comment_id'];
  $is_featured = isset($_POST['is_featured']) ? 1 : 0;
  $section = $_POST['section'] ?? 'comments';

  try {
      $stmt = $cnx->prepare("UPDATE comments SET is_featured = ? WHERE id = ?");
      if ($stmt->execute([$is_featured, $comment_id])) {
          $notification = ['message' => 'Comment updated successfully.', 'type' => 'success', 'section' => 'comments'];
      } else {
          $notification = ['message' => 'Error updating comment.', 'type' => 'error', 'section' => 'comments'];
      }
  } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $notification = ['message' => 'Database error.', 'type' => 'error', 'section' => 'comments'];
  }
  header(header: "location:dashboard.php#comments");
}

// Fetch Data for Display
if (isset($_GET['search']) && !empty($_GET['search'])) {

    $users = searchUsers($cnx, $_GET['search']);
} else {
    $users = getAllUsers($cnx);

}

$documents = getAllDocuments($cnx);
$previewDocuments = array_slice($documents, 0, 8);
$reports = getAllReports($cnx);
$comments = getAllComments($cnx);
$totalComments = count($comments);

?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .notification {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
   <div class="container">
        <div class="navigation">
            <ul>
                <li>
                  <a href="#"><span class="icon"><img src="../assets/edulogo.jpg" alt=""></span>
                    <span class="title">Knowshare</span>
                    </a>  
                </li>
                <li>
                    <a href="#dashboard"><span class="icon">
                        <i class="fa-solid fa-house"></i></span>
                      <span class="title">Dashboard</span>
                      </a>  
                  </li>
                <li>
                    <a href="#users"><span class="icon">
                        <i class="fa-solid fa-users"></i></span>                     
                         <span class="title">users</span>
                      </a>  
                  </li>
                <li>
                    <a href="#documents"><span class="icon">
                        <i class="fa-solid fa-file"></i></span>
                      <span class="title">documents</span>
                      </a>  
                  </li>
                <li>
                    <a href="#comments"><span class="icon">
                    <i class="fa-solid fa-star-half-stroke"></i></span>
                      <span class="title">comments</span>
                      </a>  
                  </li>
                  <li>
                    <a href="#reports"><span class="icon">
                         <i class="fa-solid fa-flag"></i></span>
                         <span class="title">reports</span>
                      </a>  
                  </li>
                <li>
                    <a href="#signout"><span class="icon">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> </span>
                        <span class="title">signout</span>
                      </a>  
                  </li>
            </ul>
        </div>
   </div>

   <div class="main">
    <div class="topbar">
      <div class="toggle">
        <i class="fa-solid fa-bars"></i>
      </div>
      <div class="search">
        <label for=""></label>
      </div>
    </div>

    <div class="content-sections">
      <div class="content-section dashboard-section" style="display: <?= $currentSection === 'dashboard' ? 'block' : 'none'; ?>;">
        <div class="cardbox">
          <div class="card">
            <div class="content">
              <div class="numbers"><?= count($users) ?></div>
              <div class="cardname">Registered users</div>
            </div>
            <div class="iconbx"><i class="fa-solid fa-users-rectangle"></i></div>
          </div>
          <div class="card">
            <div class="content">
              <div class="numbers"><?= count($documents) ?></div>
              <div class="cardname">shared document</div>
            </div>
            <div class="iconbx"><i class="fa-regular fa-file"></i></div>
          </div>
          <div class="card">
            <div class="content">
              <div class="numbers"><?= $totalComments ?></div>
              <div class="cardname">comments</div>
            </div>
            <div class="iconbx"><i class="fa-solid fa-star-half-stroke"></i></div>
          </div>
        </div>
        <div class="details">
          <div class="shareddocuments">
            <div class="cardheader">
              <h2>shared documents</h2>
              <a href="#documents" class="btn">view all</a>
            </div>
            <table>
              <thead>
                <tr>
                  <td>name</td>
                  <td>user_acc</td>
                  <td>status</td>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($previewDocuments as $doc) {
                    echo '<tr>
                        <td>' . htmlspecialchars($doc['title']) . '</td>
                        <td>' . htmlspecialchars($doc['uploaded_by']) . '</td>
                        <td><span class="status approved">approved</span></td>
                    </tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="popcourses">
            <div class="cardheader">
              <h2>Most popular courses   <i class="fa-solid fa-fire-flame-curved"></i></h2>
            </div>
            <table>
              <thead>
                <tr>
                  <td>Rank</td>
                  <td>Name</td>
                </tr>
              </thead>
              <tbody>
                <?php
                $documents = getAllDocuments($cnx);
                if ($documents && is_array($documents)) {
                    $popularDocuments = array_filter($documents, function($doc) {
                        return $doc['is_popular'] == 1; 
                    });
                    $popularDocuments = array_slice($popularDocuments, 0, 4);
                    $rank = 1;
                    foreach ($popularDocuments as $doc) {
                        echo '<tr>
                            <td>' . $rank++ . '</td>
                            <td>' . htmlspecialchars($doc['title']) . '</td>
                        </tr>';
                    }
                    if (empty($popularDocuments)) {
                        echo '<tr><td colspan="3">No popular documents found</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">No documents available</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Users Section -->
      <div class="content-section users-section" style="display: <?= $currentSection === 'users' ? 'block' : 'none'; ?>;">
        <div class="details">
          <div class="user-management">
            <div class="cardheader">
              <h2>User Management</h2>
            </div>
            <?php if (!empty($notification['message']) && $notification['section'] === 'users'): ?>
                <div class="notification <?= htmlspecialchars($notification['type']) ?>">
                    <?= htmlspecialchars($notification['message']) ?>
                </div>
            <?php endif; ?>
            <form method="GET" action="dashboard.php">
              <input type="hidden" name="section" value="users">
              <div class="input-group mb-3 change">
                <input type="text" class="form-control" placeholder="search user" aria-label="Recipient's username" aria-describedby="button-addon2" name="search" value="<?php if(isset($_GET['search'])) echo htmlspecialchars($_GET['search']); ?>">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2"><i class="fa-solid fa-magnifying-glass"></i></button>
              </div>
            </form>
            <div class="table-container"> 
              <table class="user-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  foreach ($users as $user) {
                      echo '<tr>
                          <td>' . htmlspecialchars($user['id']) . '</td>
                          <td>' . htmlspecialchars($user['nom']) . '</td>
                          <td>' . htmlspecialchars($user['email']) . '</td>
                          <td class="actions">
                          <form method="POST" action="dashboard.php">
                              <input type="hidden" name="action" value="delete_user">
                              <input type="hidden" name="section" value="users">
                              <input type="hidden" name="user_id" value="' . $user['id'] . '">
                              <button type="submit" class="btn-action delete" title="Delete" onclick="return confirm(\'Confirm deleting user\');">
                                  <i class="fas fa-trash"></i>
                              </button>
                          </form>
                      </td>
                      </tr>';
                  }
                  if (empty($users)) {
                      echo '<tr><td colspan="5">No user found</td></tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents Section -->
      <div class="content-section documents-section" style="display: <?= $currentSection === 'documents' ? 'block' : 'none'; ?>;">
        <div class="details">
            <div class="cardheader">
                <h2>Shared Documents</h2>
            </div>
            <?php if (!empty($notification['message']) && $notification['section'] === 'documents'): ?>
                <div class="notification <?= htmlspecialchars($notification['type']) ?>">
                    <?= htmlspecialchars($notification['message']) ?>
                </div>
            <?php endif; ?>
            <a href="dashboard.php?section=documents&showCategoryModal=true#documents" class="btn">Add category</a>
            <!-- Add Category Modal -->
            <?php if (isset($_GET['showCategoryModal']) && $_GET['showCategoryModal'] === 'true'): ?>
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;">
                    <div style="background: white; padding: 20px; border-radius: 5px; width: 400px; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
                        <h3>Add New Category</h3>
                        <form method="POST" action="dashboard.php">
                            <input type="hidden" name="section" value="documents">
                            <div style="margin-bottom: 15px;">
                                <label for="category_name" style="display: block; margin-bottom: 5px;">Category Name</label>
                                <input type="text" id="category_name" name="category_name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                            </div>
                            <div style="text-align: right;">
                                <input type="hidden" name="add_category" value="1">
                                <button type="submit" style="background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Submit</button>
                                <a href="dashboard.php?section=documents" style="background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; margin-left: 10px;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <td>Document Name</td>
                        <td>Uploaded By</td>
                        <td>Category</td>
                        <td>Upload Date</td>
                        <td>Actions</td>
                        <td>Popular</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($documents as $doc) {
                        echo '<tr>
                            <td>' . htmlspecialchars($doc['title']) . '</td>
                            <td>' . htmlspecialchars($doc['uploaded_by']) . '</td>
                            <td>' . htmlspecialchars($doc['category_name']) . '</td>
                            <td>' . htmlspecialchars($doc['date']) . '</td>
                            <td class="actions">
                                <form method="POST" action="dashboard.php">
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="section" value="documents">
                                    <input type="hidden" name="document_id" value="' . $doc['id'] . '">
                                    <button type="submit" class="btn-action delete" title="Delete" onclick="return confirm(\'Confirm deleting document\');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="dashboard.php">
                                    <input type="hidden" name="section" value="documents">
                                    <input type="hidden" name="doc_id" value="' . $doc['id'] . '">
                                    <input type="checkbox" name="is_popular" onchange="this.form.submit()" ' . ($doc['is_popular'] ? 'checked' : '') . '>
                                </form>
                            </td>
                        </tr>';
                    }
                    if (empty($documents)) {
                        echo '<tr><td colspan="5">NO Documents found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

      <!-- Comments Section -->
      <div class="content-section comments-section" style="display: <?= $currentSection === 'comments' ? 'block' : 'none'; ?>;">
        <div class="details">
          <div class="comment-management">
            <div class="cardheader">
              <h2>Comments Management</h2>
            </div>
            <?php if (!empty($notification['message']) && $notification['section'] === 'comments'): ?>
                <div class="notification <?= htmlspecialchars($notification['type']) ?>">
                    <?= htmlspecialchars($notification['message']) ?>
                </div>
            <?php endif; ?>
            <div class="table-container"> 
              <table class="comments-table">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">User</th>
                    <th scope="col">Comment</th>
                    <th scope="col">Date</th>
                    <th scope="col">Featured</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($comments as $comment): ?>
                  <tr>
                    <td><?= htmlspecialchars($comment['id']) ?></td>
                    <td><?= htmlspecialchars($comment['nom']) ?></td>
                    <td><?= htmlspecialchars($comment['content']) ?></td>
                    <td><?= htmlspecialchars($comment['date']) ?></td>
                    <td>
                      <form method="POST" action="dashboard.php">
                        <input type="hidden" name="section" value="comments">
                        <input type="hidden" name="comment_id" value="<?= htmlspecialchars($comment['id']) ?>">
                        <input type="checkbox" name="is_featured" onchange="this.form.submit()" <?= $comment['is_featured'] ? 'checked' : '' ?>>
                      </form>
                    </td>
                    <td class="actions">
                      <form method="POST" action="dashboard.php">
                        <input type="hidden" name="action" value="delete_comment">
                        <input type="hidden" name="section" value="comments">
                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                        <button type="submit" class="btn btn-delete" onclick="return confirm('Confirm deleting this comment?');"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Reports Section -->
      <div class="content-section reports-section" style="display: <?= $currentSection === 'reports' ? 'block' : 'none'; ?>;">
        <div class="details">
          <div class="reports-management">
            <div class="cardheader">
              <h2>Reports</h2>
            </div>
            <?php if (!empty($notification['message']) && $notification['section'] === 'reports'): ?>
                <div class="notification <?= htmlspecialchars($notification['type']) ?>">
                    <?= htmlspecialchars($notification['message']) ?>
                </div>
            <?php endif; ?>
            <div class="table-container"> 
              <table class="reports-table">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Message</th>
                    <th>Reported By</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reports as $report): ?>
                  <tr>
                    <td><?= htmlspecialchars($report['id']) ?></td>
                    <td><?= htmlspecialchars($report['content']) ?></td>
                    <td><?= htmlspecialchars($report['nom']) ?></td>
                    <td><?= htmlspecialchars($report['created_at']) ?></td>
                    <td class="actions">
                      <form method="POST" action="dashboard.php">
                        <input type="hidden" name="action" value="delete_report">
                        <input type="hidden" name="section" value="reports">
                        <input type="hidden" name="idreport" value="<?= $report['id'] ?>">
                        <button type="submit" class="btn btn-delete" onclick="return confirm('Confirm deleting this report?');"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Signout Section -->
      <div class="content-section signout-section" style="display: <?= $currentSection === 'signout' ? 'block' : 'none'; ?>;">
        <div class="details">
          <div class="logout-card">
            <h2>Logout</h2>
            <p>Are you sure you want to log out from your KnowShare account?</p>
            <div class="logout-actions">
              <form action="../controller/logout.php" method="POST">
                <button type="submit" class="btn-logout">Yes, Log Me Out</button>
              </form>
              <button class="btn-cancel" onclick="window.history.back()">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="dashboard.js"></script>
</body>
</html>