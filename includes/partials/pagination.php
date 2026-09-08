<?php
/**
 * File: pagination.php
 * Purpose: Reusable pagination logic
 */
// includes/partials/pagination.php
// Expected variables:
// $currentPage (int)
// $totalPages (int)
// $baseUrl (string) - e.g. "?page=" or "my_bookings.php?page="

$currentPage = $currentPage ?? $page ?? 1;

if ($totalPages > 1): ?>
    <div class="pagination-wrapper" style="display: flex; justify-content: center; margin-top: 24px; gap: 8px; align-items: center;">
        <?php if ($currentPage > 1): ?>
            <a href="<?= htmlspecialchars($baseUrl . ($currentPage - 1)) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">&laquo; Prev</a>
        <?php else: ?>
            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;" disabled>&laquo; Prev</button>
        <?php endif; ?>

        <span style="font-size: 14px; font-weight: 500; color: var(--color-secondary);">
            Page <?= $currentPage ?> of <?= $totalPages ?>
        </span>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= htmlspecialchars($baseUrl . ($currentPage + 1)) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">Next &raquo;</a>
        <?php else: ?>
            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;" disabled>Next &raquo;</button>
        <?php endif; ?>
    </div>
<?php endif; ?>
