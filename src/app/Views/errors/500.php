<?php
$title = '500 Internal Error - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card card-error">
    <h2 class="error-title">500 Internal Server Error</h2>
    <p>An unexpected error occurred. Give this request ID to the administrator:</p>
    <p><code><?= $safeRequestId ?? 'unavailable' ?></code></p>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
