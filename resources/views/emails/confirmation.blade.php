<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
        {{-- <center><div style="height: 100px; width: 100px; object-fit: contain; border-radius: 50%; border: 1px solid black; background-image: url('http://planx-dev.id.vn/logo_planx.jpg'); background-position: center; background-repeat: no-repeat; background-size: contain;"></div></center> --}}
        {{-- <center><h2>Welcome to <a style="text-decoration: none !important; color: black;" href="https://www.facebook.com/planxdev">Plan X</a></h2></center> --}}
        <p style="color: #555555; line-height: 1.6;">Dear {{ $name }},</p>
        <p style="color: #555555; line-height: 1.6;">Thank you for choosing to join our community at <a style="text-decoration: none !important; color: black;" href="https://www.facebook.com/planxdev"><b>Plan X</b></a>. We're excited to have you on board! To complete your registration and access all the great features we offer, please confirm your account by clicking the link below:</p>
        <a href='{{ "https://nguyenviet3057.github.io/gogpt/?code=$code" }}' style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">Confirm Account</a>
        <p style="color: #555555; line-height: 1.6;">Or click here if it doesn't work: <a style="font-size: small;" href='{{ "https://nguyenviet3057.github.io/gogpt/?code=$code" }}'>{{ "https://nguyenviet3057.github.io/gogpt/?code=$code" }}</a></p>
        <p style="color: #555555; line-height: 1.6;">Here are the details you provided during registration:</p>
        <ul>
            <li><strong>Full Name:</strong> {{ $name }}</li>
            <li><strong>Email Address:</strong> {{ $email }}</li>
        </ul>
        <p style="color: #555555; line-height: 1.6;">Please review the information above to ensure its accuracy. It can't be changed after confirmation.</p>
        <p style="color: #555555; line-height: 1.6;">By confirming your email, you'll gain access to a world of possibilities. Explore, connect, and engage with our community to make the most of your experience.</p>
        <p style="color: #555555; line-height: 1.6;">If you did not sign up for an account with us, please don't click the link and disregard this email.</p>
        <p style="color: #555555; line-height: 1.6;">Thank you once again for choosing <b><a style="text-decoration: none !important; color: black;" href="https://www.facebook.com/planxdev"><b>Plan X</b></a></b>. We can't wait to see you around!</p>
        <p style="color: #555555; line-height: 1.6;">Best regards,<br>From <a style="text-decoration: none !important; color: black;" href="https://www.facebook.com/planxdev"><b>Plan X</b></a></b> Team</p>
    </div>
</body>

</html>