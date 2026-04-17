<?php
require 'config.php';
require_login(); 

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id); 

if (is_post()) {
    if (isset($_POST['update_btn'])) {
        $name = post('name'); 
        if ($name != '') {
            $stm = $_db->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stm->execute([$name, $user_id]);
            temp('info', 'Profile name updated perfectly! ^^');
            redirect('profile_update.php');
        }
    }

    if (isset($_POST['password_btn'])) {
        $current = post('current_password');
        $new = post('new_password');
        $confirm = post('confirm_password');

        if (sha1($current) === $user->password) {
            if ($new === $confirm && $new != '') {
                $stm = $_db->prepare("UPDATE users SET password = SHA1(?) WHERE id = ?");
                $stm->execute([$new, $user_id]);
                temp('info', 'Password safely changed! :>');
                redirect('profile_update.php');
            } else {
                temp('info', 'Those new passwords do not match up...');
            }
        } else {
            temp('info', 'Hmm, current password is not right >.<');
        }
    }

    // Remove Photo
    if (isset($_POST['remove_btn'])) {
        
        // delete file frm folder
        if (!empty($user->profile_photo)) {
            $file_path = 'image/' . $user->profile_photo;
            if (file_exists($file_path)) {
                unlink($file_path); 
            }
        }

        // wipe it from the database
        $stm = $_db->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
        $stm->execute([$user_id]);
        
        temp('info', 'Profile picture has been reset');
        redirect('profile_update.php');
    }

    if (isset($_POST['upload_btn'])) {
        $file_name = uploadImage(get_file('profile_photo'));
        if ($file_name) {
            $stm = $_db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stm->execute([$file_name, $user_id]);
            temp('info', 'Looking good! Photo saved ^^');
            redirect('profile_update.php');
        } else {
            temp('info', 'Oops, something went wrong with the photo upload');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Shop - Update Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>Edit My Profile</h2>
        </div>

        <?php if ($msg = temp('info')): ?>
            <div style="color: green; margin-bottom: 15px; font-weight: bold;"><?= $msg ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 30px;">
            <h3>Profile Picture</h3>
            <?php if (!empty($user->profile_photo)): ?>
                <img src="image/<?= htmlspecialchars($user->profile_photo) ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-bottom: 10px; display: block;">
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="profile_photo" accept="image/*" required style="margin-bottom: 10px; display: block;">
                <button type="submit" name="upload_btn" class="btn btn-p">Upload Photo</button>
            </form>

            <?php if (!empty($user->profile_photo)): ?>
                <form method="post" style="margin-top: 10px;">
                    <button type="submit" name="remove_btn" class="btn btn-s" style="background: #ffcccc; border-color: #ff9999;">Reset to Default</button>
                </form>
            <?php endif; ?>
        </div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <div style="margin-bottom: 30px;">
            <h3>Basic Info</h3>
            <form method="post">
                <label style="display: block; margin-bottom: 5px;">Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required style="padding: 8px; width: 100%; max-width: 300px; margin-bottom: 10px;">
                <br>
                <button type="submit" name="update_btn" class="btn btn-p">Save Name</button>
            </form>
        </div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <div style="margin-bottom: 30px;">
            <h3>Change Password</h3>
            <form method="post">
                <label style="display: block; margin-bottom: 5px;">Current Password:</label>
                <input type="password" name="current_password" required style="padding: 8px; width: 100%; max-width: 300px; margin-bottom: 10px;">
                
                <label style="display: block; margin-bottom: 5px;">New Password:</label>
                <input type="password" name="new_password" required style="padding: 8px; width: 100%; max-width: 300px; margin-bottom: 10px;">
                
                <label style="display: block; margin-bottom: 5px;">Confirm New Password:</label>
                <input type="password" name="confirm_password" required style="padding: 8px; width: 100%; max-width: 300px; margin-bottom: 10px;">
                
                <br>
                <button type="submit" name="password_btn" class="btn btn-p">Update Password</button>
            </form>
        </div>

        <a href="profile.php" class="btn btn-s">⬅ Back to Profile</a>
    </div>

</body>
</html>