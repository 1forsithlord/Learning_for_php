<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'save';

  if ($action === 'delete') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(422);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
      exit;
    }

    $select = mysqli_prepare($conn, 'SELECT id FROM users WHERE id = ? AND is_deleted = 0');
    mysqli_stmt_bind_param($select, 'i', $id);
    mysqli_stmt_execute($select);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($select));
    mysqli_stmt_close($select);

    if (!$user) {
      http_response_code(422);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'User not found.']);
      exit;
    }

    $delete = mysqli_prepare($conn, 'UPDATE users SET is_deleted = 1 WHERE id = ?');
    mysqli_stmt_bind_param($delete, 'i', $id);
    mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
    exit;
  }

  $errors = [];
  //Ternary Operator
  // if id is set and not empty then mode is edit else add
  $mode = (isset($_POST['id']) && $_POST['id'] !== '') ? 'edit' : 'add';
  // Null Coalescing Operator
  $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

  if ($mode === 'edit') {
    if (!$id) {
      $errors[] = 'Invalid user ID.';
    } else {
      $checkStatement = mysqli_prepare($conn, 'SELECT id FROM users WHERE id = ? AND is_deleted = 0');
      mysqli_stmt_bind_param($checkStatement, 'i', $id);
      mysqli_stmt_execute($checkStatement);
      $userExists = mysqli_stmt_get_result($checkStatement)->num_rows > 0;
      mysqli_stmt_close($checkStatement);

      if (!$userExists) {
        $errors[] = 'User not found.';
      }
    }
  }

  $fullNameInput = $_POST['full-name'] ?? null;
  $fullName = is_string($fullNameInput) ? trim($fullNameInput) : '';
  if ($fullName === '') {
    $errors[] = 'Full Name is required.';
  } elseif (strlen($fullName) > 100) {
    $errors[] = 'Name must be under 100 characters.';
  }

  $emailInput = $_POST['email'] ?? null;
  $email = is_string($emailInput) ? trim($emailInput) : '';
  if ($email === '') {
    $errors[] = 'Email is required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
  }

  $genderInput = $_POST['gender'] ?? null;
  $gender = is_string($genderInput) ? $genderInput : '';
  if (!in_array($gender, ['M', 'F', 'O'], true)) {
    $errors[] = 'Gender is required.';
  }

  $passwordInput = $_POST['pwd'] ?? null;
  $password = is_string($passwordInput) ? $passwordInput : '';
  if ($mode === 'add' && $password === '') {
    $errors[] = 'Password is required.';
  } elseif ($password !== '' && (strlen($password) < 6 || strlen($password) > 20 || !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/', $password))) {
    $errors[] = 'Use uppercase, lowercase, a number, and a special character.';
  }

  $confirmPasswordInput = $_POST['confirm_password'] ?? null;
  $confirmPassword = is_string($confirmPasswordInput) ? $confirmPasswordInput : '';
  if ($mode === 'add' && $confirmPassword === '') {
    $errors[] = 'Confirm Password is required.';
  } elseif ($password !== '' && $confirmPassword === '') {
    $errors[] = 'Confirm Password is required.';
  } elseif ($password !== '' && $password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
  }

  $statusInput = $_POST['status'] ?? null;
  $status = is_scalar($statusInput) ? filter_var($statusInput, FILTER_VALIDATE_INT) : false;
  if (!in_array($status, [0, 1], true)) {
    $errors[] = 'Status is required.';
  }

  $file = $_FILES['myfile'] ?? null;
  if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Unable to upload the profile picture.';
    } elseif ($file['size'] > 1024 * 1024) {
      $errors[] = 'Profile picture must be smaller than 1 MB.';
    } else {
      $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($fileInfo, $file['tmp_name']);
      finfo_close($fileInfo);

      if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
        $errors[] = 'Only JPG and PNG pictures are allowed.';
      }
    }
  }

  if ($errors) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode("\n", $errors), 'errors' => $errors]);
    exit;
  }

  try {
    if ($mode === 'add') {
      $profilePicture = upload_profile_picture($file);
      $statement = mysqli_prepare($conn, 'INSERT INTO users (full_name, email_id, gender, password, status, profile_picture) VALUES (?, ?, ?, ?, ?, ?)');
      mysqli_stmt_bind_param($statement, 'ssssis', $fullName, $email, $gender, $password, $status, $profilePicture);
      mysqli_stmt_execute($statement);
      mysqli_stmt_close($statement);

      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'message' => 'User added successfully.']);
      exit;
    }

    $profilePicture = null;
    if ($file !== null && $file['error'] === UPLOAD_ERR_OK) {
      $profilePicture = upload_profile_picture($file);
    }

    $fields = ['full_name = ?', 'email_id = ?', 'gender = ?', 'status = ?'];
    $types = 'sssi';
    $params = [$fullName, $email, $gender, $status];

    if ($password !== '') {
      $fields[] = 'password = ?';
      $types .= 's';
      $params[] = $password;
    }

    if ($profilePicture !== null) {
      $fields[] = 'profile_picture = ?';
      $types .= 's';
      $params[] = $profilePicture;
    }

    $types .= 'i';
    $params[] = $id;
    $statement = mysqli_prepare($conn, 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    mysqli_stmt_bind_param($statement, $types, ...$params);
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
  } catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unable to save user.']);
    exit;
  }

  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
  exit;
}

