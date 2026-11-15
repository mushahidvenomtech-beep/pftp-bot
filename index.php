<?php
/**
 * PFTP AI Student Counselling Chatbot
 * Standalone & Globally Embeddable Interface
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>PFTP AI Chatbot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      width: 100%;
      height: 100%;
      min-height: 100vh;
      min-height: 100dvh;
      background: #f8fafc;
      background: radial-gradient(circle at bottom right, rgba(251, 203, 68, 0.16) 0%, rgba(7, 97, 63, 0.1) 45%, #f8fafc 85%);
      overflow: hidden;
      font-family: 'Asap Condensed', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
  </style>
</head>
<body>

  <!-- PFTP Floating AI Chatbot Widget -->
  <?php include_once __DIR__ . '/chatbot/index.php'; ?>

  <script>
    // Automatically open the chatbot window on load so the user immediately sees the chat bot
    document.addEventListener('DOMContentLoaded', function () {
      if (window.PFTPChatbot) {
        window.PFTPChatbot.open();
      }
    });
  </script>

</body>
</html>
