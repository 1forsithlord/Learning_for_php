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
</style>
</head>

<body>

 <div style="margin-top: 20px; margin-bottom: 20px;">
    <button type="button" id="show-register-form" class="btn btn-primary">Register</button>
  </div>

  <div id="registration-wrapper" style="display: none;">
    <h3>Registration Form</h3>

    <form id="registration-form" action="ajax.php" method="POST" enctype="multipart/form-data"
      novalidate >
    <label for="full_name">Full Name <span style="color: red;">*</span> : </label>
    <input type="text" id="full_name" name="full-name" maxlength="100" autofocus>
    <small id="full-name-error"></small>
    <br><br>

    <label for="email">Email ID <span style="color: red;">*</span> : </label>
    <input type="email" id="email" name="email" maxlength="100">
    <small id="email-error"></small>
    <br><br>

    <label for="gender">Gender <span style="color: red;">*</span> : </label>
    <select id="gender" name="gender">
      <option value="">Select Gender</option>
      <option value="M">Male</option>
      <option value="F">Female</option>
      <option value="O">Other</option>
    </select>
    <small id="gender-error"></small>
    <br><br>

    <label for="myfile">Upload Profile Picture:</label>
    <input type="file" id="myfile" name="myfile" accept="image/jpeg, image/png, image/jpg">
    <small id="myfile-error"></small>
    <br><br>

    <div style="width: 200px;">
      <label for="pwd"> Password <span style="color: red;">*</span> : </label>
      <div style="position: relative; display: inline-block; width: 100%;">
        <input type="password" id="pwd" name="pwd" maxlength="20" style="width: 100%; padding-right: 40px;">
        <i class="fa-solid fa-eye" id="togglePassword" data-target="pwd"
          style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
      </div>
    </div>

    <div style="width: 200px;">
      <label for="confirm_password">Confirm Password <span style="color: red;">*</span> : </label>
      <div style="position: relative; display: inline-block; width: 100%;">
        <input type="password" id="confirm_password" name="confirm_password" maxlength="20" style="width: 100%; padding-right: 40px;">
        <i class="fa-solid fa-eye toggle-password-confirm" data-target="confirm_password"
          style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
      </div>
    </div>
    <small id="confirm_password-error"></small>
    <br>

    <p>Status <span style="color: red;">*</span> :</p>
    <input type="radio" id="active" name="status" value="1" checked>
    <label for="active">Active</label>
    <input type="radio" id="inactive" name="status" value="0">
    <label for="inactive">Inactive</label>
    <small id="status-error"></small>
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
      let missingFields = [];

      let fullNameValue = document.getElementById("full_name").value.trim();
      if (fullNameValue === "") {
        missingFields.push("Full Name");
      } else if (fullNameValue.length > 100) {
        missingFields.push("Name should be under 100 characters");
      }

      let emailValue = document.getElementById("email").value.trim();
      if (emailValue === "") {
        missingFields.push("Email ID");
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
        missingFields.push("Valid Email ID");
      }

      let genderValue = document.getElementById("gender").value.trim();
      if (genderValue === "") {
        missingFields.push("Gender");
      }

      let fileData = document.getElementById("myfile").files[0];
      if (fileData && fileData.size > 1024 * 1024) {
        missingFields.push("Upload profile picture");
      }

      let passwordValue = document.getElementById("pwd").value.trim();
      let complexPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/;
      if (passwordValue === "") {
        missingFields.push("Password");
      } else if (passwordValue.length < 6 || passwordValue.length > 20) {
        missingFields.push("Password must be at least 6 characters and less than 20");
      } else if (!complexPattern.test(passwordValue)) {
        missingFields.push("Password must contain uppercase, lowercase, a number, and a special character");
      }

      let confirmpasswordValue = document.getElementById("confirm_password").value.trim();
      if (confirmpasswordValue === "") {
        missingFields.push("Confirm Password");
      } else if (passwordValue !== confirmpasswordValue) {
        missingFields.push("Password does not Match");
      }

      if (missingFields.length > 0) {
        alert("The following fields are required: " + missingFields.join(", "));
        return false;
      }

      return true;
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
            return response.text().then(function (text) {
              return { ok: response.ok, data: JSON.parse(text) };
            });
          })
          .then(function (result) {
            if (!result.ok || !result.data.success) {
              throw new Error(result.data.message || "Unable to delete user.");
            }
            alert(result.data.message);
            form.closest("tr").remove();
          })
          .catch(function (error) {
            alert(error.message);
          });
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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