$users = mysqli_query($conn, 'SELECT id, full_name, email_id, gender, status, profile_picture FROM users WHERE is_deleted = 0 ORDER BY id DESC');
if (!$users) {
    die('Unable to load users: ' . mysqli_error($conn));
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$ajaxEndpoint = $_SERVER['SCRIPT_NAME'];
?>
<!DOCTYPE html>
<html>

<head>
  <title>Registration Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel = "stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
 <style>
    #registration-wrapper {
      font-size: 13px;
    }

  #show-register-form {
    margin-left: 5%;
  }

    #toast-message {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1055;
      display: none;
      padding: 12px 18px;
      color: white;
      background-color: #198754;
      border-radius: 4px;
      white-space: pre-line;
    }
</style>
</head>

<body>

  <div id="toast-message" role="status"></div>

 <div style="margin-top: 20px; margin-bottom: 20px;">
    <button type="button" id="show-register-form" class="btn btn-primary">Add User</button>
  </div>

  <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="registration-form" action="<?= escape($ajaxEndpoint) ?>" method="POST" enctype="multipart/form-data" novalidate>
          <div class="modal-header">
            <h3 class="modal-title" id="userModalLabel">Registration Form</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <!-- form-mode: 0 = Add (blank form, no id), 1 = Edit (form pre-filled with existing user data) -->
            <input type="hidden" id="form-mode" value="0">

            <small id="full-name-error" style="color: red;"></small>
            <label for="full_name">Full Name <span style="color: red;">*</span> : </label>
            <input type="text" id="full_name" name="full-name" maxlength="100" autofocus>
            <br><br>

            <small id="email-error" style="color: red;"></small>
            <label for="email">Email ID <span style="color: red;">*</span> : </label>
            <input type="email" id="email" name="email" maxlength="100">
            <br><br>

            <small id="gender-error" style="color: red;"></small>
            <label for="gender">Gender <span style="color: red;">*</span> : </label>
            <select id="gender" name="gender">
              <option value="">Select Gender</option>
              <option value="M">Male</option>
              <option value="F">Female</option>
              <option value="O">Other</option>
            </select>
            <br><br>

            <small id="myfile-error" style="color: red;"></small>
            <label for="myfile">Upload Profile Picture:</label>
            <input type="file" id="myfile" name="myfile" accept="image/jpeg, image/png, image/jpg">
            <br><br>

            <div style="width: 200px;">
              <small id="pwd-error" style="color: red;"></small>
              <label for="pwd"> Password <span style="color: red;">*</span> : </label>
              <div style="position: relative; display: inline-block; width: 100%;">
                <input type="password" id="pwd" name="pwd" maxlength="20" style="width: 100%; padding-right: 40px;">
                <i class="fa-solid fa-eye password-toggle" id="togglePassword" data-target="pwd" aria-label="Show password" title="Show password"
                  style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
              </div>
            </div>

            <div style="width: 200px;">
              <small id="confirm_password-error" style="color: red;"></small>
              <label for="confirm_password">Confirm Password <span style="color: red;">*</span> : </label>
              <div style="position: relative; display: inline-block; width: 100%;">
                <input type="password" id="confirm_password" name="confirm_password" maxlength="20" style="width: 100%; padding-right: 40px;">
                <i class="fa-solid fa-eye password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"
                  style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
              </div>
            </div>
            <br>

            <small id="status-error" style="color: red;"></small>
            <p>Status <span style="color: red;">*</span> :</p>
            <input type="radio" id="active" name="status" value="1" checked>
            <label for="active">Active</label>
            <input type="radio" id="inactive" name="status" value="0">
            <label for="inactive">Inactive</label>
            <br><br>

            <input type="submit" value="Add">
            <input type="reset" value="Clear" onclick="clear_errors();">
          </div>
        </form>
      </div>
    </div>
  </div>

  <script> 
    $("#show-register-form").on("click", function () {
      const form = $("#registration-form");
      form[0].reset();
      $("#edit-id").val("");
      $("#form-mode").val("0"); // 0 = Add: no id, blank form
      form.attr("action", <?= json_encode($ajaxEndpoint) ?>);
      $("#userModalLabel").text("Registration Form");
      $("#registration-form input[type='submit']").val("Add");
      clear_errors();
      bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).show();
    });

    function validateForm(isEditMode) {
      $("#registration-form small").text("");

      const errors = [];
      const validateField = function (errorId, isInvalid, message) {
        const existingError = errors.findIndex(function (error) {
          return error.id === errorId;
        });
        if (existingError !== -1) {
          errors.splice(existingError, 1);
        }
        if (isInvalid) {
          errors.push({ id: errorId, message: message });
        }
      };

      const fullNameValue = $("#full_name").val().trim();
      validateField("full-name-error", fullNameValue === "" || fullNameValue.length > 100, fullNameValue === "" ? "Full Name is required." : "Name must be under 100 characters.");

      const emailValue = $("#email").val().trim();
      validateField("email-error", emailValue === "" || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue), emailValue === "" ? "Email is required." : "Enter a valid email address.");

      const genderValue = $("#gender").val().trim();
      validateField("gender-error", genderValue === "", "Gender is required.");

      const fileData = $("#myfile")[0].files[0];
      validateField("myfile-error", fileData && fileData.size > 1024 * 1024, fileData && fileData.size > 1024 * 1024 ? "Profile picture must be smaller than 1 MB." : "");
      if (fileData && fileData.size <= 1024 * 1024 && !["image/jpeg", "image/png"].includes(fileData.type)) {
        validateField("myfile-error", true, "Only JPG and PNG pictures are allowed.");
      }

      const passwordValue = $("#pwd").val().trim();
      const complexPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/;
      validateField("pwd-error", (!isEditMode && passwordValue === "") || (passwordValue !== "" && (passwordValue.length < 6 || passwordValue.length > 20 || !complexPattern.test(passwordValue))), passwordValue === "" ? "Password is required." : passwordValue.length < 6 || passwordValue.length > 20 ? "Password must be between 6 and 20 characters." : "Use uppercase, lowercase, a number, and a special character.");

      const confirmpasswordValue = $("#confirm_password").val().trim();
      validateField("confirm_password-error", (!isEditMode && confirmpasswordValue === "") || passwordValue !== confirmpasswordValue, confirmpasswordValue === "" ? "Confirm Password is required." : "Passwords do not match.");

      validateField("status-error", !$("#registration-form input[name='status']:checked").val(), "Status is required.");

      if (errors.length > 0) {
        showToast(errors.map(function (error) {
          return error.message;
        }).join("\n"), true);
      }

      return errors.length === 0;
    }
    function clear_errors() {
      $("#registration-form small").text("");
    }

    $(document).on("click", ".password-toggle", function () {
      const button = $(this);
      const password = button.closest("form").find("#" + button.data("target"));
      if (password.attr("type") === "password") {
        password.attr("type", "text");
        button.removeClass("fa-eye").addClass("fa-eye-slash");
        button.attr("aria-label", "Hide password").attr("title", "Hide password");
      } else {
        password.attr("type", "password");
        button.removeClass("fa-eye-slash").addClass("fa-eye");
        button.attr("aria-label", "Show password").attr("title", "Show password");
      }
    });

    $("#registration-form").on("submit", function (event) {
      const form = this;
      const mode = $("#form-mode").val(); // 0/1 for add and edit
      const isEditMode = mode === "1";

      form.action = <?= json_encode($ajaxEndpoint) ?>;

      if (!validateForm(isEditMode)) {
        event.preventDefault();
        return;
      }

      // Add and edit both call this page through AJAX.
      event.preventDefault();
      fetch(form.action, {
        method: "POST",
        body: new FormData(form)
      })
        .then(function (response) {
          return response.text().then(function (text) {
            try {
              return { ok: response.ok, data: JSON.parse(text) };
            } catch (error) {
              return { ok: false, data: { message: text || (isEditMode ? "Unable to update user." : "Unable to add user.") } };
            }
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            throw new Error(result.data.message || (isEditMode ? "Unable to update user." : "Unable to add user."));
          }
          showToast(result.data.message, false);
          bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).hide();
          form.reset();
        })
        .catch(function (error) {
          showToast(error.message, true);
        });
    });

  </script>

   <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?> 
    <p id="delete-message" style="color: green;">User deleted successfully.</p>
    <script>
      setTimeout(function () {
        const deleteMessage = document.getElementById('delete-message');
        if (deleteMessage) {
          deleteMessage.remove();
        }
      }, 5000);

      const cleanUrl = new URL(window.location.href);
      cleanUrl.searchParams.delete('deleted');
      window.history.replaceState({}, document.title, cleanUrl.pathname + cleanUrl.search);
    </script>
  <?php endif; ?>
  <br>

  <?php if (mysqli_num_rows($users) > 0): ?>
  <div class="container"><br><br>
    <h2>List of Users</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Action</th>
          <th> Profile Picture</th>
          <th>Full Name</th>
          <th>Email ID</th>
          <th>Gender</th>
          <th>Status</th>
    
        </tr>
      </thead>
      <tbody>
        <?php while ($user = mysqli_fetch_assoc($users)): ?>
          <tr>
            <td>
              <button type="button" class="btn btn-primary btn-sm edit-user-button"
                data-id="<?= (int) $user['id'] ?>"
                data-full-name="<?= escape($user['full_name']) ?>"
                data-email="<?= escape($user['email_id']) ?>"
                data-gender="<?= escape($user['gender']) ?>"
                data-status="<?= (int) $user['status'] ?>">Edit</button>
              <form action="<?= escape($ajaxEndpoint) ?>" method="POST" data-action="delete" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
            <td>
              <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?= escape($user['profile_picture']) ?>" alt="Profile picture" width="48" height="48">
              <?php else: ?>
                None
              <?php endif; ?>
            </td>
            <td><?= escape($user['full_name']) ?></td>
            <td><?= escape($user['email_id']) ?></td>
            <td><?= escape(['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$user['gender']] ?? $user['gender']) ?></td>
            <td><?= $user['status'] ? 'Active' : 'Inactive' ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function showToast(message, isError = false) {
      const toast = document.getElementById("toast-message");
      toast.textContent = message;
      toast.style.backgroundColor = isError ? "#dc3545" : "#198754";
      toast.style.display = "block";
      setTimeout(function () {
        toast.style.display = "none";
      }, 5000);
    }

    <?php if (isset($_GET['message'])): ?>
      showToast(<?= json_encode($_GET['message']) ?>, false);
      const updateCleanUrl = new URL(window.location.href);
      updateCleanUrl.searchParams.delete("message");
      window.history.replaceState({}, document.title, updateCleanUrl.pathname + updateCleanUrl.search);
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      showToast(<?= json_encode($_GET['error']) ?>, true);
      const errorCleanUrl = new URL(window.location.href);
      errorCleanUrl.searchParams.delete("error");
      window.history.replaceState({}, document.title, errorCleanUrl.pathname + errorCleanUrl.search);
    <?php endif; ?>

    document.querySelectorAll("form[data-action='delete']").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!confirm("Are you sure to delete this user?")) {
          return;
        }

        fetch(<?= json_encode($ajaxEndpoint) ?>, {
          method: "POST",
          body: new FormData(form)
        })
          .then(function (response) {
            return response.text().then(function (text) {
              try {
                return { ok: response.ok, data: JSON.parse(text) };
              } catch (error) {
                return {
                  ok: false,
                  data: { message: text || "Unable to delete user." }
                };
              }
            });
          })
          .then(function (result) {
            if (!result.ok || !result.data.success) {
              throw new Error(result.data.message || "Unable to delete user.");
            }

            form.closest("tr").remove();
            showToast(result.data.message);
          })
          .catch(function (error) {
            showToast(error.message, true);
          });
      });
    });

    document.querySelectorAll('.edit-user-button').forEach(function (button) {
      button.addEventListener('click', function () {
        const form = $("#registration-form");
        form.attr("action", <?= json_encode($ajaxEndpoint) ?>);
        $("#userModalLabel").text("Edit User");
        $("#registration-form input[type='submit']").val("Update");
        $("#form-mode").val("1"); // 1 = Edit: id present, form pre-filled with existing data
        $("#edit-id").val(button.dataset.id);
        $("#full_name").val(button.dataset.fullName);
        $("#email").val(button.dataset.email);
        $("#gender").val(button.dataset.gender);
        $("#pwd, #confirm_password").val("").attr("type", "password");
        $("#active").prop("checked", button.dataset.status === "1");
        $("#inactive").prop("checked", button.dataset.status === "0");
        $(".password-toggle").removeClass("fa-eye-slash").addClass("fa-eye");
        clear_errors();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).show();
      });
    });
  </script>
</body>
       
</html>