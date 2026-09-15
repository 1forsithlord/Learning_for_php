<?php
require 'connection.php';

$users = mysqli_query($conn, 'SELECT id, full_name, email_id, gender, status, profile_picture FROM users WHERE is_deleted = 0 ORDER BY id DESC');
if (!$users) {
    die('Unable to load users: ' . mysqli_error($conn));
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$ajaxEndpoint = 'ajax.php';
?>
<!DOCTYPE html>
<html>

<head>
  <title>Registration Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
 <style>
    #registration-wrapper {
      font-size: 13px;
    }

  #show-register-form {
    margin-left: 5%;
  }

    #user-filters {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin: 12px 0;
    }

    #user-filters input {
      max-width: 180px;
    }

    #search-users-button {
      white-space: nowrap;
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

    .profile-picture,
    .profile-placeholder {
      display: inline-flex;
      width: 48px;
      height: 48px;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      vertical-align: middle;
    }

    .profile-picture {
      object-fit: cover;
      border: 2px solid #fff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
    }

    .profile-placeholder {
      color: #6c757d;
      background: #e9ecef;
      border: 2px solid #dee2e6;
    }

    #profile-picture-viewer {
      position: fixed;
      inset: 0;
      z-index: 1060;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: rgba(0, 0, 0, 0.78);
    }

    #profile-picture-viewer.is-visible {
      display: flex;
    }

    #profile-picture-viewer-image {
      max-width: min(90vw, 900px);
      max-height: 90vh;
      object-fit: contain;
      border-radius: 6px;
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45);
    }

    #close-profile-picture-viewer {
      position: absolute;
      top: 18px;
      right: 24px;
      color: #fff;
      background: transparent;
      border: 0;
      font-size: 2rem;
      line-height: 1;
      cursor: pointer;
    }
</style>
</head>

