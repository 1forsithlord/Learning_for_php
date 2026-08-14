<!DOCTYPE html>
<html>

<head>
  <title>Registration Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

  <h1>Registration Form</h1>

  <form id="registration-form" action="ajax.php" method="POST" enctype="multipart/form-data" target="_blank"
    novalidate>
    <label for="full_name">Full Name <span style="color: red;">*</span> : </label>
    <input type="text" id="full_name" name="full-name" maxlength="100" autofocus>
    <br>
    <small id="full-name-error"></small>
    <br><br>

    <label for="email">Email ID <span style="color: red;">*</span> : </label>
    <input type="email" id="email" name="email" maxlength="100">
    <br>
    <small id="email-error"></small>
    <br><br>

    <label for="gender">Gender <span style="color: red;">*</span> : </label>
    <select id="gender" name="gender">
      <option value="">Select Gender</option>
      <option value="M">Male</option>
      <option value="F">Female</option>
      <option value="O">Other</option>
    </select>
    <br>
    <small id="gender-error"></small>
    <br><br>

    <label for="myfile">Upload Profile Picture:</label>
    <input type="file" id="myfile" name="myfile" accept="image/jpeg, image/png, image/jpg">
    <br>
    <small id="myfile-error"></small>
    <br><br>

    <div style="width: 300px;">
      <label for="pwd"> Password <span style="color: red;">*</span> : </label>
      <div style="position: relative; display: inline-block; width: 100%;">
        <input type="password" id="pwd" name="pwd" maxlength="20" style="width: 100%; padding-right: 40px;">
        <i class="fa-solid fa-eye" id="togglePassword" data-target="pwd"
          style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
      </div>
    </div>

    <div style="width: 300px;">
      <label for="confirm_password">Confirm Password <span style="color: red;">*</span> : </label>
      <div style="position: relative; display: inline-block; width: 100%;">
        <input type="password" id="confirm_password" name="confirm_password" maxlength="20" style="width: 100%; padding-right: 40px;">
        <i class="fa-solid fa-eye toggle-password-confirm" data-target="confirm_password"
          style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
      </div>
    </div>
    <br>
    <small id="confirm_password-error"></small>
    <br><br>

    <p>Status <span style="color: red;">*</span> :</p>
    <input type="radio" id="active" name="status" value="1" checked>
    <label for="active">Active</label>
    <br>
    <input type="radio" id="inactive" name="status" value="0">
    <label for="inactive">Inactive</label>
    <br>
    <small id="status-error"></small>
    <br><br>

    <input type="button" value="Submit" onclick="validateForm();">
    <input type="reset" value="Reset" onclick="clear_errors();">
  </form>

  <script>
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

      let fileValue = document.getElementById("myfile").value.trim();
      let fileData = document.getElementById("myfile").files[0];
      if (fileData) {
        let MaxSizeInBytes = 1024 * 1024;
        if (fileData.size > MaxSizeInBytes) {
          missingFields.push("Upload profile picture")
        }
      }


      let passwordValue = document.getElementById("pwd").value.trim();
      let complexPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/;
      if (passwordValue === "") {
        missingFields.push("Password");
      } else if (passwordValue.length < 6 && passwordValue.length > 20) {
        missingFields.push("Password must be at least 6 characters and less than 20");
      } else if (!complexPattern.test(passwordValue)) {
        missingFields.push("Password must contain uppercase, lowercase, a number, and a special character");
      }

      let confirmpasswordValue = document.getElementById("confirm_password").value.trim();

      if (confirmpasswordValue === "") { // Fixed: Added back the empty check for confirmation
        missingFields.push("Confirm Password");
      } else if (passwordValue !== confirmpasswordValue) {
        missingFields.push("Password does not Match");
      }


      if (missingFields.length > 0) {
        // .join(", ") glues the names together with a comma and a space
        alert("The following fields are required: " + missingFields.join(", "));
      } else {
        // If the array is empty, everything is filled!
        alert("Form submitted successfully!");
        document.getElementById("registration-form").submit();


      }
    }

    $("#togglePassword").on("click", function () {
      let password = $("#pwd");

      if (password.attr("type") === "password") {
        password.attr("type", "text");
        $(this).removeClass("fa-eye")
          .addClass("fa-eye-slash");
      } else {
        password.attr("type", "password");
        $(this).removeClass("fa-eye-slash")
          .addClass("fa-eye");
      }
    });

    $(".toggle-password-confirm").on("click", function () {
      let confirmPassword = $("#confirm_password");

      if (confirmPassword.attr("type") === "password") {
        confirmPassword.attr("type", "text");
        $(this).removeClass("fa-eye")
          .addClass("fa-eye-slash");
      } else {
        confirmPassword.attr("type", "password");
        $(this).removeClass("fa-eye-slash")
          .addClass("fa-eye");
      }
    });

    document.getElementById("registration-form").addEventListener("submit", validateForm);

  </script>
</body>

</html>