<?php
// Email sending wrapper

function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: Civic Issue System <noreply@civic.gov.in>\r\n";
    $headers .= "Content-Type: text/" . ($isHtml ? 'html' : 'plain') . "; charset=UTF-8\r\n";
    
    $emailBody = $isHtml ? nl2br($body) : $body;
    
    return mail($to, $subject, $emailBody, $headers);
}

function sendIssueAssignedEmail($citizenEmail, $citizenName, $issueId, $issueTitle, $solverName) {
    $subject = "Your Issue #$issueId Has Been Assigned";
    $body = "
    <html>
    <head><title>Issue Assigned</title></head>
    <body>
        <h2>Hello $citizenName,</h2>
        <p>Good news! Your issue <strong>\"$issueTitle\"</strong> (ID: #$issueId) has been assigned to <strong>$solverName</strong>.</p>
        <p>Our team will now work on resolving your issue. You can track the progress on the citizen portal.</p>
        <p>Thank you for your patience.</p>
        <hr>
        <p><small>This is an automated notification from the Rural Civic Issue Reporting System.</small></p>
    </body>
    </html>
    ";
    return sendEmail($citizenEmail, $subject, $body);
}

function sendStatusUpdateEmail($citizenEmail, $citizenName, $issueId, $issueTitle, $status) {
    $statusLabels = [
        'pending' => 'Pending',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed'
    ];
    $statusLabel = $statusLabels[$status] ?? $status;
    
    $subject = "Issue #$issueId Status Update: $statusLabel";
    $body = "
    <html>
    <head><title>Status Update</title></head>
    <body>
        <h2>Hello $citizenName,</h2>
        <p>Your issue <strong>\"$issueTitle\"</strong> (ID: #$issueId) status has been updated to: <strong>$statusLabel</strong>.</p>
        " . ($status === 'resolved' ? "<p>Please login to provide feedback on the resolution.</p>" : "") . "
        <hr>
        <p><small>This is an automated notification from the Rural Civic Issue Reporting System.</small></p>
    </body>
    </html>
    ";
    return sendEmail($citizenEmail, $subject, $body);
}

function sendIssueResolvedEmail($citizenEmail, $citizenName, $issueId, $issueTitle, $solverName) {
    $subject = "Your Issue #$issueId Has Been Resolved";
    $body = "
    <html>
    <head><title>Issue Resolved</title></head>
    <body>
        <h2>Hello $citizenName,</h2>
        <p>Great news! Your issue <strong>\"$issueTitle\"</strong> (ID: #$issueId) has been resolved by <strong>$solverName</strong>.</p>
        <p>Please login to the citizen portal to view the resolution details and provide your feedback.</p>
        <p>We value your feedback to improve our service.</p>
        <hr>
        <p><small>This is an automated notification from the Rural Civic Issue Reporting System.</small></p>
    </body>
    </html>
    ";
    return sendEmail($citizenEmail, $subject, $body);
}

function sendWelcomeEmail($email, $name, $password, $role) {
    $subject = "Welcome to Civic Issue Reporting System";
    $roleLabel = ucfirst($role);
    $body = "
    <html>
    <head><title>Welcome</title></head>
    <body>
        <h2>Welcome, $name!</h2>
        <p>Your account has been created as a <strong>$roleLabel</strong> in the Rural Civic Issue Reporting System.</p>
        <p>Your login credentials:</p>
        <ul>
            <li><strong>Email:</strong> $email</li>
            <li><strong>Password:</strong> $password</li>
        </ul>
        <p>Please change your password after first login.</p>
        <hr>
        <p><small>This is an automated notification from the Rural Civic Issue Reporting System.</small></p>
    </body>
    </html>
    ";
    return sendEmail($email, $subject, $body);
}

function sendSolverAssignedEmail($solverEmail, $solverName, $issueId, $issueTitle, $category, $location) {
    $subject = "New Issue Assigned - #$issueId";
    $body = "
    <html>
    <head><title>New Assignment</title></head>
    <body>
        <h2>Hello $solverName,</h2>
        <p>A new issue has been assigned to you:</p>
        <ul>
            <li><strong>Issue ID:</strong> #$issueId</li>
            <li><strong>Title:</strong> $issueTitle</li>
            <li><strong>Category:</strong> $category</li>
            <li><strong>Location:</strong> $location</li>
        </ul>
        <p>Please login to the solver portal to view and work on this issue.</p>
        <hr>
        <p><small>This is an automated notification from the Rural Civic Issue Reporting System.</small></p>
    </body>
    </html>
    ";
    return sendEmail($solverEmail, $subject, $body);
}
