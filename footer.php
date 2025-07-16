<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    .site-footer {
    background-color: #000; /* black or dark blue could work well */
    color: #fff;
    text-align: center;
    padding: 20px 10px;
    font-size: 0.9em;
    margin-top: 40px;
    border-top: 4px solid #25559D; /* matches your button/header accent */
}

.site-footer .footer-content p {
    margin: 6px 0;
}

</style>
<body>
<footer class="site-footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> Kesho Chartered Accountants. All rights reserved.</p>
        <p>Committed to Integrity • Excellence • Growth</p>
    </div>
</footer>
   
</body>
</html>