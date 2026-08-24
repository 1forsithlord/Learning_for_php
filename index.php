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
    width: 420px;
    margin-left: 5%;
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
    }
</style>
</head>

<body>

  <div id="toast-message" role="status"></div>

 <div style="margin-top: 20px; margin-bottom: 20px;">
    <button type="button" id="show-register-form" class="btn btn-primary">Register</button>
  </div>

  <div id="registration-wrapper" style="display: none;">
    <h3>Registration Form</h3>

    <form id="registration-form" action="ajax.php" method="POST" enctype="multipart/form-data"
      novalidate >
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
        <i class="fa-solid fa-eye" id="togglePassword" data-target="pwd"
          style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
      </div>
    </div>

    <div style="width: 200px;">
      <small id="confirm_password-error" style="color: red;"></small>
      <label for="confirm_password">Confirm Password <span style="color: red;">*</span> : </label>
      <div style="position: relative; display: inline-block; width: 100%;">
        <input type="password" id="confirm_password" name="confirm_password" maxlength="20" style="width: 100%; padding-right: 40px;">
        <i class="fa-solid fa-eye toggle-password-confirm" data-target="confirm_password"
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

      <input type="submit" value="Submit">
      <input type="reset" value="Reset" onclick="clear_errors();">
    </form>
  </div>

  <script>
    document.getElementById("show-register-form").addEventListener("click", function () {
      const wrapper = document.getElementById("registration-wrapper");
      const button = this;

      if (wrapper.style.display === "none") {
        wrapper.style.display = "block";
        button.textContent = "Hide Form";
        document.getElementById("full_name").focus();
      } else {
        wrapper.style.display = "none";
        button.textContent = "Register";
      }
    });

    function validateForm() {
      const errorElements = document.querySelectorAll("#registration-form small");
      errorElements.forEach(function (errorElement) {
        errorElement.textContent = "";
      });

      let isValid = true;
      const showError = function (errorId, message) {
        document.getElementById(errorId).textContent = message;
        isValid = false;
      };

      const fullName = document.getElementById("full_name");
      let fullNameValue = fullName.value.trim();
      if (fullNameValue === "") {
        showError("full-name-error", "Full Name is required.");
      } else if (fullNameValue.length > 100) {
        showError("full-name-error", "Name must be under 100 characters.");
      }

      const email = document.getElementById("email");
      let emailValue = email.value.trim();
      if (emailValue === "") {
        showError("email-error", "Email is required.");
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
        showError("email-error", "Enter a valid email address.");
      }

      const gender = document.getElementById("gender");
      let genderValue = gender.value.trim();
      if (genderValue === "") {
        showError("gender-error", "Gender is required.");
      }

      const myfile = document.getElementById("myfile");
      let fileData = myfile.files[0];
      if (fileData && fileData.size > 1024 * 1024) {
        showError("myfile-error", "Profile picture must be smaller than 1 MB.");
      }

      const password = document.getElementById("pwd");
      let passwordValue = password.value.trim();
      let complexPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/;
      if (passwordValue === "") {
        showError("pwd-error", "Password is required.");
      } else if (passwordValue.length < 6 || passwordValue.length > 20) {
        showError("pwd-error", "Password must be between 6 and 20 characters.");
      } else if (!complexPattern.test(passwordValue)) {
        showError("pwd-error", "Use uppercase, lowercase, a number, and a special character.");
      }

      const confirmPassword = document.getElementById("confirm_password");
      let confirmpasswordValue = confirmPassword.value.trim();
      if (confirmpasswordValue === "") {
        showError("confirm_password-error", "Confirm Password is required.");
      } else if (passwordValue !== confirmpasswordValue) {
        showError("confirm_password-error", "Passwords do not match.");
      }

      const activeStatus = document.getElementById("active");
      const inactiveStatus = document.getElementById("inactive");
      if (!activeStatus.checked && !inactiveStatus.checked) {
        showError("status-error", "Status is required.");
      }

      return isValid;
    }

    function clear_errors() {
      document.querySelectorAll("#registration-form small").forEach(function (errorElement) {
        errorElement.textContent = "";
      });
    }

    $("#togglePassword").on("click", function () {
      let password = $("#pwd");
      if (password.attr("type") === "password") {
        password.attr("type", "text");
        $(this).removeClass("fa-eye").addClass("fa-eye-slash");
      } else {
        password.attr("type", "password");
        $(this).removeClass("fa-eye-slash").addClass("fa-eye");
      }
    });

    $(".toggle-password-confirm").on("click", function () {
      let confirmPassword = $("#confirm_password");
      if (confirmPassword.attr("type") === "password") {
        confirmPassword.attr("type", "text");
        $(this).removeClass("fa-eye").addClass("fa-eye-slash");
      } else {
        confirmPassword.attr("type", "password");
        $(this).removeClass("fa-eye-slash").addClass("fa-eye");
      }
    });

    document.getElementById("registration-form").addEventListener("submit", function (event) {
      event.preventDefault();
      if (!validateForm()) {
        return;
      }

      fetch(this.action, {
        method: "POST",
        body: new FormData(this)
      })
        .then(function (response) {
          return response.text().then(function (text) {
            return { ok: response.ok, data: JSON.parse(text) };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            throw new Error(result.data.message || "Unable to add user.");
          }
          alert(result.data.message);
          window.location.reload();
        })
        .catch(function (error) {
          alert(error.message);
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
  <?php elseif (isset($_GET['message'])): ?>
    <p style="color: green;"><?= escape($_GET['message']) ?></p>
  <?php endif; ?>
  <br>

  <?php if (mysqli_num_rows($users) > 0): ?>
  <div class="container">
    <h2>List of Users</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Action</th>
          <th>ID</th>
          <th>Picture</th>
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
                data-bs-toggle="modal" data-bs-target="#editUserModal"
                data-id="<?= (int) $user['id'] ?>"
                data-full-name="<?= escape($user['full_name']) ?>"
                data-email="<?= escape($user['email_id']) ?>"
                data-gender="<?= escape($user['gender']) ?>"
                data-status="<?= (int) $user['status'] ?>">Edit</button>
              <form action="delete.php" method="POST" style="display: inline;">
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
            <td><?= escape($user['id']) ?></td>
            <td>
              <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?= escape($user['profile_picture']) ?>" alt="Profile picture" width="48" height="48">
              <?php else: ?>
                None
              <?php endif; ?>
            </td>
            <td><?= escape($user['full_name']) ?></td>
            <td><?= escape($user['email_id']) ?></td>
            <td><?= escape($user['gender']) ?></td>
            <td><?= $user['status'] ? 'Active' : 'Inactive' ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="edit.php" method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
              <label for="edit-full-name" class="form-label">Full Name</label>
              <input type="text" name="full-name" id="edit-full-name" maxlength="100" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="edit-email" class="form-label">Email</label>
              <input type="email" name="email" id="edit-email" maxlength="100" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="edit-gender" class="form-label">Gender</label>
              <select name="gender" id="edit-gender" class="form-select" required>
                <option value="M">Male</option>
                <option value="F">Female</option>
                <option value="O">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit-password" class="form-label">New Password (optional)</label>
              <input type="password" name="pwd" id="edit-password" minlength="6" maxlength="20" class="form-control">
            </div>
            <div class="mb-3">
              <label for="edit-picture" class="form-label">New Profile Picture</label>
              <input type="file" name="myfile" id="edit-picture" accept="image/jpeg,image/png" class="form-control">
            </div>
            <fieldset>
              <legend class="col-form-label pt-0">Status</legend>
              <label class="me-3"><input type="radio" name="status" value="1" id="edit-active"> Active</label>
              <label><input type="radio" name="status" value="0" id="edit-inactive"> Inactive</label>
            </fieldset>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function showToast(message) {
      const toast = document.getElementById("toast-message");
      toast.textContent = message;
      toast.style.display = "block";
      setTimeout(function () {
        toast.style.display = "none";
      }, 5000);
    }

    document.querySelectorAll("form[action='delete.php']").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!confirm("Delete this user?")) {
          return;
        }

        fetch(form.action, {
          method: "POST",
          body: new FormData(form)
        })
          .then(function (response) {
            return response.json().then(function (data) {
              return { ok: response.ok, data: data };
            });
          })
          .then(function (result) {
            if (!result.ok || !result.data.success) {
              throw new Error(result.data.message || "Unable to delete user.");
            }
            showToast(result.data.message);
            form.closest("tr").remove();
          })
          .catch(function (error) {
            alert(error.message);
          });
      });
    });

    document.querySelectorAll('.edit-user-button').forEach(function (button) {
      button.addEventListener('click', function () {
        document.getElementById('edit-id').value = button.dataset.id;
        document.getElementById('edit-full-name').value = button.dataset.fullName;
        document.getElementById('edit-email').value = button.dataset.email;
        document.getElementById('edit-gender').value = button.dataset.gender;
        document.getElementById('edit-active').checked = button.dataset.status === '1';
        document.getElementById('edit-inactive').checked = button.dataset.status === '0';
      });
    });
  </script>
</body>
</html>