<body>

  <div id="toast-message" role="status"></div>

  <div id="profile-picture-viewer" role="dialog" aria-modal="true" aria-label="Profile picture preview">
    <button type="button" id="close-profile-picture-viewer" aria-label="Close profile picture preview">&times;</button>
    <img id="profile-picture-viewer-image" src="" alt="">
  </div>

 <div style="margin-top: 20px; margin-bottom: 20px;">
    <button type="button" id="show-register-form" class="btn btn-primary">Add User</button>
  </div>

  <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="registration-form" action="" method="POST" enctype="multipart/form-data" novalidate>
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

            <button type="button" id="save-user-button" class="btn btn-secondary btn-sm" onclick="handleUserSave(event)">Add</button>
            <button type="reset" class="btn btn-secondary btn-sm" onclick="clear_errors();">Clear</button>
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
      $("#save-user-button").text("Add");
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
     // escapeHtml() makes user data safe before placing it inside HTML. It replaces special characters with their HTML entity equivalents to prevent XSS attacks.
    function escapeHtml(value) {
      return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function getUserFilters() {
      return {
        name: $("#name-search").val().trim(),
        email: $("#email-search").val().trim(),
        gender: $("#gender-search").val().trim(),
        status: $("#status-search").val().trim()
      };
    }

    function normalizeGenderValue(value) {
      const normalized = String(value ?? "").toLowerCase();
      return ({ m: "male", f: "female", o: "other", male: "male", female: "female", other: "other" }[normalized] || normalized);
    }

    function normalizeStatusValue(value) {
      const normalized = String(value ?? "").toLowerCase();
      return ({ "1": "active", "0": "inactive", active: "active", inactive: "inactive" }[normalized] || normalized);
    }

    function refreshUsers() {
      // Step 8: Get the latest users and rebuild the whole table.
      const filters = getUserFilters();
      return $.ajax({
        url: <?= json_encode($ajaxEndpoint) ?>,
        method: "GET",
        data: {
          action: "list_users",
          ...filters
        },
        dataType: "json"
      }).then(function (data) {
        if (!data.success) {
          throw new Error(data.message || "Unable to refresh users.");
        }
        return data.users;
      }).then(function (users) {
        // Step 8.1: Search users using the current filter values before rebuilding the table.
        const matchingUsers = users.filter(function (user) {
          const gender = normalizeGenderValue(user.gender);
          const status = normalizeStatusValue(user.status);

          return (!filters.name || String(user.full_name).toLowerCase().includes(filters.name.toLowerCase()))
            && (!filters.email || String(user.email_id).toLowerCase().includes(filters.email.toLowerCase()))
            && (!filters.gender || gender === filters.gender.toLowerCase())
            && (!filters.status || status === filters.status.toLowerCase());
        });

        document.getElementById("users-showbody").innerHTML = matchingUsers.length === 0
          ? '<tr><td colspan="6"><p>User not found</p></td></tr>'
          : matchingUsers.map(function (user) {
          return `
            <tr data-user-id="${escapeHtml(user.id)}">
              <td>
                <button type="button" class="btn btn-primary btn-sm edit-user-button" data-id="${escapeHtml(user.id)}">Edit</button>
                <form action="<?= escape($ajaxEndpoint) ?>" method="POST" data-action="delete" style="display: inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="${escapeHtml(user.id)}">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
              <td>${user.profile_picture ? `<img class="profile-picture" src="${escapeHtml(user.profile_picture)}" alt="Profile picture" width="48" height="48" tabindex="0">` : '<span class="profile-placeholder" role="img" aria-label="No profile picture"><i class="fa-solid fa-user" aria-hidden="true"></i></span>'}</td>
              <td>${escapeHtml(user.full_name)}</td>
              <td>${escapeHtml(user.email_id)}</td>
              <td>${escapeHtml({ M: "Male", F: "Female", O: "Other" }[user.gender] || user.gender)}</td>
              <td>${Number(user.status) === 1 ? "Active" : "Inactive"}</td>
            </tr>`;
          }).join("");
      });
    }

    $(document).on("click", "#search-users-button", function () {
      refreshUsers();
    });

    $(document).on("click", ".profile-picture", function () {
      $("#profile-picture-viewer-image").attr("src", this.src).attr("alt", this.alt);
      $("#profile-picture-viewer").addClass("is-visible");
    });

    $(document).on("click", "#close-profile-picture-viewer, #profile-picture-viewer", function (event) {
      if (event.target.id === "profile-picture-viewer" || event.target.id === "close-profile-picture-viewer") {
        $("#profile-picture-viewer").removeClass("is-visible");
        $("#profile-picture-viewer-image").attr("src", "");
      }
    });

    $(document).on("keydown", function (event) {
      if (event.key === "Escape") {
        $("#profile-picture-viewer").removeClass("is-visible");
        $("#profile-picture-viewer-image").attr("src", "");
      }
    });

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

    function handleUserSave(event) {
      const form = event.currentTarget.closest("form");
      const mode = $("#form-mode").val(); // 0/1 for add and edit
      const isEditMode = mode === "1";
   
 

      if (!validateForm(isEditMode)) {
        event.preventDefault();
        return;
      }

      // Step 4: Stop normal form navigation and send the form with jQuery AJAX.
      event.preventDefault();
      $.ajax({
        url: <?= json_encode($ajaxEndpoint) ?>,
        method: "POST",
        data: new FormData(form),
        processData: false,
        contentType: false,
        dataType: "json"
      })
        .then(function (data) {
          if (data.changed === false) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).hide();
            form.reset();
            return;
          }
          if (!data.success) {
            throw new Error(data.message || (isEditMode ? "Unable to update user." : "Unable to add user."));
          }
          // Step 7: The server has saved the change and returned success JSON.
          showToast(data.message, false);
          bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).hide();
          form.reset();
          return refreshUsers();
        })
        .catch(function (xhr) {
          showToast(xhr.responseJSON?.message || xhr.message || (isEditMode ? "Unable to update user." : "Unable to add user."), true);
        });
    }

  </script>

   <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?> 
    <p id="delete-message" style="color: green;">User deleted successfully2.</p>
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

  <div class="container" id="users-list"><br><br>
    <h2>List of Users</h2>
    <div id="user-filters">
      <input type="search" id="name-search" placeholder=" Full Name">
      <input type="search" id="email-search" placeholder=" Email ID">
      <select id="gender-search" class="form-select form-select-sm" style="max-width: 180px;">
        <option value=""> Gender</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
      </select>
      <select id="status-search" class="form-select form-select-sm" style="max-width: 180px;">
        <option value="">Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <button type="button" id="search-users-button" class="btn btn-secondary btn-sm">Search</button>
    </div>
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
      <tbody id="users-showbody">
        <?php while ($user = mysqli_fetch_assoc($users)): ?>
          <tr data-user-id="<?= (int) $user['id'] ?>">
            <td>
              <button type="button" class="btn btn-primary btn-sm edit-user-button"
                data-id="<?= (int) $user['id'] ?>">Edit</button>
              <form action="<?= escape($ajaxEndpoint) ?>" method="POST" data-action="delete" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
            <td>
              <?php if (!empty($user['profile_picture'])): ?>
                <img class="profile-picture" src="<?= escape($user['profile_picture']) ?>" alt="Profile picture" width="48" height="48" tabindex="0">
              <?php else: ?>
                <span class="profile-placeholder" role="img" aria-label="No profile picture">
                  <i class="fa-solid fa-user" aria-hidden="true"></i>
                </span>
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

    document.addEventListener("submit", function (event) {
      const form = event.target.closest("form[data-action='delete']");
      if (!form) {
        return;
      }

        event.preventDefault();
        if (!confirm("Are you sure to delete this user?")) {
          return;
        }

        $.ajax({
          url: <?= json_encode($ajaxEndpoint) ?>,
          method: "POST",
          data: new FormData(form),
          processData: false,
          contentType: false,
          dataType: "json"
        })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || "Unable to delete user.");
            }

            return refreshUsers().then(function () {
              showToast(data.message);
            });
          })
          .catch(function (xhr) {
            showToast(xhr.responseJSON?.message || xhr.message || "Unable to delete user.", true);
          });
    });

    document.addEventListener('click', function (event) {
      // Step 1: Find out whether the user clicked an Edit button.
      const button = event.target.closest('.edit-user-button');
      if (!button) {
        return;
      }

        // Step 2: Request the selected user's data as JSON.
        const userUrl = new URL(<?= json_encode($ajaxEndpoint) ?>, window.location.href);
        userUrl.searchParams.set('action', 'get_user');
        userUrl.searchParams.set('id', button.dataset.id);

        $.ajax({
          url: userUrl.toString(),
          method: 'GET',
          dataType: 'json'
        })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || 'Unable to load user.');
            }
            return data.user;
          })
          .then(function (user) {
            // Step 3: Put the JSON values into the edit modal.
            const form = $("#registration-form");
            // form.attr("action", <?= json_encode($ajaxEndpoint) ?>);
            $("#userModalLabel").text("Edit User");
            $("#save-user-button").text("Update");
            $("#form-mode").val("1");
            $("#edit-id").val(user.id);
            $("#full_name").val(user.full_name);
            $("#email").val(user.email_id);
            $("#gender").val(user.gender);
            $("#pwd, #confirm_password").val("").attr("type", "password");
            $("#active").prop("checked", Number(user.status) === 1);
            $("#inactive").prop("checked", Number(user.status) === 0);
            $(".password-toggle").removeClass("fa-eye-slash").addClass("fa-eye");
            clear_errors();
            bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal")).show();
          })
          .catch(function (xhr) {
            showToast(xhr.responseJSON?.message || xhr.message || 'Unable to load user.', true);
          });
    });
  </script>
</body>
</html>
  